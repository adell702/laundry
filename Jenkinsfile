pipeline {
  agent any

  options {
    disableConcurrentBuilds()
    skipDefaultCheckout()
    skipStagesAfterUnstable()
    timestamps()
    // PHP extensions and Vite take longer on a cold 4 GB Raspberry Pi build.
    timeout(time: 90, unit: 'MINUTES')
    buildDiscarder(logRotator(numToKeepStr: '10'))
  }

  parameters {
    booleanParam(name: 'DEPLOY', defaultValue: true, description: 'Deploy successful main builds to the Raspberry Pi.')
  }

  environment {
    COMPOSE_PROJECT_NAME = 'laundry'
    DOCKER_CONFIG = "${WORKSPACE}/.docker-ci"
  }

  stages {
    stage('Checkout') {
      steps {
        deleteDir()
        checkout scm
        script {
          env.CI_ID = 'laundry-' + sh(script: 'printf "%s" "$BUILD_TAG" | sha256sum | cut -c1-16', returnStdout: true).trim()
          env.LAUNDRY_IMAGE_TAG = env.GIT_COMMIT.take(12) + '-' + env.CI_ID
          // Covers both Multibranch and a Pipeline-from-SCM job on */main.
          env.DEPLOY_MAIN = (!env.CHANGE_ID && (env.BRANCH_NAME == 'main' ||
            (!env.BRANCH_NAME && env.GIT_BRANCH == 'origin/main'))).toString()
        }
      }
    }

    stage('Validate host') {
      steps {
        sh '''
          set -eu
          test "$(docker info --format '{{.Name}}')" = raspberrypi
          case "$(docker info --format '{{.Architecture}}')" in
            aarch64|arm64) ;;
            *) echo 'This job requires the ARM64 Raspberry Pi Docker host.' >&2; exit 1 ;;
          esac
          docker compose version

          # Jenkins has Docker/Compose but no Buildx. Copy the ARM64 plugin
          # into this build's workspace, without changing the Jenkins image.
          if ! docker buildx version >/dev/null 2>&1; then
            mkdir -p "$DOCKER_CONFIG/cli-plugins"
            docker create --name "$CI_ID-tools" docker:29-cli >/dev/null
            docker cp "$CI_ID-tools:/usr/local/libexec/docker/cli-plugins/docker-buildx" \
              "$DOCKER_CONFIG/cli-plugins/docker-buildx"
            docker rm "$CI_ID-tools" >/dev/null
          fi
          docker buildx version
        '''
      }
    }

    stage('Validate deployment') {
      when { expression { params.DEPLOY && env.DEPLOY_MAIN == 'true' } }
      steps {
        withCredentials([file(credentialsId: 'laundry-production-env', variable: 'DEPLOY_ENV')]) {
          sh '''
            set -eu
            trap 'rm -f .env.production' EXIT
            install -m 600 "$DEPLOY_ENV" .env.production
            docker network inspect shared-services >/dev/null
            docker inspect -f '{{.State.Health.Status}}' shared-mariadb | grep -qx healthy
            docker compose --env-file .env.production -f docker-compose.production.yml config --quiet

            # Expand secrets only in the non-traced client container.
            docker run --rm --network shared-services --env-file .env.production mariadb:11.4 \
              sh -ec 'MYSQL_PWD="$DB_PASSWORD" mariadb --skip-ssl -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" -D "$DB_DATABASE" -e "SELECT 1" >/dev/null'
          '''
        }
      }
    }

    stage('Build test image') {
      steps {
        sh 'docker build --target ci --tag "laundry-ci:$LAUNDRY_IMAGE_TAG" .'
      }
    }

    stage('Test') {
      steps {
        sh '''
          set -eu
          mkdir -p test-results
          # No host workspace mount: Jenkins runs in a named Docker volume.
          # Tests use in-memory SQLite and cannot access shared services.
          docker create --name "$CI_ID-test" --network none \
            "laundry-ci:$LAUNDRY_IMAGE_TAG" sh -ec '
              export APP_KEY="base64:$(php -r "echo base64_encode(random_bytes(32));")"
              composer validate --strict --no-check-publish
              mkdir -p /tmp/test-results
              vendor/bin/phpunit --log-junit /tmp/test-results/junit.xml
            ' >/dev/null
          docker cp tests "$CI_ID-test:/var/www/html/tests"
          docker start --attach "$CI_ID-test"
          test "$(docker inspect -f '{{.State.ExitCode}}' "$CI_ID-test")" -eq 0
        '''
      }
      post {
        always {
          sh 'docker cp "$CI_ID-test:/tmp/test-results/." test-results/ || true'
          junit testResults: 'test-results/junit.xml', allowEmptyResults: false
        }
      }
    }

    stage('Build release images') {
      steps {
        sh '''
          set -eu
          docker build --target app --tag "laundry-app:$LAUNDRY_IMAGE_TAG" .
          docker build --target web --tag "laundry-web:$LAUNDRY_IMAGE_TAG" .
          printf 'LAUNDRY_IMAGE_TAG=%s\\nGIT_COMMIT=%s\\n' "$LAUNDRY_IMAGE_TAG" "$GIT_COMMIT" > test-results/images.txt
        '''
        archiveArtifacts artifacts: 'test-results/images.txt', fingerprint: true
      }
    }

    stage('Deploy') {
      when { expression { params.DEPLOY && env.DEPLOY_MAIN == 'true' } }
      steps {
        withCredentials([file(credentialsId: 'laundry-production-env', variable: 'DEPLOY_ENV')]) {
          sh '''
            set -eu
            install -m 600 "$DEPLOY_ENV" .env.production
            # Force migrate to run on every deployment, including a repeated
            # revision. Only the already-built images are used here.
            docker compose --env-file .env.production -f docker-compose.production.yml \
              up -d --no-build --pull never --force-recreate --remove-orphans --wait --wait-timeout 300
            for service in app web queue scheduler; do
              docker compose --env-file .env.production -f docker-compose.production.yml \
                ps --status running --services | grep -qx "$service"
            done
            docker compose --env-file .env.production -f docker-compose.production.yml \
              exec -T web wget --quiet --tries=1 --spider http://127.0.0.1/up
          '''
        }
      }
    }
  }

  post {
    always {
      sh '''
        if [ -f .env.production ]; then
          docker compose --env-file .env.production -f docker-compose.production.yml ps || true
        fi
      '''
    }
    failure {
      sh '''
        if [ -f .env.production ]; then
          docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=100 || true
        fi
      '''
    }
    cleanup {
      sh '''
        if [ -n "${CI_ID:-}" ]; then
          docker rm -f "$CI_ID-test" "$CI_ID-tools" >/dev/null 2>&1 || true
        fi
        if [ -n "${LAUNDRY_IMAGE_TAG:-}" ]; then
          docker image rm "laundry-ci:$LAUNDRY_IMAGE_TAG" >/dev/null 2>&1 || true
        fi
        rm -f -- .env.production
        rm -rf -- .docker-ci
      '''
    }
  }
}

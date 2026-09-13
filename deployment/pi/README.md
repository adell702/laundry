# Jenkins deployment on the Raspberry Pi

This follows the Jenkins/Compose conventions in `korporat.com`, `healthy-food`,
and `anak-magang-wa`. The SSH alias is `pi-cloudflare`; Jenkins is available at
https://jenkins.bits.my.id/ and runs in Docker on `raspberrypi` (ARM64).
The pipeline builds and deploys through its mounted Docker socket.

## One-time setup

1. On the Pi's existing `shared-mariadb`, create a database named `laundry` and
   a dedicated `laundry` user with grants on `laundry.*` only. Connect using
   `docker exec -it shared-mariadb mariadb -uroot -p`. For a new database/user:

   ```sql
   CREATE DATABASE laundry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'laundry'@'%' IDENTIFIED BY 'REPLACE_WITH_RANDOM_HEX_PASSWORD';
   GRANT ALL PRIVILEGES ON laundry.* TO 'laundry'@'%';
   ```

   Generate the password with `openssl rand -hex 32`, replace the placeholder
   before running the SQL, and keep the same password in the environment file.
   This is one-time provisioning; the pipeline only runs application migrations.

2. Copy [`.env.production.example`](../../.env.production.example), set
   `DB_PASSWORD`, and generate a persistent `APP_KEY` with:

   ```bash
   php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
   ```

   Upload the completed file to Jenkins as a **Secret file** credential with ID
   **`laundry-production-env`**. Unlike projects that inject a password from a
   `/run/*-db.env` mount, Laundry keeps its database password in this credential;
   no Jenkins container restart or extra bind mount is needed. Use an unquoted
   hex database password so both Compose and the preflight Docker client parse
   it identically. Do not use the MariaDB root account for the application.

3. Create a **Pipeline** job with **Pipeline script from SCM**, Git repository
   `https://github.com/adell702/laundry.git`, branch `*/main`, and script path
   **`Jenkinsfile`**. Select an existing Git credential if the repository requires
   authentication. Run on the existing Pi Jenkins executor. The required
   Pipeline, Git, Credentials Binding, JUnit, and Timestamper plugins are already
   installed there. No PHP, Node.js, or Docker Pipeline plugin is required.

The database/user, credential, and Laundry job are prerequisites, not resources
created by adding these files to the repository.

## Pipeline behavior

- Clean checkout; confirm the ARM64 `raspberrypi` Docker daemon. If Buildx is
  missing, copy it from `docker:29-cli` into the workspace's `.docker-ci` directory.
  This leaves the Jenkins installation unchanged and is removed after the build.
- For deployments, validate the secret file, shared MariaDB health, and database
  login before the slow build. Temporary copies of secrets are removed afterward.
- Build the `ci` Docker target using PHP 8.4, all locked Composer dependencies,
  and the Node 24/Vite assets. Copy the test suite into a disposable container,
  run Composer validation and PHPUnit with in-memory SQLite and networking
  disabled, and publish JUnit results even on test failure.
- Build the `app` and `web` production targets with a unique tag recorded in
  the `test-results/images.txt` Jenkins artifact. Production images have no
  development dependencies or test suite. Builds run sequentially for the Pi's
  limited memory. Vite's application name uses the Dockerfile default, AA Laundry.
- Deploy only successful `main` builds when **DEPLOY** is enabled (the default).
  A Multibranch pull request or other branch cannot enter the deployment stages.
  Uncheck **DEPLOY** for build/test verification without production credentials.
- Start the exact release images with `--no-build --pull never`, rerun migrations,
  wait for health checks, and verify app/web/queue/scheduler are running.
  Migrations must succeed before application services start. No seeder runs.

Jenkins occupies port 8080; Laundry uses **127.0.0.1:8088** by default. Access it
locally on the Pi or use `ssh -L 8088:127.0.0.1:8088 pi-cloudflare` and browse
http://localhost:8088. A public domain/tunnel is a separate setup: configure
Laravel's trusted reverse proxy for HTTPS, set `APP_URL` and secure cookies,
and point the tunnel at `http://127.0.0.1:8088`. Tripay callbacks require a
reachable public URL. No DNS or tunnel configuration is changed by this pipeline.

The database stays in the existing shared MariaDB stack; application files use
the persistent `laundry_app-storage` volume. Keep this volume and `APP_KEY` across
deployments. The production Compose project has no database service.

## Operations

From a checkout with the production environment file available, use the release
tag recorded by Jenkins:

```bash
export LAUNDRY_IMAGE_TAG=the-tag-from-images.txt
docker compose --env-file .env.production -f docker-compose.production.yml ps -a
docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=100 app web migrate queue scheduler
docker compose --env-file .env.production -f docker-compose.production.yml exec app php artisan migrate:status
```

Retain backups of the shared database and application storage before schema
changes. Failed deployments do not automatically roll back schema migrations.
Release images are retained for manual recovery; periodically remove only older
Laundry image tags that you no longer need. Do not prune shared Docker resources
or delete application volumes as part of a build. Use one deployment job for
this Compose project; `disableConcurrentBuilds()` serializes that job only.

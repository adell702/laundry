#!/bin/sh
set -eu

cd "$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"

source_file=.env.docker
[ -f "$source_file" ] || source_file=.env.docker.example

# Share the same blank-value detection between checking and writing. Keep CRLF
# records intact, and accept empty quoted values and inline comments.
env_program='
function credential(line, key) {
    if (line !~ /^[ \t]*(export[ \t]+)?(APP_KEY|DB_PASSWORD|MYSQL_ROOT_PASSWORD)[ \t]*=/) return ""
    key = line
    sub(/^[ \t]*(export[ \t]+)?/, "", key)
    sub(/[ \t]*=.*/, "", key)
    return key
}
function blank(line, value, quote) {
    value = line
    sub(/^[^=]*=/, "", value)
    sub(/^[ \t]*/, "", value)
    sub(/[ \t]*$/, "", value)
    quote = sprintf("%c", 39)
    return value == "" || value ~ /^#/ || value ~ /^""([ \t]*#.*)?$/ || value ~ ("^" quote quote "([ \t]*#.*)?$")
}
BEGIN {
    keys[1] = "APP_KEY"
    keys[2] = "DB_PASSWORD"
    keys[3] = "MYSQL_ROOT_PASSWORD"
    values["APP_KEY"] = ENVIRON["setup_app_key"]
    values["DB_PASSWORD"] = ENVIRON["setup_db_password"]
    values["MYSQL_ROOT_PASSWORD"] = ENVIRON["setup_root_password"]
}
{
    line = $0
    cr = sub(/\r$/, "", line) ? "\r" : ""
    if (NR == 1) newline_cr = cr
    key = credential(line)
    if (key != "") {
        seen[key] = 1
        if (blank(line)) {
            needed[key] = 1
            if (mode == "write") {
                prefix = line
                sub(/=.*/, "=", prefix)
                comment = line
                if (comment ~ /#/) sub(/^[^#]*/, " ", comment)
                else comment = ""
                line = prefix values[key] comment
            }
        }
    }
    if (mode == "write") print line cr
}
END {
    for (i = 1; i <= 3; i++) {
        key = keys[i]
        if (mode == "check" && (!seen[key] || needed[key])) print key
        if (mode == "write" && !seen[key]) print key "=" values[key] newline_cr
    }
}'

needed="$(awk -v mode=check "$env_program" "$source_file")"

if [ "$source_file" = .env.docker ] && [ -z "$needed" ]; then
    echo ".env.docker already has its credentials; keeping it unchanged."
else
    setup_app_key=
    setup_db_password=
    setup_root_password=

    if [ -n "$needed" ]; then
        command -v openssl >/dev/null 2>&1 || {
            echo "OpenSSL is required to generate local credentials." >&2
            exit 1
        }
        for key in $needed; do
            case "$key" in
                APP_KEY) setup_app_key="base64:$(openssl rand -base64 32)" ;;
                DB_PASSWORD) setup_db_password="$(openssl rand -hex 24)" ;;
                MYSQL_ROOT_PASSWORD) setup_root_password="$(openssl rand -hex 24)" ;;
            esac
        done
    fi

    umask 077
    temp_file="$(mktemp .env.docker.tmp.XXXXXX)"
    trap 'rm -f "$temp_file"' 0
    trap 'exit 1' HUP INT TERM
    export setup_app_key setup_db_password setup_root_password
    awk -v mode=write "$env_program" "$source_file" > "$temp_file"
    mv "$temp_file" .env.docker

    if [ "$source_file" = .env.docker ]; then
        echo "Filled missing credentials in .env.docker; existing values were preserved."
    else
        echo "Created .env.docker with an application key and database passwords."
    fi
fi

echo "Start the app: docker compose --env-file .env.docker up -d --build --wait"

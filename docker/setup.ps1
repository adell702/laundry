#Requires -Version 5.1
$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$envPath = Join-Path $projectRoot '.env.docker'
$templatePath = Join-Path $projectRoot '.env.docker.example'
$exists = [System.IO.File]::Exists($envPath)
$sourcePath = if ($exists) { $envPath } else { $templatePath }
$content = [System.IO.File]::ReadAllText($sourcePath)
$changed = -not $exists

foreach ($key in @('APP_KEY', 'DB_PASSWORD', 'MYSQL_ROOT_PASSWORD')) {
    $pattern = '(?m)^[ \t]*' + $key + '[ \t]*=[^\r\n]*'
    $entries = [regex]::Matches($content, $pattern)
    if ($entries.Count -gt 1) {
        throw "Duplicate $key entries in .env.docker. Keep one entry and rerun setup."
    }

    if ($entries.Count -eq 1) {
        $value = ($entries[0].Value -split '=', 2)[1].Trim()
        if ($value -notmatch '^(?:""|'''')?[ \t]*(?:#.*)?$') {
            continue
        }
    }

    $byteCount = if ($key -eq 'APP_KEY') { 32 } else { 24 }
    $bytes = New-Object byte[] $byteCount
    $random = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try {
        $random.GetBytes($bytes)
    }
    finally {
        $random.Dispose()
    }

    $value = if ($key -eq 'APP_KEY') {
        'base64:' + [Convert]::ToBase64String($bytes)
    }
    else {
        [BitConverter]::ToString($bytes).Replace('-', '').ToLowerInvariant()
    }

    $entry = $key + '=' + $value
    if ($entries.Count -eq 1) {
        $content = $content.Remove($entries[0].Index, $entries[0].Length).Insert($entries[0].Index, $entry)
    }
    else {
        if ($content.Length -gt 0 -and -not $content.EndsWith("`n")) {
            $content += "`n"
        }
        $content += $entry + "`n"
    }
    $changed = $true
}

if ($changed) {
    $tempPath = $envPath + '.' + [Guid]::NewGuid().ToString('N') + '.tmp'
    try {
        # Windows PowerShell defaults can produce UTF-16 or a BOM; Compose needs plain UTF-8.
        $encoding = New-Object System.Text.UTF8Encoding $false
        [System.IO.File]::WriteAllText($tempPath, $content, $encoding)
        if ($exists) {
            [System.IO.File]::Replace($tempPath, $envPath, [NullString]::Value)
        }
        else {
            [System.IO.File]::Move($tempPath, $envPath)
        }
    }
    finally {
        if ([System.IO.File]::Exists($tempPath)) {
            [System.IO.File]::Delete($tempPath)
        }
    }
    Write-Host 'Prepared .env.docker; generated only missing or blank credentials.'
}
else {
    Write-Host '.env.docker already has credentials; keeping it unchanged.'
}

Write-Host 'Start the app: docker compose --env-file .env.docker up -d --build --wait'

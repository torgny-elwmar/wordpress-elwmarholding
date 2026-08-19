$ErrorActionPreference = "Stop"

& cmd.exe /d /s /c "docker info >nul 2>&1"

if ($LASTEXITCODE -ne 0) {
    Write-Host "Docker Desktop is not running. Starting it now..."

    & docker desktop start --timeout 120

    if ($LASTEXITCODE -ne 0) {
        throw "Docker Desktop could not be started. Start it manually and try again."
    }
}

& npx.cmd wp-env start

if ($LASTEXITCODE -ne 0) {
    throw "wp-env could not be started."
}
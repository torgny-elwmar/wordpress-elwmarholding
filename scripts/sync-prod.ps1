param(
    [switch] $IncludeUploads
)

$ErrorActionPreference = "Stop"

# ==========================================================
# Elwmar Holding AB - Production -> Local Development Sync
# ==========================================================

$RemoteHost = "web01.cloud"

$Database       = "elwmarholding"
$DatabaseUser   = "elwmarholding"
$DatabaseSecret = "/opt/web/database/secrets/mariadb_elwmarholding"
$RemoteUploads  = "/opt/web/sites/elwmarholding.se/wp-content/uploads"

$TablePrefix = "wp_elwmarholding_se_"

$ProdUrl  = "https://elwmarholding.se"
$ProdWww  = "https://www.elwmarholding.se"
$LocalUrl = "http://localhost:8883"

$DevAdminUser  = "devadmin"
$DevAdminEmail = "devadmin@elwmarholding.test"

$RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$DevDir   = Join-Path $RepoRoot ".dev"

$LocalDump            = Join-Path $DevDir "elwmarholding-prod.sql"
$ExportErrorFile      = Join-Path $DevDir "export-error.txt"
$ImportOutputFile     = Join-Path $DevDir "import-output.txt"
$ImportErrorFile      = Join-Path $DevDir "import-error.txt"
$DevAdminPasswordFile = Join-Path $DevDir "dev-admin-password.txt"
$PasswordBridgeFile   = Join-Path `
    $RepoRoot `
    "wp-content\themes\elwmarholding\.dev-admin-password.tmp"
$PasswordScriptFile   = Join-Path `
    $RepoRoot `
    "wp-content\themes\elwmarholding\.set-dev-admin-password.tmp.sh"
$UploadsDir           = Join-Path $RepoRoot "wp-content\uploads"
$UploadsArchive       = Join-Path $DevDir "uploads.tar"
$UploadsStageDir      = Join-Path $DevDir "uploads-stage"
$UploadsBackupDir     = Join-Path $DevDir "uploads-backup"
$RemoteManifestFile   = Join-Path $DevDir "uploads-remote.sha256"
$LocalManifestFile    = Join-Path $DevDir "uploads-local.sha256"
$RemoteStatsFile      = Join-Path $DevDir "uploads-remote.stats"
$UploadsErrorFile     = Join-Path $DevDir "uploads-error.txt"

$OriginalLocation = Get-Location
$UploadsFileCount = [long] 0
$UploadsByteCount = [long] 0
$UploadsVerification = "NOT RUN"


function Invoke-WpCli {
    param(
        [Parameter(Mandatory)]
        [string[]] $Arguments
    )

    & npx.cmd wp-env run cli wp @Arguments

    if ($LASTEXITCODE -ne 0) {
        throw "WP-CLI failed: wp $($Arguments -join ' ')"
    }
}


function New-DevPassword {
    param(
        [int] $Length = 32
    )

    $Chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
    $Rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()

    try {
        $Bytes = New-Object byte[] $Length
        $Rng.GetBytes($Bytes)

        $Password = New-Object System.Text.StringBuilder

        foreach ($Byte in $Bytes) {
            [void] $Password.Append(
                $Chars[$Byte % $Chars.Length]
            )
        }

        return $Password.ToString()
    }
    finally {
        $Rng.Dispose()
    }
}


function Invoke-SshToFile {
    param(
        [Parameter(Mandatory)]
        [string] $Command,

        [Parameter(Mandatory)]
        [string] $OutputFile
    )

    Remove-Item $OutputFile -Force -ErrorAction SilentlyContinue
    Remove-Item $UploadsErrorFile -Force -ErrorAction SilentlyContinue

    $Process = Start-Process `
        -FilePath "ssh.exe" `
        -ArgumentList @($RemoteHost, $Command) `
        -WorkingDirectory $RepoRoot `
        -NoNewWindow `
        -Wait `
        -PassThru `
        -RedirectStandardOutput $OutputFile `
        -RedirectStandardError $UploadsErrorFile

    if ($Process.ExitCode -ne 0) {
        $ErrorText = ""

        if (Test-Path $UploadsErrorFile) {
            $ErrorText = Get-Content $UploadsErrorFile -Raw
        }

        throw "Uploads SSH command failed with exit code $($Process.ExitCode).`n$ErrorText"
    }

    Remove-Item $UploadsErrorFile -Force -ErrorAction SilentlyContinue
}


function Sync-ProductionUploads {
    Write-Host ""
    Write-Host "==> Downloading production uploads"

    Remove-Item $UploadsArchive -Force -ErrorAction SilentlyContinue
    Remove-Item $UploadsStageDir -Recurse -Force -ErrorAction SilentlyContinue
    Remove-Item $UploadsBackupDir -Recurse -Force -ErrorAction SilentlyContinue
    New-Item -ItemType Directory -Force $UploadsStageDir | Out-Null

    Invoke-SshToFile `
        -Command "tar -C '$RemoteUploads' -cf - ." `
        -OutputFile $UploadsArchive

    if (-not (Test-Path $UploadsArchive) -or (Get-Item $UploadsArchive).Length -eq 0) {
        throw "Production uploads archive was not created or is empty."
    }

    & tar.exe -xf $UploadsArchive -C $UploadsStageDir

    if ($LASTEXITCODE -ne 0) {
        throw "Failed to extract production uploads archive."
    }

    Write-Host "==> Verifying downloaded uploads"

    $RemoteManifestCommand = "cd '$RemoteUploads' && find . -type f -exec sha256sum {} + | LC_ALL=C sort"
    $RemoteStatsCommand = "cd '$RemoteUploads' && find . -type f -printf '%s\n'"

    Invoke-SshToFile -Command $RemoteManifestCommand -OutputFile $RemoteManifestFile
    Invoke-SshToFile -Command $RemoteStatsCommand -OutputFile $RemoteStatsFile

    $LocalFiles = @(Get-ChildItem $UploadsStageDir -File -Recurse)
    $LocalManifest = @(
        foreach ($File in $LocalFiles) {
            $RelativePath = $File.FullName.Substring($UploadsStageDir.Length).TrimStart('\', '/').Replace('\', '/')
            $Hash = (Get-FileHash $File.FullName -Algorithm SHA256).Hash.ToLowerInvariant()
            "$Hash  ./$RelativePath"
        }
    )
    [Array]::Sort($LocalManifest, [StringComparer]::Ordinal)
    [System.IO.File]::WriteAllText(
        $LocalManifestFile,
        (($LocalManifest -join "`n") + $(if ($LocalManifest.Count -gt 0) { "`n" } else { "" })),
        (New-Object System.Text.UTF8Encoding($false))
    )

    $RemoteManifest = [System.IO.File]::ReadAllText($RemoteManifestFile).Replace("`r`n", "`n")
    $DownloadedManifest = [System.IO.File]::ReadAllText($LocalManifestFile)

    if ($RemoteManifest -cne $DownloadedManifest) {
        throw "Downloaded uploads do not match the production SHA-256 manifest."
    }

    $RemoteSizes = @(
        Get-Content $RemoteStatsFile |
            Where-Object { $_ -ne "" } |
            ForEach-Object { [long] $_ }
    )

    $Script:UploadsFileCount = [long] $LocalFiles.Count
    $Script:UploadsByteCount = [long] (($LocalFiles | Measure-Object -Property Length -Sum).Sum)
    $RemoteByteCount = [long] (($RemoteSizes | Measure-Object -Sum).Sum)

    if ($UploadsFileCount -ne $RemoteSizes.Count -or $UploadsByteCount -ne $RemoteByteCount) {
        throw "Downloaded uploads file count or byte size does not match production."
    }

    $Script:UploadsVerification = "OK"

    Write-Host "==> Replacing local uploads with verified production uploads"

    & npx.cmd wp-env stop

    if ($LASTEXITCODE -ne 0) {
        throw "wp-env could not be stopped before replacing uploads."
    }

    try {
        if (Test-Path $UploadsDir) {
            Move-Item $UploadsDir $UploadsBackupDir
        }

        Move-Item $UploadsStageDir $UploadsDir
    }
    catch {
        Remove-Item $UploadsDir -Recurse -Force -ErrorAction SilentlyContinue

        if (Test-Path $UploadsBackupDir) {
            Move-Item $UploadsBackupDir $UploadsDir
        }

        throw
    }

    Remove-Item $UploadsBackupDir -Recurse -Force -ErrorAction SilentlyContinue

    & npx.cmd wp-env start

    if ($LASTEXITCODE -ne 0) {
        throw "wp-env could not be restarted after replacing uploads."
    }
}


try {
    Set-Location $RepoRoot

    New-Item -ItemType Directory -Force $DevDir | Out-Null
    New-Item -ItemType Directory -Force $UploadsDir | Out-Null

    Write-Host ""
    Write-Host "==============================================="
    Write-Host " Elwmar Holding AB - Production -> Dev Sync"
    Write-Host "==============================================="
    Write-Host ""

    # ------------------------------------------------------
    # 1. Start / verify local wp-env
    # ------------------------------------------------------

    Write-Host "==> Starting/verifying local wp-env"

    & npx.cmd wp-env start

    if ($LASTEXITCODE -ne 0) {
        throw "wp-env could not be started."
    }

    # ------------------------------------------------------
    # 2. Generate local dev-admin password
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Generating local development administrator password"

    $DevAdminPassword = New-DevPassword

    [System.IO.File]::WriteAllText(
        $DevAdminPasswordFile,
        $DevAdminPassword,
        (New-Object System.Text.UTF8Encoding($false))
    )

    Write-Host "    Generated new local dev-admin password."

    # ------------------------------------------------------
    # 3. Export production database directly over SSH
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Streaming production database from $RemoteHost"

    Remove-Item $LocalDump -Force -ErrorAction SilentlyContinue
    Remove-Item $ExportErrorFile -Force -ErrorAction SilentlyContinue

    $RemoteCommand = (
        'DB_PASSWORD="$(cat {0})"; ' +
        'docker exec web-mariadb mariadb-dump ' +
        '--single-transaction ' +
        '--quick ' +
        '--skip-lock-tables ' +
        '-u{1} ' +
        '-p"$DB_PASSWORD" ' +
        '{2}'
    ) -f $DatabaseSecret, $DatabaseUser, $Database

    $ExportProcess = Start-Process `
        -FilePath "ssh.exe" `
        -ArgumentList @($RemoteHost, $RemoteCommand) `
        -WorkingDirectory $RepoRoot `
        -NoNewWindow `
        -Wait `
        -PassThru `
        -RedirectStandardOutput $LocalDump `
        -RedirectStandardError $ExportErrorFile

    if ($ExportProcess.ExitCode -ne 0) {
        $ErrorText = ""

        if (Test-Path $ExportErrorFile) {
            $ErrorText = Get-Content $ExportErrorFile -Raw
        }

        throw "Production database export failed with exit code $($ExportProcess.ExitCode).`n$ErrorText"
    }

    if (-not (Test-Path $LocalDump)) {
        throw "Production database dump was not created."
    }

    $DumpSize = (Get-Item $LocalDump).Length

    if ($DumpSize -lt 1024) {
        throw "Production database dump looks suspiciously small ($DumpSize bytes)."
    }

    Write-Host ("    Received: {0:N1} KB" -f ($DumpSize / 1KB))

    Remove-Item $ExportErrorFile -Force -ErrorAction SilentlyContinue

    # ------------------------------------------------------
    # 4. Reset local database
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Resetting local development database"

    Invoke-WpCli @(
        "db",
        "reset",
        "--yes"
    )

    # ------------------------------------------------------
    # 5. Match production table prefix
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Setting local table prefix to $TablePrefix"

    Invoke-WpCli @(
        "config",
        "set",
        "table_prefix",
        $TablePrefix,
        "--type=variable"
    )

    # ------------------------------------------------------
    # 6. Mark WordPress environment as local
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Setting WP_ENVIRONMENT_TYPE=local"

    Invoke-WpCli @(
        "config",
        "set",
        "WP_ENVIRONMENT_TYPE",
        "local",
        "--type=constant"
    )

    # ------------------------------------------------------
    # 7. Import production database
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Importing production database"

    Remove-Item $ImportOutputFile -Force -ErrorAction SilentlyContinue
    Remove-Item $ImportErrorFile -Force -ErrorAction SilentlyContinue

    $ImportProcess = Start-Process `
        -FilePath "cmd.exe" `
        -ArgumentList @(
            "/d",
            "/s",
            "/c",
            "npx wp-env run cli wp db import -"
        ) `
        -WorkingDirectory $RepoRoot `
        -NoNewWindow `
        -Wait `
        -PassThru `
        -RedirectStandardInput $LocalDump `
        -RedirectStandardOutput $ImportOutputFile `
        -RedirectStandardError $ImportErrorFile

    if ($ImportProcess.ExitCode -ne 0) {
        $ErrorText = ""

        if (Test-Path $ImportErrorFile) {
            $ErrorText = Get-Content $ImportErrorFile -Raw
        }

        throw "Local database import failed with exit code $($ImportProcess.ExitCode).`n$ErrorText"
    }

    if (Test-Path $ImportOutputFile) {
        $ImportOutput = Get-Content $ImportOutputFile -Raw

        if ($ImportOutput) {
            Write-Host $ImportOutput.Trim()
        }
    }

    Remove-Item $ImportOutputFile -Force -ErrorAction SilentlyContinue
    Remove-Item $ImportErrorFile -Force -ErrorAction SilentlyContinue

    # Raw prod dump may contain secrets.
    Remove-Item $LocalDump -Force

    # ------------------------------------------------------
    # 8. Rewrite production URLs
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Rewriting production URLs"

    Invoke-WpCli @(
        "search-replace",
        $ProdWww,
        $LocalUrl,
        "--all-tables-with-prefix",
        "--precise",
        "--skip-columns=guid",
        "--skip-plugins",
        "--skip-themes"
    )

    Invoke-WpCli @(
        "search-replace",
        $ProdUrl,
        $LocalUrl,
        "--all-tables-with-prefix",
        "--precise",
        "--skip-columns=guid",
        "--skip-plugins",
        "--skip-themes"
    )

    # ------------------------------------------------------
    # 9. Explicit local settings
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Applying local WordPress settings"

    Invoke-WpCli @(
        "option",
        "update",
        "home",
        $LocalUrl,
        "--skip-plugins",
        "--skip-themes"
    )

    Invoke-WpCli @(
        "option",
        "update",
        "siteurl",
        $LocalUrl,
        "--skip-plugins",
        "--skip-themes"
    )

    Invoke-WpCli @(
        "option",
        "update",
        "blog_public",
        "0",
        "--skip-plugins",
        "--skip-themes"
    )

    # ------------------------------------------------------
    # 10. Disable production OIDC locally
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Disabling Green Identity in local development"

    Invoke-WpCli @(
        "plugin",
        "deactivate",
        "daggerhart-openid-connect-generic",
        "--skip-plugins",
        "--skip-themes"
    )

    Invoke-WpCli @(
        "option",
        "delete",
        "openid_connect_generic_settings",
        "--skip-plugins",
        "--skip-themes"
    )

    Invoke-WpCli @(
        "option",
        "delete",
        "openid-connect-generic-logs",
        "--skip-plugins",
        "--skip-themes"
    )

    # ------------------------------------------------------
    # 11. Create local development administrator
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Creating local development administrator"

    Invoke-WpCli @(
        "user",
        "create",
        $DevAdminUser,
        $DevAdminEmail,
        "--display_name=Development Administrator",
        "--role=administrator",
        "--porcelain",
        "--skip-plugins",
        "--skip-themes"
    )

    [System.IO.File]::WriteAllText(
        $PasswordBridgeFile,
        $DevAdminPassword,
        (New-Object System.Text.UTF8Encoding($false))
    )

    $SetPasswordScript = @'
#!/bin/sh
wp user update devadmin --user_pass="$(cat /var/www/html/wp-content/themes/elwmarholding/.dev-admin-password.tmp)" --skip-plugins --skip-themes >/dev/null
'@

    [System.IO.File]::WriteAllText(
        $PasswordScriptFile,
        $SetPasswordScript.Replace("`r`n", "`n"),
        (New-Object System.Text.UTF8Encoding($false))
    )

    try {
        & npx.cmd wp-env run cli bash /var/www/html/wp-content/themes/elwmarholding/.set-dev-admin-password.tmp.sh

        if ($LASTEXITCODE -ne 0) {
            throw "Failed to set local development administrator password."
        }
    }
    finally {
        Remove-Item $PasswordBridgeFile -Force -ErrorAction SilentlyContinue
        Remove-Item $PasswordScriptFile -Force -ErrorAction SilentlyContinue
    }

    # ------------------------------------------------------
    # 12. Clear transients
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Clearing WordPress transients"

    Invoke-WpCli @(
        "transient",
        "delete",
        "--all",
        "--skip-plugins",
        "--skip-themes"
    )

    # ------------------------------------------------------
    # 13. Database schema
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Checking WordPress database schema"

    Invoke-WpCli @(
        "core",
        "update-db",
        "--skip-plugins",
        "--skip-themes"
    )

    # ------------------------------------------------------
    # 14. Rewrite rules
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Flushing rewrite rules"

    Invoke-WpCli @(
        "rewrite",
        "flush",
        "--skip-plugins",
        "--skip-themes"
    )

    # ------------------------------------------------------
    # 15. Synchronize uploads when requested
    # ------------------------------------------------------

    if ($IncludeUploads) {
        Sync-ProductionUploads
    }

    # ------------------------------------------------------
    # 16. Verify
    # ------------------------------------------------------

    Write-Host ""
    Write-Host "==> Verifying synchronized environment"

    Invoke-WpCli @(
        "core",
        "is-installed",
        "--skip-plugins",
        "--skip-themes"
    )

    Write-Host ""
    Write-Host "Local URL:"

    Invoke-WpCli @(
        "option",
        "get",
        "home",
        "--skip-plugins",
        "--skip-themes"
    )

    Write-Host ""
    Write-Host "Database prefix:"

    Invoke-WpCli @(
        "db",
        "prefix"
    )

    Write-Host ""
    Write-Host "==============================================="
    Write-Host " Sync complete"
    Write-Host "==============================================="
    Write-Host ""
    Write-Host "Database:     synchronized"

    if ($IncludeUploads) {
        Write-Host "Uploads:      synchronized"
        Write-Host "Files:        $UploadsFileCount"
        Write-Host "Bytes:        $UploadsByteCount"
        Write-Host "Verification: $UploadsVerification"
    }
    else {
        Write-Host "Uploads:      NOT synchronized"
    }

    Write-Host ""
    Write-Host "Site:"
    Write-Host "  $LocalUrl"
    Write-Host ""
    Write-Host "Admin:"
    Write-Host "  $LocalUrl/wp-admin"
    Write-Host ""
    Write-Host "Username:"
    Write-Host "  $DevAdminUser"
    Write-Host ""
    Write-Host "Password:"
    Write-Host "  $DevAdminPasswordFile"
    Write-Host ""
    Write-Host "OIDC:"
    Write-Host "  disabled and production config removed"
    Write-Host ""
}
finally {
    Remove-Item $LocalDump -Force -ErrorAction SilentlyContinue
    Remove-Item $ExportErrorFile -Force -ErrorAction SilentlyContinue
    Remove-Item $ImportOutputFile -Force -ErrorAction SilentlyContinue
    Remove-Item $ImportErrorFile -Force -ErrorAction SilentlyContinue
    Remove-Item $PasswordBridgeFile -Force -ErrorAction SilentlyContinue
    Remove-Item $PasswordScriptFile -Force -ErrorAction SilentlyContinue
    Remove-Item $UploadsArchive -Force -ErrorAction SilentlyContinue
    Remove-Item $UploadsStageDir -Recurse -Force -ErrorAction SilentlyContinue
    Remove-Item $RemoteManifestFile -Force -ErrorAction SilentlyContinue
    Remove-Item $LocalManifestFile -Force -ErrorAction SilentlyContinue
    Remove-Item $RemoteStatsFile -Force -ErrorAction SilentlyContinue
    Remove-Item $UploadsErrorFile -Force -ErrorAction SilentlyContinue

    if (Test-Path $UploadsBackupDir) {
        if (Test-Path $UploadsDir) {
            Remove-Item $UploadsBackupDir -Recurse -Force -ErrorAction SilentlyContinue
        }
        else {
            Move-Item $UploadsBackupDir $UploadsDir -ErrorAction SilentlyContinue
        }
    }

    Set-Location $OriginalLocation
}
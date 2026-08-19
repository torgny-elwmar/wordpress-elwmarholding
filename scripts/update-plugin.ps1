param(
    [Parameter(Mandatory, Position = 0)]
    [ValidateSet("polylang", "daggerhart-openid-connect-generic")]
    [string] $Plugin,

    [Parameter(Position = 1)]
    [string] $Version,

    [switch] $CheckOnly,
    [switch] $Force
)

$ErrorActionPreference = "Stop"

$PluginFiles = @{
    "polylang"                          = "polylang.php"
    "daggerhart-openid-connect-generic" = "openid-connect-generic.php"
}

$RepoRoot          = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$RelativePluginDir = "wp-content/plugins/$Plugin"
$PluginDir         = Join-Path $RepoRoot $RelativePluginDir
$MainPluginFile    = Join-Path $PluginDir $PluginFiles[$Plugin]
$WorkRoot          = Join-Path $RepoRoot ".dev/plugin-updates"
$BackupRoot        = Join-Path $RepoRoot ".dev/plugin-backups"

function Get-PluginVersion {
    param(
        [Parameter(Mandatory)]
        [string] $FilePath
    )

    $Header = Get-Content $FilePath -Raw
    $Match = [regex]::Match($Header, "(?mi)^\s*\*?\s*Version:\s*(.+?)\s*$")

    if (-not $Match.Success) {
        throw "Could not read the plugin version from $FilePath."
    }

    return $Match.Groups[1].Value.Trim()
}

if (-not (Test-Path $MainPluginFile)) {
    throw "The source-controlled plugin was not found at $RelativePluginDir."
}

Set-Location $RepoRoot

$CurrentVersion = Get-PluginVersion $MainPluginFile
$ApiUrl = "https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request%5Bslug%5D=$Plugin&request%5Bfields%5D%5Bversions%5D=1"
$PluginInfo = Invoke-RestMethod -Uri $ApiUrl

if (-not $PluginInfo -or $PluginInfo.slug -ne $Plugin) {
    throw "WordPress.org returned unexpected metadata for $Plugin."
}

$TargetVersion = if ($Version) { $Version } else { [string] $PluginInfo.version }
$DownloadUrl = if ($Version) {
    $VersionEntry = $PluginInfo.versions.PSObject.Properties[$Version]

    if (-not $VersionEntry) {
        throw "Version $Version is not available from WordPress.org for $Plugin."
    }

    [string] $VersionEntry.Value
}
else {
    [string] $PluginInfo.download_link
}

Write-Host ""
Write-Host "Plugin:  $Plugin"
Write-Host "Current: $CurrentVersion"
Write-Host "Target:  $TargetVersion"

if ($CheckOnly) {
    if ($CurrentVersion -eq $TargetVersion) {
        Write-Host "Status:  up to date"
    }
    else {
        Write-Host "Status:  update available"
    }

    return
}

$PluginChanges = @(& git status --porcelain -- $RelativePluginDir)

if ($LASTEXITCODE -ne 0) {
    throw "Could not inspect the Git status for $RelativePluginDir."
}

if ($PluginChanges.Count -gt 0 -and -not $Force) {
    throw "The plugin directory has uncommitted changes. Commit or discard them first, or use -Force."
}

if ($CurrentVersion -eq $TargetVersion -and -not $Force) {
    Write-Host "Status:  already up to date; no files changed."
    return
}

if (-not $DownloadUrl.StartsWith("https://downloads.wordpress.org/plugin/", [StringComparison]::OrdinalIgnoreCase)) {
    throw "WordPress.org returned an unexpected download URL: $DownloadUrl"
}

$Timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$WorkDir = Join-Path $WorkRoot "$Plugin-$Timestamp"
$ArchiveFile = Join-Path $WorkDir "$Plugin-$TargetVersion.zip"
$ExtractDir = Join-Path $WorkDir "extracted"
$BackupDir = Join-Path $BackupRoot "$Plugin-$CurrentVersion-$Timestamp"

New-Item -ItemType Directory -Force $ExtractDir | Out-Null
New-Item -ItemType Directory -Force $BackupRoot | Out-Null

try {
    Write-Host "Downloading $DownloadUrl"
    Invoke-WebRequest -Uri $DownloadUrl -OutFile $ArchiveFile

    if ((Get-Item $ArchiveFile).Length -lt 1024) {
        throw "The downloaded plugin archive is unexpectedly small."
    }

    Expand-Archive -Path $ArchiveFile -DestinationPath $ExtractDir

    $ExtractedPluginDir = Join-Path $ExtractDir $Plugin
    $ExtractedMainFile = Join-Path $ExtractedPluginDir $PluginFiles[$Plugin]

    if (-not (Test-Path $ExtractedMainFile)) {
        throw "The archive does not contain the expected $Plugin plugin structure."
    }

    $ArchiveVersion = Get-PluginVersion $ExtractedMainFile

    if ($ArchiveVersion -ne $TargetVersion) {
        throw "The archive contains version $ArchiveVersion, expected $TargetVersion."
    }

    Write-Host "Backing up the current plugin to $BackupDir"
    Move-Item $PluginDir $BackupDir

    try {
        Move-Item $ExtractedPluginDir $PluginDir
    }
    catch {
        Remove-Item $PluginDir -Recurse -Force -ErrorAction SilentlyContinue
        Move-Item $BackupDir $PluginDir
        throw
    }

    $InstalledVersion = Get-PluginVersion (Join-Path $PluginDir $PluginFiles[$Plugin])

    if ($InstalledVersion -ne $TargetVersion) {
        Remove-Item $PluginDir -Recurse -Force
        Move-Item $BackupDir $PluginDir
        throw "Post-install verification failed; the original plugin was restored."
    }

    Write-Host ""
    Write-Host "Updated $Plugin from $CurrentVersion to $InstalledVersion."
    Write-Host "Backup: $BackupDir"
    Write-Host ""
    Write-Host "Next steps:"
    Write-Host "  npm start"
    Write-Host "  npx wp-env run cli wp plugin list"
    Write-Host "  git diff -- $RelativePluginDir"
}
finally {
    Remove-Item $WorkDir -Recurse -Force -ErrorAction SilentlyContinue
}
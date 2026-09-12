# One-time local setup: PHP with PostgreSQL support + Lemonwares database
# Run in PowerShell: .\scripts\setup-local-php-pgsql.ps1

$ErrorActionPreference = "Stop"

$phpRoot = Join-Path $env:USERPROFILE ".config\php-8.5-nts"
$phpExe = Join-Path $phpRoot "php.exe"
$phpZip = Join-Path $env:TEMP "php-8.5.0-nts.zip"
$phpUrl = "https://windows.php.net/downloads/releases/archives/php-8.5.0-nts-Win32-vs17-x64.zip"
$pgBin = "C:\Program Files\PostgreSQL\18\bin"
$repoRoot = Split-Path -Parent $PSScriptRoot

if (-not (Test-Path $phpExe)) {
    Write-Host "==> Downloading PHP 8.5 NTS with PostgreSQL extensions..." -ForegroundColor Cyan
    New-Item -ItemType Directory -Force -Path $phpRoot | Out-Null
    Invoke-WebRequest -Uri $phpUrl -OutFile $phpZip -UseBasicParsing
    Expand-Archive -Path $phpZip -DestinationPath $phpRoot -Force
    Copy-Item (Join-Path $phpRoot "php.ini-development") (Join-Path $phpRoot "php.ini") -Force
    Add-Content (Join-Path $phpRoot "php.ini") "`nextension_dir=`"$phpRoot\ext`"`nextension=pdo_pgsql"
}

Copy-Item (Join-Path $pgBin "libpq.dll") $phpRoot -Force
Copy-Item (Join-Path $pgBin "libintl-9.dll") $phpRoot -Force
Copy-Item (Join-Path $pgBin "libiconv-2.dll") $phpRoot -Force

Write-Host "==> Verifying pdo_pgsql..." -ForegroundColor Cyan
& $phpExe -m | Select-String -Pattern "pdo_pgsql"

Set-Location $repoRoot
Write-Host "==> Creating database (if missing)..." -ForegroundColor Cyan
& (Join-Path $repoRoot "bin\create-db.bat")

Write-Host "==> Running migrations..." -ForegroundColor Cyan
$env:Path = (Join-Path $repoRoot "bin") + ";" + $env:Path
& (Join-Path $repoRoot "bin\php.bat") artisan migrate

Write-Host ""
Write-Host "Done. From this project, use:" -ForegroundColor Green
Write-Host "  .\bin\php.bat artisan serve"
Write-Host "  .\bin\php.bat artisan migrate"
Write-Host ""
Write-Host "Or in Git Bash:" -ForegroundColor Green
Write-Host "  export PATH=`"`$PWD/bin:`$PATH`""
Write-Host "  php artisan migrate"

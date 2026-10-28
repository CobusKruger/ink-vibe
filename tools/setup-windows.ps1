#Requires -RunAsAdministrator
<#
.SYNOPSIS
  Installs the tools ink-vibe needs on a Windows machine. Safe to re-run.

.DESCRIPTION
  winget:  Local (Flywheel), PHP 8.3, NVM for Windows, Python 3.12
  direct:  Composer and WP-CLI (not on winget), into ~\bin with .bat and Git Bash shims
  nvm:     Node 24.12.0 (tools/fidelity/run.sh needs 20+)
  npm:     ink-vibe packages + Playwright Chromium in tmp\ms-playwright
  composer: ink-vibe PHP dev tools

  Run from an elevated Windows PowerShell:
    powershell -ExecutionPolicy Bypass -File tools\setup-windows.ps1
    powershell -ExecutionPolicy Bypass -File tools\setup-windows.ps1 -IncludeVerseHaven

.PARAMETER IncludeVerseHaven
  Also runs `npm ci` in ..\verse-haven (adds node_modules there; nothing else changes).
#>
param(
	[switch]$IncludeVerseHaven
)

$ErrorActionPreference = 'Stop'
$Repo     = Split-Path -Parent $PSScriptRoot
$BinDir   = Join-Path $env:USERPROFILE 'bin'
$NodeVer  = '24.12.0'

function Say($msg) { Write-Host "== $msg ==" -ForegroundColor Cyan }

function Update-Path {
	$machine = [Environment]::GetEnvironmentVariable('Path', 'Machine')
	$user    = [Environment]::GetEnvironmentVariable('Path', 'User')
	$env:Path = "$machine;$user"
	foreach ($name in 'NVM_HOME', 'NVM_SYMLINK') {
		$value = [Environment]::GetEnvironmentVariable($name, 'Machine')
		if (-not $value) { $value = [Environment]::GetEnvironmentVariable($name, 'User') }
		if ($value) { Set-Item "env:$name" $value }
	}
}

function Install-Winget([string]$Id, [string]$Override) {
	$listed = winget list --id $Id --exact --accept-source-agreements --disable-interactivity 2>$null | Out-String
	if ($listed -match [regex]::Escape($Id)) {
		Write-Host "$Id already installed"
		return
	}
	Say "winget install $Id"
	$wingetArgs = @('install', '--id', $Id, '--exact', '--silent',
		'--accept-package-agreements', '--accept-source-agreements', '--disable-interactivity')
	if ($Override) { $wingetArgs += @('--override', $Override) }
	& winget @wingetArgs
	if ($LASTEXITCODE -ne 0) { throw "winget install $Id failed ($LASTEXITCODE)" }
}

function Get-Verified([string]$Url, [string]$HashUrl, [string]$Algorithm, [string]$Dest) {
	$tmp = "$Dest.download"
	Invoke-WebRequest -Uri $Url -OutFile $tmp -UseBasicParsing
	$expected = ((Invoke-WebRequest -Uri $HashUrl -UseBasicParsing).Content.Trim() -split '\s+')[0]
	$actual   = (Get-FileHash $tmp -Algorithm $Algorithm).Hash
	if ($actual -ne $expected.ToUpper()) {
		Remove-Item $tmp -Force
		throw "Checksum mismatch for $Url"
	}
	Move-Item $tmp $Dest -Force
}

function Add-Shims([string]$Name, [string]$Phar) {
	# .bat for PowerShell/cmd, extensionless script for Git Bash.
	Set-Content -Path (Join-Path $BinDir "$Name.bat") -Encoding Ascii -Value "@php `"%~dp0$Phar`" %*"
	$sh = "#!/usr/bin/env bash`nexec php `"`$(dirname `"`$0`")/$Phar`" `"`$@`"`n"
	[IO.File]::WriteAllText((Join-Path $BinDir $Name), $sh)
}

if (-not (Get-Command winget -ErrorAction SilentlyContinue)) {
	throw 'winget not found. Install "App Installer" from the Microsoft Store first.'
}

# --- 1. winget packages --------------------------------------------------
Install-Winget 'Flywheel.Local'
Install-Winget 'PHP.PHP.8.3'
Install-Winget 'CoreyButler.NVMforWindows'
Install-Winget 'Python.Python.3.12' 'InstallAllUsers=0 PrependPath=1 Include_launcher=1 /quiet'
Update-Path

# --- 2. php.ini ------------------------------------------------------------
Say 'PHP configuration'
$phpCmd = Get-Command php -ErrorAction SilentlyContinue
if (-not $phpCmd) { throw 'php not on PATH after install. Open a new elevated PowerShell and re-run.' }
$phpExe = $phpCmd.Source
$target = (Get-Item $phpExe).Target
if ($target) { $phpExe = @($target)[0] }
$phpDir = Split-Path -Parent $phpExe
$ini    = Join-Path $phpDir 'php.ini'
if (-not (Test-Path $ini)) {
	Copy-Item (Join-Path $phpDir 'php.ini-development') $ini
}
$text = Get-Content $ini -Raw
$text = $text -replace '(?m)^;\s*extension_dir\s*=\s*"ext"', 'extension_dir = "ext"'
foreach ($ext in 'curl', 'fileinfo', 'gd', 'intl', 'mbstring', 'mysqli', 'openssl', 'pdo_mysql', 'sodium', 'zip') {
	# `;extension=x` only: php.ini's own help text has `;   extension=mysqli` examples.
	$text = $text -replace "(?m)^;extension=$ext\s*$", "extension=$ext"
}
$text = $text -replace '(?m)^memory_limit\s*=.*$', 'memory_limit = 1G'
Set-Content -Path $ini -Value $text -Encoding Ascii
php -v

# --- 3. Composer and WP-CLI ------------------------------------------------
New-Item -ItemType Directory -Force -Path $BinDir | Out-Null
$userPath = [Environment]::GetEnvironmentVariable('Path', 'User')
if (($userPath -split ';') -notcontains $BinDir) {
	[Environment]::SetEnvironmentVariable('Path', "$userPath;$BinDir", 'User')
}
Update-Path

if (-not (Test-Path (Join-Path $BinDir 'composer.phar'))) {
	Say 'Composer'
	Get-Verified 'https://getcomposer.org/download/latest-stable/composer.phar' `
		'https://getcomposer.org/download/latest-stable/composer.phar.sha256' 'SHA256' (Join-Path $BinDir 'composer.phar')
}
Add-Shims 'composer' 'composer.phar'

if (-not (Test-Path (Join-Path $BinDir 'wp-cli.phar'))) {
	Say 'WP-CLI'
	Get-Verified 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar' `
		'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar.sha512' 'SHA512' (Join-Path $BinDir 'wp-cli.phar')
}
Add-Shims 'wp' 'wp-cli.phar'
composer --version
wp --version

# --- 4. Node via nvm -------------------------------------------------------
Say "Node $NodeVer"
if (-not $env:NVM_HOME) { throw 'NVM_HOME not set after install. Open a new elevated PowerShell and re-run.' }
$nvm = Join-Path $env:NVM_HOME 'nvm.exe'
& $nvm install $NodeVer
& $nvm use $NodeVer
Update-Path
$env:Path = "$env:NVM_SYMLINK;$env:Path"
node -v

# --- 5. Project packages ---------------------------------------------------
Say 'ink-vibe: npm ci'
Push-Location $Repo
try {
	npm ci
	if ($LASTEXITCODE -ne 0) { throw 'npm ci failed' }

	Say 'ink-vibe: Playwright Chromium -> tmp\ms-playwright'
	$env:PLAYWRIGHT_BROWSERS_PATH = Join-Path $Repo 'tmp\ms-playwright'
	npx playwright install chromium
	if ($LASTEXITCODE -ne 0) { throw 'playwright install failed' }

	Say 'ink-vibe: composer install'
	composer install --no-interaction
	if ($LASTEXITCODE -ne 0) { throw 'composer install failed' }
} finally {
	Pop-Location
}

if ($IncludeVerseHaven) {
	Say 'verse-haven: npm ci'
	Push-Location (Join-Path (Split-Path -Parent $Repo) 'verse-haven')
	try {
		npm ci
		if ($LASTEXITCODE -ne 0) { throw 'npm ci failed in verse-haven' }
	} finally {
		Pop-Location
	}
}

Say 'Done'
Write-Host @'
Next:
  - Open Local once and create the site "nuwe-ink" (it ships its own PHP/MySQL for the site).
  - If `python` still opens the Microsoft Store: Settings > Apps > Advanced app settings >
    App execution aliases, and turn off python.exe / python3.exe.
  - Open a new terminal so PATH changes apply everywhere.
'@

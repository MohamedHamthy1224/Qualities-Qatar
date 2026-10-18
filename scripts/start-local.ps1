[CmdletBinding()]
param(
    [switch] $CheckOnly
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

# Local development only. This script never creates or repairs database files.
$projectRoot = Split-Path -Parent $PSScriptRoot
$databaseDirectory = Join-Path $projectRoot 'storage\app\private\database-restored-20261018'
$logDirectory = Join-Path $projectRoot 'storage\app\private\local-runtime'
$phpExecutable = 'D:\Project\xampp\php\php.exe'
$databaseExecutable = 'D:\Project\xampp\mysql\bin\mysqld.exe'
$databaseBase = 'D:\Project\xampp\mysql'
$publicDirectory = Join-Path $projectRoot 'public'
$routerFile = Join-Path $projectRoot 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'

function Get-DotEnvValue([string] $Name) {
    $entries = @(Get-Content -LiteralPath (Join-Path $projectRoot '.env') | Where-Object {
        $_ -match ('^\s*' + [regex]::Escape($Name) + '\s*=')
    })
    if ($entries.Count -gt 1) { throw "Duplicate $Name entries in .env; resolve them before starting." }
    if ($entries.Count -eq 0) { return '' }
    $value = ($entries[0] -split '=', 2)[1].Trim()
    if ($value.StartsWith('"') -or $value.StartsWith("'")) {
        return $value.Trim('"', "'")
    }
    return ($value -replace '\s+#.*$', '').Trim()
}

function Assert-LocalConfiguration {
    $expected = @{
        APP_ENV = 'local'
        DB_CONNECTION = 'mysql'
        DB_HOST = '127.0.0.1'
        DB_PORT = '3309'
        DB_DATABASE = 'qualities_erp'
    }
    foreach ($entry in $expected.GetEnumerator()) {
        if ((Get-DotEnvValue $entry.Key) -ne $entry.Value) {
            throw "Unexpected $($entry.Key) in .env. See docs/local-development.md; configuration was not changed."
        }
        $inheritedValue = [Environment]::GetEnvironmentVariable($entry.Key, 'Process')
        if ($inheritedValue -and $inheritedValue -ne $entry.Value) {
            throw "An inherited $($entry.Key) overrides this project's local configuration."
        }
    }
    foreach ($name in @('DB_URL', 'DATABASE_URL', 'APP_CONFIG_CACHE')) {
        if ((Get-DotEnvValue $name) -or [Environment]::GetEnvironmentVariable($name, 'Process')) {
            throw "$name is set; remove the override before using this local startup script."
        }
    }
    if (Test-Path -LiteralPath (Join-Path $projectRoot 'bootstrap\cache\config.php')) {
        throw 'Cached Laravel configuration may target another database. Review .env, then run php artisan config:clear first.'
    }
}

function Test-CommandArgument([string] $CommandLine, [string] $Argument) {
    $normalizedCommand = $CommandLine.Replace('"', '').Replace('/', '\')
    $normalizedArgument = $Argument.Replace('/', '\')
    return $normalizedCommand -match ('(?i)(?:^|\s)' + [regex]::Escape($normalizedArgument) + '(?=\s|$)')
}

function Test-RouterArgument([string] $CommandLine) {
    # artisan serve uses this exact Console/../resources alias for the same router.
    $foundationDirectory = Split-Path -Parent (Split-Path -Parent $routerFile)
    $artisanRouterAlias = Join-Path $foundationDirectory 'Console\..\resources\server.php'
    foreach ($allowedRouter in @($routerFile, $artisanRouterAlias)) {
        if (Test-CommandArgument $CommandLine $allowedRouter) { return $true }
    }
    return $false
}

function Get-ExpectedListener([int] $Port, [ValidateSet('database', 'web')] [string] $Kind) {
    # Inspect every listening address so a wildcard/IPv6 listener cannot be mistaken for our loopback service.
    $listeners = @(Get-NetTCPConnection -State Listen -ErrorAction Stop | Where-Object LocalPort -eq $Port)
    if ($listeners.Count -eq 0) { return $null }
    if (@($listeners | Where-Object LocalAddress -ne '127.0.0.1').Count -gt 0) {
        throw "Port $Port has an unexpected listening address. No process will be stopped."
    }
    $owners = @($listeners.OwningProcess | Select-Object -Unique)
    if ($owners.Count -ne 1) { throw "Port $Port has unexpected process owners. No process will be stopped." }
    $owner = Get-CimInstance Win32_Process -Filter "ProcessId = $($owners[0])" -ErrorAction Stop
    if (-not $owner -or -not $owner.ExecutablePath -or -not $owner.CommandLine) {
        throw "Cannot verify the process on port $Port. Run from a shell permitted to inspect local processes."
    }
    $expectedExecutable = if ($Kind -eq 'database') { $databaseExecutable } else { $phpExecutable }
    if ([IO.Path]::GetFullPath($owner.ExecutablePath) -ne [IO.Path]::GetFullPath($expectedExecutable)) {
        throw "Port $Port belongs to an unexpected executable. No process will be stopped."
    }
    $requiredArguments = if ($Kind -eq 'database') {
        @('--no-defaults', "--datadir=$databaseDirectory", '--port=3309', '--bind-address=127.0.0.1', '--innodb-force-recovery=0', '--read-only=OFF')
    } else {
        @('-S', '127.0.0.1:8001')
    }
    foreach ($argument in $requiredArguments) {
        if (-not (Test-CommandArgument $owner.CommandLine $argument)) {
            throw "Port $Port is not this project's expected $Kind process. No process will be stopped."
        }
        if ($Kind -eq 'database') {
            $optionName = ($argument -split '=', 2)[0]
            $occurrences = [regex]::Matches($owner.CommandLine.Replace('"', ''), '(?i)(?:^|\s)' + [regex]::Escape($optionName) + '(?==|\s|$)').Count
            if ($occurrences -ne 1) { throw "Port $Port has ambiguous database arguments. Startup was refused." }
        }
    }
    if ($Kind -eq 'database' -and $owner.CommandLine -match '(?i)--(?:skip-grant-tables|bootstrap|initialize)') {
        throw 'The database process has unexpected recovery/initialization options. Startup was refused.'
    }
    if ($Kind -eq 'web' -and -not (Test-RouterArgument $owner.CommandLine)) {
        throw "Port 8001 does not use this project's known Laravel router. No process will be stopped."
    }
    return $owner
}

function Assert-NoPendingProcess([ValidateSet('database', 'web')] [string] $Kind) {
    $processName = if ($Kind -eq 'database') { 'mysqld.exe' } else { 'php.exe' }
    $runningProcesses = @(Get-CimInstance Win32_Process -Filter "Name = '$processName'" -ErrorAction Stop)
    foreach ($running in $runningProcesses) {
        if (-not $running.CommandLine) { throw "Cannot inspect a running $Kind process; startup was refused to avoid a duplicate." }
        $sameRuntime = if ($Kind -eq 'database') {
            Test-CommandArgument $running.CommandLine "--datadir=$databaseDirectory"
        } else {
            (Test-CommandArgument $running.CommandLine '127.0.0.1:8001') -and (Test-RouterArgument $running.CommandLine)
        }
        if ($sameRuntime) { throw "This project's $Kind process exists without a listening port. Wait or inspect its private logs before retrying." }
    }
}

function Start-LocalProcess([string] $Executable, [string[]] $Arguments, [string] $Name, [string] $WorkingDirectory) {
    # Quote argument values for Start-Process; no shell command is constructed.
    $argumentLine = ($Arguments | ForEach-Object { '"' + $_.Replace('"', '\"') + '"' }) -join ' '
    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss-fff'
    return Start-Process -FilePath $Executable -ArgumentList $argumentLine -WorkingDirectory $WorkingDirectory `
        -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $logDirectory "$Name-$stamp.out.log") `
        -RedirectStandardError (Join-Path $logDirectory "$Name-$stamp.err.log")
}

function Wait-ForLocalListener([int] $Port, [string] $Kind, [System.Diagnostics.Process] $StartedProcess) {
    $deadline = (Get-Date).AddSeconds(30)
    do {
        $StartedProcess.Refresh()
        if ($StartedProcess.HasExited) { throw "$Kind exited before startup completed. Inspect the private local-runtime logs." }
        $listener = Get-ExpectedListener $Port $Kind
        if ($listener) {
            if ($listener.ProcessId -ne $StartedProcess.Id) { throw "Another process took port $Port during startup. No process was stopped." }
            return
        }
        Start-Sleep -Milliseconds 500
    } while ((Get-Date) -lt $deadline)
    throw "$Kind did not listen on port $Port within 30 seconds. Inspect private logs; do not start a duplicate process."
}

foreach ($file in @($phpExecutable, $databaseExecutable, $routerFile, (Join-Path $projectRoot '.env'), (Join-Path $publicDirectory 'build\manifest.json'), (Join-Path $databaseDirectory 'ibdata1'))) {
    if (-not (Test-Path -LiteralPath $file -PathType Leaf)) { throw "Required local runtime file is missing: $file" }
}
foreach ($directory in @((Join-Path $databaseDirectory 'mysql'), (Join-Path $databaseDirectory 'qualities_erp'))) {
    if (-not (Test-Path -LiteralPath $directory -PathType Container)) {
        throw 'The restored database is missing. This script will not initialize, import or repair it.'
    }
}

Assert-LocalConfiguration
$startupLock = [System.Threading.Mutex]::new($false, 'Local\QatarERPStartup-3309-8001')
$lockHeld = $false
try {
    try { $lockHeld = $startupLock.WaitOne(0) }
    catch [System.Threading.AbandonedMutexException] { $lockHeld = $true }
    if (-not $lockHeld) { throw 'Another Qatar ERP startup is in progress. Wait for it to finish before retrying.' }
    $databaseOwner = Get-ExpectedListener 3309 'database'
    $webOwner = Get-ExpectedListener 8001 'web'
    if (-not $databaseOwner) { Assert-NoPendingProcess 'database' }
    if (-not $webOwner) { Assert-NoPendingProcess 'web' }
    if ($CheckOnly) {
        Write-Output ('Database 3309: ' + $(if ($databaseOwner) { 'verified, already running' } else { 'available to start' }))
        Write-Output ('Web 8001: ' + $(if ($webOwner) { 'verified, already running' } else { 'available to start' }))
        Write-Output 'Preflight passed. No processes or files were changed.'
        return
    }

New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
if (-not $databaseOwner) {
    $databaseArguments = @(
        '--no-defaults', "--basedir=$databaseBase", "--datadir=$databaseDirectory",
        "--innodb-data-home-dir=$databaseDirectory", "--innodb-log-group-home-dir=$databaseDirectory",
        '--innodb-data-file-path=ibdata1:10M:autoextend', '--innodb-log-file-size=5M',
        '--innodb-force-recovery=0', '--read-only=OFF', '--port=3309', '--bind-address=127.0.0.1',
        '--character-set-server=utf8mb4', '--collation-server=utf8mb4_unicode_ci',
        "--pid-file=$databaseDirectory\erp.pid", "--log-error=$databaseDirectory\erp-error.log",
        '--skip-log-bin', '--standalone', '--console'
    )
    $databaseProcess = Start-LocalProcess $databaseExecutable $databaseArguments 'database' $projectRoot
    Wait-ForLocalListener 3309 'database' $databaseProcess
}
if (-not $webOwner) {
    $webProcess = Start-LocalProcess $phpExecutable @('-S', '127.0.0.1:8001', '-t', $publicDirectory, $routerFile) 'web' $publicDirectory
    Wait-ForLocalListener 8001 'web' $webProcess
}
Write-Output 'Qatar ERP is running at http://127.0.0.1:8001/'
Write-Output "Private startup logs: $logDirectory"
}
finally {
    if ($lockHeld) { $startupLock.ReleaseMutex() }
    $startupLock.Dispose()
}

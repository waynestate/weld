# weld.ps1 — run WELD locally on Windows with one command.
#
# Works on Windows PowerShell 5.1 and PowerShell 7; no bash, no WSL. The app
# and Vite run in Docker; PHP and Node live in the containers, so the host
# only needs Docker. Linux and macOS have their own native path: bin/weld.sh.
#
# Usage: bin\weld.ps1 <up|down|test|logs>

[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [string] $Command
)

$ErrorActionPreference = 'Stop'

function Show-Usage {
    Write-Host "Usage: bin\weld.ps1 <command>"
    Write-Host ""
    Write-Host "Commands:"
    Write-Host "  up     Copy .env.example to .env if missing, install the PHP and"
    Write-Host "         Node dependencies in the containers, start the app and"
    Write-Host "         Vite, generate the app key if needed, and migrate."
    Write-Host "  down   Stop and remove the containers."
    Write-Host "  test   Run 'composer ci' in the app container."
    Write-Host "  logs   Follow the container logs; Ctrl+C stops."
}

function Invoke-Docker {
    param(
        [Parameter(Mandatory = $true)]
        [string[]] $Arguments
    )

    Write-Host ("weld: docker " + ($Arguments -join ' '))
    & docker @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "docker $($Arguments -join ' ') failed with exit code $LASTEXITCODE"
    }
}

# Run from the repository root, no matter where the caller stands, and leave
# the caller's location as we found it.
Push-Location -LiteralPath (Split-Path -Parent $PSScriptRoot)
try {
    switch ($Command) {
        'up' {
            if (-not (Test-Path -LiteralPath '.env')) {
                Write-Host 'weld: Copy-Item .env.example .env'
                Copy-Item -LiteralPath '.env.example' -Destination '.env'
            }
            Invoke-Docker -Arguments @('compose', 'run', '--rm', 'app', 'composer', 'install')
            Invoke-Docker -Arguments @('compose', 'run', '--rm', 'vite', 'npm', 'ci')
            Invoke-Docker -Arguments @('compose', 'up', '-d')
            $appKey = Select-String -Path '.env' -Pattern '^APP_KEY=.'
            if (-not $appKey) {
                Invoke-Docker -Arguments @('compose', 'run', '--rm', 'app', 'php', 'artisan', 'key:generate')
            }
            Write-Host 'weld: New-Item database/database.sqlite'
            New-Item -ItemType File -Force -Path 'database/database.sqlite' | Out-Null
            Invoke-Docker -Arguments @('compose', 'run', '--rm', 'app', 'php', 'artisan', 'migrate')
            Write-Host 'weld: done. Open http://localhost:8000'
        }
        'down' {
            Invoke-Docker -Arguments @('compose', 'down')
        }
        'test' {
            Invoke-Docker -Arguments @('compose', 'run', '--rm', 'app', 'composer', 'ci')
        }
        'logs' {
            Invoke-Docker -Arguments @('compose', 'logs', '-f')
        }
        default {
            Show-Usage
            exit 2
        }
    }
}
finally {
    Pop-Location
}

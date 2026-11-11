# weld.ps1: thin pointer for one release (D-087). The local runtime is Parity
# (D-082), and bin\weld is deleted in the next release.
#
# Works on Windows PowerShell 5.1 and PowerShell 7; no bash, no WSL.
# `up`, `down` and `logs` print the Parity commands that replace them and
# exit 2, so a script cannot mistake the pointer for a running app. `test`
# and `doctor` still run here, because Parity cannot run them yet.
#
# Usage: bin\weld.ps1 <test|doctor>

[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [string] $Command,
    # Anything past the command is unknown (the old opt-out flag is gone), so
    # the script refuses it below.
    [Parameter(ValueFromRemainingArguments)]
    [string[]] $Rest
)

$ErrorActionPreference = 'Stop'

function Show-Usage {
    Write-Host "Usage: bin\weld.ps1 <command>"
    Write-Host ""
    Write-Host "Commands still run here:"
    Write-Host "  test    Run 'composer ci' in the app container."
    Write-Host "  doctor  Run 'php artisan weld:doctor' in the app container."
    Write-Host ""
    Write-Host "Moved to Parity (D-082):"
    Write-Host "  up      parity up"
    Write-Host "  down    parity down"
    Write-Host "  logs    parity logs -f"
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

# A second argument is unknown, and the script refuses it the way it refuses
# an unknown command.
foreach ($arg in $Rest) {
    Show-Usage
    Write-Host ''
    Write-Host "weld: unknown argument: $arg"
    exit 2
}

# Run from the repository root, no matter where the caller stands, and leave
# the caller's location as we found it.
Push-Location -LiteralPath (Split-Path -Parent $PSScriptRoot)
try {
    switch ($Command) {
        'up' {
            Write-Host 'weld: "up" moved to Parity (D-082). Run:'
            Write-Host '  parity init --from-squire (first time only; plain parity init if there is no Squire kit)'
            Write-Host '  parity up'
            Write-Host '  parity test (smoke test)'
            exit 2
        }
        'down' {
            Write-Host 'weld: "down" moved to Parity (D-082). Run:'
            Write-Host '  parity down'
            Write-Host '  (parity down --volumes empties the database)'
            exit 2
        }
        'logs' {
            Write-Host 'weld: "logs" moved to Parity (D-082). Run:'
            Write-Host '  parity logs -f'
            exit 2
        }
        'test' {
            Invoke-Docker -Arguments @('compose', 'run', '--rm', 'app', 'composer', 'ci')
        }
        'doctor' {
            Invoke-Docker -Arguments @('compose', 'run', '--rm', 'app', 'php', 'artisan', 'weld:doctor')
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

# Compatibility entry point. JSON files are now the canonical translation source.
$ErrorActionPreference='Stop'
& (Join-Path $PSScriptRoot '008-language-files.ps1')
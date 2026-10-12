# Creates/reuses one MWK 100 sandbox checkout; never completes a payment or grants credit.
$ErrorActionPreference='Stop'
$root=Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
Set-Location $root
php backend/tests/paychangu-sandbox.php
if($LASTEXITCODE -ne 0){throw 'PayChangu sandbox checkout check failed; inspect provider configuration without printing secrets'}
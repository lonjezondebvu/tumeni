$ErrorActionPreference='Stop'
$root=Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
Set-Location $root
Push-Location backend
try {
 php artisan test --compact
 if($LASTEXITCODE -ne 0){throw 'Backend tests failed'}
}finally{Pop-Location}
Push-Location frontend
try {
 npm.cmd run build
 if($LASTEXITCODE -ne 0){throw 'Typecheck/build failed'}
}finally{Pop-Location}
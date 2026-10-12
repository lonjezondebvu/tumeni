$ErrorActionPreference='Stop'
$root=Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
Set-Location $root
& "$PSScriptRoot/001-local-postgres.ps1"
$env:COMPOSER_CACHE_DIR=Join-Path $root '.cache/composer'
$env:npm_config_cache=Join-Path $root '.cache/npm'
Push-Location backend
try {
 composer install --no-interaction
 if($LASTEXITCODE -ne 0){throw 'Composer install failed'}
 if(!(Test-Path .env)){Copy-Item -LiteralPath .env.example -Destination .env}
 $content=[IO.File]::ReadAllText((Join-Path $pwd '.env'))
 if($content -match '(?m)^APP_KEY=\s*$'){
  php artisan key:generate
  if($LASTEXITCODE -ne 0){throw 'Key generation failed'}
 }
 php artisan migrate --force
 if($LASTEXITCODE -ne 0){throw 'Migration failed'}
 php artisan db:seed --class=DemoSeeder --force
 if($LASTEXITCODE -ne 0){throw 'Seeding failed'}
}finally{Pop-Location}
Push-Location frontend
try {
 npm.cmd ci
 if($LASTEXITCODE -ne 0){throw 'npm ci failed'}
 npm.cmd run build
 if($LASTEXITCODE -ne 0){throw 'Build failed'}
}finally{Pop-Location}
Write-Output 'Setup complete. Run: Set-Location backend; php artisan serve --host=127.0.0.1 --port=8000'
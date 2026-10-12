$ErrorActionPreference = 'Stop'
$root = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
Set-Location $root
$pg = 'C:\Program Files\PostgreSQL\18\bin'
$data = Join-Path $root '.local/pgdata'
New-Item -ItemType Directory -Force -Path '.local' | Out-Null
if (-not (Test-Path "$data/PG_VERSION")) {
    & "$pg/initdb.exe" -D $data -U tumeni --auth=trust --encoding=UTF8 --locale=C
    if ($LASTEXITCODE -ne 0) { throw 'initdb failed' }
}
& "$pg/pg_ctl.exe" -D $data status
if ($LASTEXITCODE -ne 0) {
    & "$pg/pg_ctl.exe" -D $data -l (Join-Path $root '.local/postgres.log') -o '-p 55432 -h 127.0.0.1' start
    if ($LASTEXITCODE -ne 0) { throw 'PostgreSQL start failed' }
}
foreach ($db in @('tumeni','tumeni_test')) {
    $exists = & "$pg/psql.exe" -h 127.0.0.1 -p 55432 -U tumeni -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname='$db'"
    if ($LASTEXITCODE -ne 0) { throw 'database check failed' }
    if ($exists -ne '1') {
        & "$pg/createdb.exe" -h 127.0.0.1 -p 55432 -U tumeni $db
        if ($LASTEXITCODE -ne 0) { throw 'createdb failed' }
    }
}
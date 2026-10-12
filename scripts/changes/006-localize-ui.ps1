$ErrorActionPreference='Stop'
$root=Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
Set-Location $root
$enc=New-Object System.Text.UTF8Encoding($false)
$raw=node scripts/changes/translate-ui-plan.mjs
if($LASTEXITCODE -ne 0){throw 'Read-only UI analysis failed'}
$plan=$raw|ConvertFrom-Json
foreach($file in $plan){
 $path=Join-Path $root $file.path
 $source=[IO.File]::ReadAllText($path)
 $sha=[Security.Cryptography.SHA256]::Create()
 try{$hash=([BitConverter]::ToString($sha.ComputeHash($enc.GetBytes($source)))).Replace('-','').ToLowerInvariant()}finally{$sha.Dispose()}
 if($hash -ne $file.sha256){throw "Source changed during analysis: $($file.path)"}
 foreach($edit in ($file.edits|Sort-Object start -Descending)){
  if($source.Substring($edit.start,$edit.end-$edit.start) -cne $edit.expected){throw "Expected source mismatch: $($file.path):$($edit.start)"}
  $source=$source.Remove($edit.start,$edit.end-$edit.start).Insert($edit.start,$edit.value)
 }
 [IO.File]::WriteAllText($path,$source,$enc)
 Write-Output "Localized $($file.path): $($file.edits.Count) guarded edits"
}
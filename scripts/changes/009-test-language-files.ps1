$ErrorActionPreference='Stop'
$root=Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
$enc=New-Object System.Text.UTF8Encoding($false)
$testRoot=Join-Path $root '.local/language-tests'
$fixture=Join-Path $testRoot ([guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $fixture -Force|Out-Null
$resolved=[IO.Path]::GetFullPath($fixture)
if(!$resolved.StartsWith([IO.Path]::GetFullPath($testRoot)+[IO.Path]::DirectorySeparatorChar,[StringComparison]::OrdinalIgnoreCase)){throw 'Fixture outside intended test directory'}
$previousFixture=$env:TUMENI_LANGUAGE_FIXTURE
try{
 foreach($file in @('en.json','ny.json','_template.json')){Copy-Item -LiteralPath (Join-Path $root "frontend/src/i18n/locales/$file") -Destination (Join-Path $fixture $file)}
 $fr=[ordered]@{code='fr';name=('Fran'+[char]0xE7+'ais');enabled=$true;documentTitle=('Tumeni '+[char]0xB7+' Voyager simplement');messages=[ordered]@{'Sign in'='Se connecter';'Your pickup'='';'{0} is on the way.'='{0} arrive.'}}
 $frPath=Join-Path $fixture 'fr.json'
 [IO.File]::WriteAllText($frPath,($fr|ConvertTo-Json -Depth 6),$enc)
 $output=Join-Path $fixture 'catalogs.generated.ts'
 & (Join-Path $PSScriptRoot '008-language-files.ps1') -LocaleDirectory $fixture -OutputPath $output
 $core=[IO.File]::ReadAllText((Join-Path $root 'frontend/src/i18n/core.ts'),$enc)
 [IO.File]::WriteAllText((Join-Path $fixture 'core.ts'),$core,$enc)
 $env:TUMENI_LANGUAGE_FIXTURE=$fixture
 node --test (Join-Path $root 'frontend/tests/language-fixture.test.mjs')
 if($LASTEXITCODE -ne 0){throw 'Third-language runtime tests failed'}
 $prior=[IO.File]::ReadAllText($output,$enc)
 function Assert-Rejected($content,$expected){
  [IO.File]::WriteAllText($frPath,$content,$enc)
  $rejected=$false
  try{& (Join-Path $PSScriptRoot '008-language-files.ps1') -LocaleDirectory $fixture -OutputPath $output}catch{if($_.Exception.Message -notlike "*$expected*"){throw};$rejected=$true}
  if(!$rejected){throw "Invalid catalog was accepted: $expected"}
  if([IO.File]::ReadAllText($output,$enc) -cne $prior){throw 'Invalid input changed the prior generated catalog'}
  Write-Output "PASS: rejects $expected and preserves generated output"
 }
 $fr.messages['{0} is on the way.']='Placeholder missing'
 Assert-Rejected ($fr|ConvertTo-Json -Depth 6) 'preserve placeholders'
 $fr.messages['{0} is on the way.']='{0} arrive.'
 $fr.messages['Unknown source key']='unexpected'
 Assert-Rejected ($fr|ConvertTo-Json -Depth 6) 'unknown message key'
 $fr.messages.Remove('Unknown source key')
 $fr.messages['Sign in']=42
 Assert-Rejected ($fr|ConvertTo-Json -Depth 6) 'translation must be text'
 Assert-Rejected '{not valid json' 'Invalid JSON'
}finally{
 $env:TUMENI_LANGUAGE_FIXTURE=$previousFixture
 Remove-Item -LiteralPath $resolved -Recurse -Force
}
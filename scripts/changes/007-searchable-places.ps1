$ErrorActionPreference='Stop'
$root=Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
Set-Location $root
$enc=New-Object System.Text.UTF8Encoding($false)
function Replace-Once($source,$before,$after){$first=$source.IndexOf($before,[StringComparison]::Ordinal);if($first -lt 0 -or $source.IndexOf($before,$first+$before.Length,[StringComparison]::Ordinal) -ge 0){throw "Missing/ambiguous replacement: $before"};return $source.Remove($first,$before.Length).Insert($first,$after)}
$p=Join-Path $root 'frontend/src/Rider.tsx'
$s=[IO.File]::ReadAllText($p,$enc)
if($s.Contains("from './components/SearchablePlaceSelect'")){Write-Output 'Booking integration already applied';exit 0}
$s=Replace-Once $s "import {Button,Modal} from './components/ui';" "import {Button,Modal} from './components/ui';`r`nimport {SearchablePlaceSelect} from './components/SearchablePlaceSelect';"
$s=Replace-Once $s "const [geoMessage,setGeoMessage]=useState('');const [query,setQuery]=useState('');" "const [geoMessage,setGeoMessage]=useState('');`r`n const placeOptions=landmarks.map(l=>({value:String(l.id),label:l.name,description:l.meeting_point,aliases:l.aliases,verified:l.verified}));"
$s=Replace-Once $s '<label className="field-label" htmlFor="pickup">' '<label id="pickup-label" className="field-label" htmlFor="pickup">'
$s=Replace-Once $s '<select id="pickup" value={pickup} onChange={e=>{setPickup(e.target.value);if(dest===e.target.value)setDest('''');}}>{landmarks.map(l=><option value={l.id} key={l.id}>{l.name}</option>)}</select>' '<SearchablePlaceSelect id="pickup" value={pickup} options={placeOptions} placeholder={tr("Search places or local names")} onChange={value=>{setPickup(value);if(dest===value)setDest('''');}}/>'
$s=Replace-Once $s '<label className="field-label" htmlFor="destination">' '<label id="destination-label" className="field-label" htmlFor="destination">'
$s=Replace-Once $s '<select id="destination" required value={dest} onChange={e=>setDest(e.target.value)}><option value="" disabled>{tr("Choose your destination")}</option>{landmarks.filter(l=>manual||String(l.id)!==pickup).map(l=><option value={l.id} key={l.id}>{l.name}</option>)}</select>' '<SearchablePlaceSelect id="destination" value={dest} options={placeOptions.filter(l=>manual||l.value!==pickup)} placeholder={tr("Search places or local names")} onChange={setDest}/>'
$start=$s.IndexOf('<div className="suggestions">');$end=$s.IndexOf('<label className="field-label" htmlFor="note">',$start)
if($start -lt 0 -or $end -le $start){throw 'Booking suggestions boundary missing'}
$s=$s.Remove($start,$end-$start)
$s=Replace-Once $s 'e.preventDefault();onQuote(manual?location:' 'e.preventDefault();if(!dest||(!manual&&!pickup))return;onQuote(manual?location:'
$s=Replace-Once $s 'disabled={busy||!dest}' 'disabled={busy||!dest||(!manual&&!pickup)}'
$s=Replace-Once $s 'onClick={()=>setManual(!manual)}' 'onClick={()=>{setManual(!manual);if(manual&&dest===pickup)setDest('''');}}'
[IO.File]::WriteAllText($p,$s,$enc)
Write-Output 'Booking integration applied.'
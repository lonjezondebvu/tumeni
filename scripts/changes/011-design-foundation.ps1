$ErrorActionPreference='Stop'
$root=Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
Set-Location $root
$utf8=[System.Text.UTF8Encoding]::new($false)
function Write-Source([string]$path,[string]$content){[System.IO.File]::WriteAllText((Join-Path $root $path),$content,$utf8)}
function Replace-Once([string]$source,[string]$before,[string]$after){
 if($source.Contains($after)){return $source}
 if(([regex]::Matches($source,[regex]::Escape($before))).Count -ne 1){throw "Missing or ambiguous replacement: $before"}
 return $source.Replace($before,$after)
}
$path='frontend/src/style.css'
$css=[System.IO.File]::ReadAllText((Join-Path $root $path),$utf8)
# One-time guarded transformation; after application, leave subsequent styles alone.
if(!$css.Contains('@import "./tokens.css";')){
 $css=Replace-Once $css '@import "tailwindcss";' "@import `"tailwindcss`";`r`n@import `"./tokens.css`";"
 $css=Replace-Once $css ':root{font-family:Inter,"Segoe UI",Arial,sans-serif;color:#202922;background:#fbfcf9;font-synthesis:none;font-weight:400;--green:#075c43;--muted:#687363;--border:#e3e6df}' ':root{font-family:Inter,"Segoe UI",Arial,sans-serif;color:var(--text);background:var(--canvas);font-synthesis:none;font-weight:400;line-height:var(--leading-body)}'
 $colours=@{
 '#687363'='var(--muted)';'#687269'='var(--muted)';'#687068'='var(--muted)';'#677267'='var(--muted)';
 '#68775d'='var(--muted)';'#687a58'='var(--muted)';'#667067'='var(--muted)';'#6a7563'='var(--muted)';
 '#084934'='var(--green-hover)';'#57a986'='var(--focus)';'#eaf2ec'='var(--selected)';'#edf3e9'='var(--selected)';
 '#fff0e9'='var(--error-bg)';'#8b3a26'='var(--error-text)';'#e9f2e2'='var(--success-bg)';'#37602c'='var(--success-text)';
 '#dbe3d9'='var(--border)';'#d5e3cf'='var(--selected-border)';'#f3f5ef'='var(--surface-muted)';'#f1f3ed'='var(--surface-muted)'
 }
 foreach($colour in $colours.Keys){$css=$css.Replace($colour,$colours[$colour])}
 $values=@{
 'border-radius:9px'='border-radius:var(--radius-control)';'border-radius:12px'='border-radius:var(--radius-panel)';
 'border-radius:20px;padding:38px'='border-radius:var(--radius-dialog);padding:var(--space-6)';
 'max-width:1480px'='max-width:var(--layout-wide)';'max-width:1150px'='max-width:var(--layout-content)';
 'font-size:clamp(36px,3.8vw,54px)'='font-size:var(--text-title)';
 'font-size:13px;font-weight:550'='font-size:var(--text-label);font-weight:550';
 'min-height:48px'='min-height:var(--control-height)';'min-height:49px'='min-height:var(--control-height)';
 'min-height:32px'='min-height:var(--touch-target)';'min-height:41px'='min-height:var(--touch-target)';
 'min-height:40px'='min-height:var(--touch-target)';'min-height:37px'='min-height:var(--touch-target)';
 'box-shadow:0 12px 28px #132c1d20'='box-shadow:var(--shadow-popover)';
 'z-index:20;'='z-index:var(--layer-overlay);';'z-index:21;'='z-index:var(--layer-dialog);';
 'z-index:30;'='z-index:var(--layer-options);';'z-index:10;'='z-index:var(--layer-navigation);'
 }
 foreach($value in $values.Keys){$css=$css.Replace($value,$values[$value])}
 $css=$css.Replace('background:white','background:var(--surface)').Replace('background:#fff;','background:var(--surface);')
 $css=Replace-Once $css 'body{margin:0}' 'body{margin:0}main{min-width:0}button svg{flex-shrink:0}input,select,textarea{min-width:0}input::placeholder,textarea::placeholder{color:var(--muted);opacity:1}input:disabled,select:disabled,textarea:disabled{background:var(--surface-muted);color:var(--muted)}input[aria-invalid="true"],select[aria-invalid="true"],textarea[aria-invalid="true"]{border-color:var(--error-text)}.skip-link{position:fixed;left:var(--space-4);top:var(--space-4);transform:translateY(-200%);z-index:50;background:var(--surface);color:var(--green);border:2px solid var(--green);border-radius:var(--radius-control);padding:var(--space-3) var(--space-4)}.skip-link:focus{transform:none}.content-page a:not(.button),.document-slot a,.topup-row a{text-decoration:underline;text-underline-offset:3px}.ride-layout>*,.driver-grid>*,.onboarding-fields>*,.document-grid>*{min-width:0}'
 $css=Replace-Once $css 'button{transition:background .15s,transform .15s}' 'button{transition:background .15s,transform .15s}.button:active:not(:disabled){transform:translateY(1px);filter:brightness(.95)}'
 $css=Replace-Once $css '.error button,.notice button{background:none;' '.error button,.notice button{min-height:var(--touch-target);color:inherit;background:none;'
 $css=Replace-Once $css '.help-link{display:flex;' '.help-link{min-width:var(--touch-target);min-height:var(--touch-target);display:flex;'
 $css=Replace-Once $css '.close{position:absolute;' '.close{min-width:var(--touch-target);min-height:var(--touch-target);position:absolute;'
 $css=Replace-Once $css '.checkbox{display:flex;' '.checkbox{min-height:var(--touch-target);display:flex;'
 $css=Replace-Once $css '.footer button{display:flex;' '.footer button{min-height:var(--touch-target);display:flex;'
 $css=Replace-Once $css '.content-page h1{font-size:42px}' '.content-page h1{font-size:var(--text-title)}'
 $css=Replace-Once $css '.map-tools button{display:grid;place-items:center;width:39px;height:41px;' '.map-tools button{display:grid;place-items:center;width:var(--touch-target);height:var(--touch-target);'
 $css=Replace-Once $css '.map-tools button{width:32px;height:32px}' '.map-tools button{width:var(--touch-target);height:var(--touch-target)}'
 $css=Replace-Once $css '.header-end .help-link{min-width:24px}' '.header-end .help-link{min-width:var(--touch-target)}'
 $css=Replace-Once $css '.booking-panel h1{font-size:43px;' '.booking-panel h1{font-size:clamp(2rem,8.5vw,2.6875rem);'
 $css=Replace-Once $css '@media(max-width:700px){.header{height:76px;' '@media(max-width:700px){body{padding-bottom:calc(var(--mobile-navigation-height) + env(safe-area-inset-bottom,0px) + var(--space-4))}html{scroll-padding-bottom:calc(var(--mobile-navigation-height) + env(safe-area-inset-bottom,0px) + var(--space-4))}input,select,textarea{font-size:var(--text-body)}.header{height:76px;'
 $css=Replace-Once $css 'height:67px;z-index:var(--layer-navigation);' 'height:calc(var(--mobile-navigation-height) + env(safe-area-inset-bottom,0px));z-index:var(--layer-navigation);'
 $css=Replace-Once $css 'padding:5px 15px;backdrop-filter:blur(12px)' 'padding:5px 15px calc(5px + env(safe-area-inset-bottom,0px));backdrop-filter:blur(12px)'
 $css=Replace-Once $css '.dev-bar{margin:0 24px 90px}' '.dev-bar{margin:0 24px 20px}'
 $css=Replace-Once $css '@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important}}' '@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;transition:none!important;animation:none!important}.button:active:not(:disabled){transform:none}}'
 $css=$css.Replace('font-size:9px','font-size:var(--text-small)').Replace('font-size:10px','font-size:var(--text-small)')
 Write-Source $path $css
}
$path='frontend/src/main.tsx';$main=[System.IO.File]::ReadAllText((Join-Path $root $path),$utf8)
$main=Replace-Once $main 'return <><header className="header">' 'return <><a className="skip-link" href="#main-content">{tr("Skip to main content")}</a><header className="header">'
$main=Replace-Once $main '<button key={n.id} className={page===n.id?' '<button key={n.id} aria-current={page===n.id?"page":undefined} className={page===n.id?'
$main=Replace-Once $main ' <main>{error' ' <main id="main-content" tabIndex={-1}>{error'
$main=Replace-Once $main '<div className="loading">' '<div className="loading" role="status">'
Write-Source $path $main
foreach($locale in @('en','ny','_template')){
 $path="frontend/src/i18n/locales/$locale.json"
 $json=[System.IO.File]::ReadAllText((Join-Path $root $path),$utf8)
 if(!$json.Contains('"Skip to main content"')){
  $translation=switch($locale){'en'{'Skip to main content'} 'ny'{'Pitani ku nkhani yaikulu'} default{''}}
  $json=Replace-Once $json '"messages":  {' ('"messages":  {'+"`r`n                     `"Skip to main content`":  `"$translation`",")
  Write-Source $path $json
 }
}
Write-Output 'Shared design foundation applied; later style changes preserved on rerun.'
# Guarded initial JSX edits for the design pass; subsequent source changes are authoritative.
# Windows PowerShell 5.1 must read this Unicode source explicitly as UTF-8.
# From the repository root:
# & ([scriptblock]::Create([IO.File]::ReadAllText((Join-Path $PWD 'scripts/changes/012-apply-design.ps1'),[Text.Encoding]::UTF8)))
# Later changes to the same layout can intentionally make the guarded targets fail.
$ErrorActionPreference='Stop'
$root=if($PSScriptRoot){Split-Path (Split-Path $PSScriptRoot -Parent) -Parent}else{$PWD.Path}
Set-Location $root
$utf8=[System.Text.UTF8Encoding]::new($false)
function Edit([string]$path,[string]$before,[string]$after){
 $absolute=Join-Path $root $path;$source=[System.IO.File]::ReadAllText($absolute,$utf8).Replace("`r`n","`n");$before=$before.Replace("`r`n","`n");$after=$after.Replace("`r`n","`n")
 if($source.Contains($after)){return}
 if(([regex]::Matches($source,[regex]::Escape($before))).Count -ne 1){throw "Missing or ambiguous target in ${path}: $before"}
 [System.IO.File]::WriteAllText($absolute,$source.Replace($before,$after),$utf8)
}
Edit 'frontend/src/components/ui.tsx' "import {X} from 'lucide-react';" "import {X,LoaderCircle} from 'lucide-react';"
Edit 'frontend/src/components/ui.tsx' "export function Button({className,variant='primary',...props}:ButtonHTMLAttributes<HTMLButtonElement>&{variant?:'primary'|'secondary'|'ghost'}){return <button className={twMerge(clsx('button',variant,className))} {...props}/>;}" "export function Button({className,variant='primary',loading=false,disabled,children,...props}:ButtonHTMLAttributes<HTMLButtonElement>&{variant?:'primary'|'secondary'|'ghost';loading?:boolean}){return <button className={twMerge(clsx('button',variant,className))} disabled={disabled||loading} aria-busy={loading||undefined} {...props}>{loading&&<LoaderCircle size={18} className=`"button-spinner`" aria-hidden=`"true`"/>}{children}</button>;}"
$path=Join-Path $root 'frontend/src/components/ui.tsx';$ui=[System.IO.File]::ReadAllText($path,$utf8)
if(!$ui.Contains('export function PageHeader')){
 $ui+="`r`n"+'export function PageHeader({title,description}:{title:string;description?:string}){return <header className="page-heading"><h1>{title}</h1>{description&&<p>{description}</p>}</header>;}' +"`r`n"
 [System.IO.File]::WriteAllText($path,$ui,$utf8)
}
Edit 'frontend/src/Rider.tsx' "import {Button,Modal} from './components/ui';" "import {Button,Modal,PageHeader} from './components/ui';"
Edit 'frontend/src/Rider.tsx' '<div className="eyebrow">{tr("A SIMPLER WAY TO RIDE")}</div><h1>{tr("Where are")}<br/>{tr("you going")}<span className="green">?</span></h1><p className="intro">{tr("A familiar place. A new destination.")}<br/>{tr("Let’’s get you there.")}</p>' '<PageHeader title={tr("Where are you going?")} description={tr("A simpler way to ride.")}/>'
Edit 'frontend/src/Rider.tsx' '<label className="field-label" htmlFor="note">{tr("Pickup note ")}<span>{tr("Optional")}</span></label><input id="note" maxLength={500} value={note} onChange={e=>setNote(e.target.value)} placeholder={tr("e.g. I’’m beside the main gate")}/>' '<details className="pickup-note"><summary>{tr("Pickup note ")}<span className="muted">{tr("Optional")}</span></summary><label className="field-label" htmlFor="note">{tr("Pickup note ")}</label><input id="note" maxLength={500} value={note} onChange={e=>setNote(e.target.value)} placeholder={tr("e.g. I’’m beside the main gate")}/></details>'
Edit 'frontend/src/Rider.tsx' '<div className="eyebrow">{tr("YOUR JOURNEY")}</div><h1>{labels[t.state]||statusLabel(t.state)}</h1>' '<PageHeader title={labels[t.state]||statusLabel(t.state)}/>'
Edit 'frontend/src/Rider.tsx' '<section className="content-page"><div className="eyebrow">{tr("YOUR ACCOUNT")}</div><h1>{account?tr("Hello, {0}.",[account.first_name]):tr("Good to see you.")}</h1>' '<section className="content-page account-page"><PageHeader title={account?tr("Hello, {0}.",[account.first_name]):tr("Good to see you.")}/>'
Edit 'frontend/src/main.tsx' "import {Button,Modal} from './components/ui';" "import {Button,Modal,PageHeader} from './components/ui';"
Edit 'frontend/src/main.tsx' '<div className="eyebrow">{tr("YOUR JOURNEYS")}</div><h1>{tr("Trips, all in one place.")}</h1>' '<PageHeader title={tr("Trips")} description={tr("Completed rides and receipts will appear here.")}/>'
Edit 'frontend/src/main.tsx' '<div className="eyebrow">{tr("YOUR RIDE, YOUR WAY")}</div><h1>{tr("A little closer")}<br/>{tr("to being there.")}</h1>' '<PageHeader title={tr("Review your ride")} description={tr("Check pickup, destination and fare before requesting.")}/>'
Edit 'frontend/src/main.tsx' '<div className="booking-footer"><ShieldCheck size={19}/><p>{tr("A clear fare. A known driver.")}<br/><strong>{tr("A simpler way to ride.")}</strong></p></div>' '<div className="booking-footer"><ShieldCheck size={18} aria-hidden="true"/><p>{tr("A clear fare. A known driver.")}</p></div>'
Edit 'frontend/src/Consoles.tsx' "import {Button} from './components/ui';" "import {Button,PageHeader} from './components/ui';"
Edit 'frontend/src/Consoles.tsx' '<div className="loading">' '<div className="loading" role="status">'
Edit 'frontend/src/Consoles.tsx' '<div className="eyebrow">{tr("DEVELOPMENT DRIVER CONSOLE")}</div><h1>{tr("Your day, your way.")}</h1>' '<PageHeader title={tr("Driver workspace")}/>'
Edit 'frontend/src/Consoles.tsx' '<div className="driver-grid"><div>' '<div className="driver-grid"><div className="driver-sidebar">'
Edit 'frontend/src/Consoles.tsx' '</p>)}</div><div>{trip?' '</p>)}</div><div className="driver-task">{trip?'
Edit 'frontend/src/Consoles.tsx' '<details className="application"><summary>{tr("Driver verification application")}</summary>' '<details className="application" open={d.driver.status!=="approved"?true:undefined}><summary>{tr("Driver verification application")}</summary>'
Edit 'frontend/src/Admin.tsx' "import {Button} from './components/ui';" "import {Button,PageHeader} from './components/ui';"
Edit 'frontend/src/Admin.tsx' '<div className="loading">' '<div className="loading" role="status">'
Edit 'frontend/src/Admin.tsx' '<div className="eyebrow">{tr("OPERATIONS")}</div><h1>{tr("A clear view of every ride.")}</h1><p className="intro">{tr("Review applications, manage service and follow the money.")}</p>' '<PageHeader title={tr("OPERATIONS")} description={tr("Review applications, manage service and follow the money.")}/>'
Edit 'frontend/src/Admin.tsx' '<div className="admin-tabs">' '<nav className="admin-tabs" aria-label={tr("OPERATIONS")}>'
Edit 'frontend/src/Admin.tsx' '<button className={tab===t?' '<button aria-current={tab===t?"page":undefined} className={tab===t?'
Edit 'frontend/src/Admin.tsx' '{statusLabel(t)}</button>)}</div><label className="reason-field">' '{statusLabel(t)}</button>)}</nav><label className="reason-field">'
Edit 'frontend/src/Admin.tsx' '<label className="reason-field">{tr("Reason for your next action")}' '<label className="reason-field" hidden={tab==="trips"||tab==="audit"}>{tr("Reason for your next action")}'
Edit 'frontend/src/Admin.tsx' '<div className="table-scroll"><table><thead><tr><th>{tr("Driver")}</th><th>{tr("Vehicle")}</th><th>{tr("Status")}</th><th>{tr("Review")}</th></tr></thead><tbody>{d.drivers.map(v=><tr key={v.id}><td><strong>{v.first_name}</strong><small>{tr("Application #")}{v.id}</small></td><td>{v.vehicle}<small>{v.plate}</small></td><td><span className="status-pill">{statusLabel(v.status)}</span></td><td><DriverReview id={v.id} reason={reason} reload={reload}/></td></tr>)}</tbody></table></div>' '<div className="driver-review-list">{d.drivers.length===0&&<p className="empty">{tr("No driver applications yet.")}</p>}{d.drivers.map(v=><article className="driver-review-item" key={v.id}><header className="review-summary"><div><h3>{v.first_name}</h3><small>{tr("Application #")}{v.id}</small></div><p>{v.vehicle}<small>{v.plate}</small></p><span className="status-pill">{statusLabel(v.status)}</span></header><DriverReview id={v.id} reason={reason} reload={reload}/></article>)}</div>'
Edit 'frontend/src/DriverSetup.tsx' '{groups.map(group=><section key={group.title}><h3>{tr(group.title)}</h3>' '{groups.map((group,index)=><section className="onboarding-section" key={group.title}><h3><span className="section-number" aria-hidden="true">{index+1}</span>{tr(group.title)}</h3>'
Edit 'frontend/src/DriverSetup.tsx' '<p className="fine">{tr("Starter credit after approval")}: {money(data?.starter_credit_offer_minor??0)}</p>' '<p className="fine">{tr("Starter credit after approval")}: {data?money(data.starter_credit_offer_minor??0):tr("Please wait…")}</p>'
Edit 'frontend/src/DriverSetup.tsx' '<div className="document-grid">{data.document_kinds.map' '<p className="document-progress" role="status">{tr("{0} of {1} required documents uploaded",[String(data.required_documents.filter(kind=>data.documents.some(doc=>doc.kind===kind)).length),String(data.required_documents.length)])}</p><div className="document-grid">{data.document_kinds.map'
Edit 'frontend/src/DriverSetup.tsx' '<form onSubmit={e=>{e.preventDefault();perform(async()=>{const minor=' '<form className="topup-form" onSubmit={e=>{e.preventDefault();perform(async()=>{const minor='
Edit 'frontend/src/DriverReview.tsx' '{error&&<p role="alert">{translateError(error)}</p>}{data&&' '{error&&<p role="alert" className="error">{translateError(error)}</p>}{busy&&!data&&<p role="status">{tr("Please wait…")}</p>}{!data&&!busy&&error&&<Button variant="secondary" onClick={()=>perform(refresh)}>{tr("Retry")}</Button>}{data&&'
Edit 'frontend/src/DriverReview.tsx' '<dl className="review-details">' '<h3>{tr("Personal details")}</h3><dl className="review-details">'
Edit 'frontend/src/DriverReview.tsx' '{data.documents.map(doc=>' '<h3>{tr("Verification documents")}</h3><div className="document-grid">{data.documents.map(doc=>'
Edit 'frontend/src/DriverReview.tsx' '</div>)}
 <p className="fine">' '</div>)}</div>
 <p className="fine">'
Write-Output 'Page-level design changes applied.'
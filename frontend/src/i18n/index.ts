import {useSyncExternalStore} from 'react';
import {readPreference,savePreference,translate,otherLanguage,languageButtonLabel,LANGUAGE_STORAGE_KEY,availableLanguages,documentTitle,resolveLanguage} from './core.ts';
import type {Language,PreferenceStorage} from './core.ts';
function storage(): PreferenceStorage | undefined {try{return typeof window==='undefined'?undefined:window.localStorage;}catch{return undefined;}}
let language=readPreference(storage());
const listeners=new Set<()=>void>();
const subscribe=(listener:()=>void)=>{listeners.add(listener);return()=>{listeners.delete(listener);};};
export const getLanguage=()=>language;
export function tr(message: string,values: readonly (string | number | undefined)[] = []): string {return translate(language,message,values);}
function updateDocument(){if(typeof document!=='undefined'){document.documentElement.lang=language;document.title=documentTitle(language);}}
export function setLanguage(next: Language): void {language=resolveLanguage(next);savePreference(language,storage());updateDocument();listeners.forEach(listener=>listener());}
export function useLanguage(){const current=useSyncExternalStore(subscribe,getLanguage,()=> 'ny' as const);return {language:current,toggleLanguage:()=>setLanguage(otherLanguage(current)),buttonLabel:languageButtonLabel(current),targetLanguage:otherLanguage(current),availableLanguages,changeLanguage:(code:string)=>setLanguage(resolveLanguage(code))};}
if(typeof window!=='undefined')window.addEventListener('storage',event=>{if(event.key===LANGUAGE_STORAGE_KEY || event.key===null){language=readPreference(storage());updateDocument();listeners.forEach(listener=>listener());}});
updateDocument();
const labels: Record<string,string> = {
 topup:'Top up driver credit',trial_credit:'Starter credit granted',drivers:'Drivers',trips:'Trips',credit:'Credit',service:'Service',landmarks:'Landmarks',support:'Support',audit:'Audit',south:'South',north:'North',west:'West',east:'East',
 matching:'Finding a driver',assigned:'Driver assigned',driver_arriving:'Driver on the way',driver_arrived:'Driver arrived',in_progress:'Trip in progress',completed:'Completed',cancelled:'Cancelled',interrupted:'Interrupted',expired:'Expired',
 approved:'Approved',pending:'Pending',rejected:'Rejected',suspended:'Suspended',open:'Open case',resolved:'Resolved',unpaid:'Unpaid',cash_due_to_driver:'Cash due to driver',reserved:'Reserved',captured:'Captured',released:'Released',none:'None',
 rider:'Rider',driver:'Driver',admin:'Administrator',ops:'Operations staff',reviewer:'Reviewer',reserve:'Reservation',capture:'Fee capture',release:'Credit release',adjustment:'Adjustment',development_topup:'Demo top-up',settlement:'Settlement',
 help:'Help',already_in_car:'I’m already in the car',road_report:'Report a road issue',fee_dispute:'Review a fee',driver_review:'Driver & vehicle review',pricing_version:'Pricing version',service_settings:'Service configuration',landmark:'Landmark',credit_adjustment:'Credit adjustment',incident_resolution:'Incident resolution',interrupted_trip:'Interrupted trip',
 development_pending:'Demo payment pending',development_paid:'Demo payment recorded',development_failed:'Demo payment failed',development_refunded:'Demo refund recorded',cash_retained_by_driver:'Cash retained by driver',pending_provider:'Awaiting payment provider',
};
export const statusLabel=(value:string)=>tr(labels[value]||value.replaceAll('_',' '));
export function translateError(message:string):string {const translated=tr(message);return language!=='en' && translated===message ? tr('Unrecognized error') : translated;}
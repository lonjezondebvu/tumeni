export type Point={landmark_id?:number;display_name:string;latitude:number;longitude:number;meeting_point?:string};
export type Landmark={id:number;name:string;aliases:string[];latitude:number;longitude:number;verified:boolean;meeting_point:string};
export type Account={id:number;first_name:string;role:string};
export type Pricing={version:number;currency:string;fare_minor:number;fee_minor:number;driver_minor:number};
export type Quote={id:string;pickup:Point;destination:Point;pricing:Pricing;route:{distance_m:number;duration_s:number;label:string};expires_at:string};
export type Trip={id:string;state:string;pickup:Point;destination:Point;pickup_note:string;pricing:Pricing;payment_method:string;payment_status:string;fee_state:string;pickup_pin?:string;route:{distance_m:number;duration_s:number;label:string};driver?:{first_name:string;vehicle:string;plate:string};created_at:string;completed_at?:string};
export type Bootstrap={csrf_token:string;account:Account|null;landmarks:Landmark[];development:boolean;support_hours:string;service:{name:string;opens:string;closes:string;enabled:boolean}};
let csrf='';
export async function api<T>(path:string,body?:unknown,headers:Record<string,string>={}):Promise<T>{
 const res=await fetch('/api/v1/'+path,{method:body===undefined?'GET':'POST',credentials:'same-origin',headers:{Accept:'application/json',...(body instanceof FormData?{}:{'Content-Type':'application/json'}),'X-CSRF-TOKEN':csrf,...headers},body:body===undefined?undefined:body instanceof FormData?body:JSON.stringify(body)});
 const data=await res.json().catch(()=>({message:'The service could not respond. Please try again.'}));
 if(!res.ok)throw new Error(data.errors?Object.values(data.errors).flat().join(' '):data.message||'Something went wrong. Please try again.');
 if(data.csrf_token)csrf=data.csrf_token;return data;
}
export const money=(minor:number)=>'MWK '+new Intl.NumberFormat('en-MW',{maximumFractionDigits:2}).format(minor/100);
export const active=(t:Trip)=>['matching','assigned','driver_arriving','driver_arrived','in_progress'].includes(t.state);
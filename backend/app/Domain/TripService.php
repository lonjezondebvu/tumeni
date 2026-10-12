<?php
namespace App\Domain;
use Illuminate\Support\Facades\{DB,Hash,Crypt};
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;
final class TripService {
 public const ACTIVE=['matching','assigned','driver_arriving','driver_arrived','in_progress'];
 public function quote(array $pickup,array $destination): array {
  $s=DB::table('service_settings')->first(); abort_unless($s && $s->enabled,422,'Service is currently closed.');
  $time=now($s->timezone)->format('H:i');
  $open=$s->opens <= $s->closes ? ($time >= $s->opens && $time <= $s->closes) : ($time >= $s->opens || $time <= $s->closes);
  abort_unless($open,422,'Service is outside operating hours.');
  $b=json_decode($s->bounds,true);
  foreach([$pickup,$destination] as $p) abort_unless($p['latitude'] >= $b['south'] && $p['latitude'] <= $b['north'] && $p['longitude'] >= $b['west'] && $p['longitude'] <= $b['east'],422,'Location is outside the service area.');
  $route=app(RoutingProvider::class)->route($pickup,$destination); abort_unless($route,422,'A reliable route is unavailable. Choose a supported landmark or contact support.');
  $r=DB::table('pricing_rules')->orderByDesc('id')->first(); abort_unless($r,503,'Pricing unavailable.');
  $fare=$r->base_minor+intdiv($r->per_km_minor*$route['distance_m']+999,1000); $fee=intdiv($fare*$r->commission_bps,10000);
  $p=['version'=>$r->id,'currency'=>'MWK','fare_minor'=>$fare,'fee_minor'=>$fee,'driver_minor'=>$fare-$fee,'commission_bps'=>$r->commission_bps]; $id=(string)Str::uuid();
  DB::table('quotes')->insert(['id'=>$id,'pickup'=>json_encode($pickup),'destination'=>json_encode($destination),'pricing'=>json_encode($p),'distance_m'=>$route['distance_m'],'duration_s'=>$route['duration_s'],'provider'=>$route['provider'],'expires_at'=>now()->addMinutes(5),'created_at'=>now(),'updated_at'=>now()]);
  return ['id'=>$id,'pickup'=>$pickup,'destination'=>$destination,'pricing'=>$p,'route'=>$route,'expires_at'=>now()->addMinutes(5)->toIso8601String(),'payment_methods'=>['cash']];
 }
 public function request(int $rider,string $quote,string $key,string $note): object {
  return DB::transaction(function()use($rider,$quote,$key,$note){
   DB::table('accounts')->where('id',$rider)->lockForUpdate()->first(); $hash=hash('sha256',json_encode([$quote,$note,'cash']));
   $prior=DB::table('trips')->where(['rider_id'=>$rider,'request_key'=>$key])->first();
   if($prior){abort_unless(hash_equals($prior->request_hash,$hash),409,'Idempotency key was used for a different request.');return $prior;}
   abort_if(DB::table('trips')->where('rider_id',$rider)->whereIn('state',self::ACTIVE)->exists(),409,'You already have an active trip.');
   $q=DB::table('quotes')->where('id',$quote)->lockForUpdate()->first(); abort_unless($q && CarbonImmutable::parse($q->expires_at)->isFuture(),409,'Quote expired. Please get a new price.');
   abort_if(DB::table('trips')->where('quote_id',$quote)->exists(),409,'Quote already used.');
   $pin=(string)random_int(1000,9999);$id=(string)Str::uuid();
   DB::table('trips')->insert(['id'=>$id,'rider_id'=>$rider,'quote_id'=>$quote,'request_key'=>$key,'request_hash'=>$hash,'state'=>'matching','payment_method'=>'cash','payment_status'=>'unpaid','fee_state'=>'none','pickup'=>$q->pickup,'destination'=>$q->destination,'pickup_note'=>$note,'pricing'=>$q->pricing,'pin_hash'=>Hash::make($pin),'pin_encrypted'=>Crypt::encryptString($pin),'expires_at'=>now()->addMinutes(10),'created_at'=>now(),'updated_at'=>now()]);
   $this->event($id,$rider,'matching',['routing_provider'=>$q->provider]);return DB::table('trips')->find($id);
  },5);
 }
 public function accept(int $driver,string $id,int $actor): object {
  return DB::transaction(function()use($driver,$id,$actor){
   $d=DB::table('drivers')->where('id',$driver)->lockForUpdate()->first();abort_unless($d && $d->online && $d->status==='approved' && $d->vehicle_approved && $d->verified_at && app(DriverOnboarding::class)->current($d),403,'Driver is not eligible.');
   $t=DB::table('trips')->where('id',$id)->lockForUpdate()->first();abort_unless($t,404);
   if($t->driver_id===$driver && in_array($t->state,['assigned','driver_arrived','in_progress','completed']))return $t;
   abort_unless($t->state==='matching' && CarbonImmutable::parse($t->expires_at)->isFuture(),409,'Offer is no longer available.');
   abort_if(DB::table('offer_declines')->where(['driver_id'=>$driver,'trip_id'=>$id])->exists(),409,'You declined this offer.');
   abort_if(DB::table('trips')->where('driver_id',$driver)->whereIn('state',self::ACTIVE)->exists(),409,'Finish your current trip first.');
   $p=json_decode($t->pricing,true);
   if($t->payment_method==='cash'){
    abort_if($this->balance($driver)<$p['fee_minor'],422,'Insufficient driver credit.');
    DB::table('fee_reservations')->insert(['trip_id'=>$id,'driver_id'=>$driver,'amount_minor'=>$p['fee_minor'],'state'=>'reserved','created_at'=>now(),'updated_at'=>now()]);
    $this->ledger($driver,$id,"reserve:$id",'reserve',-$p['fee_minor'],'Cash trip fee reservation',$actor);
   }
   DB::table('trips')->where('id',$id)->update(['driver_id'=>$driver,'state'=>'assigned','fee_state'=>$t->payment_method==='cash'?'reserved':'settlement','updated_at'=>now()]);
   $this->event($id,$actor,'assigned',['driver_id'=>$driver]);return DB::table('trips')->find($id);
  },5);
 }
 public function transition(string $id,int $actor,string $role,string $action,?string $pin=null,?string $reason=null): object {
  $result=DB::transaction(function()use($id,$actor,$action,$pin,$reason){
   $t=DB::table('trips')->where('id',$id)->lockForUpdate()->first();abort_unless($t,404);
   $d=$t->driver_id?DB::table('drivers')->find($t->driver_id):null;$isDriver=$d && $d->account_id===$actor;$isRider=$t->rider_id===$actor;
   abort_unless($isRider || $isDriver,403,'This trip belongs to another account.');
   if($action==='cancel'){
    if($t->state==='cancelled')return $t;
    abort_unless(in_array($t->state,['matching','assigned','driver_arriving','driver_arrived']),409,'Trip has started or ended. Use help or interruption.');abort_unless($reason,422,'A reason is required.');$this->release($t,$actor);$next='cancelled';
   }else{
    abort_unless($isDriver,403);$next=['arrive'=>'driver_arrived','start'=>'in_progress','complete'=>'completed','interrupt'=>'interrupted'][$action]??null;abort_unless($next,422,'Unknown action.');if($t->state===$next)return $t;
    $allowed=['arrive'=>['assigned','driver_arriving'],'start'=>['driver_arrived'],'complete'=>['in_progress'],'interrupt'=>['in_progress']];abort_unless(in_array($t->state,$allowed[$action]),409,'This action is not allowed in the current trip state.');
    if($action==='start'){
     if($t->pin_locked_until && CarbonImmutable::parse($t->pin_locked_until)->isFuture())return ['error'=>429];
     if(!$pin || !Hash::check($pin,$t->pin_hash)){$a=$t->pin_attempts+1;DB::table('trips')->where('id',$id)->update(['pin_attempts'=>$a,'pin_locked_until'=>$a%5===0?now()->addMinutes(5):null]);return ['error'=>$a%5===0?429:422];}
    }
    if($action==='complete'){
     $p=json_decode($t->pricing,true);
     if($t->payment_method==='cash'){DB::table('fee_reservations')->where('trip_id',$id)->where('state','reserved')->update(['state'=>'captured','updated_at'=>now()]);$this->ledger($t->driver_id,$id,"capture:$id",'capture',0,'Fee earned; reservation already reduced available credit',$actor);}
     DB::table('commissions')->insert(['trip_id'=>$id,'amount_minor'=>$p['fee_minor'],'source'=>$t->payment_method==='cash'?'driver_credit':'settlement']);
     DB::table('settlements')->insert(['trip_id'=>$id,'driver_minor'=>$p['driver_minor'],'state'=>$t->payment_method==='cash'?'cash_retained_by_driver':'pending_provider']);
     DB::table('trips')->where('id',$id)->update(['payment_status'=>$t->payment_method==='cash'?'cash_due_to_driver':'pending','fee_state'=>$t->payment_method==='cash'?'captured':'settlement','completed_at'=>now()]);
    }
    if($action==='interrupt'){abort_unless($reason,422,'Tell support what happened.');$this->release($t,$actor);DB::table('incidents')->insert(['account_id'=>$actor,'trip_id'=>$id,'category'=>'interrupted_trip','description'=>$reason,'status'=>'open','review_due_at'=>now()->addHours(48),'created_at'=>now(),'updated_at'=>now()]);}
   }
   DB::table('trips')->where('id',$id)->update(['state'=>$next,'updated_at'=>now()]);$this->event($id,$actor,$next,['reason'=>$reason]);return DB::table('trips')->find($id);
  },5);
  if(is_array($result))abort($result['error'],$result['error']===429?'Too many PIN attempts. Try again in five minutes.':'Pickup PIN is incorrect.');return $result;
 }
 private function release(object $t,int $actor): void {
  if($t->fee_state!=='reserved')return;$r=DB::table('fee_reservations')->where('trip_id',$t->id)->where('state','reserved')->first();if(!$r)return;
  DB::table('drivers')->where('id',$r->driver_id)->lockForUpdate()->first();DB::table('fee_reservations')->where('id',$r->id)->update(['state'=>'released','updated_at'=>now()]);$this->ledger($r->driver_id,$t->id,"release:{$t->id}",'release',$r->amount_minor,'Unfulfilled trip fee released',$actor);DB::table('trips')->where('id',$t->id)->update(['fee_state'=>'released']);
 }
 public function balance(int $driver): int{return (int)DB::table('credit_ledger')->where('driver_id',$driver)->sum('amount_minor');}
 public function ledger(int $driver,?string $trip,string $reference,string $kind,int $amount,string $reason,?int $actor): void{DB::table('credit_ledger')->insert(['driver_id'=>$driver,'trip_id'=>$trip,'reference'=>$reference,'kind'=>$kind,'amount_minor'=>$amount,'reason'=>$reason,'actor_id'=>$actor]);}
 public function event(string $trip,?int $actor,string $event,array $details=[]): void{DB::table('trip_events')->insert(['trip_id'=>$trip,'actor_id'=>$actor,'event'=>$event,'details'=>json_encode($details)]);}
 public function expire(): void{
  foreach(DB::table('trips')->where('state','matching')->where('expires_at','<=',now())->pluck('id') as $id)DB::transaction(function()use($id){$t=DB::table('trips')->where('id',$id)->lockForUpdate()->first();if($t->state==='matching'){DB::table('trips')->where('id',$id)->update(['state'=>'expired','updated_at'=>now()]);$this->event($id,null,'expired');}},5);
 }
 public function view(object $t,int $actor,string $role): array{
  $d=$t->driver_id?DB::table('drivers')->join('accounts','accounts.id','=','drivers.account_id')->where('drivers.id',$t->driver_id)->select('drivers.id','accounts.first_name','drivers.vehicle','drivers.plate')->first():null;
  $v=['id'=>$t->id,'state'=>$t->state,'pickup'=>json_decode($t->pickup,true),'destination'=>json_decode($t->destination,true),'pickup_note'=>$t->pickup_note,'pricing'=>json_decode($t->pricing,true),'payment_method'=>$t->payment_method,'payment_status'=>$t->payment_status,'fee_state'=>$t->fee_state,'driver'=>$d,'created_at'=>$t->created_at,'completed_at'=>$t->completed_at,'expires_at'=>$t->expires_at];
  $q=DB::table('quotes')->find($t->quote_id);
  $v['route']=['distance_m'=>$q->distance_m,'duration_s'=>$q->duration_s,'provider'=>$q->provider,'label'=>$q->provider==='development_fixture'?'Demo route · not live road conditions':'Provider route estimate'];
  if($t->rider_id===$actor && in_array($t->state,self::ACTIVE))$v['pickup_pin']=Crypt::decryptString($t->pin_encrypted);return $v;
 }
}
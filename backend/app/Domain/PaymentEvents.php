<?php
namespace App\Domain;
use Illuminate\Support\Facades\DB;
final class PaymentEvents {
 public function receive(string $raw,string $signature): array {
  $e=app(PaymentProvider::class)->verify($raw,$signature);$hash=hash('sha256',$raw);
  return DB::transaction(function()use($e,$hash){
   $t=DB::table('trips')->where('id',$e['trip_id'])->lockForUpdate()->first();abort_unless($t,404);
   abort_unless($t->payment_method==='mobile_money',409,'Cash trips cannot receive digital payment events.');$p=json_decode($t->pricing,true);abort_unless($p['fare_minor']===$e['amount_minor'] && $p['currency']===$e['currency'],422,'Payment amount or currency does not match the trip.');
   $prior=DB::table('payment_events')->where('event_id',$e['event_id'])->first();if($prior){abort_unless(hash_equals($prior->payload_hash,$hash),409,'Event reference reused with different data.');return ['development'=>true,'duplicate'=>true,'applied'=>$prior->applied];}
   $latest=DB::table('payment_events')->where('trip_id',$t->id)->where('applied',true)->orderByDesc('sequence')->first();
   $apply=!$latest || $e['sequence']>$latest->sequence;
   // Paid is terminal except an explicit later refund. Older pending/failed callbacks cannot erase it.
   if($latest && in_array($latest->status,['paid','refunded']) && $e['status']!=='refunded')$apply=false;
   DB::table('payment_events')->insert(['event_id'=>$e['event_id'],'trip_id'=>$t->id,'sequence'=>$e['sequence'],'status'=>$e['status'],'amount_minor'=>$e['amount_minor'],'currency'=>$e['currency'],'payload_hash'=>$hash,'applied'=>$apply]);
   if($apply)DB::table('trips')->where('id',$t->id)->update(['payment_status'=>'development_'.$e['status'],'updated_at'=>now()]);
   return ['development'=>true,'duplicate'=>false,'applied'=>$apply,'message'=>'Simulated provider event only. No money was moved.'];
  },5);
 }
}
<?php
namespace App\Domain;
use Illuminate\Support\Facades\{DB,Http};
use Illuminate\Support\Str;
final class PayChanguTopups {
 public function ready():bool{
  $url=(string)config('paychangu.public_url');$mode=config('paychangu.mode');$production=app()->environment('production');
  $secure=filter_var($url,FILTER_VALIDATE_URL) && parse_url($url,PHP_URL_SCHEME)==='https';
  $loopback=!$production && $mode==='test' && filter_var($url,FILTER_VALIDATE_URL) && parse_url($url,PHP_URL_SCHEME)==='http' && in_array(parse_url($url,PHP_URL_HOST),['localhost','127.0.0.1']);
  return (bool)(config('paychangu.enabled') && config('paychangu.secret_key') && in_array($mode,['live','test']) && ($secure || $loopback) && (!$production || $mode==='live') && ($mode!=='live' || (config('paychangu.webhook_secret') && !str_starts_with((string)config('paychangu.secret_key'),'sec-test-'))));
 }
 private function client(){abort_unless($this->ready(),503,'Driver top-ups are not configured yet.');return Http::withToken(config('paychangu.secret_key'))->acceptJson()->connectTimeout(5)->timeout(20);}
 public function create(object $driver,object $account,int $amount,string $key):object{
  abort_unless($this->ready(),503,'Driver top-ups are not configured yet.');
  $created=false;
  $topup=DB::transaction(function()use($driver,$amount,$key,&$created){
   DB::table('drivers')->where('id',$driver->id)->lockForUpdate()->first();
   $prior=DB::table('driver_topups')->where(['driver_id'=>$driver->id,'request_key'=>$key])->first();
   if($prior){abort_unless($prior->amount_minor===$amount,409,'Top-up key was used for another amount.');return $prior;}
   $id=(string)Str::uuid();DB::table('driver_topups')->insert(['id'=>$id,'driver_id'=>$driver->id,'request_key'=>$key,'tx_ref'=>'tumeni-'.$id,'amount_minor'=>$amount,'currency'=>'MWK','mode'=>config('paychangu.mode'),'status'=>'pending','created_at'=>now(),'updated_at'=>now()]);$created=true;return DB::table('driver_topups')->find($id);
  },5);
  if(!$created)return $topup;
  $url=rtrim(config('paychangu.public_url'),'/').'/api/v1/payments/paychangu/return';
  // A timeout leaves a pending reference for verification; never blindly repeat a charge request.
  $response=$this->client()->post('https://api.paychangu.com/payment',['amount'=>number_format($amount/100,2,'.',''),'currency'=>'MWK','tx_ref'=>$topup->tx_ref,'first_name'=>$account->first_name,'callback_url'=>$url,'return_url'=>$url,'customization'=>['title'=>'Tumeni driver credit','description'=>'Driver credit top-up']]);
  abort_unless($response->successful() && $response->json('status')==='success',502,'Payment checkout could not be created. Check this top-up before trying again.');
  $checkout=$response->json('data.checkout_url');$host=is_string($checkout)?parse_url($checkout,PHP_URL_HOST):null;
  $expected=$topup->mode==='live'?'checkout.paychangu.com':'test-checkout.paychangu.com';
  abort_unless(is_string($checkout) && parse_url($checkout,PHP_URL_SCHEME)==='https' && $host===$expected,502,'Payment provider returned an invalid checkout link.');
  DB::table('driver_topups')->where('id',$topup->id)->update(['checkout_url'=>$checkout,'updated_at'=>now()]);return DB::table('driver_topups')->find($topup->id);
 }
 public static function minor(mixed $amount):?int{
  if(!is_string($amount) && !is_int($amount) && !is_float($amount))return null;
  if(!preg_match('/^(\d{1,9})(?:\.(\d{1,2}))?$/D',(string)$amount,$m))return null;
  return ((int)$m[1])*100+(int)str_pad($m[2]??'',2,'0');
 }
 public function verify(string $reference):?object{
  $topup=DB::table('driver_topups')->where('tx_ref',$reference)->first();if(!$topup)return null;if($topup->credited_at)return $topup;
  abort_unless($topup->mode===config('paychangu.mode'),409,'Payment mode changed. Contact support for reconciliation.');
  $response=$this->client()->get('https://api.paychangu.com/verify-payment/'.rawurlencode($reference));
  abort_unless($response->successful() && $response->json('status')==='success',503,'Payment verification is temporarily unavailable.');
  $data=$response->json('data');abort_unless(is_array($data),502,'Invalid payment verification response.');
  if(($data['status']??null)!=='success')return $topup; // Failed attempts may later succeed; keep them reconcilable.
  abort_unless(($data['tx_ref']??null)===$topup->tx_ref && ($data['currency']??null)==='MWK' && ($data['mode']??null)===$topup->mode && self::minor($data['amount']??null)===$topup->amount_minor && !empty($data['reference']),422,'Payment details do not match the top-up.');
  return DB::transaction(function()use($topup,$data){
   DB::table('drivers')->where('id',$topup->driver_id)->lockForUpdate()->first();$locked=DB::table('driver_topups')->where('id',$topup->id)->lockForUpdate()->first();if($locked->credited_at)return $locked;
   abort_if(DB::table('driver_topups')->where('provider_reference',(string)$data['reference'])->where('id','!=',$topup->id)->exists(),409,'Provider payment was already used.');
   app(TripService::class)->ledger($topup->driver_id,null,'paychangu:'.$topup->tx_ref,'topup',$topup->amount_minor,'PayChangu verified driver top-up',null);
   DB::table('driver_topups')->where('id',$topup->id)->update(['provider_reference'=>(string)$data['reference'],'status'=>'credited','credited_at'=>now(),'updated_at'=>now()]);return DB::table('driver_topups')->find($topup->id);
  },5);
 }
 public function webhook(string $body,?string $signature):void{
  abort_unless($this->ready() && config('paychangu.webhook_secret'),503,'Payments unavailable.');
  abort_unless($signature && hash_equals(hash_hmac('sha256',$body,config('paychangu.webhook_secret')),$signature),401,'Invalid payment signature.');
  $payload=json_decode($body,true);abort_unless(is_array($payload),400,'Invalid webhook.');$ref=$payload['tx_ref']??($payload['data']['tx_ref']??null);
  if(is_string($ref))$this->verify($ref);
 }
 public function view(object $t):array{return ['id'=>$t->id,'tx_ref'=>$t->tx_ref,'amount_minor'=>$t->amount_minor,'currency'=>$t->currency,'status'=>$t->status,'checkout_url'=>$t->credited_at?null:$t->checkout_url,'created_at'=>$t->created_at,'credited_at'=>$t->credited_at];}
}

<?php
namespace App\Domain;
use Illuminate\Support\Facades\{DB,Crypt,Storage,Validator};
use Carbon\CarbonImmutable;
final class DriverOnboarding {
 public const DOCUMENTS=['national_id_front','national_id_back','selfie','licence_front','licence_back','vehicle_registration','insurance','fitness_certificate','transport_permit','vehicle_front','vehicle_back','vehicle_interior','passport','owner_authorization'];
 public const CHECKS=['identity_matches','selfie_matches','licence_valid','vehicle_ownership','insurance_valid','fitness_valid','permit_valid','vehicle_condition'];
 private function identity(string $number):string{return hash_hmac('sha256',preg_replace('/[^A-Z0-9]/','',strtoupper($number)),config('app.key'));}
 public function profile(object $d):array{return $d->profile_encrypted?json_decode(Crypt::decryptString($d->profile_encrypted),true):[];}
 public function required(array $p):array{$k=array_values(array_diff(self::DOCUMENTS,['passport','owner_authorization']));if(!empty($p['passport_number']))$k[]='passport';if(($p['ownership']??'owned')!=='owned')$k[]='owner_authorization';return $k;}
 public function rules():array{
  $r=[];foreach(['full_name','nationality','address','district','emergency_name','national_id_number','licence_number','licence_class','make','model','colour','plate','vin'] as $k)$r[$k]='required|string|max:200';
  return $r+['date_of_birth'=>'required|date|before_or_equal:'.now()->subYears(18)->toDateString(),'email'=>'nullable|email|max:200','emergency_phone'=>['required','regex:/^\+265[0-9]{9}$/'],'gender'=>'nullable|in:female,male,other,prefer_not_to_say','national_id_expires_at'=>'required|date|after:today','licence_expires_at'=>'required|date|after:today','insurance_expires_at'=>'required|date|after:today','fitness_expires_at'=>'required|date|after:today','permit_expires_at'=>'required|date|after:today','passport_number'=>'nullable|string|max:100','passport_expires_at'=>'required_with:passport_number|nullable|date|after:today','year'=>'required|integer|between:1980,'.(now()->year+1),'seats'=>'required|integer|between:2,9','ownership'=>'required|in:owned,leased,borrowed','owner_name'=>'required_unless:ownership,owned|nullable|string|max:200','consent'=>'accepted'];
 }
 public function current(object $d):bool{
  if(!$d->profile_encrypted)return false;
  $p=$this->profile($d);foreach(['national_id_expires_at','licence_expires_at','insurance_expires_at','fitness_expires_at','permit_expires_at'] as $k){if(empty($p[$k]) || CarbonImmutable::parse($p[$k])->endOfDay()->isPast())return false;}if(!empty($p['passport_number']) && (empty($p['passport_expires_at']) || CarbonImmutable::parse($p['passport_expires_at'])->endOfDay()->isPast()))return false;return true;
 }
 public function save(object $account,array $profile):void{
  DB::transaction(function()use($account,$profile){
   DB::table('accounts')->where('id',$account->id)->lockForUpdate()->first();$d=DB::table('drivers')->where('account_id',$account->id)->lockForUpdate()->first();
   abort_if($d && in_array($d->onboarding_state,['submitted','approved','suspended']),409,'This application is locked. Contact support for changes.');
   if($d && $d->identity_hash){$identity=$this->identity($profile['national_id_number']);abort_unless(hash_equals($d->identity_hash,$identity),422,'Contact support to correct a submitted national identity.');}
   $data=['vehicle'=>$profile['make'].' '.$profile['model'].' '.$profile['colour'],'plate'=>strtoupper($profile['plate']),'profile_encrypted'=>Crypt::encryptString(json_encode($profile)),'status'=>'pending','onboarding_state'=>'draft','vehicle_approved'=>false,'online'=>false,'review_reason'=>null,'updated_at'=>now()];
   if($d){DB::table('drivers')->where('id',$d->id)->update($data);DB::table('driver_documents')->where('driver_id',$d->id)->update(['status'=>'pending','review_reason'=>null,'reviewed_by'=>null,'reviewed_at'=>null]);}
   else DB::table('drivers')->insert($data+['account_id'=>$account->id,'created_at'=>now()]);
   DB::table('accounts')->where('id',$account->id)->update(['role'=>'driver']);
  },5);
 }
 public function submit(int $id):void{
  DB::transaction(function()use($id){$d=DB::table('drivers')->where('id',$id)->lockForUpdate()->first();abort_unless($d,404);if($d->onboarding_state==='submitted')return;abort_unless(in_array($d->onboarding_state,['draft','rejected']),409,'Application cannot be submitted.');
   $p=$this->profile($d);Validator::make($p,$this->rules())->validate();$documents=DB::table('driver_documents')->where('driver_id',$id)->pluck('kind')->all();
   abort_unless(!array_diff($this->required($p),$documents),422,'Upload all required documents before submitting.');
   $identity=$this->identity($p['national_id_number']);
   abort_if(DB::table('drivers')->where('identity_hash',$identity)->where('id','!=',$id)->exists(),409,'This identity already has a driver application. Contact support.');
   DB::table('drivers')->where('id',$id)->update(['identity_hash'=>$identity,'onboarding_state'=>'submitted','status'=>'pending','submitted_at'=>now(),'online'=>false,'vehicle_approved'=>false,'updated_at'=>now()]);
  },5);
 }
 public function review(int $id,object $actor,string $status,bool $vehicle,string $reason,array $checks):void{
  DB::transaction(function()use($id,$actor,$status,$vehicle,$reason,$checks){$d=DB::table('drivers')->where('id',$id)->lockForUpdate()->first();abort_unless($d,404);
   $data=['status'=>$status,'onboarding_state'=>$status,'vehicle_approved'=>$status==='approved' && $vehicle,'online'=>false,'review_reason'=>$reason,'updated_at'=>now()];
   if($status==='approved'){
    abort_unless(in_array($d->onboarding_state,['submitted','suspended','approved']),422,'A complete submitted application is required.');
    $p=$this->profile($d);Validator::make($p,$this->rules())->validate();abort_unless($vehicle && $this->current($d),422,'Valid driver and vehicle documents are required.');
    $accepted=DB::table('driver_documents')->where('driver_id',$id)->where('status','accepted')->pluck('kind')->all();$all=DB::table('driver_documents')->where('driver_id',$id)->pluck('kind')->all();
    abort_if(array_diff($this->required($p),$accepted) || array_diff($all,$accepted),422,'Review and accept every submitted document first.');
    foreach(self::CHECKS as $check)abort_unless(($checks[$check]??false)===true,422,'Complete all driver verification checks.');
    $data['verified_at']=$d->verified_at??now();
    if(!$d->trial_granted_at){$bonus=max(0,(int)config('paychangu.driver_trial_credit_minor'));abort_if($bonus>100000000,422,'Starter credit configuration is invalid.');app(TripService::class)->ledger($id,null,'driver-trial:'.$id,'trial_credit',$bonus,'One-time verified driver starter credit',$actor->id);$data['trial_granted_at']=now();$data['trial_credit_minor']=$bonus;}
   }
   DB::table('drivers')->where('id',$id)->update($data);
   DB::table('audit_records')->insert(['actor_id'=>$actor->id,'action'=>'driver_review','resource'=>(string)$id,'reason'=>$reason,'details'=>json_encode(['status'=>$status,'vehicle_approved'=>$vehicle,'checks'=>$checks])]);
  },5);
 }
 public function view(object $d,bool $reviewer=false):array{
  $account=DB::table('accounts')->find($d->account_id);return ['account'=>['first_name'=>$account->first_name,'phone'=>$account->phone],'id'=>$d->id,'status'=>$d->status,'onboarding_state'=>$d->onboarding_state,'profile'=>$this->profile($d),'required_documents'=>$this->required($this->profile($d)),'document_kinds'=>self::DOCUMENTS,'verification_checks'=>self::CHECKS,'documents'=>DB::table('driver_documents')->where('driver_id',$d->id)->get(['id','kind','status','review_reason','reviewed_at']),'submitted_at'=>$d->submitted_at,'verified_at'=>$d->verified_at,'trial_granted_at'=>$d->trial_granted_at,'trial_credit_minor'=>$d->trial_credit_minor,'starter_credit_offer_minor'=>(int)config('paychangu.driver_trial_credit_minor'),'review_reason'=>$d->review_reason];
 }
}

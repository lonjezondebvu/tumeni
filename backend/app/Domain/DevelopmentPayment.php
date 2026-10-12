<?php
namespace App\Domain;
use Illuminate\Support\Facades\Validator;
final class DevelopmentPayment implements PaymentProvider {
 public function verify(string $raw,string $signature): array {
  abort_if(app()->environment('production') || !config('ride.development_adapters'),503,'Development payments are disabled.');
  $secret=config('ride.payment_webhook_secret');abort_unless(is_string($secret) && strlen($secret)>=32,503,'Development payment signing secret is not configured.');
  abort_unless(hash_equals(hash_hmac('sha256',$raw,$secret),$signature),403,'Invalid callback signature.');
  $data=json_decode($raw,true);abort_unless(is_array($data),422,'Invalid event payload.');
  return Validator::make($data,['event_id'=>'required|string|max:100','trip_id'=>'required|uuid','sequence'=>'required|integer|min:1','status'=>'required|in:pending,paid,failed,refunded','amount_minor'=>'required|integer|min:0','currency'=>'required|in:MWK'])->validate();
 }
}
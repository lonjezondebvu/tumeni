<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\{DB,Http,Storage,Crypt};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use App\Domain\{DriverOnboarding,TripService,PayChanguTopups};
use Database\Seeders\DemoSeeder;
class DriverSetupTest extends TestCase {
 use DatabaseMigrations;
 protected function setUp():void{parent::setUp();$this->seed(DemoSeeder::class);Storage::fake('local');Http::preventStrayRequests();config(['paychangu.enabled'=>true,'paychangu.secret_key'=>'test-placeholder','paychangu.webhook_secret'=>'test-webhook-placeholder','paychangu.mode'=>'test','paychangu.public_url'=>'http://localhost:8000','paychangu.driver_trial_credit_minor'=>450000]);}
 private function profile():array{return ['full_name'=>'Fictional Driver','date_of_birth'=>'1990-01-01','nationality'=>'Malawian','address'=>'Demo address','district'=>'Mzuzu','emergency_name'=>'Demo Contact','emergency_phone'=>'+265991234567','national_id_number'=>'FAKE-ID-123','licence_number'=>'FAKE-LICENCE','licence_class'=>'B','national_id_expires_at'=>now()->addYear()->toDateString(),'licence_expires_at'=>now()->addYear()->toDateString(),'insurance_expires_at'=>now()->addYear()->toDateString(),'fitness_expires_at'=>now()->addYear()->toDateString(),'permit_expires_at'=>now()->addYear()->toDateString(),'make'=>'Toyota','model'=>'Corolla','colour'=>'Silver','plate'=>'DEMO 1234','vin'=>'FAKEVIN123456','year'=>2020,'seats'=>5,'ownership'=>'owned','consent'=>true];}
 private function submitted():void{
  $this->withSession(['account_id'=>2])->postJson('/api/v1/driver/application',$this->profile())->assertOk();
  $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j6N8AAAAASUVORK5CYII=');
  foreach(app(DriverOnboarding::class)->required($this->profile()) as $kind)$this->post('/api/v1/driver/document',['kind'=>$kind,'document'=>UploadedFile::fake()->createWithContent('fixture.png',$png)],['Accept'=>'application/json'])->assertOk();
  $this->postJson('/api/v1/driver/application/submit')->assertOk()->assertJsonPath('onboarding_state','submitted');
 }
 private function acceptedDocuments():void{foreach(DB::table('driver_documents')->where('driver_id',1)->get() as $doc)$this->withSession(['account_id'=>1])->postJson('/api/v1/admin/drivers/1/documents/'.$doc->id.'/review',['status'=>'accepted','reason'=>'Fixture reviewed'])->assertOk();}
 private function approval():array{return ['status'=>'approved','vehicle_approved'=>true,'reason'=>'Fixture identity and vehicle reviewed','checks'=>array_fill_keys(DriverOnboarding::CHECKS,true)];}
 public function test_full_submission_is_private_locked_and_grants_starter_credit_once():void{
  $this->submitted();$this->withSession(['account_id'=>2])->postJson('/api/v1/driver/online',['online'=>true])->assertForbidden();
  $this->postJson('/api/v1/driver/application',$this->profile())->assertStatus(409);
  $d=DB::table('drivers')->find(1);$this->assertStringNotContainsString('FAKE-ID',$d->profile_encrypted);
  $doc=DB::table('driver_documents')->first();$this->assertStringNotContainsString('PNG',Storage::disk('local')->get($doc->path));
  $this->withSession(['account_id'=>4])->get('/api/v1/drivers/1/documents/'.$doc->id)->assertForbidden();
  $this->withSession(['account_id'=>1])->postJson('/api/v1/admin/drivers/1/review',$this->approval())->assertStatus(422);
  $this->acceptedDocuments();$this->postJson('/api/v1/admin/drivers/1/review',$this->approval())->assertOk();$this->postJson('/api/v1/admin/drivers/1/review',$this->approval())->assertOk();
  $this->assertSame(2450000,app(TripService::class)->balance(1));$this->assertSame(1,DB::table('credit_ledger')->where('kind','trial_credit')->count());
  $this->withSession(['account_id'=>2])->postJson('/api/v1/driver/online',['online'=>true])->assertOk();
  $this->withSession(['account_id'=>1])->postJson('/api/v1/admin/drivers/1/review',['status'=>'suspended','vehicle_approved'=>false,'reason'=>'Fixture temporary suspension'])->assertOk();
  $this->postJson('/api/v1/admin/drivers/1/review',$this->approval())->assertOk();$this->assertSame(1,DB::table('credit_ledger')->where('kind','trial_credit')->count());
 }
 public function test_missing_optional_conditional_and_expired_documents_are_enforced():void{
  $this->withSession(['account_id'=>2])->postJson('/api/v1/driver/application',$this->profile())->assertOk();$this->postJson('/api/v1/driver/application/submit')->assertStatus(422);
  $profile=$this->profile();$profile['licence_expires_at']='2000-01-01';$this->postJson('/api/v1/driver/application',$profile)->assertStatus(422);
  $profile=$this->profile();$profile['passport_number']='FAKE-PASSPORT';$this->postJson('/api/v1/driver/application',$profile)->assertStatus(422);
  $profile=$this->profile();$profile['ownership']='borrowed';$this->postJson('/api/v1/driver/application',$profile)->assertStatus(422);
  $required=app(DriverOnboarding::class)->required(['passport_number'=>'FAKE','ownership'=>'borrowed']);$this->assertContains('passport',$required);$this->assertContains('owner_authorization',$required);
 }
 public function test_identity_cannot_claim_two_driver_applications():void{
  $this->submitted();$this->withSession(['account_id'=>4])->postJson('/api/v1/driver/application',$this->profile())->assertOk();
  // Existing accepted document metadata is irrelevant to identity uniqueness; copy fixture rows to the second driver.
  foreach(DB::table('driver_documents')->where('driver_id',1)->get() as $row){$copy=(array)$row;unset($copy['id']);$copy['driver_id']=2;DB::table('driver_documents')->insert($copy);}
  $this->postJson('/api/v1/driver/application/submit')->assertStatus(409);$this->assertSame(0,DB::table('credit_ledger')->where('kind','trial_credit')->count());
 }
 private function checkout():array{
  Http::fake(['api.paychangu.com/payment'=>Http::response(['status'=>'success','data'=>['checkout_url'=>'https://test-checkout.paychangu.com/fixture']],200)]);
  $body=['amount_minor'=>450000,'request_key'=>(string)Str::uuid()];$response=$this->withSession(['account_id'=>2])->postJson('/api/v1/driver/topups',$body)->assertOk();return [$response->json(),$body];
 }
 private function verified(array $topup,array $overrides=[]):void{Http::swap((new \Illuminate\Http\Client\Factory)->preventStrayRequests());Http::fake(['api.paychangu.com/verify-payment/*'=>Http::response(['status'=>'success','data'=>array_replace(['tx_ref'=>$topup['tx_ref'],'status'=>'success','currency'=>'MWK','amount'=>'4500.00','mode'=>'test','reference'=>'provider-fixture'], $overrides)],200)]);}
 public function test_hosted_topups_are_idempotent_and_credit_only_after_verified_payment():void{
  [$topup,$body]=$this->checkout();$this->postJson('/api/v1/driver/topups',$body)->assertOk()->assertJsonPath('id',$topup['id']);Http::assertSentCount(1);$this->assertSame(2000000,app(TripService::class)->balance(1));
  $this->postJson('/api/v1/driver/topups',array_replace($body,['amount_minor'=>500000]))->assertStatus(409);
  $this->verified($topup);$this->postJson('/api/v1/driver/topups/'.$topup['id'].'/verify')->assertOk();$this->postJson('/api/v1/driver/topups/'.$topup['id'].'/verify')->assertOk();
  $this->assertSame(2450000,app(TripService::class)->balance(1));$this->assertSame(1,DB::table('credit_ledger')->where('kind','topup')->count());
  $this->withSession(['account_id'=>4])->postJson('/api/v1/driver/topups/'.$topup['id'].'/verify')->assertNotFound();
 }
 public function test_payment_reference_amount_currency_mode_and_pending_status_are_checked():void{
  [$topup]=$this->checkout();
  foreach([['tx_ref'=>'wrong'],['amount'=>'4499.99'],['amount'=>'4500.001'],['currency'=>'USD'],['mode'=>'live']] as $mismatch){$this->verified($topup,$mismatch);$this->postJson('/api/v1/driver/topups/'.$topup['id'].'/verify')->assertStatus(422);}
  $this->verified($topup,['status'=>'pending']);$this->postJson('/api/v1/driver/topups/'.$topup['id'].'/verify')->assertOk()->assertJsonPath('status','pending');$this->assertSame(2000000,app(TripService::class)->balance(1));
 }
 public function test_signed_webhook_verifies_server_side_and_duplicates_are_safe():void{
  [$topup]=$this->checkout();$this->verified($topup);$body=json_encode(['tx_ref'=>$topup['tx_ref'],'status'=>'success']);
  $this->call('POST','/api/v1/payments/paychangu/webhook',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_ACCEPT'=>'application/json','HTTP_SIGNATURE'=>'bad'],$body)->assertUnauthorized();
  $signature=hash_hmac('sha256',$body,'test-webhook-placeholder');
  for($i=0;$i<2;$i++)$this->call('POST','/api/v1/payments/paychangu/webhook',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_ACCEPT'=>'application/json','HTTP_SIGNATURE'=>$signature],$body)->assertOk();
  $this->assertSame(1,DB::table('credit_ledger')->where('kind','topup')->count());
 }
 public function test_browser_return_does_not_credit_and_production_rejects_test_keys():void{
  [$topup]=$this->checkout();$this->get('/api/v1/payments/paychangu/return?tx_ref='.$topup['tx_ref'].'&status=success')->assertRedirect('/?payment_return=1');$this->assertSame(2000000,app(TripService::class)->balance(1));
  $this->app->detectEnvironment(fn()=>'production');try{$this->assertFalse(app(PayChanguTopups::class)->ready());}finally{$this->app->detectEnvironment(fn()=>'testing');}
 }
 public function test_checkout_timeout_is_reconcilable_and_retry_does_not_recreate_charge():void{
  Http::fake(['api.paychangu.com/payment'=>Http::failedConnection()]);$body=['amount_minor'=>450000,'request_key'=>(string)Str::uuid()];$this->withSession(['account_id'=>2])->postJson('/api/v1/driver/topups',$body)->assertStatus(500);
  Http::fake();$this->postJson('/api/v1/driver/topups',$body)->assertOk()->assertJsonPath('status','pending');Http::assertNothingSent();$this->assertDatabaseCount('driver_topups',1);
 }
 public function test_verified_documents_expiring_block_new_work_and_starter_amount_is_configurable():void{
  config(['paychangu.driver_trial_credit_minor'=>123400]);$this->submitted();$this->acceptedDocuments();$this->postJson('/api/v1/admin/drivers/1/review',$this->approval())->assertOk();$this->assertSame(2123400,app(TripService::class)->balance(1));
  $this->travel(2)->years();$this->withSession(['account_id'=>2])->postJson('/api/v1/driver/online',['online'=>true])->assertForbidden();$this->travelBack();
 }
 public function test_live_readiness_requires_https_signing_secret_and_a_non_test_key():void{
  $service=app(PayChanguTopups::class);config(['paychangu.mode'=>'live','paychangu.public_url'=>'https://example.test','paychangu.secret_key'=>'sec-test-fixture']);$this->assertFalse($service->ready());
  config(['paychangu.secret_key'=>'live-fixture','paychangu.webhook_secret'=>null]);$this->assertFalse($service->ready());config(['paychangu.webhook_secret'=>'fixture-signing']);$this->assertTrue($service->ready());config(['paychangu.public_url'=>'http://localhost:8000']);$this->assertFalse($service->ready());
 }
}
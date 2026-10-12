<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\{DB,Crypt,RateLimiter};
use App\Domain\{TripService,RoutingProvider};
use Database\Seeders\DemoSeeder;
class CashTripTest extends TestCase {
 use DatabaseMigrations;
 protected function setUp():void{parent::setUp();$this->seed(DemoSeeder::class);}
 private function service():TripService{return app(TripService::class);}
 private function quote():array{return $this->service()->quote(['landmark_id'=>1,'display_name'=>'University','latitude'=>-11.421,'longitude'=>33.997],['landmark_id'=>2,'display_name'=>'Market','latitude'=>-11.465,'longitude'=>34.02]);}
 private function requested():object{return $this->service()->request(3,$this->quote()['id'],'request-test-key','Main gate');}
 private function eligible(int $driver=1):void{DB::table('drivers')->where('id',$driver)->update(['status'=>'approved','vehicle_approved'=>true,'online'=>true]);}
 private function assigned():object{$this->eligible();return $this->service()->accept(1,$this->requested()->id,2);}
 private function started():object{$t=$this->assigned();$this->service()->transition($t->id,2,'driver','arrive');return $this->service()->transition($t->id,2,'driver','start',Crypt::decryptString($t->pin_encrypted));}
 public function test_cash_flow_captures_once_and_preserves_snapshot():void{
  $t=$this->started();$this->assertSame(1950000,$this->service()->balance(1));
  DB::table('pricing_rules')->insert(['base_minor'=>999999,'per_km_minor'=>999999,'commission_bps'=>2000,'created_at'=>now(),'updated_at'=>now()]);
  $done=$this->service()->transition($t->id,2,'driver','complete');$again=$this->service()->transition($t->id,2,'driver','complete');
  $this->assertSame('completed',$again->state);$this->assertSame(500000,json_decode($done->pricing,true)['fare_minor']);$this->assertSame(1950000,$this->service()->balance(1));
  $this->assertDatabaseCount('commissions',1);$this->assertDatabaseCount('settlements',1);$this->assertSame(1,DB::table('credit_ledger')->where('kind','capture')->count());$this->assertDatabaseHas('fee_reservations',['state'=>'captured','amount_minor'=>50000]);
 }
 public function test_request_retry_is_idempotent_and_changed_payload_conflicts():void{
  $q=$this->quote();$a=$this->service()->request(3,$q['id'],'stable-request-key','gate');$b=$this->service()->request(3,$q['id'],'stable-request-key','gate');$this->assertSame($a->id,$b->id);$this->assertDatabaseCount('trips',1);
  $this->withSession(['account_id'=>3])->postJson('/api/v1/trips',['quote_id'=>$q['id'],'payment_method'=>'cash','pickup_note'=>'different'],['Idempotency-Key'=>'stable-request-key'])->assertStatus(409);
 }
 public function test_insufficient_credit_has_no_partial_assignment():void{
  $this->eligible();$this->service()->ledger(1,null,'test-debit','adjustment',-2000000,'test',1);$t=$this->requested();
  $this->withSession(['account_id'=>2])->postJson("/api/v1/driver/offers/{$t->id}/accept")->assertStatus(422);$this->assertDatabaseHas('trips',['id'=>$t->id,'state'=>'matching']);$this->assertDatabaseCount('fee_reservations',0);
 }
 public function test_cancellation_releases_once():void{
  $t=$this->assigned();$this->service()->transition($t->id,3,'rider','cancel',null,'Plans changed');$this->service()->transition($t->id,3,'rider','cancel',null,'Plans changed');$this->assertSame(2000000,$this->service()->balance(1));$this->assertDatabaseHas('fee_reservations',['state'=>'released']);$this->assertDatabaseCount('commissions',0);$this->assertSame(1,DB::table('credit_ledger')->where('kind','release')->count());
 }
 public function test_invalid_transition_and_post_start_cancellation():void{
  $t=$this->assigned();$this->withSession(['account_id'=>2])->postJson("/api/v1/trips/{$t->id}/complete")->assertStatus(409);
  $this->service()->transition($t->id,2,'driver','arrive');$this->service()->transition($t->id,2,'driver','start',Crypt::decryptString($t->pin_encrypted));$this->withSession(['account_id'=>3])->postJson("/api/v1/trips/{$t->id}/cancel",['reason'=>'test'])->assertStatus(409);
 }
 public function test_pin_attempts_persist_and_lock():void{
  $t=$this->assigned();$this->service()->transition($t->id,2,'driver','arrive');
  for($i=0;$i<4;$i++)$this->withSession(['account_id'=>2])->postJson("/api/v1/trips/{$t->id}/start",['pin'=>'0000'])->assertStatus(422);
  $this->withSession(['account_id'=>2])->postJson("/api/v1/trips/{$t->id}/start",['pin'=>'0000'])->assertStatus(429);
  $this->withSession(['account_id'=>2])->postJson("/api/v1/trips/{$t->id}/start",['pin'=>Crypt::decryptString($t->pin_encrypted)])->assertStatus(429);$this->assertDatabaseHas('trips',['id'=>$t->id,'pin_attempts'=>5,'state'=>'driver_arrived']);
 }
 public function test_resource_and_role_authorization():void{
  $t=$this->assigned();$this->withSession(['account_id'=>4])->getJson("/api/v1/trips/{$t->id}")->assertStatus(403);$this->withSession(['account_id'=>3])->getJson('/api/v1/drivers/1/document')->assertStatus(403);$this->withSession(['account_id'=>4])->getJson('/api/v1/drivers/1/document')->assertStatus(403);$this->withSession(['account_id'=>3])->getJson('/api/v1/admin')->assertStatus(403);
  $this->withSession(['account_id'=>2])->getJson("/api/v1/trips/{$t->id}")->assertOk()->assertJsonMissingPath('pickup_pin');$this->withSession(['account_id'=>3])->getJson("/api/v1/trips/{$t->id}")->assertOk()->assertJsonStructure(['pickup_pin']);
 }
 public function test_expired_offer_cannot_reserve_credit():void{
  $t=$this->requested();$this->eligible();DB::table('trips')->where('id',$t->id)->update(['expires_at'=>now()->subMinute()]);$this->withSession(['account_id'=>2])->postJson("/api/v1/driver/offers/{$t->id}/accept")->assertStatus(409);$this->service()->expire();$this->assertDatabaseHas('trips',['id'=>$t->id,'state'=>'expired']);$this->assertDatabaseCount('fee_reservations',0);
 }
 public function test_driver_cannot_accept_conflicting_trip():void{
  $this->assigned();$r=DB::table('accounts')->insertGetId(['phone'=>'+265990000099','first_name'=>'Other','role'=>'rider','created_at'=>now(),'updated_at'=>now()]);$t=$this->service()->request($r,$this->quote()['id'],'other-rider-key','');$this->withSession(['account_id'=>2])->postJson("/api/v1/driver/offers/{$t->id}/accept")->assertStatus(409);
 }
 public function test_development_adapters_are_disabled_in_production():void{
  $this->withSession(['_token'=>'production-test-token'])->withHeader('X-CSRF-TOKEN','production-test-token');$this->app->detectEnvironment(fn()=>'production');$this->postJson('/api/v1/dev/login',['role'=>'admin'])->assertStatus(503);$this->postJson('/api/v1/auth/otp',['phone'=>'+265991234567'])->assertStatus(503);
  $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);try{$this->quote();}finally{$this->app->detectEnvironment(fn()=>'testing');}
 }
 public function test_digital_branch_does_not_deduct_credit_and_public_booking_rejects_it():void{
  $q=$this->quote();$this->withSession(['account_id'=>3])->postJson('/api/v1/trips',['quote_id'=>$q['id'],'payment_method'=>'mobile_money'],['Idempotency-Key'=>'digital-test-key'])->assertStatus(422);
  $t=$this->requested();DB::table('trips')->where('id',$t->id)->update(['payment_method'=>'mobile_money']);$this->eligible();$this->service()->accept(1,$t->id,2);$this->service()->transition($t->id,2,'driver','arrive');$this->service()->transition($t->id,2,'driver','start',Crypt::decryptString($t->pin_encrypted));$this->service()->transition($t->id,2,'driver','complete');$this->assertSame(2000000,$this->service()->balance(1));$this->assertDatabaseCount('fee_reservations',0);$this->assertDatabaseHas('commissions',['source'=>'settlement']);$this->assertDatabaseHas('settlements',['state'=>'pending_provider']);
 }
 public function test_otp_verification_retry_limit_and_account_recovery():void{
  $a=$this->postJson('/api/v1/auth/otp',['phone'=>'+265991234567'])->assertOk()->json();
  $this->postJson('/api/v1/auth/verify',['challenge_id'=>$a['challenge_id'],'code'=>'000000','first_name'=>'Test'])->assertStatus(422);$this->assertDatabaseHas('otp_challenges',['id'=>$a['challenge_id'],'attempts'=>1]);
  $this->postJson('/api/v1/auth/verify',['challenge_id'=>$a['challenge_id'],'code'=>$a['development_code'],'first_name'=>'Test'])->assertOk();$this->getJson('/api/v1/bootstrap')->assertJsonPath('account.first_name','Test');
  $this->postJson('/api/v1/auth/verify',['challenge_id'=>$a['challenge_id'],'code'=>$a['development_code'],'first_name'=>'Test'])->assertStatus(422);
  for($i=0;$i<2;$i++)$this->postJson('/api/v1/auth/otp',['phone'=>'+265991234567'])->assertOk();$this->postJson('/api/v1/auth/otp',['phone'=>'+265991234567'])->assertStatus(429);
 }
 public function test_admin_review_and_adjustments_are_audited_and_retry_safe():void{
  $this->withSession(['account_id'=>2])->postJson('/api/v1/driver/online',['online'=>true])->assertStatus(403);
  $this->withSession(['account_id'=>1])->postJson('/api/v1/admin/drivers/1/review',['status'=>'approved','vehicle_approved'=>true,'reason'=>'Demo documents reviewed'])->assertOk();
  $body=['amount_minor'=>100000,'reference'=>'admin-test-credit','reason'=>'Demonstration top-up'];$this->withSession(['account_id'=>1])->postJson('/api/v1/admin/drivers/1/credit',$body)->assertOk();$this->postJson('/api/v1/admin/drivers/1/credit',$body)->assertOk();$this->assertSame(2100000,$this->service()->balance(1));$this->assertDatabaseCount('audit_records',2);
 }
 public function test_manual_coordinate_route_is_unavailable_not_straight_line():void{
  $this->postJson('/api/v1/quotes',['pickup'=>['display_name'=>'Manual','latitude'=>-11.44,'longitude'=>34.0],'destination'=>['landmark_id'=>2]])->assertStatus(422);$this->assertDatabaseCount('quotes',0);
 }
 public function test_interruption_creates_visible_timed_review_and_releases_fee():void{
  $t=$this->started();$this->service()->transition($t->id,2,'driver','interrupt',null,'Vehicle breakdown before destination');$this->assertDatabaseHas('incidents',['trip_id'=>$t->id,'status'=>'open']);$this->assertSame(2000000,$this->service()->balance(1));$this->assertDatabaseCount('commissions',0);
 }
 public function test_ledger_cannot_be_rewritten():void{
  $this->expectException(\Illuminate\Database\QueryException::class);DB::table('credit_ledger')->where('id',1)->update(['amount_minor'=>1]);
 }
 public function test_concurrent_acceptance_has_one_winner():void{
  $t=$this->requested();$this->eligible(1);$this->eligible(2);$results=$this->race([['accept',$t->id,'1','2'],['accept',$t->id,'2','4']]);sort($results);$this->assertSame(['200','409'],$results);$this->assertDatabaseCount('fee_reservations',1);$this->assertSame(1,DB::table('credit_ledger')->where('kind','reserve')->count());
 }
 public function test_concurrent_completion_captures_exactly_once():void{
  $t=$this->started();$this->assertSame(['200','200'],$this->race([['complete',$t->id,'2'],['complete',$t->id,'2']]));$this->assertDatabaseCount('commissions',1);$this->assertSame(1,DB::table('credit_ledger')->where('kind','capture')->count());
 }
 public function test_cancel_accept_race_never_leaves_reserved_credit():void{
  $t=$this->requested();$this->eligible();$results=$this->race([['accept',$t->id,'1','2'],['cancel',$t->id,'3']]);$this->assertContains($results[0],['200','409']);$this->assertSame('200',$results[1]);$this->assertDatabaseHas('trips',['id'=>$t->id,'state'=>'cancelled']);$this->assertSame(2000000,$this->service()->balance(1));$this->assertSame(0,DB::table('fee_reservations')->where('state','reserved')->count());
 }
 private function race(array $jobs):array{
  $processes=[];$gate=tempnam(sys_get_temp_dir(),'tumeni-gate-');unlink($gate);
  foreach($jobs as $args){$pipes=[];$p=proc_open(array_merge([PHP_BINARY,base_path('tests/race-worker.php'),$gate],$args),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,base_path());$this->assertIsResource($p);fclose($pipes[0]);$processes[]=[$p,$pipes];}
  usleep(300000);touch($gate);$results=[];foreach($processes as [$p,$pipes]){$out=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($p);$this->assertSame(0,$exit,$err);$results[]=$out;}unlink($gate);return $results;
 }
}
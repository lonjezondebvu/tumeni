<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use App\Domain\{TripService,PaymentEvents};
use Database\Seeders\DemoSeeder;
class PaymentEventTest extends TestCase {
 use DatabaseMigrations;
 private string $secret='development-test-signature-key-32-characters';
 protected function setUp():void{parent::setUp();$this->seed(DemoSeeder::class);config(['ride.payment_webhook_secret'=>$this->secret]);}
 private function trip():object {$s=app(TripService::class);$q=$s->quote(['landmark_id'=>1,'latitude'=>-11.421,'longitude'=>33.997],['landmark_id'=>2,'latitude'=>-11.465,'longitude'=>34.02]);$t=$s->request(3,$q['id'],'digital-event-test','');DB::table('trips')->where('id',$t->id)->update(['payment_method'=>'mobile_money']);return $t;}
 private function event(object $t,string $id,int $seq,string $status):string{return json_encode(['event_id'=>$id,'trip_id'=>$t->id,'sequence'=>$seq,'status'=>$status,'amount_minor'=>500000,'currency'=>'MWK']);}
 private function send(string $raw):array{return app(PaymentEvents::class)->receive($raw,hash_hmac('sha256',$raw,$this->secret));}
 public function test_signed_callbacks_are_duplicate_and_order_safe():void {
  $t=$this->trip();$raw=$this->event($t,'paid-event',2,'paid');$this->assertTrue($this->send($raw)['applied']);$this->assertTrue($this->send($raw)['duplicate']);$this->assertFalse($this->send($this->event($t,'older-pending',1,'pending'))['applied']);$this->assertFalse($this->send($this->event($t,'late-failed',3,'failed'))['applied']);$this->assertDatabaseHas('trips',['id'=>$t->id,'payment_status'=>'development_paid']);$this->assertSame(2000000,app(TripService::class)->balance(1));$this->assertDatabaseCount('commissions',0);$this->assertDatabaseCount('payment_events',3);
 }
 public function test_invalid_signature_has_no_side_effects():void {$t=$this->trip();try{app(PaymentEvents::class)->receive($this->event($t,'event',1,'paid'),'invalid');$this->fail('Signature accepted');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}$this->assertDatabaseCount('payment_events',0);}
 public function test_duplicate_reference_with_changed_payload_conflicts():void {$t=$this->trip();$this->send($this->event($t,'same-event',1,'pending'));$this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);$this->send($this->event($t,'same-event',2,'paid'));}
 public function test_adapter_rejects_production_even_when_enabled():void {$t=$this->trip();$this->app->detectEnvironment(fn()=>'production');$this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);try{$this->send($this->event($t,'event',1,'paid'));}finally{$this->app->detectEnvironment(fn()=>'testing');}}
}
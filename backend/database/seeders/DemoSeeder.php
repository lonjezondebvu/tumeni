<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB,Storage};
use App\Domain\TripService;
class DemoSeeder extends Seeder {
 public function run(): void {
  if(app()->environment('production') || !config('ride.development_adapters'))throw new \RuntimeException('Demo seeding is disabled.');
  if(DB::table('accounts')->exists())return;
  DB::transaction(function(){
   foreach([['+265990000001','Operations','admin'],['+265990000002','Peter','driver'],['+265990000003','Chikondi','rider'],['+265990000004','Grace','driver']] as $a)DB::table('accounts')->insert(['phone'=>$a[0],'first_name'=>$a[1],'role'=>$a[2],'created_at'=>now(),'updated_at'=>now()]);
   DB::table('service_settings')->insert(['id'=>1,'name'=>'Mzuzu · demo service area','bounds'=>json_encode(['south'=>-11.6,'north'=>-11.3,'west'=>33.8,'east'=>34.2]),'created_at'=>now(),'updated_at'=>now()]);
   DB::table('pricing_rules')->insert(['base_minor'=>200000,'per_km_minor'=>50000,'commission_bps'=>1000,'created_at'=>now(),'updated_at'=>now()]);
   foreach([['Mzuzu University',-11.421,33.997,'Main gate'],['Mzuzu Market',-11.465,34.02,'Market entrance'],['Moyale Barracks',-11.448,34.006,'Public meeting point']] as $i=>$l)DB::table('landmarks')->insert(['id'=>$i+1,'name'=>$l[0],'aliases'=>json_encode($i===0?['Mzuni','University']:[]),'latitude'=>$l[1],'longitude'=>$l[2],'verified'=>true,'meeting_point'=>$l[3],'created_at'=>now(),'updated_at'=>now()]);
   Storage::disk('local')->put('driver-documents/demo-review.txt','DEVELOPMENT FIXTURE. No real identity or vehicle documents. Human review must be simulated for this demo only.');
   foreach([2,4] as $account){$id=DB::table('drivers')->insertGetId(['account_id'=>$account,'status'=>'pending','vehicle'=>'Silver Toyota Corolla','plate'=>$account===2?'DEMO 1234':'DEMO 5678','document_path'=>'driver-documents/demo-review.txt','created_at'=>now(),'updated_at'=>now()]);app(TripService::class)->ledger($id,null,"demo-topup:$id",'development_topup',2000000,'Demo credit, not platform revenue',1);}
  });
 }
}
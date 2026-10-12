<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service=app(\App\Domain\PayChanguTopups::class);
if(config('paychangu.mode')!=='test' || app()->environment('production')){fwrite(STDERR,"Sandbox smoke test refuses non-test mode.\n");exit(1);}
$driver=\Illuminate\Support\Facades\DB::table('drivers')->first();
$account=\Illuminate\Support\Facades\DB::table('accounts')->find($driver->account_id);
try{
 $topup=$service->create($driver,$account,10000,'6b95d3d4-17ec-4e99-946c-a5f1f53b687a');
 echo json_encode(['mode'=>config('paychangu.mode'),'status'=>$topup->status,'checkout_host'=>$topup->checkout_url?parse_url($topup->checkout_url,PHP_URL_HOST):null,'credited'=>$topup->credited_at!==null])."\n";
 if(!$topup->checkout_url)exit(2);
}catch(\Throwable $e){fwrite(STDERR,'Sandbox checkout failed: '.get_class($e).' ('.(method_exists($e,'getStatusCode')?$e->getStatusCode():$e->getCode()).")\n");exit(1);}
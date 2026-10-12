<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('paychangu:reconcile',function(){
 $payments=app(\App\Domain\PayChanguTopups::class);if(!$payments->ready()){$this->error('PayChangu is not configured.');return 1;}
 $failed=0;\Illuminate\Support\Facades\DB::table('driver_topups')->whereNull('credited_at')->orderBy('id')->chunkById(100,function($rows)use($payments,&$failed){foreach($rows as $row){try{$payments->verify($row->tx_ref);}catch(\Throwable){$failed++;}}});
 $this->info('Reconciliation finished. Verification errors: '.$failed);return $failed?1:0;
});
\Illuminate\Support\Facades\Schedule::command('paychangu:reconcile')->everyTenMinutes()->withoutOverlapping();
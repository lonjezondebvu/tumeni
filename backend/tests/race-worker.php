<?php
putenv('APP_ENV=testing');
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default'=>'pgsql','database.connections.pgsql.host'=>'127.0.0.1','database.connections.pgsql.port'=>55432,'database.connections.pgsql.database'=>'tumeni_test','database.connections.pgsql.username'=>'tumeni','database.connections.pgsql.password'=>'','ride.development_adapters'=>true]);
$deadline=microtime(true)+10;while(!file_exists($argv[1])){if(microtime(true)>$deadline)exit(2);usleep(10000);}
try{$s=app(App\Domain\TripService::class);if($argv[2]==='accept')$s->accept((int)$argv[4],$argv[3],(int)$argv[5]);else $s->transition($argv[3],(int)$argv[4],$argv[2]==='cancel'?'rider':'driver',$argv[2],null,'Concurrent test cancellation');echo '200';}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){echo $e->getStatusCode();}catch(Throwable $e){fwrite(STDERR,$e->getMessage());exit(1);}
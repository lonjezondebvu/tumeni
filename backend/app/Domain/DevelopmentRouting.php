<?php
namespace App\Domain;
final class DevelopmentRouting implements RoutingProvider {
 public function route(array $pickup,array $destination): ?array {
  if(app()->environment('production') || !config('ride.development_adapters')) abort(503,'Routing is unavailable.');
  $ids=[$pickup['landmark_id']??null,$destination['landmark_id']??null]; sort($ids);
  $fixtures=['1:2'=>[6000,720],'1:3'=>[4000,540],'2:3'=>[3000,420]];
  $f=$fixtures[implode(':',$ids)]??null;
  return $f ? ['distance_m'=>$f[0],'duration_s'=>$f[1],'provider'=>'development_fixture','label'=>'Demo route · not live road conditions'] : null;
 }
}
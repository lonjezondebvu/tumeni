<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class PrivacyHeaders {
 public function handle(Request $request,Closure $next) {
  $response=$next($request);
  $response->headers->set('X-Content-Type-Options','nosniff');
  $response->headers->set('Referrer-Policy','same-origin');
  $response->headers->set('X-Frame-Options','DENY');
  if($request->is('api/*'))$response->headers->set('Cache-Control','private, no-store');
  return $response;
 }
}
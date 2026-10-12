<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RideController as C;
use App\Http\Controllers\DriverSetupController as S;
Route::prefix('api/v1')->group(function(){
 Route::get('bootstrap',[C::class,'bootstrap']);Route::post('dev/payment-events',[C::class,'developmentPayment']);
 Route::post('auth/otp',[C::class,'otp']);Route::post('auth/verify',[C::class,'verify']);Route::post('auth/logout',[C::class,'logout']);Route::post('dev/login',[C::class,'demoLogin']);
 Route::post('quotes',[C::class,'quote']);Route::get('trips',[C::class,'trips']);Route::post('trips',[C::class,'requestTrip']);Route::get('trips/{id}',[C::class,'trip']);Route::post('trips/{id}/{action}',[C::class,'action'])->whereIn('action',['arrive','start','complete','cancel','interrupt']);
 Route::get('driver',[C::class,'driverHome']);Route::post('driver/online',[C::class,'online']);Route::post('driver/offers/{id}/accept',[C::class,'accept']);Route::post('driver/offers/{id}/decline',[C::class,'decline']);Route::post('driver/application',[S::class,'save']);Route::post('driver/document',[S::class,'upload']);Route::post('incidents',[C::class,'incident']);
 Route::get('admin',[C::class,'admin']);Route::post('admin/drivers/{id}/review',[S::class,'review']);Route::get('drivers/{id}/document',[C::class,'document']);Route::post('admin/pricing',[C::class,'pricing']);Route::post('admin/settings',[C::class,'settings']);Route::post('admin/landmarks',[C::class,'landmark']);Route::post('admin/drivers/{id}/credit',[C::class,'adjustment']);Route::post('admin/incidents/{id}/resolve',[C::class,'resolve']);
 Route::get('driver/application',[S::class,'show']);Route::post('driver/application/submit',[S::class,'submit']);
 Route::get('admin/drivers/{id}/application',[S::class,'inspect']);Route::get('drivers/{id}/documents/{document}',[S::class,'document']);Route::post('admin/drivers/{id}/documents/{document}/review',[S::class,'documentReview']);
 Route::get('driver/topups',[S::class,'topups']);Route::post('driver/topups',[S::class,'createTopup']);Route::post('driver/topups/{id}/verify',[S::class,'verifyTopup']);
 Route::post('payments/paychangu/webhook',[S::class,'webhook']);Route::match(['get','post'],'payments/paychangu/return',[S::class,'returned']);
});
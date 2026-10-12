<?php
use Illuminate\Support\Facades\Route;
require __DIR__.'/ride.php';
Route::get('/{path?}',fn()=>response()->file(public_path('app/index.html')))->where('path','^(?!api/).*$');
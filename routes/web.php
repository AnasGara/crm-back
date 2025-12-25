<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailProviderController;
use App\Http\Controllers\GoogleMailController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// routes/web.php

Route::get('/migrate', function () {
    \Artisan::call('migrate:fresh', [
        '--force' => true
    ]);
    return 'Migrations done!';
});

Route::middleware('web')->group(function () {
    Route::get('/email-provider/{provider}/redirect', [EmailProviderController::class, 'redirect']);
    Route::get('/email-provider/{provider}/callback', [EmailProviderController::class, 'callback']);
});


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/google/connect', [GoogleMailController::class, 'connect']);
    Route::get('/google/callback', [GoogleMailController::class, 'callback']);
    
    Route::post('/google/send', [GoogleMailController::class, 'sendEmail']);
});

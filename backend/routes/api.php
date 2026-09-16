<?php

use App\Http\Controllers\Api\Payment\PaystackWebhookController;
use App\Http\Controllers\Api\Verification\DiditWebhookController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*|--------------------------------------------------------------------------
| API Version 1
|--------------------------------------------------------------------------
*/

Broadcast::routes([
    'middleware' => ['auth:api', 'check_user_status'],
]);

// Webhooks — server-to-server, verified by signature rather than auth:api,
// so these live outside the v1 group and its default middleware entirely.
Route::prefix('webhooks')->as('webhooks.')->group(function () {
    Route::post('didit', [DiditWebhookController::class, 'handle'])->name('didit');
    Route::post('paystack', [PaystackWebhookController::class, 'handle'])->name('paystack');
});

Route::prefix('v1')->as('api.v1.')->group(function () {
    require __DIR__.'/api/api_v1.php';
});

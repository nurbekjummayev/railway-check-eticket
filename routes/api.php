<?php

use App\Http\Controllers\WebAppController;
use Illuminate\Support\Facades\Route;
use SergiX44\Nutgram\Nutgram;

Route::any('/webhook', fn (Nutgram $bot) => $bot->run());

Route::prefix('webapp')->group(function () {
    Route::post('/login', [WebAppController::class, 'login'])->name('webapp.login');
});

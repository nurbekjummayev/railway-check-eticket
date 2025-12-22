<?php

use App\Http\Controllers\WebAppController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('webapp')->name('webapp.')->group(function () {
    Route::get('/', [WebAppController::class, 'index'])->name('index');
    Route::delete('/searches/{id}', [WebAppController::class, 'deleteSearch'])->name('searches.delete');
});

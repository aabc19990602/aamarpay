<?php

use Illuminate\Support\Facades\Route;

/**
 * 'portal' middleware and 'portal/aamarpay' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::portal('aamarpay', function () {
    Route::get('invoices/{invoice}', 'Payment@show')->name('invoices.show');
    // Route::get('tt/{invoice}', 'Payment@test');
    // Route::post('invoices/{invoice}/complete', 'Payment@test')->name('invoices.complete')->middleware(['middleware' => 'guest']);
    Route::get('tt/{invoice}', 'Payment@test');
});

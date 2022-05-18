<?php

use Illuminate\Support\Facades\Route;

/**
 * 'portal' middleware and 'portal/aamarpay' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */


Route::portal('aamarpay', function () {
    Route::get('invoices/{invoice}/complete', 'Payment@confirm')->name('invoices.return');
    Route::post('invoices/{invoice}/complete', 'Payment@complete')->name('invoices.complete');
}, ['middleware' => 'guest']);

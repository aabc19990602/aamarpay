<?php

use Illuminate\Support\Facades\Route;

/**
 * 'portal' middleware and 'portal/aamarpay' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */


Route::portal('aamarpay', function () {
    Route::post('invoices/{invoice}/complete', 'Payment@complete')->name('invoices.complete');
    Route::post('invoices/{invoice}/return', 'Payment@return')->name('invoices.return');
}, ['middleware' => ['web','auth.disabled',\Modules\Aamarpay\Http\Middleware\CompanyIdentifiy::class, 'bindings']]);

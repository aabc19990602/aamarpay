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
}, ['middleware' => ['cookies.encrypt',
'cookies.response',
'session.start',
'session.errors',
'install.redirect',
'header.x',
'language',
'firewall.all',
'auth.disabled',
\Modules\Aamarpay\Http\Middleware\CompanyIdentifiy::class, 
'bindings']]);

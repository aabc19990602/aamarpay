<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'aamarpay' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('aamarpay', function () {
    Route::get('/', 'Main@index');
});

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-migrations-xk92p', function () {
    Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
    return response('<pre>' . Artisan::output() . '</pre>');
});
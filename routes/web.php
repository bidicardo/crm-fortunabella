<?php

use App\Http\Controllers\LogoutController;
use App\Livewire\Home;
use App\Livewire\Login;
use Illuminate\Support\Facades\Route;

Route::livewire('/login', Login::class)->middleware('guest')->name('login');

Route::middleware('auth')->group(function () {
    Route::livewire('/', Home::class);
    Route::post('/logout', LogoutController::class)->name('logout');
});

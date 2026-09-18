<?php

use App\Http\Controllers\LogoutController;
use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Livewire\AcceptInvite;
use App\Livewire\Home;
use App\Livewire\Login;
use App\Livewire\Users;
use Illuminate\Support\Facades\Route;

Route::livewire('/login', Login::class)->middleware('guest')->name('login');

// Лимит на страницу приглашения — чтобы токены нельзя было перебирать.
Route::livewire('/invite/{token}', AcceptInvite::class)->middleware(['guest', 'throttle:10,1'])->name('invite.accept');

Route::middleware(['auth', EnsureUserIsNotBlocked::class])->group(function () {
    Route::livewire('/', Home::class)->name('home');
    Route::livewire('/users', Users::class)->middleware('can:manage-users')->name('users');
    Route::post('/logout', LogoutController::class)->name('logout');
});

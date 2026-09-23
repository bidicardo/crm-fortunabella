<?php

use App\Http\Controllers\LogoutController;
use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Livewire\AcceptInvite;
use App\Livewire\ClientForm;
use App\Livewire\ClientMerge;
use App\Livewire\ClientShow;
use App\Livewire\ClientTable;
use App\Livewire\Home;
use App\Livewire\Login;
use App\Livewire\Users;
use Illuminate\Support\Facades\Route;

Route::livewire('/login', Login::class)->middleware('guest')->name('login');

// Только локальная разработка (проверка в тесте, не по регистрации маршрута,
// иначе смену окружения нельзя было бы проверить без перезапуска приложения).
Route::get('/design-showcase', function () {
    abort_unless(config('app.env') === 'local', 404);

    return view('design-showcase');
})->name('design-showcase');

// Лимит на страницу приглашения — чтобы токены нельзя было перебирать.
Route::livewire('/invite/{token}', AcceptInvite::class)->middleware(['guest', 'throttle:10,1'])->name('invite.accept');

Route::middleware(['auth', EnsureUserIsNotBlocked::class])->group(function () {
    Route::livewire('/', Home::class)->name('home');
    // create объявлен раньше {client}, чтобы «create» не считался id.
    Route::livewire('/clients', ClientTable::class)->name('clients.index');
    Route::livewire('/clients/create', ClientForm::class)->name('clients.create');
    Route::livewire('/clients/{client}', ClientShow::class)->name('clients.show');
    Route::livewire('/clients/{client}/edit', ClientForm::class)->name('clients.edit');
    Route::livewire('/clients/{client}/merge', ClientMerge::class)->name('clients.merge');
    Route::livewire('/users', Users::class)->middleware('can:manage-users')->name('users');
    Route::post('/logout', LogoutController::class)->name('logout');
});

<?php

use App\Http\Controllers\Auth\CreatePasswordController;
use App\Http\Controllers\Auth\GoogleController;
use App\Livewire\Budgets;
use App\Livewire\Dashboard;
use App\Livewire\Goals;
use App\Livewire\Transactions;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('definir-senha', [CreatePasswordController::class, 'create'])->name('password.create');
    Route::post('definir-senha', [CreatePasswordController::class, 'store'])->name('password.store');
});

Route::middleware(['auth', 'verified', 'password.set'])->group(function () {
    Route::livewire('dashboard', Dashboard\Index::class)->name('dashboard');
    Route::livewire('transacoes', Transactions\Index::class)->name('transactions.index');
    Route::livewire('orcamento', Budgets\Index::class)->name('budgets.index');
    Route::livewire('metas', Goals\Index::class)->name('goals.index');
});

require __DIR__.'/settings.php';

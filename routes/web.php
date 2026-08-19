<?php

use App\Livewire\Budgets;
use App\Livewire\Dashboard;
use App\Livewire\Transactions;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', Dashboard\Index::class)->name('dashboard');
    Route::livewire('transacoes', Transactions\Index::class)->name('transactions.index');
    Route::livewire('orcamento', Budgets\Index::class)->name('budgets.index');
});

require __DIR__.'/settings.php';

<?php

use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [WorkspaceController::class, 'index'])->name('dashboard');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->middleware('throttle:10,1')->name('workspaces.store');
    Route::prefix('workspaces/{tenant}')->middleware('workspace')->group(function () {
        Route::get('/', [WorkspaceController::class, 'show'])->name('workspaces.show');
        Route::get('leads', [LeadController::class, 'index'])->middleware('workspace.permission:leads.view')->name('leads.index');
        Route::post('leads', [LeadController::class, 'store'])->middleware('workspace.permission:leads.create')->name('leads.store');
        Route::patch('leads/{lead}', [LeadController::class, 'update'])->middleware('workspace.permission:leads.update')->name('leads.update');
        Route::get('configuration', [ConfigurationController::class, 'index'])->middleware('workspace.permission:configuration.publish')->name('configuration.index');
        Route::post('configuration', [ConfigurationController::class, 'store'])->middleware('workspace.permission:configuration.publish')->name('configuration.store');
    });
});

require __DIR__.'/settings.php';

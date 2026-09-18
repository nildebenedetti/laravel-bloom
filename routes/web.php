<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EmotionController;
use App\Http\Controllers\Admin\RecordController;
use App\Http\Controllers\Admin\TierController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\IsAdmin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// profile area for athenticated users
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth', 'verified', IsAdmin::class)
        ->prefix('admin')        
        ->group( function () {

    Route::resource('/users', UserController::class);
    Route::resource('/records', RecordController::class);
    Route::resource('/categories', CategoryController::class);
    Route::resource('/tiers', TierController::class);
    Route::resource('/emotions', EmotionController::class);

});



require __DIR__.'/auth.php';

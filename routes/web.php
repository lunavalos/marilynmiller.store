<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

// Workaround for PHP built-in server 403 error on paths with spaces
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        abort(404);
    }
    return response()->file($fullPath);
})->where('path', '.*');

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user?->hasAnyRole(['super_admin', 'admin'])) {
        return redirect('/admin');
    }

    if ($user?->hasRole('subscribed')) {
        return redirect('/member');
    }

    return Inertia\Inertia::render('Dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

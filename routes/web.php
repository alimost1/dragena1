<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Add webhook route for n8n callbacks (outside auth middleware)
Route::post('/webhook/n8n/callback', [VideoController::class, 'handleN8nCallback'])->name('n8n.callback');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Video routes
    Route::resource('videos', VideoController::class);
    Route::get('/videos/latest', [VideoController::class, 'latest'])->name('videos.latest');
});

Route::get('/baserow-video', [VideoController::class, 'showBaserowVideo'])->name('baserow.video');

require __DIR__.'/auth.php';
<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('projects.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/participants', [ProjectController::class, 'addParticipant'])->name('projects.participants.add');
    Route::delete('/projects/{project}/participants/{user}', [ProjectController::class, 'removeParticipant'])->name('projects.participants.remove');
    Route::patch('/projects/{project}/participants/{user}/hours', [ProjectController::class, 'updateParticipantHours'])->name('projects.participants.hours');
});

require __DIR__.'/auth.php';

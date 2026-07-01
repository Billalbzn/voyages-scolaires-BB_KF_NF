<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VoyageController;
use App\Http\Controllers\ParticipantController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CRUD Voyages (Bloc C)
    Route::resource('voyages', VoyageController::class);

    // Participants (Bloc D) : inscription + désinscription
    Route::resource('voyages.participants', ParticipantController::class)
        ->only(['store', 'destroy']);

    // Autorisation parentale (parent/admin)
    Route::patch('participants/{participant}/autoriser', [ParticipantController::class, 'autoriser'])
        ->name('participants.autoriser');

    // Formalités / documents administratifs du voyage
    Route::post('voyages/{voyage}/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
});

require __DIR__.'/auth.php';

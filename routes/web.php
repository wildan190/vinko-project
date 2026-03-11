<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductExportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;

// authentication pages
Route::get('/signin', [AuthController::class, 'showSignin'])->name('signin');
Route::get('/login', [AuthController::class, 'showSignin'])->name('login'); // Add login route alias
Route::post('/signin', [AuthController::class, 'signin'])->name('signin.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    // redirect root to product export
    Route::get('/', function () {
        return redirect()->route('product-export.index');
    })->name('dashboard');

    // product export pages
    Route::prefix('product-export')->name('product-export.')->group(function () {
        Route::get('/', [ProductExportController::class, 'index'])->name('index');
        Route::post('/import', [ProductExportController::class, 'import'])->name('import');
        Route::get('/export', [ProductExportController::class, 'export'])->name('export');
        Route::get('/export-template', [ProductExportController::class, 'exportTemplate'])->name('export-template');
        Route::post('/upload/{product}', [ProductExportController::class, 'uploadImage'])->name('upload');
        Route::post('/bulk-upload', [ProductExportController::class, 'bulkUpload'])->name('bulk-upload');
        Route::post('/process', [ProductExportController::class, 'processMerge'])->name('process');
        Route::get('/batch-status/{batchId}', [ProductExportController::class, 'getBatchStatus'])->name('batch-status');
        Route::post('/merge-single/{product}', [ProductExportController::class, 'mergeSingle'])->name('merge-single');
        Route::delete('/destroy/{product}', [ProductExportController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [ProductExportController::class, 'bulkDelete'])->name('bulk-delete');
    });

    // profile pages
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::get('/signup', function () {
    return view('pages.auth.signup', ['title' => 'Sign Up']);
})->name('signup');

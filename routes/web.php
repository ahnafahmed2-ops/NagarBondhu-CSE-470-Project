<?php

use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// F4 – public tracking by code (no login needed)
Route::get('/track', [TrackingController::class, 'index'])->name('track');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // F1 – index, create, store, show, edit, update, destroy
    Route::resource('reports', ReportController::class);

    // F5 – community page and votes
    Route::get('/community', [CommunityController::class, 'index'])->name('community.index');
    Route::get('/community/{report}', [CommunityController::class, 'show'])->name('community.show');
    Route::post('/reports/{report}/votes', [VoteController::class, 'store'])->name('reports.votes.store');
    Route::delete('/reports/{report}/votes/{type}', [VoteController::class, 'destroy'])->name('reports.votes.destroy');

    // F4 + F7 – admin review (role checked in the controller)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
        Route::post('/reports/{report}/status', [AdminReportController::class, 'updateStatus'])->name('reports.status');
        Route::post('/reports/{report}/priority', [AdminReportController::class, 'updatePriority'])->name('reports.priority');
        Route::delete('/reports/{report}/priority', [AdminReportController::class, 'resetPriority'])->name('reports.priority.reset');
    });
});

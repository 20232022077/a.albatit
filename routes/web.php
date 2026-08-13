<?php

use App\Http\Controllers\Admin\BiographyController;
use App\Http\Controllers\Admin\BookController as AdminBookController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\QuranCentralityController as AdminQuranCentralityController;
use App\Http\Controllers\Admin\QuraniyatController as AdminQuraniyatController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StaticAdminPageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\QuranCentralityController;
use App\Http\Controllers\QuraniyatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return app(HomeController::class)->index();
})->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('search');
Route::get('/quran-centrality', [QuranCentralityController::class, 'index'])->name('quran-centrality.index');
Route::get('/quran-centrality/{item:slug}', [QuranCentralityController::class, 'show'])->name('quran-centrality.show');
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{item:slug}', [BookController::class, 'show'])->name('books.show');
Route::get('/quraniyat', [QuraniyatController::class, 'index'])->name('quraniyat.index');
Route::get('/quraniyat/{item:slug}', [QuraniyatController::class, 'show'])->name('quraniyat.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::prefix('admin')->as('admin.')->group(function () {
        Route::get('biography', [BiographyController::class, 'edit'])->name('biography.edit');
        Route::put('biography', [BiographyController::class, 'update'])->name('biography.update');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('roles', RoleController::class)->except('show');
        Route::prefix('quran-centrality')->as('quran-centrality.')->group(function () {
            Route::get('/', [AdminQuranCentralityController::class, 'index'])->name('index');
            Route::get('/create', [AdminQuranCentralityController::class, 'create'])->name('create');
            Route::post('/', [AdminQuranCentralityController::class, 'store'])->name('store');
            Route::get('/{item}/edit', [AdminQuranCentralityController::class, 'edit'])->name('edit');
            Route::put('/{item}', [AdminQuranCentralityController::class, 'update'])->name('update');
            Route::delete('/{item}', [AdminQuranCentralityController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [AdminQuranCentralityController::class, 'restore'])->name('restore');
            Route::post('/{item}/publish', [AdminQuranCentralityController::class, 'publish'])->name('publish');
            Route::post('/{item}/unpublish', [AdminQuranCentralityController::class, 'unpublish'])->name('unpublish');
        });
        Route::prefix('books')->as('books.')->group(function () {
            Route::get('/', [AdminBookController::class, 'index'])->name('index');
            Route::get('/create', [AdminBookController::class, 'create'])->name('create');
            Route::post('/', [AdminBookController::class, 'store'])->name('store');
            Route::get('/{item}/edit', [AdminBookController::class, 'edit'])->name('edit');
            Route::put('/{item}', [AdminBookController::class, 'update'])->name('update');
            Route::delete('/{item}', [AdminBookController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [AdminBookController::class, 'restore'])->name('restore');
            Route::post('/{item}/publish', [AdminBookController::class, 'publish'])->name('publish');
            Route::post('/{item}/unpublish', [AdminBookController::class, 'unpublish'])->name('unpublish');
        });
        Route::prefix('quraniyat')->as('quraniyat.')->group(function () {
            Route::get('/', [AdminQuraniyatController::class, 'index'])->name('index');
            Route::get('/create', [AdminQuraniyatController::class, 'create'])->name('create');
            Route::post('/', [AdminQuraniyatController::class, 'store'])->name('store');
            Route::get('/{item}/edit', [AdminQuraniyatController::class, 'edit'])->name('edit');
            Route::put('/{item}', [AdminQuraniyatController::class, 'update'])->name('update');
            Route::delete('/{item}', [AdminQuraniyatController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [AdminQuraniyatController::class, 'restore'])->name('restore');
            Route::post('/{item}/publish', [AdminQuraniyatController::class, 'publish'])->name('publish');
            Route::post('/{item}/unpublish', [AdminQuraniyatController::class, 'unpublish'])->name('unpublish');
        });
        Route::get('{section}', StaticAdminPageController::class)->whereIn('section', ['content', 'media', 'categories', 'tags', 'settings', 'activity-logs', 'backups'])->name('section');
    });
});

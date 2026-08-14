<?php

use App\Http\Controllers\Admin\BiographyController;
use App\Http\Controllers\Admin\BookController as AdminBookController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LectureController as AdminLectureController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Admin\ProgramEpisodeController;
use App\Http\Controllers\Admin\QuranCentralityController as AdminQuranCentralityController;
use App\Http\Controllers\Admin\QuraniyatController as AdminQuraniyatController;
use App\Http\Controllers\Admin\ReflectionController as AdminReflectionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaticAdminPageController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WallPostController as AdminWallPostController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BiographyController as PublicBiographyController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LectureController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\QuranCentralityController;
use App\Http\Controllers\QuraniyatController;
use App\Http\Controllers\ReflectionController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WallController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return app(HomeController::class)->index();
})->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('search');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');
Route::get('/biography', [PublicBiographyController::class, 'show'])->name('biography.show');
Route::get('/quran-centrality', [QuranCentralityController::class, 'index'])->name('quran-centrality.index');
Route::get('/quran-centrality/{slug}', [QuranCentralityController::class, 'show'])->name('quran-centrality.show');
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{slug}', [BookController::class, 'show'])->name('books.show');
Route::get('/quraniyat', [QuraniyatController::class, 'index'])->name('quraniyat.index');
Route::get('/quraniyat/{slug}', [QuraniyatController::class, 'show'])->name('quraniyat.show');
Route::get('/lectures', [LectureController::class, 'index'])->name('lectures.index');
Route::get('/lectures/{slug}', [LectureController::class, 'show'])->name('lectures.show');
Route::get('/reflections', [ReflectionController::class, 'index'])->name('reflections.index');
Route::get('/reflections/{slug}', [ReflectionController::class, 'show'])->name('reflections.show');
Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
Route::get('/programs/{slug}', [ProgramController::class, 'show'])->name('programs.show');
Route::get('/wall', [WallController::class, 'index'])->name('wall.index');
Route::get('/pdf/{media}', [PdfController::class, 'show'])->name('pdf.show');

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
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
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
        Route::prefix('programs')->as('programs.')->group(function () {
            Route::get('/', [AdminProgramController::class, 'index'])->name('index');
            Route::get('/create', [AdminProgramController::class, 'create'])->name('create');
            Route::post('/', [AdminProgramController::class, 'store'])->name('store');
            Route::get('/{item}/edit', [AdminProgramController::class, 'edit'])->name('edit');
            Route::put('/{item}', [AdminProgramController::class, 'update'])->name('update');
            Route::delete('/{item}', [AdminProgramController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [AdminProgramController::class, 'restore'])->name('restore');
            Route::post('/{item}/publish', [AdminProgramController::class, 'publish'])->name('publish');
            Route::post('/{item}/unpublish', [AdminProgramController::class, 'unpublish'])->name('unpublish');
            Route::prefix('{program}/episodes')->as('episodes.')->group(function () {
                Route::get('/', [ProgramEpisodeController::class, 'index'])->name('index');
                Route::get('/create', [ProgramEpisodeController::class, 'create'])->name('create');
                Route::post('/', [ProgramEpisodeController::class, 'store'])->name('store');
                Route::get('/{episode}/edit', [ProgramEpisodeController::class, 'edit'])->name('edit');
                Route::put('/{episode}', [ProgramEpisodeController::class, 'update'])->name('update');
                Route::delete('/{episode}', [ProgramEpisodeController::class, 'destroy'])->name('destroy');
                Route::post('/{id}/restore', [ProgramEpisodeController::class, 'restore'])->name('restore');
                Route::post('/{episode}/publish', [ProgramEpisodeController::class, 'publish'])->name('publish');
                Route::post('/{episode}/unpublish', [ProgramEpisodeController::class, 'unpublish'])->name('unpublish');
            });
        });
        Route::prefix('lectures')->as('lectures.')->group(function () {
            Route::get('/', [AdminLectureController::class, 'index'])->name('index');
            Route::get('/create', [AdminLectureController::class, 'create'])->name('create');
            Route::post('/', [AdminLectureController::class, 'store'])->name('store');
            Route::get('/{item}/edit', [AdminLectureController::class, 'edit'])->name('edit');
            Route::put('/{item}', [AdminLectureController::class, 'update'])->name('update');
            Route::delete('/{item}', [AdminLectureController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [AdminLectureController::class, 'restore'])->name('restore');
            Route::post('/{item}/publish', [AdminLectureController::class, 'publish'])->name('publish');
            Route::post('/{item}/unpublish', [AdminLectureController::class, 'unpublish'])->name('unpublish');
        });
        Route::prefix('reflections')->as('reflections.')->group(function () {
            Route::get('/', [AdminReflectionController::class, 'index'])->name('index');
            Route::get('/create', [AdminReflectionController::class, 'create'])->name('create');
            Route::post('/', [AdminReflectionController::class, 'store'])->name('store');
            Route::get('/{item}/edit', [AdminReflectionController::class, 'edit'])->name('edit');
            Route::put('/{item}', [AdminReflectionController::class, 'update'])->name('update');
            Route::delete('/{item}', [AdminReflectionController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [AdminReflectionController::class, 'restore'])->name('restore');
            Route::post('/{item}/publish', [AdminReflectionController::class, 'publish'])->name('publish');
            Route::post('/{item}/unpublish', [AdminReflectionController::class, 'unpublish'])->name('unpublish');
        });
        Route::prefix('wall-posts')->as('wall-posts.')->group(function () {
            Route::get('/', [AdminWallPostController::class, 'index'])->name('index');
            Route::get('/create', [AdminWallPostController::class, 'create'])->name('create');
            Route::post('/', [AdminWallPostController::class, 'store'])->name('store');
            Route::get('/{item}/edit', [AdminWallPostController::class, 'edit'])->name('edit');
            Route::put('/{item}', [AdminWallPostController::class, 'update'])->name('update');
            Route::delete('/{item}', [AdminWallPostController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [AdminWallPostController::class, 'restore'])->name('restore');
            Route::post('/{item}/publish', [AdminWallPostController::class, 'publish'])->name('publish');
            Route::post('/{item}/unpublish', [AdminWallPostController::class, 'unpublish'])->name('unpublish');
        });
        Route::prefix('categories')->as('categories.')->group(function () {
            Route::get('/', [AdminCategoryController::class, 'index'])->name('index');
            Route::get('/create', [AdminCategoryController::class, 'create'])->name('create');
            Route::post('/', [AdminCategoryController::class, 'store'])->name('store');
            Route::get('/{category}/edit', [AdminCategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [AdminCategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [AdminCategoryController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [AdminCategoryController::class, 'restore'])->name('restore');
            Route::post('/{category}/activate', [AdminCategoryController::class, 'activate'])->name('activate');
            Route::post('/{category}/deactivate', [AdminCategoryController::class, 'deactivate'])->name('deactivate');
        });
        Route::prefix('tags')->as('tags.')->group(function () {
            Route::get('/', [AdminTagController::class, 'index'])->name('index');
            Route::get('/create', [AdminTagController::class, 'create'])->name('create');
            Route::post('/', [AdminTagController::class, 'store'])->name('store');
            Route::get('/{tag}/edit', [AdminTagController::class, 'edit'])->name('edit');
            Route::put('/{tag}', [AdminTagController::class, 'update'])->name('update');
            Route::delete('/{tag}', [AdminTagController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [AdminTagController::class, 'restore'])->name('restore');
            Route::post('/{tag}/activate', [AdminTagController::class, 'activate'])->name('activate');
            Route::post('/{tag}/deactivate', [AdminTagController::class, 'deactivate'])->name('deactivate');
        });
        Route::prefix('media')->as('media.')->group(function () {
            Route::get('/', [AdminMediaController::class, 'index'])->name('index');
            Route::post('/', [AdminMediaController::class, 'store'])->name('store');
            Route::put('/{media}', [AdminMediaController::class, 'update'])->name('update');
            Route::delete('/{media}', [AdminMediaController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [AdminMediaController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force', [AdminMediaController::class, 'forceDestroy'])->name('force-destroy');
        });
        Route::get('{section}', StaticAdminPageController::class)->whereIn('section', ['content', 'activity-logs', 'backups'])->name('section');
    });
});

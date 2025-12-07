<?php

use App\Http\Controllers\Admin\AboutController as AdminAboutController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FrontpageController as AdminFrontpageController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\LogController as AdminLogController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\PhotoController as AdminPhotoController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\FrontPageController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Front Page / Home (CV/Resume style)
Route::get('/', [FrontPageController::class, 'index'])->name('home');

// Public gallery routes
Route::get('/photos', [GalleryController::class, 'index'])->name('photos.index');
Route::get('/photos/map', [GalleryController::class, 'map'])->name('photos.map');
Route::get('/photo/{photo:slug}', [GalleryController::class, 'show'])->name('photos.show');
Route::get('/category/{category:slug}', [GalleryController::class, 'category'])->name('category.show');
Route::get('/gallery/{gallery:slug}', [GalleryController::class, 'gallery'])->name('gallery.show');
Route::get('/tag/{tag:slug}', [GalleryController::class, 'tag'])->name('tag.show');

// Static pages
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'sendContact'])->name('contact.send');

Route::get('/dashboard', [AdminDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Photos
    Route::get('photos', [AdminPhotoController::class, 'index'])->name('photos.index');
    Route::get('photos/create', [AdminPhotoController::class, 'create'])->name('photos.create');
    Route::get('photos/bulk-edit', [AdminPhotoController::class, 'bulkEdit'])->name('photos.bulk-edit');
    Route::post('photos', [AdminPhotoController::class, 'store'])->name('photos.store');
    Route::get('photos/{photo}', [AdminPhotoController::class, 'show'])->name('photos.show');
    Route::get('photos/{photo}/edit', [AdminPhotoController::class, 'edit'])->name('photos.edit');
    Route::put('photos/{photo}', [AdminPhotoController::class, 'update'])->name('photos.update');
    Route::patch('photos/{photo}/quick', [AdminPhotoController::class, 'quickUpdate'])->name('photos.quick-update');
    Route::delete('photos/{photo}', [AdminPhotoController::class, 'destroy'])->name('photos.destroy');
    Route::post('photos/bulk-action', [AdminPhotoController::class, 'bulkAction'])->name('photos.bulk-action');
    Route::post('photos/bulk-update', [AdminPhotoController::class, 'bulkUpdate'])->name('photos.bulk-update');
    Route::post('photos/bulk-tags', [AdminPhotoController::class, 'bulkTags'])->name('photos.bulk-tags');
    Route::post('photos/reoptimize', [AdminPhotoController::class, 'reoptimize'])->name('photos.reoptimize');

    // Categories
    Route::get('categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
    Route::post('categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    Route::post('categories/update-order', [AdminCategoryController::class, 'updateOrder'])->name('categories.update-order');

    // Galleries
    Route::get('galleries', [AdminGalleryController::class, 'index'])->name('galleries.index');
    Route::get('galleries/create', [AdminGalleryController::class, 'create'])->name('galleries.create');
    Route::post('galleries', [AdminGalleryController::class, 'store'])->name('galleries.store');
    Route::get('galleries/{gallery}', [AdminGalleryController::class, 'show'])->name('galleries.show');
    Route::get('galleries/{gallery}/edit', [AdminGalleryController::class, 'edit'])->name('galleries.edit');
    Route::put('galleries/{gallery}', [AdminGalleryController::class, 'update'])->name('galleries.update');
    Route::delete('galleries/{gallery}', [AdminGalleryController::class, 'destroy'])->name('galleries.destroy');
    Route::post('galleries/{gallery}/add-photos', [AdminGalleryController::class, 'addPhotos'])->name('galleries.add-photos');
    Route::delete('galleries/{gallery}/photos/{photo}', [AdminGalleryController::class, 'removePhoto'])->name('galleries.remove-photo');

    // Tags
    Route::get('tags', [AdminTagController::class, 'index'])->name('tags.index');
    Route::post('tags', [AdminTagController::class, 'store'])->name('tags.store');
    Route::put('tags/{tag}', [AdminTagController::class, 'update'])->name('tags.update');
    Route::delete('tags/{tag}', [AdminTagController::class, 'destroy'])->name('tags.destroy');

    // Front Page Settings
    Route::get('frontpage', [AdminFrontpageController::class, 'index'])->name('frontpage.index');
    Route::put('frontpage', [AdminFrontpageController::class, 'update'])->name('frontpage.update');

    // Settings
    Route::get('settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::post('settings/validate-ai-api', [AdminSettingController::class, 'validateAiApiKey'])->name('settings.validate-ai-api');
    Route::post('settings/regenerate-watermarks', [AdminSettingController::class, 'regenerateWatermarks'])->name('settings.regenerate-watermarks');

    // About Page Editors
    Route::get('about/editor', [AdminAboutController::class, 'editor'])->name('about.editor');
    Route::get('about/editorjs', [AdminAboutController::class, 'editorJs'])->name('about.editorjs');
    Route::post('about/save', [AdminAboutController::class, 'save'])->name('about.save');
    Route::post('about/save-editorjs', [AdminAboutController::class, 'saveEditorJs'])->name('about.save-editorjs');

    // Logs
    Route::get('logs', [AdminLogController::class, 'index'])->name('logs.index');
    Route::get('logs/{log}/details', [AdminLogController::class, 'details'])->name('logs.details');
    Route::post('logs/clear', [AdminLogController::class, 'clear'])->name('logs.clear');
    Route::delete('logs/{log}', [AdminLogController::class, 'destroy'])->name('logs.destroy');

    // Media Library API
    Route::get('media/photos', [AdminMediaController::class, 'photos'])->name('media.photos');
});

require __DIR__.'/auth.php';

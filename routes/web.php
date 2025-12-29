<?php

use App\Http\Controllers\Admin\AboutController as AdminAboutController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\BackupController as AdminBackupController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FrontpageController as AdminFrontpageController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\LogController as AdminLogController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\PhotoController as AdminPhotoController;
use App\Http\Controllers\Admin\SeriesController as AdminSeriesController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\EquipmentController as AdminEquipmentController;
use App\Http\Controllers\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\Admin\LightroomSyncController as AdminLightroomController;
use App\Http\Controllers\Admin\SocialMediaController as AdminSocialController;
use App\Http\Controllers\Admin\ABTestController as AdminABTestController;
use App\Http\Controllers\Admin\SeoAuditController as AdminSeoController;
use App\Http\Controllers\Admin\TranslationController as AdminTranslationController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\PhotoInteractionController;
use App\Http\Controllers\ClientProofingController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FrontPageController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ClientGalleryController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Front Page / Home (CV/Resume style)
Route::get('/', [FrontPageController::class, 'index'])->name('home');

// Public gallery routes
Route::get('/photos', [GalleryController::class, 'index'])->name('photos.index');
Route::get('/photos/map', [GalleryController::class, 'map'])->name('photos.map');
Route::get('/photo/{photo:slug}', [GalleryController::class, 'show'])->name('photos.show');
Route::get('/category/{category:slug}', [GalleryController::class, 'category'])->name('category.show');
Route::get('/gallery/{gallery:slug}', [GalleryController::class, 'gallery'])->name('gallery.show');
Route::post('/gallery/{gallery:slug}/unlock', [GalleryController::class, 'verifyGalleryPassword'])->name('gallery.unlock');
Route::get('/tag/{tag:slug}', [GalleryController::class, 'tag'])->name('tag.show');

// Photo interactions (likes & comments)
Route::prefix('photo/{photo:slug}')->group(function () {
    Route::post('/like', [PhotoInteractionController::class, 'toggleLike'])->name('photos.like');
    Route::get('/like/check', [PhotoInteractionController::class, 'checkLike'])->name('photos.like.check');
    Route::post('/comment/request-otp', [PhotoInteractionController::class, 'requestOtp'])->name('photos.comment.request-otp');
    Route::post('/comment/verify-otp', [PhotoInteractionController::class, 'verifyOtp'])->name('photos.comment.verify-otp');
    Route::post('/comment/resend-otp', [PhotoInteractionController::class, 'resendOtp'])->name('photos.comment.resend-otp');
    Route::get('/comments', [PhotoInteractionController::class, 'getComments'])->name('photos.comments');
});

// Photo download routes
Route::get('/photo/{photo:slug}/download/{format?}', [DownloadController::class, 'download'])
    ->where('format', 'webp|jpeg|jpg')
    ->name('photos.download');

// Print store routes
Route::get('/photo/{photo:slug}/print', [PrintController::class, 'show'])->name('print.options');
Route::post('/photo/{photo:slug}/print/inquiry', [PrintController::class, 'inquiry'])->name('print.inquiry');
Route::get('/api/print/products', [PrintController::class, 'products'])->name('print.products');

// Checkout routes
Route::get('/photo/{photo:slug}/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/photo/{photo:slug}/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
Route::get('/order/{order}/confirmation', [CheckoutController::class, 'confirm'])->name('order.confirmation');
Route::post('/stripe/webhook', [CheckoutController::class, 'webhook'])->name('stripe.webhook');

// Digital download route for licensed photos
Route::get('/download/{order}', [CheckoutController::class, 'download'])->name('download.photo');

// Client proofing routes (photo selection)
Route::prefix('selections')->name('client.')->group(function () {
    Route::get('/', [ClientProofingController::class, 'index'])->name('selections');
    Route::get('/count', [ClientProofingController::class, 'count'])->name('count');
    Route::post('/photo/{photo}/toggle', [ClientProofingController::class, 'toggle'])->name('toggle');
    Route::get('/photo/{photo}/check', [ClientProofingController::class, 'check'])->name('check');
    Route::post('/clear', [ClientProofingController::class, 'clear'])->name('clear');
    Route::get('/export', [ClientProofingController::class, 'export'])->name('export');
    Route::post('/send', [ClientProofingController::class, 'sendToPhotographer'])->name('send');
});

// Client gallery routes (token-based access)
Route::prefix('client-gallery')->group(function () {
    Route::get('/{token}', [ClientGalleryController::class, 'view'])->name('client-gallery.view');
    Route::post('/{token}/password', [ClientGalleryController::class, 'verifyPassword'])->name('client-gallery.password');
    Route::get('/{token}/download/{photo}', [ClientGalleryController::class, 'download'])->name('client-gallery.download');
    Route::post('/{token}/toggle/{photo}', [ClientGalleryController::class, 'toggleSelection'])->name('client-gallery.toggle');
    Route::get('/{token}/selections', [ClientGalleryController::class, 'getSelections'])->name('client-gallery.selections');
    Route::post('/{token}/submit', [ClientGalleryController::class, 'submitSelections'])->name('client-gallery.submit');
});

// Static pages
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'sendContact'])->name('contact.send');

// Blog/Stories routes
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

// Photo Series routes
Route::get('/series', [SeriesController::class, 'index'])->name('series.index');
Route::get('/series/{series:slug}', [SeriesController::class, 'show'])->name('series.show');

// Gear/Equipment routes
Route::get('/gear', [EquipmentController::class, 'index'])->name('gear.index');
Route::get('/gear/{equipment:slug}', [EquipmentController::class, 'show'])->name('gear.show');

// Locations routes
Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
Route::get('/locations/{location:slug}', [LocationController::class, 'show'])->name('locations.show');

// Search routes
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');

// RSS/Atom Feeds
Route::get('/feed/rss', [FeedController::class, 'rss'])->name('feed.rss');
Route::get('/feed/atom', [FeedController::class, 'atom'])->name('feed.atom');
Route::get('/blog/feed.xml', [FeedController::class, 'rss'])->name('feed.blog');

// Newsletter
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-images.xml', [SitemapController::class, 'images'])->name('sitemap.images');

// Dynamic robots.txt based on admin settings
Route::get('/robots.txt', function () {
    $allowCrawling = \App\Models\Setting::get('seo_robots_allow') === '1';

    $content = "# Photography Portfolio - Robots.txt\n";
    $content .= "# https://mfaruk.com\n\n";
    $content .= "User-agent: *\n";

    if ($allowCrawling) {
        $content .= "Allow: /\n\n";
        $content .= "# Sitemaps\n";
        $content .= "Sitemap: https://mfaruk.com/sitemap.xml\n";
        $content .= "Sitemap: https://mfaruk.com/sitemap-images.xml\n\n";
        $content .= "# Disallow admin and auth pages\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /login\n";
        $content .= "Disallow: /register\n";
        $content .= "Disallow: /password/\n";
        $content .= "Disallow: /dashboard\n";
        $content .= "Disallow: /profile\n\n";
        $content .= "# Disallow API endpoints\n";
        $content .= "Disallow: /api/\n\n";
        $content .= "# Allow all images to be indexed\n";
        $content .= "Allow: /storage/photos/\n\n";
        $content .= "# Crawl delay for politeness\n";
        $content .= "Crawl-delay: 1\n";
    } else {
        $content .= "Disallow: /\n\n";
        $content .= "# Site is currently hidden from search engines.\n";
        $content .= "# Enable crawling from Admin > Settings > SEO when ready.\n";
    }

    return response($content, 200)->header('Content-Type', 'text/plain');
})->name('robots');

Route::get('/dashboard', [AdminDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Redirect /admin to /dashboard
Route::get('/admin', function () {
    return redirect()->route('dashboard');
})->middleware(['auth', 'verified']);

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
    Route::get('photos/{photo}/suggest-slug', [AdminPhotoController::class, 'suggestSlug'])->name('photos.suggest-slug');
    Route::post('photos/validate-slug', [AdminPhotoController::class, 'validateSlug'])->name('photos.validate-slug');
    Route::post('photos/validate-title', [AdminPhotoController::class, 'validateTitle'])->name('photos.validate-title');
    Route::get('photos/processing-status', [AdminPhotoController::class, 'processingStatus'])->name('photos.processing-status');
    Route::post('photos/{photo}/retry', [AdminPhotoController::class, 'retryProcessing'])->name('photos.retry');
    Route::post('photos/{photo}/replace-image', [AdminPhotoController::class, 'replaceImage'])->name('photos.replace-image');

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

    // Blog Posts
    Route::get('posts', [AdminPostController::class, 'index'])->name('posts.index');
    Route::get('posts/create', [AdminPostController::class, 'create'])->name('posts.create');
    Route::post('posts', [AdminPostController::class, 'store'])->name('posts.store');
    Route::get('posts/{post}/edit', [AdminPostController::class, 'edit'])->name('posts.edit');
    Route::put('posts/{post}', [AdminPostController::class, 'update'])->name('posts.update');
    Route::delete('posts/{post}', [AdminPostController::class, 'destroy'])->name('posts.destroy');

    // Photo Series
    Route::get('series', [AdminSeriesController::class, 'index'])->name('series.index');
    Route::get('series/create', [AdminSeriesController::class, 'create'])->name('series.create');
    Route::post('series', [AdminSeriesController::class, 'store'])->name('series.store');
    Route::get('series/{series}/edit', [AdminSeriesController::class, 'edit'])->name('series.edit');
    Route::put('series/{series}', [AdminSeriesController::class, 'update'])->name('series.update');
    Route::delete('series/{series}', [AdminSeriesController::class, 'destroy'])->name('series.destroy');
    Route::post('series/{series}/add-photos', [AdminSeriesController::class, 'addPhotos'])->name('series.add-photos');
    Route::delete('series/{series}/photos/{photo}', [AdminSeriesController::class, 'removePhoto'])->name('series.remove-photo');
    Route::post('series/{series}/update-order', [AdminSeriesController::class, 'updateOrder'])->name('series.update-order');

    // Front Page Settings
    Route::get('frontpage', [AdminFrontpageController::class, 'index'])->name('frontpage.index');
    Route::put('frontpage', [AdminFrontpageController::class, 'update'])->name('frontpage.update');

    // Settings
    Route::get('settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::post('settings/theme', [AdminSettingController::class, 'updateTheme'])->name('settings.update-theme');
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

    // Contacts
    Route::get('contacts', [AdminContactController::class, 'index'])->name('contacts.index');
    Route::get('contacts/{contact}', [AdminContactController::class, 'show'])->name('contacts.show');
    Route::patch('contacts/{contact}/status', [AdminContactController::class, 'updateStatus'])->name('contacts.update-status');
    Route::delete('contacts/{contact}', [AdminContactController::class, 'destroy'])->name('contacts.destroy');
    Route::post('contacts/bulk-delete', [AdminContactController::class, 'bulkDelete'])->name('contacts.bulk-delete');
    Route::post('contacts/archive-old', [AdminContactController::class, 'archiveOld'])->name('contacts.archive-old');

    // Comments
    Route::get('comments', [AdminCommentController::class, 'index'])->name('comments.index');
    Route::post('comments/{comment}/approve', [AdminCommentController::class, 'approve'])->name('comments.approve');
    Route::post('comments/{comment}/reject', [AdminCommentController::class, 'reject'])->name('comments.reject');
    Route::post('comments/{comment}/spam', [AdminCommentController::class, 'spam'])->name('comments.spam');
    Route::post('comments/bulk-approve', [AdminCommentController::class, 'bulkApprove'])->name('comments.bulk-approve');
    Route::delete('comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('comments/{comment}/reply', [AdminCommentController::class, 'reply'])->name('comments.reply');
    Route::post('comments/{comment}/block-email', [AdminCommentController::class, 'blockEmail'])->name('comments.block-email');
    Route::get('comments/blocked-emails', [AdminCommentController::class, 'blockedEmails'])->name('comments.blocked-emails');
    Route::post('comments/unblock-email', [AdminCommentController::class, 'unblockEmail'])->name('comments.unblock-email');

    // Orders
    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('orders/{order}/ship', [AdminOrderController::class, 'ship'])->name('orders.ship');
    Route::post('orders/{order}/note', [AdminOrderController::class, 'addNote'])->name('orders.note');

    // Media Library API
    Route::get('media/photos', [AdminMediaController::class, 'photos'])->name('media.photos');

    // Backup
    Route::get('backup', [AdminBackupController::class, 'index'])->name('backup.index');
    Route::post('backup/run', [AdminBackupController::class, 'runBackup'])->name('backup.run');
    Route::post('backup/test', [AdminBackupController::class, 'testConnection'])->name('backup.test');
    Route::get('backup/list', [AdminBackupController::class, 'listBackups'])->name('backup.list');

    // Analytics
    Route::get('analytics/referrals', [AdminAnalyticsController::class, 'referrals'])->name('analytics.referrals');

    // Equipment
    Route::get('equipment', [AdminEquipmentController::class, 'index'])->name('equipment.index');
    Route::get('equipment/create', [AdminEquipmentController::class, 'create'])->name('equipment.create');
    Route::post('equipment', [AdminEquipmentController::class, 'store'])->name('equipment.store');
    Route::get('equipment/{equipment}/edit', [AdminEquipmentController::class, 'edit'])->name('equipment.edit');
    Route::put('equipment/{equipment}', [AdminEquipmentController::class, 'update'])->name('equipment.update');
    Route::delete('equipment/{equipment}', [AdminEquipmentController::class, 'destroy'])->name('equipment.destroy');

    // Locations
    Route::get('locations', [AdminLocationController::class, 'index'])->name('locations.index');
    Route::get('locations/create', [AdminLocationController::class, 'create'])->name('locations.create');
    Route::post('locations', [AdminLocationController::class, 'store'])->name('locations.store');
    Route::get('locations/{location}/edit', [AdminLocationController::class, 'edit'])->name('locations.edit');
    Route::put('locations/{location}', [AdminLocationController::class, 'update'])->name('locations.update');
    Route::delete('locations/{location}', [AdminLocationController::class, 'destroy'])->name('locations.destroy');

    // Lightroom Sync
    Route::get('lightroom', [AdminLightroomController::class, 'index'])->name('lightroom.index');
    Route::post('lightroom/process', [AdminLightroomController::class, 'process'])->name('lightroom.process');
    Route::post('lightroom/preview', [AdminLightroomController::class, 'preview'])->name('lightroom.preview');

    // Social Media
    Route::get('social', [AdminSocialController::class, 'index'])->name('social.index');
    Route::get('social/create', [AdminSocialController::class, 'create'])->name('social.create');
    Route::post('social', [AdminSocialController::class, 'store'])->name('social.store');
    Route::get('social/{socialPost}', [AdminSocialController::class, 'show'])->name('social.show');
    Route::post('social/{socialPost}/publish', [AdminSocialController::class, 'publish'])->name('social.publish');
    Route::delete('social/{socialPost}', [AdminSocialController::class, 'destroy'])->name('social.destroy');
    Route::get('social/accounts', [AdminSocialController::class, 'accounts'])->name('social.accounts');

    // A/B Testing
    Route::get('abtests', [AdminABTestController::class, 'index'])->name('abtests.index');
    Route::get('abtests/create', [AdminABTestController::class, 'create'])->name('abtests.create');
    Route::post('abtests', [AdminABTestController::class, 'store'])->name('abtests.store');
    Route::get('abtests/{abtest}', [AdminABTestController::class, 'show'])->name('abtests.show');
    Route::get('abtests/{abtest}/edit', [AdminABTestController::class, 'edit'])->name('abtests.edit');
    Route::put('abtests/{abtest}', [AdminABTestController::class, 'update'])->name('abtests.update');
    Route::post('abtests/{abtest}/start', [AdminABTestController::class, 'start'])->name('abtests.start');
    Route::post('abtests/{abtest}/pause', [AdminABTestController::class, 'pause'])->name('abtests.pause');
    Route::post('abtests/{abtest}/complete', [AdminABTestController::class, 'complete'])->name('abtests.complete');
    Route::delete('abtests/{abtest}', [AdminABTestController::class, 'destroy'])->name('abtests.destroy');

    // SEO Audit
    Route::get('seo', [AdminSeoController::class, 'index'])->name('seo.index');
    Route::get('seo/photo/{photo}', [AdminSeoController::class, 'photo'])->name('seo.photo');
    Route::get('seo/post/{post}', [AdminSeoController::class, 'post'])->name('seo.post');
    Route::get('seo/data', [AdminSeoController::class, 'data'])->name('seo.data');

    // Translations
    Route::get('translations', [AdminTranslationController::class, 'index'])->name('translations.index');
    Route::get('translations/photo/{photo}', [AdminTranslationController::class, 'editPhoto'])->name('translations.photo');
    Route::put('translations/photo/{photo}', [AdminTranslationController::class, 'updatePhoto'])->name('translations.photo.update');
    Route::get('translations/post/{post}', [AdminTranslationController::class, 'editPost'])->name('translations.post');
    Route::put('translations/post/{post}', [AdminTranslationController::class, 'updatePost'])->name('translations.post.update');
});

require __DIR__.'/auth.php';

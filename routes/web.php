<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BoardMemberController as AdminBoardMemberController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FormBuilderController;
use App\Http\Controllers\Admin\FormDefinitionController as AdminFormDefinitionController;
use App\Http\Controllers\Admin\ImageUploadController as AdminImageUploadController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\SectorController as AdminSectorController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SubmissionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JoinController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PrivateFormController;
use App\Http\Controllers\SitemapController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/', function (Request $request) {
    $accept = strtolower($request->header('Accept-Language', ''));
    $locale = str_starts_with($accept, 'ar') ? 'ar' : 'en';

    return redirect('/'.$locale);
});

Route::prefix('{locale}')
    ->where(['locale' => 'en|ar'])
    ->middleware('locale')
    ->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/about', [AboutController::class, 'index'])->name('about');

        Route::get('/news', [NewsController::class, 'index'])->name('news.index');
        Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

        Route::get('/join', [JoinController::class, 'create'])->name('join.create');
        Route::post('/join', [JoinController::class, 'store'])->name('join.store')->middleware('throttle:5,1');
        Route::get('/join/thanks', [JoinController::class, 'thanks'])->name('join.thanks');

        Route::get('/forms/{form:slug}/{token}', [PrivateFormController::class, 'show'])->name('private-forms.show');
        Route::post('/forms/{form:slug}/{token}', [PrivateFormController::class, 'store'])->name('private-forms.store')->middleware('throttle:5,1');
        Route::get('/forms/{form:slug}/{token}/thanks', [PrivateFormController::class, 'thanks'])->name('private-forms.thanks');

        Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
        Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
        Route::get('/contact/thanks', [ContactController::class, 'thanks'])->name('contact.thanks');
    });

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        $user = auth()->user();

        if ($user?->isSubadmin()) {
            if ($user->canManageNews()) {
                return redirect()->route('admin.news.index');
            }
            if ($user->canCustomizeSite()) {
                return redirect()->route('admin.settings.edit');
            }
            if ($user->canViewSubmissions()) {
                return redirect()->route('admin.submissions.index');
            }
            abort(403);
        }

        return redirect()->route('admin.dashboard');
    })->middleware('auth');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('admin.role:admin')
            ->name('dashboard');

        Route::resource('news', AdminNewsController::class)
            ->middleware('admin.permission:news_write,news_publish')
            ->except(['show']);

        Route::post('upload-image', [AdminImageUploadController::class, 'store'])
            ->middleware('admin.permission:news_write,news_publish')
            ->name('upload-image');

        Route::middleware('admin.permission:site_customization')->group(function () {
            Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::patch('/settings/home', [SettingsController::class, 'updateHome'])->name('settings.home.update');
            Route::patch('/settings/about', [SettingsController::class, 'updateAbout'])->name('settings.about.update');
            Route::post('/settings/hero-image', [SettingsController::class, 'updateHeroImage'])->name('settings.hero.update');
            Route::delete('/settings/hero-image', [SettingsController::class, 'deleteHeroImage'])->name('settings.hero.destroy');
        });

        Route::middleware('admin.role:admin')->group(function () {
            Route::resource('users', AdminUserController::class)->except(['show']);

            Route::resource('board-members', AdminBoardMemberController::class)->except(['show']);
            Route::resource('sectors', AdminSectorController::class)->except(['show']);

            Route::get('/contact', [AdminContactController::class, 'index'])->name('contact.index');
            Route::get('/contact/{contactSubmission}', [AdminContactController::class, 'show'])->name('contact.show');
            Route::patch('/contact/{contactSubmission}', [AdminContactController::class, 'update'])->name('contact.update');
            Route::delete('/contact/{contactSubmission}', [AdminContactController::class, 'destroy'])->name('contact.destroy');

            Route::get('/forms', [AdminFormDefinitionController::class, 'index'])->name('forms.index');
            Route::post('/forms', [AdminFormDefinitionController::class, 'store'])->name('forms.store');
            Route::patch('/forms/{formDefinition}', [AdminFormDefinitionController::class, 'update'])->name('forms.update');

            Route::get('/forms/join-us', [AdminFormDefinitionController::class, 'joinUs'])->name('forms.join-us');
            Route::get('/forms/{formDefinition}/builder', [FormBuilderController::class, 'index'])->name('forms.builder');
            Route::get('/forms/{formDefinition}/preview', [FormBuilderController::class, 'preview'])->name('forms.preview');
            Route::post('/forms/{formDefinition}/sections', [FormBuilderController::class, 'storeSection'])->name('forms.sections.store');
            Route::put('/forms/{formDefinition}/sections/{section}', [FormBuilderController::class, 'updateSection'])->name('forms.sections.update');
            Route::delete('/forms/{formDefinition}/sections/{section}', [FormBuilderController::class, 'destroySection'])->name('forms.sections.destroy');
            Route::post('/forms/{formDefinition}/sections/reorder', [FormBuilderController::class, 'reorderSections'])->name('forms.sections.reorder');
            Route::post('/forms/{formDefinition}/fields', [FormBuilderController::class, 'storeField'])->name('forms.fields.store');
            Route::put('/forms/{formDefinition}/fields/{field}', [FormBuilderController::class, 'updateField'])->name('forms.fields.update');
            Route::delete('/forms/{formDefinition}/fields/{field}', [FormBuilderController::class, 'destroyField'])->name('forms.fields.destroy');
            Route::post('/forms/{formDefinition}/fields/reorder', [FormBuilderController::class, 'reorderFields'])->name('forms.fields.reorder');

        });

        Route::middleware('admin.permission:view_submissions')->group(function () {
            Route::get('/submissions/export', [SubmissionController::class, 'export'])->name('submissions.export');
            Route::get('/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
            Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
            Route::patch('/submissions/{submission}', [SubmissionController::class, 'update'])->name('submissions.update');
            Route::delete('/submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');
            Route::get('/submissions/{submission}/pdf', [SubmissionController::class, 'pdf'])->name('submissions.pdf');
        });
    });
});

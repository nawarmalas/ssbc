<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FormBuilderController;
use App\Http\Controllers\Admin\JoinController as AdminJoinController;
use App\Http\Controllers\Admin\MembershipController as AdminMembershipController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SubmissionController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JoinController;
use App\Http\Controllers\NewsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root locale redirect
|--------------------------------------------------------------------------
*/
Route::get('/', function (Request $request) {
    $accept = strtolower($request->header('Accept-Language', ''));
    $locale = str_starts_with($accept, 'ar') ? 'ar' : 'en';
    return redirect('/'.$locale);
});

/*
|--------------------------------------------------------------------------
| Public locale-prefixed routes
|--------------------------------------------------------------------------
*/
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

        Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
        Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
        Route::get('/contact/thanks', [ContactController::class, 'thanks'])->name('contact.thanks');
    });

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/', fn () => redirect()->route('admin.dashboard'));

    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('news', AdminNewsController::class)->except(['show']);

        Route::get('/join', [AdminJoinController::class, 'index'])->name('join.index');
        Route::get('/join/{joinSubmission}', [AdminJoinController::class, 'show'])->name('join.show');
        Route::patch('/join/{joinSubmission}', [AdminJoinController::class, 'update'])->name('join.update');
        Route::delete('/join/{joinSubmission}', [AdminJoinController::class, 'destroy'])->name('join.destroy');

        Route::get('/contact', [AdminContactController::class, 'index'])->name('contact.index');
        Route::get('/contact/{contactSubmission}', [AdminContactController::class, 'show'])->name('contact.show');
        Route::patch('/contact/{contactSubmission}', [AdminContactController::class, 'update'])->name('contact.update');
        Route::delete('/contact/{contactSubmission}', [AdminContactController::class, 'destroy'])->name('contact.destroy');

        Route::get('/membership', [AdminMembershipController::class, 'index'])->name('membership.index');
        Route::get('/membership/{membershipApplication}', [AdminMembershipController::class, 'show'])->name('membership.show');
        Route::patch('/membership/{membershipApplication}', [AdminMembershipController::class, 'update'])->name('membership.update');
        Route::delete('/membership/{membershipApplication}', [AdminMembershipController::class, 'destroy'])->name('membership.destroy');

        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // Form Builder
        Route::get('/forms/join-us', [FormBuilderController::class, 'index'])->name('forms.builder');
        Route::get('/forms/join-us/preview', [FormBuilderController::class, 'preview'])->name('forms.preview');
        Route::post('/forms/join-us/sections', [FormBuilderController::class, 'storeSection'])->name('forms.sections.store');
        Route::put('/forms/join-us/sections/{section}', [FormBuilderController::class, 'updateSection'])->name('forms.sections.update');
        Route::delete('/forms/join-us/sections/{section}', [FormBuilderController::class, 'destroySection'])->name('forms.sections.destroy');
        Route::post('/forms/join-us/sections/reorder', [FormBuilderController::class, 'reorderSections'])->name('forms.sections.reorder');
        Route::post('/forms/join-us/fields', [FormBuilderController::class, 'storeField'])->name('forms.fields.store');
        Route::put('/forms/join-us/fields/{field}', [FormBuilderController::class, 'updateField'])->name('forms.fields.update');
        Route::delete('/forms/join-us/fields/{field}', [FormBuilderController::class, 'destroyField'])->name('forms.fields.destroy');
        Route::post('/forms/join-us/fields/reorder', [FormBuilderController::class, 'reorderFields'])->name('forms.fields.reorder');

        // Submissions — export MUST come before {submission} wildcard
        Route::get('/submissions/export', [SubmissionController::class, 'export'])->name('submissions.export');
        Route::get('/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
        Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
        Route::patch('/submissions/{submission}', [SubmissionController::class, 'update'])->name('submissions.update');
        Route::delete('/submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');
        Route::get('/submissions/{submission}/pdf', [SubmissionController::class, 'pdf'])->name('submissions.pdf');
    });
});

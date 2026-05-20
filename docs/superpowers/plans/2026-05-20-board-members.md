# Board Members Section — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a fully admin-managed "أعضاء المجلس / Board Members" section to the home page with bilingual name/role/bio, photo upload, hover overlay bio, and mobile tap-to-reveal.

**Architecture:** New `board_members` table + `BoardMember` model following the `NewsPost` pattern. `BoardMemberController` handles admin CRUD (admin-only). A Blade partial renders the 5-column grid on the home page using Alpine.js for hover/tap interactivity.

**Tech Stack:** Laravel 11, Blade, Tailwind CSS, Alpine.js, Laravel Storage (public disk)

---

## File Map

**New files:**
- `database/migrations/2026_05_20_000010_create_board_members_table.php`
- `database/factories/BoardMemberFactory.php`
- `app/Models/BoardMember.php`
- `app/Http/Controllers/Admin/BoardMemberController.php`
- `resources/views/admin/board-members/index.blade.php`
- `resources/views/admin/board-members/_form.blade.php`
- `resources/views/admin/board-members/create.blade.php`
- `resources/views/admin/board-members/edit.blade.php`
- `resources/views/pages/partials/board-members.blade.php`
- `tests/Feature/Admin/BoardMemberControllerTest.php`

**Modified files:**
- `routes/web.php` — add resource route under `admin.role:admin`
- `resources/views/layouts/admin.blade.php` — add nav link in admin `$nav` block
- `app/Http/Controllers/HomeController.php` — pass `$boardMembers` to view
- `resources/views/pages/home.blade.php` — include board-members partial between sections 4 and 5

---

## Task 1: Database Migration

**Files:**
- Create: `database/migrations/2026_05_20_000010_create_board_members_table.php`

- [ ] **Step 1: Create the migration file**

```php
<?php
// database/migrations/2026_05_20_000010_create_board_members_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_members', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('role_ar');
            $table->string('role_en');
            $table->text('bio_ar');
            $table->text('bio_en');
            $table->string('photo')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_members');
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

Expected output includes: `2026_05_20_000010_create_board_members_table ......... running`

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_05_20_000010_create_board_members_table.php
git commit -m "feat: create board_members table migration"
```

---

## Task 2: BoardMember Model + Factory

**Files:**
- Create: `app/Models/BoardMember.php`
- Create: `database/factories/BoardMemberFactory.php`

- [ ] **Step 1: Create the factory**

```php
<?php
// database/factories/BoardMemberFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BoardMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_ar' => $this->faker->name(),
            'name_en' => $this->faker->name(),
            'role_ar' => 'عضو المجلس',
            'role_en' => 'Board Member',
            'bio_ar'  => $this->faker->paragraph(),
            'bio_en'  => $this->faker->paragraph(),
            'photo'   => null,
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
```

- [ ] **Step 2: Create the model**

```php
<?php
// app/Models/BoardMember.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BoardMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar', 'name_en',
        'role_ar', 'role_en',
        'bio_ar',  'bio_en',
        'photo', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function name(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar'
            ? ($this->name_ar ?: $this->name_en)
            : ($this->name_en ?: $this->name_ar);
    }

    public function role(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar'
            ? ($this->role_ar ?: $this->role_en)
            : ($this->role_en ?: $this->role_ar);
    }

    public function bio(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar'
            ? ($this->bio_ar ?: $this->bio_en)
            : ($this->bio_en ?: $this->bio_ar);
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? Storage::disk('public')->url($this->photo) : null;
    }
}
```

- [ ] **Step 3: Verify the factory works**

```bash
php artisan tinker --execute="App\Models\BoardMember::factory()->make()->toArray()"
```

Expected: array with `name_ar`, `name_en`, `role_ar`, `role_en`, `bio_ar`, `bio_en`, `photo`, `sort_order`, `is_active` keys.

- [ ] **Step 4: Commit**

```bash
git add app/Models/BoardMember.php database/factories/BoardMemberFactory.php
git commit -m "feat: add BoardMember model and factory"
```

---

## Task 3: Routes

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Add the import and resource route**

Open `routes/web.php`. Add the import at the top with the other admin controller imports:

```php
use App\Http\Controllers\Admin\BoardMemberController as AdminBoardMemberController;
```

Inside `Route::prefix('admin')->name('admin.')->group(...)`, find the `admin.role:admin` middleware group (the one containing `users`, `contact`, `forms`, `submissions`). Add the board members resource **inside that same group**, right after the users resource:

```php
Route::middleware('admin.role:admin')->group(function () {
    Route::resource('users', AdminUserController::class)->except(['show']);

    Route::resource('board-members', AdminBoardMemberController::class)->except(['show']); // ADD THIS LINE

    Route::get('/contact', ...
    // ... rest of existing routes unchanged
});
```

- [ ] **Step 2: Verify routes are registered**

```bash
php artisan route:list --name=admin.board-members
```

Expected output shows 6 routes: index, create, store, edit, update, destroy.

- [ ] **Step 3: Commit**

```bash
git add routes/web.php
git commit -m "feat: register board-members admin resource routes"
```

---

## Task 4: BoardMemberController + Feature Tests

**Files:**
- Create: `app/Http/Controllers/Admin/BoardMemberController.php`
- Create: `tests/Feature/Admin/BoardMemberControllerTest.php`

- [ ] **Step 1: Write the failing feature test**

```php
<?php
// tests/Feature/Admin/BoardMemberControllerTest.php

namespace Tests\Feature\Admin;

use App\Models\BoardMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BoardMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_guests_are_redirected_from_index(): void
    {
        $this->get(route('admin.board-members.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_index_shows_all_members(): void
    {
        BoardMember::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.board-members.index'))
            ->assertOk()
            ->assertViewIs('admin.board-members.index')
            ->assertViewHas('members');
    }

    public function test_create_shows_empty_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.board-members.create'))
            ->assertOk()
            ->assertViewIs('admin.board-members.create');
    }

    public function test_store_creates_member_without_photo(): void
    {
        $data = [
            'name_ar'    => 'أحمد الرشيد',
            'name_en'    => 'Ahmad Al-Rashid',
            'role_ar'    => 'رئيس المجلس',
            'role_en'    => 'Chairman',
            'bio_ar'     => 'نبذة عن العضو',
            'bio_en'     => 'Member biography.',
            'sort_order' => 1,
            'is_active'  => 1,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.board-members.store'), $data)
            ->assertRedirect(route('admin.board-members.index'));

        $this->assertDatabaseHas('board_members', ['name_en' => 'Ahmad Al-Rashid']);
    }

    public function test_store_uploads_photo(): void
    {
        Storage::fake('public');

        $data = [
            'name_ar'    => 'أحمد',
            'name_en'    => 'Ahmad',
            'role_ar'    => 'رئيس',
            'role_en'    => 'Chair',
            'bio_ar'     => 'نبذة',
            'bio_en'     => 'Bio',
            'sort_order' => 0,
            'is_active'  => 1,
            'photo'      => UploadedFile::fake()->image('photo.jpg', 400, 500),
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.board-members.store'), $data)
            ->assertRedirect(route('admin.board-members.index'));

        $member = BoardMember::first();
        $this->assertNotNull($member->photo);
        Storage::disk('public')->assertExists($member->photo);
    }

    public function test_edit_shows_form_with_member_data(): void
    {
        $member = BoardMember::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.board-members.edit', $member))
            ->assertOk()
            ->assertViewIs('admin.board-members.edit')
            ->assertViewHas('member', $member);
    }

    public function test_update_changes_member_fields(): void
    {
        $member = BoardMember::factory()->create(['name_en' => 'Old Name']);

        $this->actingAs($this->admin)
            ->put(route('admin.board-members.update', $member), [
                'name_ar'    => $member->name_ar,
                'name_en'    => 'New Name',
                'role_ar'    => $member->role_ar,
                'role_en'    => $member->role_en,
                'bio_ar'     => $member->bio_ar,
                'bio_en'     => $member->bio_en,
                'sort_order' => $member->sort_order,
                'is_active'  => 1,
            ])
            ->assertRedirect(route('admin.board-members.index'));

        $this->assertDatabaseHas('board_members', ['id' => $member->id, 'name_en' => 'New Name']);
    }

    public function test_update_replaces_photo_and_deletes_old(): void
    {
        Storage::fake('public');
        $oldPath = 'board-members/old-photo.jpg';
        Storage::disk('public')->put($oldPath, 'fake');
        $member = BoardMember::factory()->create(['photo' => $oldPath]);

        $this->actingAs($this->admin)
            ->put(route('admin.board-members.update', $member), [
                'name_ar'    => $member->name_ar,
                'name_en'    => $member->name_en,
                'role_ar'    => $member->role_ar,
                'role_en'    => $member->role_en,
                'bio_ar'     => $member->bio_ar,
                'bio_en'     => $member->bio_en,
                'sort_order' => $member->sort_order,
                'is_active'  => 1,
                'photo'      => UploadedFile::fake()->image('new.jpg', 400, 500),
            ])
            ->assertRedirect(route('admin.board-members.index'));

        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_destroy_deletes_member_and_photo(): void
    {
        Storage::fake('public');
        $path = 'board-members/photo.jpg';
        Storage::disk('public')->put($path, 'fake');
        $member = BoardMember::factory()->create(['photo' => $path]);

        $this->actingAs($this->admin)
            ->delete(route('admin.board-members.destroy', $member))
            ->assertRedirect(route('admin.board-members.index'));

        $this->assertDatabaseMissing('board_members', ['id' => $member->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_non_admin_cannot_access_board_members(): void
    {
        $subadmin = User::factory()->create(['role' => 'subadmin', 'permissions' => ['news_write']]);

        $this->actingAs($subadmin)
            ->get(route('admin.board-members.index'))
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
php artisan test tests/Feature/Admin/BoardMemberControllerTest.php
```

Expected: All tests FAIL — controller class not found.

- [ ] **Step 3: Create the controller**

```php
<?php
// app/Http/Controllers/Admin/BoardMemberController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BoardMemberController extends Controller
{
    public function index()
    {
        $members = BoardMember::orderBy('sort_order')->get();

        return view('admin.board-members.index', compact('members'));
    }

    public function create()
    {
        return view('admin.board-members.create', [
            'member' => new BoardMember(['sort_order' => 0, 'is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateMember($request);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('board-members', 'public');
        }

        BoardMember::create($data);

        return redirect()->route('admin.board-members.index')
            ->with('status', 'Board member created.');
    }

    public function edit(BoardMember $boardMember)
    {
        return view('admin.board-members.edit', ['member' => $boardMember]);
    }

    public function update(Request $request, BoardMember $boardMember)
    {
        $data = $this->validateMember($request);

        if ($request->hasFile('photo')) {
            if ($boardMember->photo) {
                Storage::disk('public')->delete($boardMember->photo);
            }
            $data['photo'] = $request->file('photo')->store('board-members', 'public');
        }

        $boardMember->fill($data)->save();

        return redirect()->route('admin.board-members.index')
            ->with('status', 'Board member updated.');
    }

    public function destroy(BoardMember $boardMember)
    {
        if ($boardMember->photo) {
            Storage::disk('public')->delete($boardMember->photo);
        }

        $boardMember->delete();

        return redirect()->route('admin.board-members.index')
            ->with('status', 'Board member deleted.');
    }

    protected function validateMember(Request $request): array
    {
        return $request->validate([
            'name_ar'    => ['required', 'string', 'max:255'],
            'name_en'    => ['required', 'string', 'max:255'],
            'role_ar'    => ['required', 'string', 'max:255'],
            'role_en'    => ['required', 'string', 'max:255'],
            'bio_ar'     => ['required', 'string', 'max:1000'],
            'bio_en'     => ['required', 'string', 'max:1000'],
            'photo'      => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active'  => ['boolean'],
        ]);
    }
}
```

- [ ] **Step 4: Run tests — expect view-not-found errors (controller exists, views don't yet)**

```bash
php artisan test tests/Feature/Admin/BoardMemberControllerTest.php
```

Expected: Some tests fail with `View [admin.board-members.index] not found`. The guest redirect test and store/update/destroy tests may pass.

- [ ] **Step 5: Commit controller and tests**

```bash
git add app/Http/Controllers/Admin/BoardMemberController.php tests/Feature/Admin/BoardMemberControllerTest.php
git commit -m "feat: add BoardMemberController and feature tests"
```

---

## Task 5: Admin Views

**Files:**
- Create: `resources/views/admin/board-members/index.blade.php`
- Create: `resources/views/admin/board-members/_form.blade.php`
- Create: `resources/views/admin/board-members/create.blade.php`
- Create: `resources/views/admin/board-members/edit.blade.php`

- [ ] **Step 1: Create the index view**

```blade
{{-- resources/views/admin/board-members/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Board Members — ' . __('admin.title'))
@section('page_title', 'أعضاء المجلس / Board Members')

@section('content')
    <div class="flex items-center justify-end mb-6">
        <a href="{{ route('admin.board-members.create') }}" class="ssbc-admin-btn-primary">+ Add Member</a>
    </div>

    <div class="ssbc-admin-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="ssbc-admin-thead">
                <tr>
                    <th class="text-left px-4 py-3">Photo</th>
                    <th class="text-left px-4 py-3">Name</th>
                    <th class="text-left px-4 py-3">Role (EN)</th>
                    <th class="text-left px-4 py-3">Order</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-right px-4 py-3">Edit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr class="ssbc-admin-row">
                        <td class="px-4 py-3">
                            @if($member->photoUrl())
                                <img src="{{ $member->photoUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
                            @else
                                <div class="h-10 w-10 rounded-full bg-ssbc-green/10 flex items-center justify-center text-ssbc-sage text-xs">–</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-ssbc-dark">{{ $member->name_en }}</p>
                            <p class="text-xs text-ssbc-sage" dir="rtl" lang="ar">{{ $member->name_ar }}</p>
                        </td>
                        <td class="px-4 py-3 text-ssbc-dark/70">{{ $member->role_en }}</td>
                        <td class="px-4 py-3 text-ssbc-dark/70">{{ $member->sort_order }}</td>
                        <td class="px-4 py-3">
                            <span class="ssbc-status-badge {{ $member->is_active ? 'ssbc-status-published' : 'ssbc-status-draft' }}">
                                {{ $member->is_active ? 'Active' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.board-members.edit', $member) }}" class="ssbc-link-gold">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-ssbc-sage">No board members yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
```

- [ ] **Step 2: Create the shared form partial**

```blade
{{-- resources/views/admin/board-members/_form.blade.php --}}
@php
    $isEdit = isset($member) && $member->exists;
    $action = $isEdit
        ? route('admin.board-members.update', $member)
        : route('admin.board-members.store');
@endphp

@if ($errors->any())
    <div class="mb-6 border border-red-300 bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="ssbc-admin-card p-6 space-y-6">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- Bilingual name --}}
    <div class="grid md:grid-cols-2 md:divide-x divide-gray-200">
        <div class="md:pr-6">
            <label class="ssbc-admin-label" for="name_en">Name (English)</label>
            <input id="name_en" name="name_en" type="text" required class="ssbc-admin-input"
                   value="{{ old('name_en', $member->name_en) }}">
        </div>
        <div class="md:pl-6 mt-4 md:mt-0">
            <label class="ssbc-admin-label" for="name_ar">الاسم (عربي)</label>
            <input id="name_ar" name="name_ar" type="text" required class="ssbc-admin-input" dir="rtl" lang="ar"
                   value="{{ old('name_ar', $member->name_ar) }}">
        </div>
    </div>

    {{-- Bilingual role --}}
    <div class="grid md:grid-cols-2 md:divide-x divide-gray-200">
        <div class="md:pr-6">
            <label class="ssbc-admin-label" for="role_en">Role / Title (English)</label>
            <input id="role_en" name="role_en" type="text" required class="ssbc-admin-input"
                   value="{{ old('role_en', $member->role_en) }}">
        </div>
        <div class="md:pl-6 mt-4 md:mt-0">
            <label class="ssbc-admin-label" for="role_ar">المنصب (عربي)</label>
            <input id="role_ar" name="role_ar" type="text" required class="ssbc-admin-input" dir="rtl" lang="ar"
                   value="{{ old('role_ar', $member->role_ar) }}">
        </div>
    </div>

    {{-- Bilingual bio --}}
    <div class="grid md:grid-cols-2 md:divide-x divide-gray-200">
        <div class="md:pr-6">
            <label class="ssbc-admin-label" for="bio_en">Biography (English) <span class="text-ssbc-sage font-normal">shown on hover</span></label>
            <textarea id="bio_en" name="bio_en" rows="4" required class="ssbc-admin-input">{{ old('bio_en', $member->bio_en) }}</textarea>
        </div>
        <div class="md:pl-6 mt-4 md:mt-0">
            <label class="ssbc-admin-label" for="bio_ar">النبذة (عربي) <span class="text-ssbc-sage font-normal">تظهر عند التمرير</span></label>
            <textarea id="bio_ar" name="bio_ar" rows="4" required class="ssbc-admin-input" dir="rtl" lang="ar">{{ old('bio_ar', $member->bio_ar) }}</textarea>
        </div>
    </div>

    {{-- Photo + meta row --}}
    <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
            <label class="ssbc-admin-label" for="photo">Photo <span class="text-ssbc-sage font-normal">(jpg/png/webp, max 2 MB)</span></label>
            @if($isEdit && $member->photoUrl())
                <div class="mb-2">
                    <img src="{{ $member->photoUrl() }}" alt="" class="h-24 border border-gray-200 object-cover">
                    <p class="text-xs text-ssbc-sage mt-1">Upload a new file to replace</p>
                </div>
            @endif
            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="ssbc-admin-input bg-white">
        </div>
        <div>
            <label class="ssbc-admin-label" for="sort_order">Display Order <span class="text-ssbc-sage font-normal">(0 = first)</span></label>
            <input id="sort_order" name="sort_order" type="number" min="0" required class="ssbc-admin-input"
                   value="{{ old('sort_order', $member->sort_order ?? 0) }}">
        </div>
        <div class="flex items-center gap-3 pt-6">
            <input id="is_active" name="is_active" type="checkbox" value="1" class="h-4 w-4 accent-ssbc-green"
                   {{ old('is_active', $member->is_active ?? true) ? 'checked' : '' }}>
            <label for="is_active" class="ssbc-admin-label mb-0 cursor-pointer">Visible on home page</label>
        </div>
    </div>

    <div class="flex items-center justify-between border-t border-gray-200 pt-6">
        <a href="{{ route('admin.board-members.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green">← Back to list</a>
        <button type="submit" class="ssbc-admin-btn-primary">{{ __('admin.save') }}</button>
    </div>
</form>

@if($isEdit)
    <div class="mt-6 flex justify-end">
        @include('partials.admin.confirm-delete', [
            'action'  => route('admin.board-members.destroy', $member),
            'title'   => 'Delete Board Member',
            'message' => 'This permanently removes the member and their photo.',
            'button'  => __('admin.delete'),
        ])
    </div>
@endif
```

- [ ] **Step 3: Create the create view**

```blade
{{-- resources/views/admin/board-members/create.blade.php --}}
@extends('layouts.admin')

@section('title', 'Add Board Member — ' . __('admin.title'))
@section('page_title', 'Add Board Member')

@section('content')
    @include('admin.board-members._form', ['member' => $member])
@endsection
```

- [ ] **Step 4: Create the edit view**

```blade
{{-- resources/views/admin/board-members/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Edit Board Member — ' . __('admin.title'))
@section('page_title', 'Edit: ' . $member->name_en)

@section('content')
    @include('admin.board-members._form', ['member' => $member])
@endsection
```

- [ ] **Step 5: Run the full feature test suite — all tests should now pass**

```bash
php artisan test tests/Feature/Admin/BoardMemberControllerTest.php
```

Expected: All 9 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add resources/views/admin/board-members/
git commit -m "feat: add board member admin views (index, create, edit)"
```

---

## Task 6: Admin Sidebar Navigation

**Files:**
- Modify: `resources/views/layouts/admin.blade.php`

- [ ] **Step 1: Add the board members nav entry**

Open `resources/views/layouts/admin.blade.php`. Find the `$nav` array builder block (around line 29). Locate the first `if ($authUser?->isAdmin())` block that adds the dashboard entry. Add the board-members entry **inside the second `isAdmin()` block** (the one with `forms`, `submissions`, `contact`, `users`), right after the dashboard block and before `forms`:

The current code looks like this:
```php
if ($authUser?->isAdmin()) {
    $nav[] = ['key' => 'dashboard', 'route' => 'admin.dashboard', 'label' => __('admin.dashboard'), 'badge' => null];
}
if ($authUser?->canManageNews()) {
    $nav[] = ['key' => 'news', 'route' => 'admin.news.index', 'label' => __('admin.news'), 'badge' => null];
}
if ($authUser?->isAdmin()) {
    $nav[] = ['key' => 'forms',       'route' => 'admin.forms.index', ...];
    ...
}
```

Add one line inside that **second** `isAdmin()` block, before `forms`:

```php
if ($authUser?->isAdmin()) {
    $nav[] = ['key' => 'forms',       'route' => 'admin.forms.index',       'label' => __('admin.form_builder'), 'badge' => null];
    $nav[] = ['key' => 'submissions', 'route' => 'admin.submissions.index', 'label' => __('admin.submissions'),  'badge' => $unread['submissions']];
    $nav[] = ['key' => 'contact',     'route' => 'admin.contact.index',     'label' => __('admin.contact'),      'badge' => $unread['contact']];
    $nav[] = ['key' => 'users',       'route' => 'admin.users.index',       'label' => 'Admin Users',            'badge' => null];
    $nav[] = ['key' => 'board-members', 'route' => 'admin.board-members.index', 'label' => 'أعضاء المجلس',     'badge' => null]; // ADD THIS
}
```

- [ ] **Step 2: Verify the link appears when logged in as admin**

```bash
php artisan serve
```

Log in at `/admin/login` and confirm "أعضاء المجلس" appears in the sidebar. Click it and confirm it navigates to the board members index.

- [ ] **Step 3: Commit**

```bash
git add resources/views/layouts/admin.blade.php
git commit -m "feat: add board members nav link to admin sidebar"
```

---

## Task 7: Frontend Partial

**Files:**
- Create: `resources/views/pages/partials/board-members.blade.php`

- [ ] **Step 1: Create the partial**

```blade
{{-- resources/views/pages/partials/board-members.blade.php --}}
@php $locale = app()->getLocale(); @endphp

<section class="bg-ssbc-beige">
    <div class="ssbc-container py-20">
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">Board Members</p>
        <h2 class="text-3xl lg:text-4xl font-display font-bold text-ssbc-green leading-tight" dir="rtl" lang="ar">
            أعضاء المجلس
        </h2>

        <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-5">
            @foreach($boardMembers as $member)
                <div class="bg-white rounded-xl overflow-hidden shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg cursor-pointer"
                     x-data="{ open: false }"
                     @mouseenter="open = true"
                     @mouseleave="open = false">

                    {{-- Photo + bio overlay --}}
                    <div class="relative overflow-hidden" style="aspect-ratio:3/4;">
                        @if($member->photoUrl())
                            <img src="{{ $member->photoUrl() }}"
                                 alt="{{ $member->name() }}"
                                 class="w-full h-full object-cover object-top">
                        @else
                            <div class="w-full h-full bg-ssbc-green/10 flex items-center justify-center">
                                <svg class="w-16 h-16 text-ssbc-green/20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                                </svg>
                            </div>
                        @endif

                        {{-- Bio overlay — slides up on hover (desktop) or tap (mobile) --}}
                        <div class="absolute left-0 right-0 text-white p-4 transition-all duration-300"
                             style="background:rgba(21,62,53,0.93);"
                             :class="open ? 'bottom-0' : '-bottom-full'">
                            <p class="text-xs font-bold uppercase tracking-wide mb-2" style="color:#daa900;">
                                {{ $locale === 'ar' ? 'نبذة مختصرة' : 'About' }}
                            </p>
                            <p class="text-xs leading-relaxed text-white/90">{{ $member->bio() }}</p>
                        </div>
                    </div>

                    {{-- Name + role — tap here on mobile to toggle bio --}}
                    <div class="text-center px-3 py-3 border-t-2 border-ssbc-beige"
                         @click="open = !open">
                        <p class="font-display font-bold text-sm text-ssbc-dark">{{ $member->name() }}</p>
                        <p class="text-xs text-ssbc-sage mt-1">{{ $member->role() }}</p>
                    </div>

                </div>
            @endforeach
        </div>
    </div>
</section>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/pages/partials/board-members.blade.php
git commit -m "feat: add board members home page partial"
```

---

## Task 8: Home Page Integration

**Files:**
- Modify: `app/Http/Controllers/HomeController.php`
- Modify: `resources/views/pages/home.blade.php`

- [ ] **Step 1: Update HomeController to pass board members**

Open `app/Http/Controllers/HomeController.php`. The current `index` method is:

```php
public function index(string $locale)
{
    $posts = NewsPost::published()->take(3)->get();

    return view('pages.home', compact('posts'));
}
```

Replace it with:

```php
public function index(string $locale)
{
    $posts        = NewsPost::published()->take(3)->get();
    $boardMembers = \App\Models\BoardMember::active()->get();

    return view('pages.home', compact('posts', 'boardMembers'));
}
```

- [ ] **Step 2: Include the partial in the home page view**

Open `resources/views/pages/home.blade.php`. Find the comment `{{-- 5. Latest News --}}` (around line 126). Insert the board members include **immediately before** that section:

```blade
{{-- 4b. Board Members --}}
@include('pages.partials.board-members')

{{-- 5. Latest News --}}
<section class="bg-ssbc-beige">
```

- [ ] **Step 3: Run the full test suite**

```bash
php artisan test
```

Expected: All tests pass. If any fail, fix before continuing.

- [ ] **Step 4: Start dev server and verify visually**

```bash
php artisan serve
```

Visit `http://localhost:8000/ar` and `http://localhost:8000/en`. Confirm:

- Board members section appears between "Strategic Pillars" and "Latest News"
- When no members exist in the database, the section is empty (no errors)
- Hover over any card (desktop) → bio overlay slides up
- On mobile, tap the name/role area → bio overlay toggles

- [ ] **Step 5: Upload the 5 member photos via admin**

1. Go to `http://localhost:8000/admin/board-members/create`
2. Create 5 board members using the photos provided, setting sort_order 1–5
3. Verify each card appears on the home page with the correct photo, name, role, and bio

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/HomeController.php resources/views/pages/home.blade.php
git commit -m "feat: integrate board members section into home page"
```

---

## Self-Review Checklist

- [x] Migration covers all spec fields (name/role/bio bilingual, photo, sort_order, is_active)
- [x] Model has `scopeActive`, locale helpers (`name()`, `role()`, `bio()`), `photoUrl()`
- [x] Controller validates all fields; deletes old photo on update and destroy
- [x] Admin views follow `NewsPost` patterns (bilingual grid, `ssbc-admin-*` classes, confirm-delete partial)
- [x] Routes are inside `admin.role:admin` middleware — no subadmin access
- [x] Sidebar nav key `board-members` matches route prefix, active state works
- [x] Frontend partial uses Alpine `@mouseenter`/`@mouseleave` for desktop hover + `@click` on footer for mobile tap
- [x] `$boardMembers` passed from `HomeController` using `BoardMember::active()->get()`
- [x] Partial inserted between sections 4 and 5 in `home.blade.php`
- [x] Method names consistent: `name()`, `role()`, `bio()`, `photoUrl()`, `scopeActive()` used identically across model, tests, partial

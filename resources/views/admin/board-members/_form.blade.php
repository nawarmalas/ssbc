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
    @if($isEdit)
        @method('PUT')
    @endif

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
                   @checked(old('is_active', $member->is_active ?? true))>
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

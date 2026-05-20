@php
    $isEdit = isset($sector) && $sector->exists;
    $action = $isEdit
        ? route('admin.sectors.update', $sector)
        : route('admin.sectors.store');
@endphp

@if ($errors->any())
    <div class="mb-6 border border-red-300 bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="ssbc-admin-card p-6 space-y-6">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    {{-- Bilingual name --}}
    <div class="grid md:grid-cols-2 md:divide-x divide-gray-200">
        <div class="md:pr-6">
            <label class="ssbc-admin-label" for="name_en">Sector Name (English)</label>
            <input id="name_en" name="name_en" type="text" required class="ssbc-admin-input"
                   value="{{ old('name_en', $sector->name_en) }}">
        </div>
        <div class="md:pl-6 mt-4 md:mt-0">
            <label class="ssbc-admin-label" for="name_ar">اسم القطاع (عربي)</label>
            <input id="name_ar" name="name_ar" type="text" required class="ssbc-admin-input" dir="rtl" lang="ar"
                   value="{{ old('name_ar', $sector->name_ar) }}">
        </div>
    </div>

    {{-- Bilingual description --}}
    <div class="grid md:grid-cols-2 md:divide-x divide-gray-200">
        <div class="md:pr-6">
            <label class="ssbc-admin-label" for="description_en">Description (English)</label>
            <textarea id="description_en" name="description_en" rows="4" required class="ssbc-admin-input">{{ old('description_en', $sector->description_en) }}</textarea>
        </div>
        <div class="md:pl-6 mt-4 md:mt-0">
            <label class="ssbc-admin-label" for="description_ar">الوصف (عربي)</label>
            <textarea id="description_ar" name="description_ar" rows="4" required class="ssbc-admin-input" dir="rtl" lang="ar">{{ old('description_ar', $sector->description_ar) }}</textarea>
        </div>
    </div>

    {{-- Meta row --}}
    <div class="grid md:grid-cols-3 gap-6">
        <div>
            <label class="ssbc-admin-label" for="sort_order">Display Order <span class="text-ssbc-sage font-normal">(0 = first)</span></label>
            <input id="sort_order" name="sort_order" type="number" min="0" required class="ssbc-admin-input"
                   value="{{ old('sort_order', $sector->sort_order ?? 0) }}">
        </div>
        <div class="flex items-center gap-3 pt-6">
            <input id="is_active" name="is_active" type="checkbox" value="1" class="h-4 w-4 accent-ssbc-green"
                   @checked(old('is_active', $sector->is_active ?? true))>
            <label for="is_active" class="ssbc-admin-label mb-0 cursor-pointer">Visible on home page</label>
        </div>
    </div>

    <div class="flex items-center justify-between border-t border-gray-200 pt-6">
        <a href="{{ route('admin.sectors.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green">Back to list</a>
        <button type="submit" class="ssbc-admin-btn-primary">{{ __('admin.save') }}</button>
    </div>
</form>

@if($isEdit)
    <div class="mt-6 flex justify-end">
        @include('partials.admin.confirm-delete', [
            'action'  => route('admin.sectors.destroy', $sector),
            'title'   => 'Delete Sector',
            'message' => 'This permanently removes the sector from the home page.',
            'button'  => __('admin.delete'),
        ])
    </div>
@endif

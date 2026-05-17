@php
    $isEdit = isset($post) && $post->exists;
    $action = $isEdit ? route('admin.news.update', $post) : route('admin.news.store');
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
        @method('PATCH')
    @endif

    <div class="grid md:grid-cols-2 gap-6">
        <div>
            <label class="ssbc-label" for="title_en">{{ __('admin.news_title_en') }}</label>
            <input id="title_en" name="title_en" type="text" required class="ssbc-input"
                   value="{{ old('title_en', $post->title_en) }}">
        </div>
        <div>
            <label class="ssbc-label" for="title_ar">{{ __('admin.news_title_ar') }}</label>
            <input id="title_ar" name="title_ar" type="text" required class="ssbc-input" dir="rtl" lang="ar"
                   value="{{ old('title_ar', $post->title_ar) }}">
        </div>

        <div>
            <label class="ssbc-label" for="excerpt_en">{{ __('admin.news_excerpt_en') }}</label>
            <textarea id="excerpt_en" name="excerpt_en" rows="3" class="ssbc-input">{{ old('excerpt_en', $post->excerpt_en) }}</textarea>
        </div>
        <div>
            <label class="ssbc-label" for="excerpt_ar">{{ __('admin.news_excerpt_ar') }}</label>
            <textarea id="excerpt_ar" name="excerpt_ar" rows="3" class="ssbc-input" dir="rtl" lang="ar">{{ old('excerpt_ar', $post->excerpt_ar) }}</textarea>
        </div>

        <div>
            <label class="ssbc-label" for="content_en">{{ __('admin.news_content_en') }}</label>
            <textarea id="content_en" name="content_en" rows="12" class="ssbc-input font-mono text-xs">{{ old('content_en', $post->content_en) }}</textarea>
        </div>
        <div>
            <label class="ssbc-label" for="content_ar">{{ __('admin.news_content_ar') }}</label>
            <textarea id="content_ar" name="content_ar" rows="12" class="ssbc-input font-mono text-xs" dir="rtl" lang="ar">{{ old('content_ar', $post->content_ar) }}</textarea>
        </div>

        <div>
            <label class="ssbc-label" for="category">{{ __('admin.news_category') }}</label>
            <input id="category" name="category" type="text" class="ssbc-input"
                   value="{{ old('category', $post->category) }}">
        </div>

        <div>
            <label class="ssbc-label" for="status">{{ __('admin.status') }}</label>
            <select id="status" name="status" required class="ssbc-input">
                @foreach(['draft','published'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $post->status) === $s)>{{ __('admin.status_'.$s) }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="ssbc-label" for="published_at">{{ __('admin.news_published_at') }}</label>
            <input id="published_at" name="published_at" type="datetime-local" class="ssbc-input"
                   value="{{ old('published_at', $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : '') }}">
        </div>

        <div class="md:col-span-2">
            <label class="ssbc-label" for="featured_image">{{ __('admin.news_featured_image') }}</label>
            @if($isEdit && $post->featured_image)
                <div class="mb-3">
                    <img src="{{ $post->featuredImageUrl() }}" alt="" class="h-32 border border-ssbc-green/15">
                </div>
            @endif
            <input id="featured_image" name="featured_image" type="file" accept="image/*" class="ssbc-input bg-white">
        </div>
    </div>

    <div class="flex items-center justify-between border-t border-ssbc-green/15 pt-6">
        <a href="{{ route('admin.news.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green">{{ __('admin.back') }}</a>

        <div class="flex items-center gap-4">
            @if($isEdit)
                <form method="POST" action="{{ route('admin.news.destroy', $post) }}"
                      onsubmit="return confirm('{{ __('admin.confirm_delete') }}');">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-red-700 hover:underline">{{ __('admin.delete') }}</button>
                </form>
            @endif
            <button type="submit" class="ssbc-btn-primary">{{ __('admin.save') }}</button>
        </div>
    </div>
</form>

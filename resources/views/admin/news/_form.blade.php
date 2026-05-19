@php
    $isEdit = isset($post) && $post->exists;
    $action = $isEdit ? route('admin.news.update', $post) : route('admin.news.store');
    // Users who can write drafts but not publish (the old "news subadmin"
    // bucket) need the status/published_at controls hidden.
    $isNewsSubadmin = auth()->user() && ! auth()->user()->canPublishNews();
    $publishedAtValue = $post->status === 'published' && $post->published_at
        ? $post->published_at->copy()->timezone(config('app.admin_timezone'))->format('Y-m-d\TH:i')
        : '';
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

    {{-- Bilingual title --}}
    <div class="grid md:grid-cols-2 md:divide-x divide-gray-200 md:gap-0">
        <div class="md:pr-6">
            <label class="ssbc-admin-label" for="title_en">{{ __('admin.news_title_en') }}</label>
            <input id="title_en" name="title_en" type="text" required class="ssbc-admin-input"
                   value="{{ old('title_en', $post->title_en) }}">
        </div>
        <div class="md:pl-6 mt-4 md:mt-0">
            <label class="ssbc-admin-label" for="title_ar">{{ __('admin.news_title_ar') }}</label>
            <input id="title_ar" name="title_ar" type="text" required class="ssbc-admin-input" dir="rtl" lang="ar"
                   value="{{ old('title_ar', $post->title_ar) }}">
        </div>
    </div>

    {{-- Bilingual excerpt --}}
    <div class="grid md:grid-cols-2 md:divide-x divide-gray-200">
        <div class="md:pr-6">
            <label class="ssbc-admin-label" for="excerpt_en">{{ __('admin.news_excerpt_en') }}</label>
            <textarea id="excerpt_en" name="excerpt_en" rows="3" class="ssbc-admin-input">{{ old('excerpt_en', $post->excerpt_en) }}</textarea>
        </div>
        <div class="md:pl-6 mt-4 md:mt-0">
            <label class="ssbc-admin-label" for="excerpt_ar">{{ __('admin.news_excerpt_ar') }}</label>
            <textarea id="excerpt_ar" name="excerpt_ar" rows="3" class="ssbc-admin-input" dir="rtl" lang="ar">{{ old('excerpt_ar', $post->excerpt_ar) }}</textarea>
        </div>
    </div>

    {{-- Bilingual content --}}
    <div class="grid md:grid-cols-2 md:divide-x divide-gray-200">
        <div class="md:pr-6">
            <label class="ssbc-admin-label" for="content_en">{{ __('admin.news_content_en') }}</label>
            <textarea id="content_en" name="content_en" rows="12" class="ssbc-admin-input font-mono text-xs">{{ old('content_en', $post->content_en) }}</textarea>
        </div>
        <div class="md:pl-6 mt-4 md:mt-0">
            <label class="ssbc-admin-label" for="content_ar">{{ __('admin.news_content_ar') }}</label>
            <textarea id="content_ar" name="content_ar" rows="12" class="ssbc-admin-input font-mono text-xs" dir="rtl" lang="ar">{{ old('content_ar', $post->content_ar) }}</textarea>
        </div>
    </div>

    {{-- Metadata row --}}
    <div class="grid md:grid-cols-3 gap-6">
        <div>
            <label class="ssbc-admin-label" for="category">{{ __('admin.news_category') }}</label>
            <input id="category" name="category" type="text" class="ssbc-admin-input"
                   value="{{ old('category', $post->category) }}">
        </div>

        @if($isNewsSubadmin)
            <input type="hidden" name="status" value="draft">
            <div class="md:col-span-2">
                <label class="ssbc-admin-label">{{ __('admin.status') }}</label>
                <div class="border border-ssbc-green/15 bg-ssbc-green/5 px-3 py-2 text-sm text-ssbc-green">
                    Draft. A main admin must publish this before it appears on the public site.
                </div>
            </div>
        @else
            <div>
                <label class="ssbc-admin-label" for="status">{{ __('admin.status') }}</label>
                <select id="status" name="status" required class="ssbc-admin-input">
                    @foreach(['draft','published'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $post->status) === $s)>{{ __('admin.status_'.$s) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="ssbc-admin-label" for="published_at">{{ __('admin.news_published_at') }}</label>
                <input id="published_at" name="published_at" type="datetime-local" class="ssbc-admin-input"
                       value="{{ old('published_at', $publishedAtValue) }}">
                <p class="text-xs text-ssbc-sage mt-1">
                    Leave blank when publishing to make the post visible immediately. Scheduled times use the admin timezone; future dates will keep it hidden until that time.
                </p>
            </div>
        @endif
    </div>

    {{-- Featured image --}}
    <div>
        <label class="ssbc-admin-label" for="featured_image">{{ __('admin.news_featured_image') }}</label>
        @if($isEdit && $post->featured_image)
            <div class="mb-3">
                <img src="{{ $post->featuredImageUrl() }}" alt="" class="h-32 border border-gray-200">
            </div>
        @endif
        <input id="featured_image" name="featured_image" type="file" accept="image/*" class="ssbc-admin-input bg-white">
    </div>

    <div class="flex items-center justify-between border-t border-gray-200 pt-6">
        <a href="{{ route('admin.news.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green">← Back to list</a>
        <button type="submit" class="ssbc-admin-btn-primary">{{ __('admin.save') }}</button>
    </div>
</form>

{{-- Delete sits OUTSIDE the edit form to avoid nested-form parser issues. --}}
@if($isEdit && (! $isNewsSubadmin || $post->status === 'draft'))
    <div class="mt-6 flex justify-end">
        @include('partials.admin.confirm-delete', [
            'action'  => route('admin.news.destroy', $post),
            'title'   => __('admin.confirm_delete'),
            'message' => 'This permanently removes the post.',
            'button'  => __('admin.delete'),
        ])
    </div>
@endif

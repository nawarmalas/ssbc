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

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="ssbc-admin-card p-6 space-y-6" id="news-form">
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

    {{-- Bilingual content (TipTap editor targets these textareas via app.js) --}}
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
        <p class="text-xs text-ssbc-sage mt-1">Used as the article thumbnail on listing pages. Upload a new file to replace the current one.</p>
    </div>

    {{-- Gallery images --}}
    <div>
        <label class="ssbc-admin-label">Additional Photos (Gallery)</label>

        @if($isEdit && $post->images->isNotEmpty())
            <p class="text-xs text-ssbc-sage mb-3">Check images to remove them when you save.</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mb-4">
                @foreach($post->images as $img)
                    <div class="relative border border-gray-200 bg-gray-50">
                        <img src="{{ $img->url() }}" alt="" class="w-full h-24 object-cover">
                        <label class="flex items-center gap-1.5 px-2 py-1.5 bg-white/90 text-xs text-red-700 cursor-pointer hover:bg-red-50">
                            <input type="checkbox" name="delete_image_ids[]" value="{{ $img->id }}" class="accent-red-600">
                            Remove
                        </label>
                    </div>
                @endforeach
            </div>
        @endif

        <input id="gallery_images" name="gallery_images[]" type="file" accept="image/*" multiple
               class="ssbc-admin-input bg-white">
        <p class="text-xs text-ssbc-sage mt-1">Select multiple files at once. Maximum 10 images, 8 MB each. These appear as a photo gallery below the article body.</p>
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

@push('scripts')
{{-- ═══════════════════════════════════════════════════════ --}}
{{-- CKEditor 5 — Word-style editor. CDN only, zero build.  --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<style>
  .ck-editor__editable_inline { min-height: 420px !important; font-size: 14px !important; line-height: 1.8 !important; padding: 20px 24px !important; color: #111827 !important; }
  .ck-excerpt .ck-editor__editable_inline { min-height: 140px !important; }
  .ck-arabic .ck-editor__editable_inline { direction: rtl !important; text-align: right !important; font-family: 'Cairo', 'Noto Sans Arabic', sans-serif !important; }
  .ck-editor__editable_inline table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
  .ck-editor__editable_inline td, .ck-editor__editable_inline th { border: 1px solid #d1d5db; padding: 8px 12px; min-width: 60px; }
  .ck-editor__editable_inline th { background: #f3f4f6; font-weight: 600; }
  .ck.ck-toolbar { background: #f5f6f7 !important; border-color: #d1d5db !important; }
  .ck.ck-button.ck-on { background: #1a3a2a !important; color: #fff !important; }
  .ck.ck-button:hover:not(.ck-disabled) { background: #e5e7eb !important; }
  .ssbc-hidden-ta { display: none !important; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var fullToolbar = { items: ['heading','|','fontSize','fontFamily','|','bold','italic','underline','strikethrough','|','fontColor','fontBackgroundColor','|','alignment','|','bulletedList','numberedList','outdent','indent','|','link','insertTable','blockQuote','horizontalLine','|','undo','redo','|','removeFormat'] }
  var excerptToolbar = { items: ['bold','italic','underline','|','fontSize','fontColor','|','alignment','|','bulletedList','numberedList','|','link','|','undo','redo'] }
  var fontSizes = { options: [10,11,12,13,14,'default',16,18,20,22,24,28,32,36,48,72], supportAllValues: true }
  var fontFamilies = { options: ['default','Arial, Helvetica, sans-serif','Georgia, serif','Times New Roman, Times, serif','Courier New, Courier, monospace','Trebuchet MS, Helvetica, sans-serif','Verdana, Geneva, sans-serif','Cairo, Noto Sans Arabic, sans-serif'], supportAllValues: true }
  var tableConfig = { contentToolbar: ['tableColumn','tableRow','mergeTableCells','tableProperties','tableCellProperties'] }
  var headingConfig = { options: [{ model:'paragraph', title:'Normal text', class:'ck-heading_paragraph' },{ model:'heading1', view:'h1', title:'Heading 1', class:'ck-heading_heading1' },{ model:'heading2', view:'h2', title:'Heading 2', class:'ck-heading_heading2' },{ model:'heading3', view:'h3', title:'Heading 3', class:'ck-heading_heading3' },{ model:'heading4', view:'h4', title:'Heading 4', class:'ck-heading_heading4' }] }

  function initEditor(textareaId, options) {
    var ta = document.getElementById(textareaId)
    if (!ta) { console.warn('SSBC Editor: textarea not found → id:', textareaId); return }
    var wrapper = document.createElement('div')
    wrapper.className = options.wrapperClass || ''
    ta.parentNode.insertBefore(wrapper, ta.nextSibling)
    ta.classList.add('ssbc-hidden-ta')
    ClassicEditor.create(wrapper, {
      initialData: ta.value || '',
      toolbar: options.toolbar || fullToolbar,
      fontSize: fontSizes,
      fontFamily: fontFamilies,
      table: tableConfig,
      heading: headingConfig,
      language: { ui: options.lang || 'en', content: options.lang || 'en' },
    }).then(function (editor) {
      editor.model.document.on('change:data', function () { ta.value = editor.getData() })
      var form = ta.closest('form')
      if (form) { form.addEventListener('submit', function () { ta.value = editor.getData() }) }
      if (options.rtl) {
        editor.editing.view.change(function (writer) {
          writer.setAttribute('dir', 'rtl', editor.editing.view.document.getRoot())
        })
      }
    }).catch(function (error) {
      console.error('CKEditor failed on #' + textareaId, error)
      ta.classList.remove('ssbc-hidden-ta')
      if (wrapper) wrapper.remove()
    })
  }

  initEditor('content_en', { toolbar: fullToolbar, lang: 'en', rtl: false, wrapperClass: 'ck-content-en' })
  initEditor('content_ar', { toolbar: fullToolbar, lang: 'ar', rtl: true,  wrapperClass: 'ck-arabic' })
  initEditor('excerpt_en', { toolbar: excerptToolbar, lang: 'en', rtl: false, wrapperClass: 'ck-excerpt' })
  initEditor('excerpt_ar', { toolbar: excerptToolbar, lang: 'ar', rtl: true,  wrapperClass: 'ck-excerpt ck-arabic' })
})
</script>
@endpush

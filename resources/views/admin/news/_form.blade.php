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
        <input id="featured_image" type="file" accept="image/jpeg,image/png,image/webp" class="ssbc-admin-input bg-white"
               data-async-single data-staged-target="featured_image_staged" data-status="featured-upload-status" data-preview="featured-upload-preview">
        <input type="hidden" name="featured_image_staged" id="featured_image_staged" value="{{ old('featured_image_staged') }}">
        @if(old('featured_image_staged'))
            <p class="text-xs text-ssbc-green mt-1">A featured image you uploaded earlier is still attached and will be used on save.</p>
        @endif
        <div id="featured-upload-status" class="mt-2 hidden"></div>
        <div id="featured-upload-preview" class="mt-2"></div>
        <p class="text-xs text-ssbc-sage mt-1">Used as the article thumbnail on listing pages. Upload a new file to replace the current one. Max 16 MB — the image uploads in the background as soon as you pick it.</p>
    </div>

    {{-- Content Blocks --}}
    <div>
        <label class="ssbc-admin-label">Content Blocks</label>
        <p class="text-xs text-ssbc-sage mb-4">Build the article body as ordered text and image blocks. EN and AR blocks are fully independent.</p>

        <div class="cb-columns">
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-ssbc-green border-b border-gray-200 pb-2 mb-4">English</h3>
                <div id="cb-panel-en">
                    @include('admin.news._content_blocks', [
                        'blocks' => $isEdit ? $post->contentBlocks->where('locale', 'en')->values() : collect(),
                        'locale' => 'en',
                    ])
                </div>
            </div>
            <div class="min-w-0" dir="rtl" lang="ar">
                <h3 class="text-sm font-semibold text-ssbc-green border-b border-gray-200 pb-2 mb-4 text-right">Arabic (عربي)</h3>
                <div id="cb-panel-ar">
                    @include('admin.news._content_blocks', [
                        'blocks' => $isEdit ? $post->contentBlocks->where('locale', 'ar')->values() : collect(),
                        'locale' => 'ar',
                    ])
                </div>
            </div>
        </div>
    </div>

    {{-- Gallery images --}}
    <div>
        <label class="ssbc-admin-label">Additional Photos (Gallery)</label>

        @if($isEdit && $post->images->isNotEmpty())
            <p class="text-xs text-ssbc-sage mb-3">Check images to remove them when you save.</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mb-4">
                @foreach($post->images as $img)
                    <div class="relative border border-gray-200 bg-gray-50 existing-gallery-item">
                        <img src="{{ $img->url() }}" alt="" class="w-full h-24 object-cover">
                        <label class="flex items-center gap-1.5 px-2 py-1.5 bg-white/90 text-xs text-red-700 cursor-pointer hover:bg-red-50">
                            <input type="checkbox" name="delete_image_ids[]" value="{{ $img->id }}" class="accent-red-600">
                            Remove
                        </label>
                    </div>
                @endforeach
            </div>
        @endif

        <input id="gallery_picker" type="file" accept="image/jpeg,image/png,image/webp" multiple
               class="ssbc-admin-input bg-white">
        <p class="text-xs text-ssbc-sage mt-1">Select multiple files at once. Maximum 10 images, 16 MB each. Each photo is compressed and uploaded in the background — you can keep editing while they upload. These appear as a photo gallery below the article body.</p>

        <div id="gallery-errors" class="mt-2 space-y-1"></div>

        <div id="gallery-overall" class="mt-3 hidden">
            <div class="flex items-center justify-between text-xs text-ssbc-sage mb-1">
                <span>Uploading photos…</span>
                <span id="gallery-overall-label">0%</span>
            </div>
            <div class="h-1.5 bg-gray-200 overflow-hidden">
                <div id="gallery-overall-bar" class="h-full bg-ssbc-green transition-all" style="width:0%"></div>
            </div>
        </div>

        <ul id="gallery-items" class="mt-3 space-y-2"></ul>

        {{-- Keep already-uploaded photos attached when validation sends the form back --}}
        @php $oldStaged = array_filter((array) old('gallery_staged', [])); @endphp
        @if($oldStaged)
            <p class="text-xs text-ssbc-green mt-2">{{ count($oldStaged) }} photo(s) you uploaded earlier are still attached and will be saved with the post.</p>
            @foreach($oldStaged as $staged)
                <input type="hidden" name="gallery_staged[]" value="{{ $staged }}">
            @endforeach
        @endif
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
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
  /* EN/AR content blocks side by side; stack below 900px */
  .cb-columns { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
  @media (min-width: 900px) {
    .cb-columns { grid-template-columns: 1fr 1fr; }
    .cb-columns > div:first-child { padding-right: 1.5rem; border-right: 1px solid #e5e7eb; }
  }
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

  // ── Content Blocks ────────────────────────────────────────────────────
  var blockEditors = {}
  var blockCounters = {
    en: parseInt((document.getElementById('blocks-section-en') || {}).dataset.count || '0'),
    ar: parseInt((document.getElementById('blocks-section-ar') || {}).dataset.count || '0')
  }
  var initializedBlockTAs = new Set()

  function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') }
  function escAttr(s) { return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;') }

  function initBlockEditor(taId, isRTL) {
    if (initializedBlockTAs.has(taId)) return
    var ta = document.getElementById(taId)
    if (!ta) return
    initializedBlockTAs.add(taId)
    var wrapper = document.createElement('div')
    wrapper.className = isRTL ? 'ck-arabic' : ''
    ta.parentNode.insertBefore(wrapper, ta.nextSibling)
    ta.classList.add('ssbc-hidden-ta')
    ClassicEditor.create(wrapper, {
      initialData: ta.value || '',
      toolbar: fullToolbar, fontSize: fontSizes, fontFamily: fontFamilies,
      table: tableConfig, heading: headingConfig,
      language: { ui: isRTL ? 'ar' : 'en', content: isRTL ? 'ar' : 'en' },
    }).then(function(editor) {
      blockEditors[taId] = editor
      editor.model.document.on('change:data', function() { ta.value = editor.getData() })
      if (isRTL) {
        editor.editing.view.change(function(writer) {
          writer.setAttribute('dir', 'rtl', editor.editing.view.document.getRoot())
        })
      }
      var form = ta.closest('form')
      if (form) form.addEventListener('submit', function() { ta.value = editor.getData() }, { once: true })
    }).catch(function(err) {
      console.error('Block CKEditor failed #' + taId, err)
      ta.classList.remove('ssbc-hidden-ta')
      wrapper.remove()
    })
  }

  function ensureBlockEditors(locale) {
    var isAr = locale === 'ar'
    document.querySelectorAll('#blocks-list-' + locale + ' .block-textarea').forEach(function(ta) {
      initBlockEditor(ta.id, isAr)
    })
  }

  function updateSortOrders(locale) {
    document.querySelectorAll('#blocks-list-' + locale + ' .block-card').forEach(function(card, i) {
      var inp = card.querySelector('.block-sort-order')
      if (inp) inp.value = i
    })
  }

  function buildTextBlockHtml(locale, slot) {
    var isAr = locale === 'ar'
    var p = 'blocks_' + locale + '[' + slot + ']'
    var taId = 'block-ta-' + locale + '-' + slot
    return '<div class="block-card border border-gray-200 bg-gray-50 p-4" data-type="text" data-slot="' + slot + '">' +
      '<input type="hidden" name="' + p + '[block_id]" value="">' +
      '<input type="hidden" name="' + p + '[type]" value="text">' +
      '<input type="hidden" name="' + p + '[sort_order]" class="block-sort-order" value="0">' +
      '<div class="flex items-center justify-between mb-3">' +
        '<span class="text-xs font-semibold uppercase px-2 py-0.5 bg-blue-100 text-blue-700">Text</span>' +
        '<div class="flex items-center gap-3">' +
          '<span class="drag-handle cursor-grab text-gray-400 hover:text-gray-600 select-none text-lg leading-none" title="Drag to reorder">⠿</span>' +
          '<button type="button" class="block-remove-btn text-xs text-red-600 hover:text-red-800">Remove</button>' +
        '</div>' +
      '</div>' +
      '<textarea name="' + p + '[content]" id="' + taId + '" class="ssbc-admin-input font-mono text-xs block-textarea"' +
        (isAr ? ' dir="rtl" lang="ar"' : '') + ' rows="6"></textarea>' +
    '</div>'
  }

  function buildImageBlockHtml(locale, slot) {
    var p   = 'blocks_' + locale + '[' + slot + ']'
    var pvId = 'block-img-preview-' + locale + '-' + slot
    return '<div class="block-card border border-gray-200 bg-gray-50 p-4" data-type="image" data-slot="' + slot + '">' +
      '<input type="hidden" name="' + p + '[block_id]" value="">' +
      '<input type="hidden" name="' + p + '[type]" value="image">' +
      '<input type="hidden" name="' + p + '[sort_order]" class="block-sort-order" value="0">' +
      '<div class="flex items-center justify-between mb-3">' +
        '<span class="text-xs font-semibold uppercase px-2 py-0.5 bg-emerald-100 text-emerald-700">Image</span>' +
        '<div class="flex items-center gap-3">' +
          '<span class="drag-handle cursor-grab text-gray-400 hover:text-gray-600 select-none text-lg leading-none" title="Drag to reorder">⠿</span>' +
          '<button type="button" class="block-remove-btn text-xs text-red-600 hover:text-red-800">Remove</button>' +
        '</div>' +
      '</div>' +
      '<div class="space-y-3">' +
        '<div id="' + pvId + '" class="block-img-preview"></div>' +
        '<div><label class="text-xs text-ssbc-sage block mb-1">Upload image</label>' +
          '<input type="file" accept="image/jpeg,image/png,image/webp" class="ssbc-admin-input bg-white block-img-input" data-preview="' + pvId + '">' +
          '<input type="hidden" name="' + p + '[staged_image]" class="block-staged-input" value="">' +
          '<div class="block-img-status mt-1 hidden"></div>' +
        '</div>' +
        '<div class="grid grid-cols-2 gap-3">' +
          '<div><label class="text-xs text-ssbc-sage block mb-1">Caption (English)</label>' +
            '<input type="text" name="' + p + '[caption_en]" class="ssbc-admin-input text-sm" dir="ltr"></div>' +
          '<div><label class="text-xs text-ssbc-sage block mb-1">Caption (Arabic)</label>' +
            '<input type="text" name="' + p + '[caption_ar]" class="ssbc-admin-input text-sm" dir="rtl"></div>' +
        '</div>' +
      '</div>' +
    '</div>'
  }

  // Add block buttons (event delegation on each section)
  ;['en','ar'].forEach(function(locale) {
    var section = document.getElementById('blocks-section-' + locale)
    if (!section) return

    section.addEventListener('click', function(e) {
      // Remove button
      if (e.target.classList.contains('block-remove-btn')) {
        var card = e.target.closest('.block-card')
        if (!card) return
        var slot = card.dataset.slot
        var key = 'block-ta-' + locale + '-' + slot
        if (blockEditors[key]) { blockEditors[key].destroy(); delete blockEditors[key] }
        initializedBlockTAs.delete(key)
        card.remove()
        updateSortOrders(locale)
      }
      // Add buttons
      if (e.target.classList.contains('add-block-btn')) {
        var type = e.target.dataset.type
        var slot2 = blockCounters[locale]++
        var html = type === 'text' ? buildTextBlockHtml(locale, slot2) : buildImageBlockHtml(locale, slot2)
        document.getElementById('blocks-list-' + locale).insertAdjacentHTML('beforeend', html)
        if (type === 'text') initBlockEditor('block-ta-' + locale + '-' + slot2, locale === 'ar')
        updateSortOrders(locale)
      }
    })

    // SortableJS
    if (typeof Sortable !== 'undefined') {
      Sortable.create(document.getElementById('blocks-list-' + locale), {
        animation: 150,
        handle: '.drag-handle',
        onEnd: function() { updateSortOrders(locale) }
      })
    }
  })

  // Init block editors for both locales (EN/AR columns are side by side now)
  ensureBlockEditors('en')
  ensureBlockEditors('ar')

  // ── Async image uploads ───────────────────────────────────────────────
  // Every image (featured, gallery, content-block) is compressed in the
  // browser and uploaded in its own request via the staging endpoint, so the
  // final Save POST carries only text fields and staged paths.
  var form = document.getElementById('news-form')
  var UPLOAD_URL = '{{ route('admin.news.upload-image') }}'
  var CSRF = form.querySelector('input[name="_token"]').value
  var MAX_BYTES = 16 * 1024 * 1024
  var MAX_GALLERY = 10
  var COMPRESS_MIN_BYTES = 500 * 1024
  var MAX_EDGE = 1920
  var QUALITY = 0.82

  // Shared queue: at most 2 uploads in flight — the host is resource-limited
  // shared hosting, so firing 10 parallel requests would recreate the 503s.
  var queue = [], active = 0
  function enqueue(run) { queue.push(run); pump() }
  function pump() {
    while (active < 2 && queue.length) {
      active++
      queue.shift()(function() { active--; pump() })
    }
  }
  function uploadsPending() { return active > 0 || queue.length > 0 }

  window.addEventListener('beforeunload', function(e) {
    if (uploadsPending()) { e.preventDefault(); e.returnValue = '' }
  })
  form.addEventListener('submit', function(e) {
    if (uploadsPending()) {
      e.preventDefault()
      alert('Photos are still uploading. Please wait for them to finish before saving.')
    }
  })

  function fmtSize(bytes) {
    if (bytes >= 1024 * 1024) return (bytes / 1024 / 1024).toFixed(1) + ' MB'
    return Math.max(1, Math.round(bytes / 1024)) + ' KB'
  }
  function isSupportedImage(file) { return /^image\/(jpeg|png|webp)$/.test(file.type) }

  // Canvas-based compression: max 1920px on the longest edge, q≈0.82.
  // Files under 500 KB are sent as-is; if compression doesn't shrink the
  // file, the original is used. Original filename is kept either way.
  function compressImage(file) {
    if (file.size < COMPRESS_MIN_BYTES) return Promise.resolve(file)
    return new Promise(function(resolve) {
      var url = URL.createObjectURL(file)
      var img = new Image()
      img.onload = function() {
        URL.revokeObjectURL(url)
        try {
          var scale = Math.min(1, MAX_EDGE / Math.max(img.naturalWidth, img.naturalHeight))
          var canvas = document.createElement('canvas')
          canvas.width = Math.max(1, Math.round(img.naturalWidth * scale))
          canvas.height = Math.max(1, Math.round(img.naturalHeight * scale))
          canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height)
          var type = file.type === 'image/png' ? 'image/png' : (file.type === 'image/webp' ? 'image/webp' : 'image/jpeg')
          canvas.toBlob(function(blob) {
            resolve(blob && blob.size < file.size ? blob : file)
          }, type, QUALITY)
        } catch (err) { resolve(file) }
      }
      img.onerror = function() { URL.revokeObjectURL(url); resolve(file) }
      img.src = url
    })
  }

  function xhrUpload(blob, filename, onProgress) {
    return new Promise(function(resolve, reject) {
      var xhr = new XMLHttpRequest()
      xhr.open('POST', UPLOAD_URL)
      xhr.setRequestHeader('X-CSRF-TOKEN', CSRF)
      xhr.setRequestHeader('Accept', 'application/json')
      xhr.responseType = 'json'
      xhr.timeout = 120000
      if (xhr.upload && onProgress) {
        xhr.upload.onprogress = function(ev) { if (ev.lengthComputable) onProgress(ev.loaded / ev.total) }
      }
      xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300 && xhr.response && xhr.response.path) {
          resolve(xhr.response)
        } else {
          var r = xhr.response || {}
          var msg = r.message || (r.errors && r.errors.image && r.errors.image[0]) || ('Upload failed (HTTP ' + xhr.status + ')')
          reject({ status: xhr.status, message: msg })
        }
      }
      xhr.onerror = function() { reject({ status: 0, message: 'Network error' }) }
      xhr.ontimeout = function() { reject({ status: 0, message: 'Upload timed out' }) }
      var fd = new FormData()
      fd.append('image', blob, filename)
      xhr.send(fd)
    })
  }

  // Compress + upload with up to 2 automatic retries (1.5s / 3s backoff).
  // The queue slot is held during backoff so the server never sees a burst.
  function uploadWithRetry(file, handlers) {
    var attempts = 0
    enqueue(function(done) {
      function attempt() {
        attempts++
        if (handlers.onStart) handlers.onStart(attempts)
        compressImage(file).then(function(blob) {
          return xhrUpload(blob, file.name, handlers.onProgress)
        }).then(function(res) {
          handlers.onSuccess(res); done()
        }).catch(function(err) {
          var retriable = !err.status || err.status >= 500 || err.status === 429
          if (retriable && attempts <= 2) {
            if (handlers.onRetryWait) handlers.onRetryWait(attempts)
            setTimeout(attempt, attempts * 1500)
          } else {
            handlers.onFailure(err); done()
          }
        })
      }
      attempt()
    })
  }

  // ── Gallery uploader ──────────────────────────────────────────────────
  var galleryPicker = document.getElementById('gallery_picker')
  var galleryItems = document.getElementById('gallery-items')
  var galleryErrors = document.getElementById('gallery-errors')
  var overallWrap = document.getElementById('gallery-overall')
  var overallBar = document.getElementById('gallery-overall-bar')
  var overallLabel = document.getElementById('gallery-overall-label')
  var galleryProgress = {}
  var seenFiles = {}

  function showGalleryError(msg) {
    var p = document.createElement('p')
    p.className = 'text-xs text-red-600'
    p.textContent = msg
    galleryErrors.appendChild(p)
  }

  function galleryCount() {
    var existing = document.querySelectorAll('.existing-gallery-item').length
    var removed = document.querySelectorAll('input[name="delete_image_ids[]"]:checked').length
    var current = galleryItems.querySelectorAll('li:not([data-state="failed"])').length
    // Staged uploads restored after a validation error live outside the list
    var restored = 0
    document.querySelectorAll('input[name="gallery_staged[]"]').forEach(function(inp) {
      if (!galleryItems.contains(inp)) restored++
    })
    return existing - removed + current + restored
  }

  function refreshOverall() {
    var keys = Object.keys(galleryProgress)
    if (!keys.length) { overallWrap.classList.add('hidden'); return }
    overallWrap.classList.remove('hidden')
    var sum = 0
    keys.forEach(function(k) { sum += galleryProgress[k] })
    var pct = Math.round((sum / keys.length) * 100)
    overallBar.style.width = pct + '%'
    overallLabel.textContent = pct + '%'
    if (pct >= 100) {
      setTimeout(function() {
        if (!uploadsPending()) { galleryProgress = {}; overallWrap.classList.add('hidden') }
      }, 800)
    }
  }

  function addGalleryFile(file) {
    var key = file.name + '|' + file.size
    if (seenFiles[key]) { showGalleryError('"' + file.name + '" is already in the list — duplicate skipped.'); return }
    if (!isSupportedImage(file)) { showGalleryError('"' + file.name + '" is not a JPEG, PNG or WebP image.'); return }
    if (file.size > MAX_BYTES) { showGalleryError('"' + file.name + '" is ' + fmtSize(file.size) + ' — the maximum is 16 MB per image.'); return }
    if (galleryCount() >= MAX_GALLERY) { showGalleryError('Maximum ' + MAX_GALLERY + ' gallery images per post — "' + file.name + '" was not added.'); return }
    seenFiles[key] = true

    var li = document.createElement('li')
    li.className = 'border border-gray-200 bg-gray-50 p-3'
    li.dataset.state = 'uploading'
    li.innerHTML =
      '<div class="flex items-center gap-3">' +
        '<span class="gallery-thumb w-12 h-12 bg-gray-200 flex-shrink-0 overflow-hidden"></span>' +
        '<div class="flex-1 min-w-0">' +
          '<div class="flex items-center justify-between gap-2">' +
            '<span class="text-xs font-medium text-gray-800 truncate">' + escHtml(file.name) + '</span>' +
            '<span class="gallery-status text-xs text-ssbc-sage flex-shrink-0">Waiting…</span>' +
          '</div>' +
          '<div class="h-1 bg-gray-200 mt-1.5"><div class="gallery-bar h-full bg-ssbc-green" style="width:0%"></div></div>' +
        '</div>' +
        '<button type="button" class="gallery-retry text-xs text-ssbc-green underline flex-shrink-0 hidden">Retry</button>' +
        '<button type="button" class="gallery-remove text-xs text-red-600 hover:text-red-800 flex-shrink-0 hidden">Remove</button>' +
      '</div>'
    galleryItems.appendChild(li)

    var bar = li.querySelector('.gallery-bar')
    var status = li.querySelector('.gallery-status')
    var removeBtn = li.querySelector('.gallery-remove')
    var retryBtn = li.querySelector('.gallery-retry')
    var thumb = li.querySelector('.gallery-thumb')

    var fr = new FileReader()
    fr.onload = function(ev) { thumb.innerHTML = '<img src="' + ev.target.result + '" class="w-full h-full object-cover">' }
    fr.readAsDataURL(file)

    function start() {
      li.dataset.state = 'uploading'
      retryBtn.classList.add('hidden')
      status.classList.remove('text-red-600')
      galleryProgress[key] = 0
      refreshOverall()
      uploadWithRetry(file, {
        onStart: function(n) { status.textContent = n > 1 ? 'Retrying (attempt ' + n + ')…' : 'Compressing…' },
        onProgress: function(f) {
          var pct = Math.round(f * 100)
          bar.style.width = pct + '%'
          status.textContent = 'Uploading… ' + pct + '%'
          galleryProgress[key] = f
          refreshOverall()
        },
        onRetryWait: function() { status.textContent = 'Failed — retrying automatically…' },
        onSuccess: function(res) {
          li.dataset.state = 'done'
          bar.style.width = '100%'
          status.textContent = 'Uploaded ✓'
          status.classList.add('text-ssbc-green')
          var hidden = document.createElement('input')
          hidden.type = 'hidden'
          hidden.name = 'gallery_staged[]'
          hidden.value = res.path
          li.appendChild(hidden)
          removeBtn.classList.remove('hidden')
          galleryProgress[key] = 1
          refreshOverall()
        },
        onFailure: function(err) {
          li.dataset.state = 'failed'
          status.textContent = 'Failed: ' + err.message
          status.classList.add('text-red-600')
          retryBtn.classList.remove('hidden')
          removeBtn.classList.remove('hidden')
          delete galleryProgress[key]
          refreshOverall()
        }
      })
    }

    retryBtn.addEventListener('click', start)
    removeBtn.addEventListener('click', function() {
      delete seenFiles[key]
      delete galleryProgress[key]
      li.remove()
      refreshOverall()
    })
    start()
  }

  galleryPicker.addEventListener('change', function() {
    galleryErrors.innerHTML = ''
    Array.prototype.slice.call(galleryPicker.files).forEach(addGalleryFile)
    galleryPicker.value = ''
  })

  // ── Single-image async upload (featured image + content-block images) ──
  function runSingleUpload(file, hidden, statusEl, onSuccessExtra) {
    statusEl.classList.remove('hidden')
    if (!isSupportedImage(file)) {
      statusEl.innerHTML = '<span class="text-xs text-red-600"></span>'
      statusEl.firstChild.textContent = '"' + file.name + '" is not a JPEG, PNG or WebP image.'
      return
    }
    if (file.size > MAX_BYTES) {
      statusEl.innerHTML = '<span class="text-xs text-red-600"></span>'
      statusEl.firstChild.textContent = '"' + file.name + '" is ' + fmtSize(file.size) + ' — the maximum is 16 MB.'
      return
    }
    statusEl.innerHTML = '<span class="single-status text-xs text-ssbc-sage">Waiting…</span>' +
      '<div class="h-1 bg-gray-200 mt-1"><div class="single-bar h-full bg-ssbc-green" style="width:0%"></div></div>'
    var st = statusEl.querySelector('.single-status')
    var bar = statusEl.querySelector('.single-bar')
    hidden.value = ''
    uploadWithRetry(file, {
      onStart: function(n) { st.textContent = n > 1 ? 'Retrying (attempt ' + n + ')…' : 'Compressing…' },
      onProgress: function(f) {
        var pct = Math.round(f * 100)
        bar.style.width = pct + '%'
        st.textContent = 'Uploading ' + file.name + ' — ' + pct + '%'
      },
      onRetryWait: function() { st.textContent = 'Failed — retrying automatically…' },
      onSuccess: function(res) {
        bar.style.width = '100%'
        st.textContent = file.name + ' uploaded ✓'
        st.classList.add('text-ssbc-green')
        hidden.value = res.path
        if (onSuccessExtra) onSuccessExtra(res)
      },
      onFailure: function(err) {
        st.textContent = 'Failed: ' + err.message + ' — choose the file again to retry.'
        st.classList.add('text-red-600')
      }
    })
  }

  var featuredInput = document.getElementById('featured_image')
  if (featuredInput) {
    featuredInput.addEventListener('change', function() {
      var file = featuredInput.files[0]
      if (!file) return
      runSingleUpload(file, document.getElementById('featured_image_staged'), document.getElementById('featured-upload-status'), function(res) {
        document.getElementById('featured-upload-preview').innerHTML =
          '<img src="' + escAttr(res.url) + '" class="h-32 border border-gray-200 object-cover">'
      })
    })
  }

  // Content-block images: preview immediately, upload in the background,
  // store the staged path in the block's hidden input (delegated so it also
  // covers blocks added dynamically).
  form.addEventListener('change', function(e) {
    if (!e.target.classList.contains('block-img-input')) return
    var input = e.target
    var file = input.files[0]
    if (!file) return
    var wrap = input.parentNode
    var hidden = wrap.querySelector('.block-staged-input')
    var statusEl = wrap.querySelector('.block-img-status')
    var pv = document.getElementById(input.dataset.preview)
    if (pv) {
      var reader = new FileReader()
      reader.onload = function(ev) {
        pv.innerHTML = '<img src="' + ev.target.result + '" class="h-32 border border-gray-200 object-cover">'
      }
      reader.readAsDataURL(file)
    }
    runSingleUpload(file, hidden, statusEl)
  })
})
</script>
@endpush

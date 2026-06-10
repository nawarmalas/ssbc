@php
    $isAr      = $locale === 'ar';
    $fPrefix   = 'blocks_' . $locale;
    $imgPrefix = 'block_image_' . $locale;
@endphp

<div id="blocks-section-{{ $locale }}" data-count="{{ $blocks->count() }}">
    <div id="blocks-list-{{ $locale }}" class="space-y-3 pb-1">
        @foreach($blocks->values() as $i => $block)
        <div class="block-card border border-gray-200 bg-gray-50 p-4"
             data-type="{{ $block->type }}" data-slot="{{ $i }}">

            <input type="hidden" name="{{ $fPrefix }}[{{ $i }}][block_id]"   value="{{ $block->id }}">
            <input type="hidden" name="{{ $fPrefix }}[{{ $i }}][type]"       value="{{ $block->type }}">
            <input type="hidden" name="{{ $fPrefix }}[{{ $i }}][sort_order]" class="block-sort-order" value="{{ $i }}">

            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase px-2 py-0.5 {{ $block->type === 'text' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ $block->type === 'text' ? 'Text' : 'Image' }}
                </span>
                <div class="flex items-center gap-3">
                    <span class="drag-handle cursor-grab text-gray-400 hover:text-gray-600 select-none text-lg leading-none" title="Drag to reorder">⠿</span>
                    <button type="button" class="block-remove-btn text-xs text-red-600 hover:text-red-800">Remove</button>
                </div>
            </div>

            @if($block->type === 'text')
                <textarea name="{{ $fPrefix }}[{{ $i }}][content]"
                          id="block-ta-{{ $locale }}-{{ $i }}"
                          class="ssbc-admin-input font-mono text-xs block-textarea"
                          @if($isAr) dir="rtl" lang="ar" @endif
                          rows="6">{{ $block->content }}</textarea>
            @else
                <div class="space-y-3">
                    <div id="block-img-preview-{{ $locale }}-{{ $i }}" class="block-img-preview">
                        @if($block->image_path)
                            <img src="{{ Storage::url($block->image_path) }}" alt=""
                                 class="h-32 border border-gray-200 object-cover">
                        @endif
                    </div>
                    <div>
                        <label class="text-xs text-ssbc-sage block mb-1">
                            {{ $block->image_path ? 'Replace image (leave empty to keep current)' : 'Upload image' }}
                        </label>
                        <input type="file"
                               name="{{ $imgPrefix }}[{{ $i }}]"
                               accept="image/*"
                               class="ssbc-admin-input bg-white block-img-input"
                               data-preview="block-img-preview-{{ $locale }}-{{ $i }}">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-ssbc-sage block mb-1">Caption (English)</label>
                            <input type="text" name="{{ $fPrefix }}[{{ $i }}][caption_en]"
                                   value="{{ $block->caption_en }}" class="ssbc-admin-input text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-ssbc-sage block mb-1">Caption (Arabic)</label>
                            <input type="text" name="{{ $fPrefix }}[{{ $i }}][caption_ar]"
                                   value="{{ $block->caption_ar }}" class="ssbc-admin-input text-sm" dir="rtl">
                        </div>
                    </div>
                </div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="flex gap-3 mt-4">
        <button type="button"
                class="add-block-btn border border-ssbc-green text-ssbc-green px-4 py-1.5 text-xs font-semibold hover:bg-ssbc-green hover:text-white transition-colors"
                data-locale="{{ $locale }}" data-type="text">+ Add Text Block</button>
        <button type="button"
                class="add-block-btn border border-ssbc-green text-ssbc-green px-4 py-1.5 text-xs font-semibold hover:bg-ssbc-green hover:text-white transition-colors"
                data-locale="{{ $locale }}" data-type="image">+ Add Image Block</button>
    </div>
</div>

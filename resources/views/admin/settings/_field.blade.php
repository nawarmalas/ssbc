{{--
    Renders one editable copy field (text, textarea, or list) inside the
    Site Customization form. Used by both the Homepage and About Page tabs.

    Required vars:
      $key         dot-key in the JSON column / lang file (e.g. "hero.headline")
      $meta        schema entry: ['label', 'type' => text|textarea|list, ...]
      $bag         saved JSON for this page (e.g. $home or $about), per-locale
      $langPrefix  'home' or 'about' — used to look up Lang::get($langPrefix.'.'.$key)

    The two locales share one row so EN and AR sit side-by-side.
--}}

@php
    $type = $meta['type'] ?? 'text';
    $defaultEn = Lang::get($langPrefix . '.' . $key, [], 'en');
    $defaultAr = Lang::get($langPrefix . '.' . $key, [], 'ar');
@endphp

<div>
    @if ($type === 'list')
        <label class="ssbc-admin-label">
            {{ $meta['label'] }}
            <span class="text-ssbc-sage font-normal normal-case tracking-normal">— {{ $key }}</span>
        </label>
        <p class="text-xs text-ssbc-sage mb-3">{{ $meta['count'] }} rows · EN / AR per row</p>

        @php
            $count    = $meta['count'] ?? 0;
            $shape    = $meta['shape'] ?? [];
            $isFlat   = count($shape) === 1;
            $flatKey  = $isFlat ? array_key_first($shape) : null;
            $savedEn  = is_array($bag['en'][$key] ?? null) ? $bag['en'][$key] : [];
            $savedAr  = is_array($bag['ar'][$key] ?? null) ? $bag['ar'][$key] : [];
            $defaultEnList = is_array($defaultEn) ? $defaultEn : [];
            $defaultArList = is_array($defaultAr) ? $defaultAr : [];
        @endphp

        <div class="space-y-4">
            @for ($i = 0; $i < $count; $i++)
                <div class="border border-gray-200 p-4 bg-ssbc-light/30">
                    <p class="ssbc-eyebrow mb-3">Row {{ $i + 1 }}</p>
                    <div class="space-y-3">
                        @foreach ($shape as $field => $fieldMeta)
                            @php
                                $fieldType = $fieldMeta['type'] ?? 'text';
                                $fieldRows = $fieldType === 'textarea' ? 3 : 1;

                                // Saved values: flat lists store strings, others store assoc arrays.
                                if ($isFlat) {
                                    $savedEnVal = is_string($savedEn[$i] ?? null) ? $savedEn[$i] : '';
                                    $savedArVal = is_string($savedAr[$i] ?? null) ? $savedAr[$i] : '';
                                    $defEnVal   = is_string($defaultEnList[$i] ?? null) ? $defaultEnList[$i] : '';
                                    $defArVal   = is_string($defaultArList[$i] ?? null) ? $defaultArList[$i] : '';
                                } else {
                                    $savedEnVal = is_array($savedEn[$i] ?? null) ? ($savedEn[$i][$field] ?? '') : '';
                                    $savedArVal = is_array($savedAr[$i] ?? null) ? ($savedAr[$i][$field] ?? '') : '';
                                    $defEnVal   = is_array($defaultEnList[$i] ?? null) ? ($defaultEnList[$i][$field] ?? '') : '';
                                    $defArVal   = is_array($defaultArList[$i] ?? null) ? ($defaultArList[$i][$field] ?? '') : '';
                                }

                                $oldEn = old("en.{$key}.{$i}.{$field}", $savedEnVal);
                                $oldAr = old("ar.{$key}.{$i}.{$field}", $savedArVal);
                                $nameEn = "en[{$key}][{$i}][{$field}]";
                                $nameAr = "ar[{$key}][{$i}][{$field}]";
                            @endphp

                            <div>
                                <p class="text-xs font-semibold text-ssbc-sage mb-1 uppercase tracking-wider">{{ $fieldMeta['label'] }}</p>
                                <div class="grid md:grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-xs text-ssbc-sage mb-1">English</p>
                                        @if ($fieldType === 'textarea')
                                            <textarea name="{{ $nameEn }}" rows="{{ $fieldRows }}" class="ssbc-admin-input" placeholder="{{ $defEnVal }}">{{ $oldEn }}</textarea>
                                        @else
                                            <input type="text" name="{{ $nameEn }}" class="ssbc-admin-input" value="{{ $oldEn }}" placeholder="{{ $defEnVal }}">
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-xs text-ssbc-sage mb-1">العربية</p>
                                        @if ($fieldType === 'textarea')
                                            <textarea name="{{ $nameAr }}" rows="{{ $fieldRows }}" class="ssbc-admin-input" dir="rtl" lang="ar" placeholder="{{ $defArVal }}">{{ $oldAr }}</textarea>
                                        @else
                                            <input type="text" name="{{ $nameAr }}" class="ssbc-admin-input" dir="rtl" lang="ar" value="{{ $oldAr }}" placeholder="{{ $defArVal }}">
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endfor
        </div>
    @else
        @php
            $rows  = $type === 'textarea' ? 3 : 1;
            $valEn = old("en.{$key}", $bag['en'][$key] ?? '');
            $valAr = old("ar.{$key}", $bag['ar'][$key] ?? '');
        @endphp
        <label class="ssbc-admin-label">
            {{ $meta['label'] }}
            <span class="text-ssbc-sage font-normal normal-case tracking-normal">— {{ $key }}</span>
        </label>
        <div class="grid md:grid-cols-2 gap-3">
            <div>
                <p class="text-xs text-ssbc-sage mb-1">English</p>
                @if ($type === 'textarea')
                    <textarea name="en[{{ $key }}]" rows="{{ $rows }}" class="ssbc-admin-input" placeholder="{{ is_string($defaultEn) ? $defaultEn : '' }}">{{ $valEn }}</textarea>
                @else
                    <input type="text" name="en[{{ $key }}]" class="ssbc-admin-input" value="{{ $valEn }}" placeholder="{{ is_string($defaultEn) ? $defaultEn : '' }}">
                @endif
            </div>
            <div>
                <p class="text-xs text-ssbc-sage mb-1">العربية</p>
                @if ($type === 'textarea')
                    <textarea name="ar[{{ $key }}]" rows="{{ $rows }}" class="ssbc-admin-input" dir="rtl" lang="ar" placeholder="{{ is_string($defaultAr) ? $defaultAr : '' }}">{{ $valAr }}</textarea>
                @else
                    <input type="text" name="ar[{{ $key }}]" class="ssbc-admin-input" dir="rtl" lang="ar" value="{{ $valAr }}" placeholder="{{ is_string($defaultAr) ? $defaultAr : '' }}">
                @endif
            </div>
        </div>
    @endif
</div>

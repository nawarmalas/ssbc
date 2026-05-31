@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $pageTitle = isset($formDefinition) ? $formDefinition->title($locale) : __('join.hero.heading');
    $pageIntro = isset($formDefinition)
        ? ($locale === 'ar'
            ? ($formDefinition->description_ar ?: $formDefinition->description_en ?: __('join.intro'))
            : ($formDefinition->description_en ?: $formDefinition->description_ar ?: __('join.intro')))
        : __('join.intro');
    $submitAction = $formAction ?? route('join.store', ['locale' => $locale]);

    // Localised month options for date-field selects (passed to Alpine below).
    $dateMonthOptions = [];
    foreach (array_values((array) __('join.date.months')) as $i => $monthName) {
        $dateMonthOptions[] = ['value' => $i + 1, 'label' => $monthName];
    }
@endphp

@section('title', $pageTitle . ' - ' . __('common.site_name'))
@section('meta_description', isset($formDefinition) && $formDefinition->isPrivate() ? $pageIntro : __('seo.join.description'))
@if((isset($formDefinition) && $formDefinition->isPrivate()) || (isset($preview) && $preview))
    @section('robots', 'noindex, nofollow')
@endif

@section('content')

@include('partials.page-hero', [
    'eyebrow'      => __('join.hero.eyebrow'),
    'heading'      => $pageTitle,
    'body'         => $pageIntro,
    'padding'      => 'py-8 lg:py-8',
    'headingClass' => 'max-w-4xl',
    'bodyClass'    => 'max-w-4xl',
])

<section class="bg-white">
    <div class="ssbc-container py-16">
        <div class="max-w-3xl mx-auto">

            {{-- Institutional header --}}
            <div class="flex flex-col items-center mb-8 text-center">
                <img src="{{ asset('images/logos/logo-two-tone.png') }}"
                     alt="{{ __('common.site_name') }}"
                     class="h-16 md:h-20 w-auto mb-4" loading="lazy">
                <div class="w-20 h-px bg-ssbc-gold"></div>
            </div>

            @if(isset($preview) && $preview)
                <div class="mb-8 bg-amber-50 border border-amber-300 px-4 py-3 text-sm text-amber-800 text-center">
                    Preview Mode — this form cannot be submitted from here.
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-8 border border-red-300 bg-red-50 p-4 text-sm text-red-800" id="server-errors">
                    <p class="font-semibold mb-2">Please correct the following errors:</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div x-data="dynamicForm({{ $form->toJson() }})" x-init="init()">

                {{-- Client-side submission error summary --}}
                <div x-show="submitError" x-cloak
                     class="mb-8 border border-red-300 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold mb-2" x-text="submitError"></p>
                    <p x-show="firstErrorStep !== null">
                        Returning to step <span x-text="firstErrorStep + 1"></span> — please fill the highlighted fields.
                    </p>
                </div>

                {{-- Step indicator --}}
                <div class="flex items-center justify-between mb-10">
                    <p class="ssbc-eyebrow">
                        {{ __('join.steps.step') }}
                        <span x-text="step + 1"></span>
                        {{ __('join.steps.of') }}
                        <span x-text="sections.length"></span>
                    </p>
                    <div class="flex gap-2">
                        <template x-for="(s, i) in sections" :key="i">
                            <span class="h-1 w-10 transition-colors"
                                  :class="i <= step ? 'bg-ssbc-gold' : 'bg-ssbc-green/15'"></span>
                        </template>
                    </div>
                </div>

                <form method="POST"
                      action="{{ isset($preview) && $preview ? '#' : $submitAction }}"
                      enctype="multipart/form-data"
                      @submit.prevent="onSubmit">
                    @csrf

                    {{-- Hidden _repeats fields --}}
                    <template x-for="(count, sectionId) in repeats" :key="sectionId">
                        <input type="hidden" :name="'_repeats[' + sectionId + ']'" :value="count">
                    </template>

                    {{-- All sections kept in DOM via x-show so all inputs submit together --}}
                    <template x-for="(section, sIdx) in sections" :key="section.id">
                        <div x-show="step === sIdx">
                            <h2 class="text-2xl font-display font-bold text-ssbc-green mb-2"
                                x-text="locale === 'ar' ? section.title_ar : section.title_en"></h2>
                            <div class="w-12 h-px bg-ssbc-gold mb-8"></div>

                            {{-- Repeatable section tabs --}}
                            <template x-if="section.is_repeatable">
                                <div>
                                    <div class="flex gap-2 flex-wrap mb-6">
                                        <template x-for="r in (repeats[section.id] || 1)" :key="r">
                                            <button type="button"
                                                    @click="activeRepeat = r - 1"
                                                    :class="activeRepeat === r - 1
                                                        ? 'bg-ssbc-gold text-ssbc-green border-ssbc-gold'
                                                        : 'bg-white text-ssbc-sage border-ssbc-green/20'"
                                                    class="px-4 py-1.5 rounded-full text-sm font-semibold border transition-colors">
                                                <span x-text="(locale === 'ar' ? section.title_ar : section.title_en) + ' ' + r"></span>
                                            </button>
                                        </template>
                                        <button type="button"
                                                x-show="(repeats[section.id] || 1) < section.max_repeats"
                                                @click="addRepeat(section)"
                                                class="px-4 py-1.5 rounded-full text-sm border border-dashed border-ssbc-gold text-ssbc-gold">
                                            + Add another
                                        </button>
                                        <button type="button"
                                                x-show="(repeats[section.id] || 1) > 1"
                                                @click="removeLastRepeat(section)"
                                                class="px-4 py-1.5 rounded-full text-sm border border-red-200 text-red-600 hover:bg-red-50">
                                            Remove latest
                                        </button>
                                    </div>
                                </div>
                            </template>

                            {{-- All repeats in DOM; x-show hides non-active repeat for repeatable sections --}}
                            <template x-for="ri in (section.is_repeatable ? (repeats[section.id] || 1) : 1)" :key="ri">
                                <div x-show="!section.is_repeatable || activeRepeat === ri - 1"
                                     class="space-y-6">
                                    <template x-for="field in section.fields" :key="field.id">
                                        <div x-show="fieldIsVisible(field, ri-1)"
                                             :id="'field_' + field.id + '_' + (ri-1)">
                                            {{-- Label — hidden for declaration fields, whose paragraph
                                                 text is shown beside the checkbox instead. --}}
                                            <label x-show="field.field_type !== 'declaration'"
                                                   class="ssbc-label" :for="'f_' + field.id + '_' + (ri-1)">
                                                <span x-text="locale === 'ar' ? field.label_ar : field.label_en"></span>
                                                <span x-show="field.is_required" class="text-red-500 ml-0.5">*</span>
                                            </label>

                                            {{-- text / email / tel / number / url
                                                 NOTE: type=url is rendered as type=text so that browsers don't
                                                 fire native URL validation against hidden steps and silently
                                                 block the submit. We validate URLs server-side and inline. --}}
                                            <template x-if="['text','email','tel','number','url'].includes(field.field_type)">
                                                <input
                                                    :id="'f_' + field.id + '_' + (ri-1)"
                                                    :type="field.field_type === 'url' ? 'text' : field.field_type"
                                                    :inputmode="field.field_type === 'tel' ? 'tel' : (field.field_type === 'number' ? 'numeric' : null)"
                                                    :dir="['tel','number'].includes(field.field_type) ? 'ltr' : null"
                                                    :name="'answers[' + field.id + '][' + (ri-1) + ']'"
                                                    :placeholder="(locale === 'ar' ? field.placeholder_ar : field.placeholder_en) || ''"
                                                    :required="step === sIdx && field.is_required && ri === 1"
                                                    :min="field.field_type === 'number' ? (field.validation_rules?.min ?? null) : null"
                                                    :max="field.field_type === 'number' ? (field.validation_rules?.max ?? null) : null"
                                                    x-model="answers[field.id + '_' + (ri-1)]"
                                                    @blur="validateField(field, ri-1)"
                                                    class="ssbc-input"
                                                    :class="[ ['tel','number'].includes(field.field_type) ? 'text-left' : '', errorClass(field, ri-1) ]"
                                                >
                                            </template>

                                            {{-- textarea --}}
                                            <template x-if="field.field_type === 'textarea'">
                                                <textarea
                                                    :id="'f_' + field.id + '_' + (ri-1)"
                                                    :name="'answers[' + field.id + '][' + (ri-1) + ']'"
                                                    :placeholder="(locale === 'ar' ? field.placeholder_ar : field.placeholder_en) || ''"
                                                    :required="step === sIdx && field.is_required && ri === 1"
                                                    x-model="answers[field.id + '_' + (ri-1)]"
                                                    rows="3"
                                                    class="ssbc-input"
                                                    :class="errorClass(field, ri-1)"
                                                ></textarea>
                                            </template>

                                            {{-- date --}}
                                            <template x-if="field.field_type === 'date'">
                                                <div>
                                                    <input type="hidden"
                                                           :name="'answers[' + field.id + '][' + (ri-1) + ']'"
                                                           :value="dateValue(field, ri-1)">
                                                    <div class="grid grid-cols-3 gap-3">
                                                        <select class="ssbc-input"
                                                                :class="errorClass(field, ri-1)"
                                                                x-model="dateParts[dateKey(field, ri-1)].month"
                                                                @change="validateField(field, ri-1)"
                                                                :aria-label="'{{ __('join.date.month') }} — ' + (locale === 'ar' ? field.label_ar : field.label_en)">
                                                            <option value="">{{ __('join.date.month') }}</option>
                                                            <template x-for="month in months" :key="month.value">
                                                                <option :value="month.value" x-text="month.label"></option>
                                                            </template>
                                                        </select>
                                                        <select class="ssbc-input"
                                                                :class="errorClass(field, ri-1)"
                                                                x-model="dateParts[dateKey(field, ri-1)].day"
                                                                @change="validateField(field, ri-1)"
                                                                :aria-label="'{{ __('join.date.day') }} — ' + (locale === 'ar' ? field.label_ar : field.label_en)">
                                                            <option value="">{{ __('join.date.day') }}</option>
                                                            <template x-for="day in dateDaysFor(field, ri-1)" :key="day">
                                                                <option :value="day" x-text="day"></option>
                                                            </template>
                                                        </select>
                                                        <select class="ssbc-input"
                                                                :class="errorClass(field, ri-1)"
                                                                x-model="dateParts[dateKey(field, ri-1)].year"
                                                                @change="validateField(field, ri-1)"
                                                                :aria-label="'{{ __('join.date.year') }} — ' + (locale === 'ar' ? field.label_ar : field.label_en)">
                                                            <option value="">{{ __('join.date.year') }}</option>
                                                            <template x-for="year in dateYearsFor(field)" :key="year">
                                                                <option :value="year" x-text="year"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                </div>
                                            </template>

                                            {{-- select --}}
                                            <template x-if="field.field_type === 'select'">
                                                <select
                                                    :id="'f_' + field.id + '_' + (ri-1)"
                                                    :name="'answers[' + field.id + '][' + (ri-1) + ']'"
                                                    :required="step === sIdx && field.is_required && ri === 1"
                                                    x-model="answers[field.id + '_' + (ri-1)]"
                                                    class="ssbc-input"
                                                    :class="errorClass(field, ri-1)"
                                                >
                                                    <option value="">— Select —</option>
                                                    <template x-for="opt in (field.options || [])" :key="opt.value">
                                                        <option :value="opt.value"
                                                                x-text="locale === 'ar' ? opt.label_ar : opt.label_en"></option>
                                                    </template>
                                                </select>
                                            </template>

                                            {{-- radio --}}
                                            <template x-if="field.field_type === 'radio'">
                                                <div class="flex flex-wrap gap-4 mt-1">
                                                    <template x-for="opt in (field.options || [])" :key="opt.value">
                                                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                                                            <input type="radio"
                                                                   :name="'answers[' + field.id + '][' + (ri-1) + ']'"
                                                                   :value="opt.value"
                                                                   x-model="answers[field.id + '_' + (ri-1)]"
                                                                   class="text-ssbc-gold focus:ring-ssbc-gold">
                                                            <span x-text="locale === 'ar' ? opt.label_ar : opt.label_en"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </template>

                                            {{-- checkbox_group --}}
                                            <template x-if="field.field_type === 'checkbox_group'">
                                                <div>
                                                    <div class="grid sm:grid-cols-2 gap-2 mt-1">
                                                        <template x-for="opt in (field.options || [])" :key="opt.value">
                                                            <label class="flex items-start gap-2 cursor-pointer text-sm p-2 hover:bg-ssbc-beige/40 rounded transition-colors">
                                                                <input type="checkbox"
                                                                       :name="'answers[' + field.id + '][' + (ri-1) + '][]'"
                                                                       :value="opt.value"
                                                                       :checked="(checkboxAnswers[field.id + '_' + (ri-1)] || []).includes(opt.value)"
                                                                       @change="toggleCheckbox(field.id, ri-1, opt.value)"
                                                                       class="mt-0.5 shrink-0 text-ssbc-gold focus:ring-ssbc-gold">
                                                                <span x-text="locale === 'ar' ? opt.label_ar : opt.label_en"></span>
                                                            </label>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>

                                            {{-- file --}}
                                            <template x-if="field.field_type === 'file'">
                                                <div>
                                                    <div class="border-2 border-dashed border-ssbc-green/20 p-6 text-center hover:border-ssbc-gold transition-colors relative"
                                                         @dragover.prevent
                                                         @drop.prevent="handleFileDrop(field, ri-1, $event)">
                                                        <input type="file"
                                                               :id="'f_' + field.id + '_' + (ri-1)"
                                                               :name="'files[' + field.id + '][' + (ri-1) + ']'"
                                                               :accept="'.' + (field.file_config?.accepted_types || ['pdf']).join(',.')"
                                                               :required="step === sIdx && field.is_required && ri === 1 && !fileNames[field.id + '_' + (ri-1)]"
                                                               @change="handleFileSelect(field, ri-1, $event)"
                                                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                                        <div x-show="!fileNames[field.id + '_' + (ri-1)]">
                                                            <p class="text-sm text-ssbc-sage">Drag & drop or click to browse</p>
                                                            <p class="text-xs text-ssbc-sage/70 mt-1"
                                                               x-text="'.' + (field.file_config?.accepted_types || ['pdf']).join(', .') + ' — max ' + (field.file_config?.max_size_mb || 100) + ' MB'"></p>
                                                        </div>
                                                        <div x-show="fileNames[field.id + '_' + (ri-1)]"
                                                             class="flex items-center justify-center gap-2">
                                                            <span class="text-sm text-ssbc-green font-semibold"
                                                                  x-text="fileNames[field.id + '_' + (ri-1)]"></span>
                                                            <span class="text-xs text-ssbc-sage">✓</span>
                                                        </div>
                                                    </div>
                                                    <p x-show="fileErrors[field.id + '_' + (ri-1)]"
                                                       x-text="fileErrors[field.id + '_' + (ri-1)]"
                                                       class="text-red-500 text-xs mt-1"></p>
                                                </div>
                                            </template>

                                            {{-- declaration --}}
                                            <template x-if="field.field_type === 'declaration'">
                                                <div class="border border-ssbc-green/15 bg-ssbc-beige/50 p-6">
                                                    <label class="flex items-start gap-3 cursor-pointer">
                                                        <input type="checkbox"
                                                               :name="'answers[' + field.id + '][' + (ri-1) + ']'"
                                                               value="1"
                                                               :required="step === sIdx && field.is_required"
                                                               x-model="answers[field.id + '_' + (ri-1)]"
                                                               class="mt-1 rounded-none border-ssbc-green/40 text-ssbc-gold focus:ring-ssbc-gold">
                                                        <span class="text-sm text-ssbc-dark leading-relaxed">
                                                            <span x-text="locale === 'ar' ? field.label_ar : field.label_en"></span><span x-show="field.is_required" class="text-red-500 ml-0.5">*</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            </template>

                                            {{-- Field error --}}
                                            <p x-show="stepErrors[field.id + '_' + (ri-1)]"
                                               x-text="stepErrors[field.id + '_' + (ri-1)]"
                                               class="text-red-500 text-xs mt-1"></p>

                                            {{-- Inline confirmation (e.g. resolved year, normalized phone) --}}
                                            <p x-show="fieldHints[field.id + '_' + (ri-1)]" x-cloak
                                               x-text="fieldHints[field.id + '_' + (ri-1)]"
                                               class="text-ssbc-green/80 text-xs mt-1"></p>

                                            {{-- Soft, non-blocking warning (e.g. ambiguous phone) --}}
                                            <p x-show="fieldWarnings[field.id + '_' + (ri-1)]" x-cloak
                                               x-text="fieldWarnings[field.id + '_' + (ri-1)]"
                                               class="text-amber-600 text-xs mt-1"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Navigation --}}
                    <div class="mt-12 flex items-center justify-between border-t border-ssbc-green/15 pt-6">
                        <button type="button" class="ssbc-btn-outline-dark"
                                x-show="step > 0" @click="prevStep()">
                            ← {{ __('common.previous') }}
                        </button>
                        <span x-show="step === 0"></span>

                        <button type="button" class="ssbc-btn-primary"
                                x-show="step < sections.length - 1"
                                @click="nextStep()">
                            {{ __('common.next') }} →
                        </button>

                        @if(!(isset($preview) && $preview))
                        <button type="submit"
                                class="ssbc-btn-primary disabled:opacity-60 disabled:cursor-not-allowed"
                                x-show="step === sections.length - 1"
                                :disabled="submitting"
                                x-text="submitting ? '{{ __('join.submitting') }}' : '{{ __('join.submit') }}'">
                        </button>
                        @endif
                    </div>

                    {{-- Upload progress — shown while large attachments are sending --}}
                    <div x-show="submitting && uploadProgress > 0 && uploadProgress < 100" x-cloak class="mt-6">
                        <div class="flex justify-between text-xs text-ssbc-sage mb-1">
                            <span>{{ __('join.uploading') }}</span>
                            <span x-text="uploadProgress + '%'"></span>
                        </div>
                        <div class="h-1.5 w-full bg-ssbc-green/10 overflow-hidden rounded">
                            <div class="h-full bg-ssbc-gold transition-all duration-150"
                                 :style="'width: ' + uploadProgress + '%'"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
function dynamicForm(sectionsJson) {
    const sections = sectionsJson;
    const locale = document.documentElement.lang || 'en';

    // Loose URL pattern — must start with http(s):// and have a host
    const urlRegex = /^https?:\/\/[^\s.]+\.[^\s]+$/i;

    return {
        sections,
        locale,
        step: 0,
        activeRepeat: 0,
        answers: {},
        dateParts: {},
        checkboxAnswers: {},
        repeats: {},
        fileNames: {},
        fileErrors: {},
        stepErrors: {},
        fieldHints: {},      // neutral, informational notes (resolved year, phone preview)
        fieldWarnings: {},   // soft, non-blocking warnings (ambiguous phone, etc.)
        submitting: false,
        submitError: null,
        firstErrorStep: null,
        uploadProgress: 0,   // 0–100 while a submission (incl. file uploads) is in flight
        codeToId: {},
        months: @json($dateMonthOptions),

        init() {
            sections.forEach(s => {
                if (s.is_repeatable) this.repeats[s.id] = 1;
                this.initDatePartsForSection(s, 1);
                (s.fields || []).forEach(f => {
                    if (f.code) this.codeToId[f.code] = f.id;
                });
            });

            // Restore saved form state after a Laravel validation redirect.
            const saved = sessionStorage.getItem('ssbc_form');
            if (saved) {
                try {
                    const state = JSON.parse(saved);
                    this.answers = Object.assign(this.answers, state.answers || {});
                    this.checkboxAnswers = Object.assign(this.checkboxAnswers, state.checkboxAnswers || {});
                    this.dateParts = Object.assign(this.dateParts, state.dateParts || {});
                    // Only restore step when Laravel has flashed validation errors.
                    const hasErrors = {{ $errors->any() ? 'true' : 'false' }};
                    if (hasErrors && state.currentStep) {
                        this.step = state.currentStep;
                    }
                } catch(e) {}
            }

            // Auto-save whenever any answer state changes.
            this.$watch('answers', () => this.saveToSession());
            this.$watch('checkboxAnswers', () => this.saveToSession());
            this.$watch('dateParts', () => this.saveToSession());
        },

        fieldIsVisible(field, ri) {
            const logic = field.conditional_logic;
            if (!logic || !logic.conditions) return true;
            const results = logic.conditions.map(c => {
                const id = this.codeToId[c.field_code];
                if (id === undefined) return true;
                switch (c.op) {
                    case 'equals':     return this.answers[id + '_' + ri] === c.value;
                    case 'not_equals': return this.answers[id + '_' + ri] !== c.value;
                    case 'contains': {
                        const arr = this.checkboxAnswers[id + '_' + ri] ?? [];
                        return arr.includes(c.value);
                    }
                    default: return true;
                }
            });
            return logic.operator === 'OR'
                ? results.some(Boolean)
                : results.every(Boolean);
        },

        get currentSection() {
            return this.sections[this.step] || null;
        },

        // Returns the max date for date fields. Date-of-birth fields cap at 18 years ago.
        dateMaxFor(field) {
            const label = (field.label_en || '').toLowerCase();
            if (label.includes('birth') || label.includes('dob')) {
                const d = new Date();
                d.setFullYear(d.getFullYear() - 18);
                return d.toISOString().slice(0, 10);
            }
            return new Date().toISOString().slice(0, 10);
        },

        dateKey(field, repeatIndex) {
            return field.id + '_' + repeatIndex;
        },

        initDatePartsForSection(section, count) {
            for (const field of section.fields || []) {
                if (field.field_type !== 'date') continue;
                for (let r = 0; r < count; r++) {
                    const key = this.dateKey(field, r);
                    if (!this.dateParts[key]) {
                        this.dateParts[key] = { month: '', day: '', year: '' };
                    }
                }
            }
        },

        dateValue(field, repeatIndex) {
            const parts = this.dateParts[this.dateKey(field, repeatIndex)] || {};
            if (!parts.year || !parts.month || !parts.day) return '';
            if (Number(parts.day) > this.dateDaysFor(field, repeatIndex).length) return '';
            const month = String(parts.month).padStart(2, '0');
            const day = String(parts.day).padStart(2, '0');
            return `${parts.year}-${month}-${day}`;
        },

        dateDaysFor(field, repeatIndex) {
            const parts = this.dateParts[this.dateKey(field, repeatIndex)] || {};
            const year = Number(parts.year || new Date().getFullYear());
            const month = Number(parts.month || 1);
            const days = new Date(year, month, 0).getDate();
            return Array.from({ length: days }, (_, i) => i + 1);
        },

        dateYearsFor(field) {
            const label = (field.label_en || '').toLowerCase();
            const currentYear = new Date().getFullYear();
            const maxYear = (label.includes('birth') || label.includes('dob')) ? currentYear - 18 : currentYear;
            const minYear = maxYear - 100;
            return Array.from({ length: maxYear - minYear + 1 }, (_, i) => maxYear - i);
        },

        addRepeat(section) {
            if (!section?.is_repeatable) return;
            const current = this.repeats[section.id] || 1;
            if (current < section.max_repeats) {
                this.repeats[section.id] = current + 1;
                this.activeRepeat = current;
                this.initDatePartsForSection(section, current + 1);
            }
        },

        removeLastRepeat(section) {
            if (!section?.is_repeatable) return;
            const current = this.repeats[section.id] || 1;
            if (current <= 1) return;

            const lastIndex = current - 1;
            for (const field of section.fields || []) {
                const key = field.id + '_' + lastIndex;
                delete this.answers[key];
                delete this.checkboxAnswers[key];
                delete this.fileNames[key];
                delete this.fileErrors[key];
                delete this.stepErrors[key];
                delete this.dateParts[key];
            }

            this.repeats[section.id] = current - 1;
            this.activeRepeat = Math.min(this.activeRepeat, current - 2);
        },

        toggleCheckbox(fieldId, repeatIndex, value) {
            const key = fieldId + '_' + repeatIndex;
            const current = this.checkboxAnswers[key] || [];
            if (current.includes(value)) {
                this.checkboxAnswers[key] = current.filter(v => v !== value);
            } else {
                this.checkboxAnswers[key] = [...current, value];
            }
        },

        handleFileSelect(field, repeatIndex, event) {
            const file = event.target.files[0];
            if (!file) return;
            this.validateAndSetFile(field, repeatIndex, file);
        },

        handleFileDrop(field, repeatIndex, event) {
            const file = event.dataTransfer.files[0];
            if (!file) return;
            this.validateAndSetFile(field, repeatIndex, file);
        },

        validateAndSetFile(field, repeatIndex, file) {
            const key = field.id + '_' + repeatIndex;
            const maxBytes = (field.file_config?.max_size_mb || 100) * 1024 * 1024;
            const accepted = (field.file_config?.accepted_types || ['pdf']).map(t => '.' + t);
            const ext = '.' + file.name.split('.').pop().toLowerCase();

            if (!accepted.includes(ext)) {
                this.fileErrors[key] = 'File type not accepted. Allowed: ' + accepted.join(', ');
                this.fileNames[key] = null;
                return;
            }
            if (file.size > maxBytes) {
                this.fileErrors[key] = 'File too large. Max ' + (field.file_config?.max_size_mb || 100) + ' MB.';
                this.fileNames[key] = null;
                return;
            }
            this.fileErrors[key] = null;
            this.fileNames[key] = file.name;
        },

        // A "year" field is a number whose minimum is itself year-shaped (>= 1000).
        // Keyed off min (not the label) so plain counts are never expanded.
        isYearField(field) {
            if (field.field_type !== 'number') return false;
            const min = field.validation_rules?.min;
            return min != null && Number(min) >= 1000;
        },

        // "96" -> "1996", "03" -> "2003": years above the current 2-digit year map
        // to the 1900s, the rest to the 2000s.
        expandTwoDigitYear(raw) {
            const n = parseInt(raw, 10);
            const pivot = new Date().getFullYear() % 100;
            return String((n > pivot ? 1900 : 2000) + n);
        },

        // On blur of a year field, expand a 1–2 digit entry and confirm it inline.
        applyYearExpansion(field, repeatIndex) {
            const key = field.id + '_' + repeatIndex;
            const raw = (this.answers[key] ?? '').toString().trim();
            if (/^\d{1,2}$/.test(raw)) {
                const expanded = this.expandTwoDigitYear(raw);
                this.answers[key] = expanded;
                this.fieldHints[key] = 'We read “' + raw + '” as ' + expanded + ' — edit if that’s not right.';
            } else {
                delete this.fieldHints[key];
            }
        },

        cleanPhone(value) {
            return String(value).replace(/[\s\-().]+/g, '');
        },

        // Readable preview for an international number, or null for a local one.
        prettyPhone(cleaned) {
            let s = cleaned;
            if (s.startsWith('00')) s = '+' + s.slice(2);
            if (!s.startsWith('+')) return null;
            const digits = s.slice(1).replace(/\D/g, '');
            if (!digits) return null;
            return '+' + (digits.match(/.{1,3}/g) || [digits]).join(' ');
        },

        // Phone is never blocked on the client — we only inform (preview) and gently
        // warn (ambiguous/local). Strips formatting before inspecting the value.
        applyPhoneHints(field, repeatIndex) {
            const key = field.id + '_' + repeatIndex;
            const raw = this.answers[key];
            if (!raw) { delete this.fieldHints[key]; delete this.fieldWarnings[key]; return; }

            const cleaned = this.cleanPhone(raw);

            // Contains something other than digits / leading + / 00 — nudge, don't block.
            if (!/^(?:\+|00)?\d+$/.test(cleaned)) {
                delete this.fieldHints[key];
                this.fieldWarnings[key] = 'This doesn’t look like a phone number — please double-check.';
                return;
            }

            const digitCount = (cleaned.match(/\d/g) || []).length;

            if (cleaned.startsWith('+') || cleaned.startsWith('00')) {
                const pretty = this.prettyPhone(cleaned);
                if (pretty) { this.fieldHints[key] = 'Will be sent as: ' + pretty; }
                else { delete this.fieldHints[key]; }
                if (digitCount < 8 || digitCount > 15) {
                    this.fieldWarnings[key] = 'That number looks unusually ' + (digitCount < 8 ? 'short' : 'long') + ' — please double-check.';
                } else {
                    delete this.fieldWarnings[key];
                }
            } else {
                // Local number — accepted as entered; just let the user know.
                delete this.fieldHints[key];
                this.fieldWarnings[key] = 'No country code detected — we’ll send this as a local number. Add + and your country code for an international number.';
            }
        },

        // Be forgiving about a missing scheme on URL fields: prepend https:// so a
        // user can type "linkedin.com/in/x" and confirm the saved address inline.
        applyUrlNormalization(field, repeatIndex) {
            const key = field.id + '_' + repeatIndex;
            let val = (this.answers[key] ?? '').toString().trim();
            if (!val) { delete this.fieldHints[key]; return; }
            if (!/^https?:\/\//i.test(val)) {
                val = 'https://' + val.replace(/^\/+/, '');
                this.answers[key] = val;
                this.fieldHints[key] = 'Will be saved as: ' + val;
            } else {
                this.answers[key] = val; // trims surrounding whitespace
                delete this.fieldHints[key];
            }
        },

        // Red border for a field that currently has an inline error.
        errorClass(field, repeatIndex) {
            return this.stepErrors[field.id + '_' + repeatIndex]
                ? 'border-red-400 focus:border-red-400'
                : '';
        },

        // Scroll the first errored field into view (and focus it). Used when a step
        // or the final submit is blocked, so the user is never left wondering why.
        scrollToFirstError() {
            let target = null, topMost = Infinity;
            for (const key of Object.keys(this.stepErrors)) {
                const el = document.getElementById('field_' + key);
                if (!el) continue;
                const rect = el.getBoundingClientRect();
                if (rect.height === 0) continue; // hidden (e.g. inactive repeat/step)
                const absTop = rect.top + window.scrollY;
                if (absTop < topMost) { topMost = absTop; target = el; }
            }
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const input = target.querySelector('input:not([type=hidden]), textarea, select');
                if (input) input.focus({ preventScroll: true });
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        // Inline format check for one field — runs on blur. Sets stepErrors[key].
        validateField(field, repeatIndex) {
            const key = field.id + '_' + repeatIndex;

            // Forgiving, non-blocking passes: expand 2-digit years; preview/soft-warn
            // phones; auto-prepend https:// on URLs.
            if (this.isYearField(field)) this.applyYearExpansion(field, repeatIndex);
            if (field.field_type === 'tel') {
                this.applyPhoneHints(field, repeatIndex);
                delete this.stepErrors[key]; // phone format must never block the user
            }
            if (field.field_type === 'url') this.applyUrlNormalization(field, repeatIndex);

            const val = field.field_type === 'date' ? this.dateValue(field, repeatIndex) : this.answers[key];
            if (!val) { delete this.stepErrors[key]; return true; }

            if (field.field_type === 'email') {
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                    this.stepErrors[key] = 'Please enter a valid email address.';
                    return false;
                }
            }
            if (field.field_type === 'url') {
                if (!urlRegex.test(val)) {
                    this.stepErrors[key] = 'Please enter a valid web address, e.g. linkedin.com/in/your-name.';
                    return false;
                }
            }
            if (field.field_type === 'number') {
                const n = Number(val);
                const min = field.validation_rules?.min;
                const max = field.validation_rules?.max;
                if (Number.isNaN(n)) {
                    this.stepErrors[key] = 'Please enter a number.';
                    return false;
                }
                if (min != null && n < min) {
                    this.stepErrors[key] = 'Value must be at least ' + min + '.';
                    return false;
                }
                if (max != null && n > max) {
                    this.stepErrors[key] = 'Value must be at most ' + max + '.';
                    return false;
                }
            }
            if (field.field_type === 'date') {
                const max = this.dateMaxFor(field);
                if (val > max) {
                    const label = (field.label_en || '').toLowerCase();
                    this.stepErrors[key] = label.includes('birth')
                        ? 'You must be at least 18 years old.'
                        : 'Date cannot be in the future.';
                    return false;
                }
            }
            delete this.stepErrors[key];
            return true;
        },

        // Validate one whole section. Returns true if no errors. Sets stepErrors for failing keys.
        validateSection(s) {
            if (!s) return true;
            const count = s.is_repeatable ? (this.repeats[s.id] || 1) : 1;
            let valid = true;

            for (const field of s.fields) {
                for (let r = 0; r < count; r++) {
                    if (r > 0 && !s.is_repeatable) break;
                    if (!this.fieldIsVisible(field, r)) continue;
                    const key = field.id + '_' + r;
                    const requiredHere = field.is_required && r === 0;

                    if (field.field_type === 'checkbox_group') {
                        if (requiredHere && !(this.checkboxAnswers[key]?.length)) {
                            this.stepErrors[key] = 'Please select at least one option.';
                            valid = false;
                        }
                    } else if (field.field_type === 'file') {
                        if (this.fileErrors[key]) {
                            // A file was selected but failed size/type validation — block advancement.
                            this.stepErrors[key] = this.fileErrors[key];
                            valid = false;
                        } else if (requiredHere && !this.fileNames[key]) {
                            this.stepErrors[key] = 'This file is required.';
                            valid = false;
                        }
                    } else if (field.field_type === 'declaration') {
                        if (requiredHere && !this.answers[key]) {
                            this.stepErrors[key] = 'You must accept the declaration to submit.';
                            valid = false;
                        }
                    } else {
                        const val = field.field_type === 'date' ? this.dateValue(field, r) : this.answers[key];
                        if (requiredHere && (val === undefined || val === null || val === '')) {
                            this.stepErrors[key] = 'This field is required.';
                            valid = false;
                        } else if (val) {
                            // Format check
                            if (!this.validateField(field, r)) valid = false;
                        }
                    }
                }
            }
            return valid;
        },

        validateCurrentStep() {
            this.stepErrors = {};
            return this.validateSection(this.currentSection);
        },

        // Walk every section. Returns index of first failing section, or -1 if all pass.
        validateAllSections() {
            this.stepErrors = {};
            let firstFail = -1;
            for (let i = 0; i < this.sections.length; i++) {
                const ok = this.validateSection(this.sections[i]);
                if (!ok && firstFail === -1) firstFail = i;
            }
            return firstFail;
        },

        saveToSession() {
            sessionStorage.setItem('ssbc_form', JSON.stringify({
                answers: this.answers,
                checkboxAnswers: this.checkboxAnswers,
                dateParts: this.dateParts,
                currentStep: this.step,
            }));
        },

        nextStep() {
            if (!this.validateCurrentStep()) {
                // Don't silently do nothing — take the user to the first problem.
                this.$nextTick(() => this.scrollToFirstError());
                return;
            }
            this.step++;
            this.activeRepeat = 0;
            this.saveToSession();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        prevStep() {
            this.step--;
            this.activeRepeat = 0;
            this.saveToSession();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // Index of the section that contains a given field id, or -1.
        sectionIndexForField(fieldId) {
            for (let i = 0; i < this.sections.length; i++) {
                if ((this.sections[i].fields || []).some(f => f.id === fieldId)) return i;
            }
            return -1;
        },

        // Map a Laravel 422 error bag (keys like "answers.12.0" / "files.12.0")
        // onto inline field errors and jump to the first failing step. Any error
        // that can't be tied to a field is surfaced in the summary so nothing is
        // silently dropped.
        applyServerErrors(errors, fallbackMessage) {
            this.stepErrors = {};
            let firstStep = null;
            const unmapped = [];
            for (const [key, messages] of Object.entries(errors || {})) {
                const msg = Array.isArray(messages) ? messages[0] : String(messages);
                const m = key.match(/^(?:answers|files)\.(\d+)\.(\d+)/);
                if (!m) { unmapped.push(msg); continue; }
                const fieldId = Number(m[1]);
                this.stepErrors[m[1] + '_' + m[2]] = msg;
                const sIdx = this.sectionIndexForField(fieldId);
                if (sIdx !== -1 && (firstStep === null || sIdx < firstStep)) firstStep = sIdx;
            }
            if (firstStep !== null) {
                this.firstErrorStep = firstStep;
                this.step = firstStep;
                this.activeRepeat = 0;
            }
            this.submitError = unmapped.length
                ? unmapped.join(' ')
                : (firstStep !== null
                    ? 'Please review the highlighted fields and try again.'
                    : (fallbackMessage || 'Your submission could not be processed. Please review your answers and try again.'));
            this.$nextTick(() => this.scrollToFirstError());
        },

        // Fetch a fresh CSRF token (for sessions that expired while the form was
        // open) by re-requesting this page and reading its <meta> token. Returns
        // true and patches the form's _token on success.
        async refreshCsrf(form) {
            try {
                const res = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store',
                });
                if (!res.ok) return false;
                const html = await res.text();
                const match = html.match(/name="csrf-token"\s+content="([^"]+)"/);
                const token = match ? match[1] : null;
                if (!token) return false;
                const input = form.querySelector('input[name="_token"]');
                if (input) input.value = token;
                return true;
            } catch (e) {
                return false;
            }
        },

        // POST the form via XHR so we get upload progress and can keep a generous
        // ceiling for large (up to 100 MB) attachments without aborting them.
        // Resolves with { status, url, body }; rejects with 'network'/'timeout'.
        postForm(form, action) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
                // High ceiling so a real 100 MB upload on a slow link is not killed
                // mid-transfer; only a truly stalled server hits this.
                xhr.timeout = 600000; // 10 minutes
                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                    }
                };
                xhr.onload = () => resolve({ status: xhr.status, url: xhr.responseURL, body: xhr.responseText });
                xhr.onerror = () => reject(new Error('network'));
                xhr.ontimeout = () => reject(new Error('timeout'));
                xhr.onabort = () => reject(new Error('abort'));
                xhr.send(new FormData(form));
            });
        },

        async onSubmit(event) {
            if (this.submitting) return;
            const form = event.target;

            // Preview mode (action="#") can't be submitted.
            const action = form.getAttribute('action');
            if (!action || action === '#') return;

            const failingStep = this.validateAllSections();
            if (failingStep !== -1) {
                this.firstErrorStep = failingStep;
                this.submitError = 'Please complete all required fields before submitting.';
                this.step = failingStep;
                this.activeRepeat = 0;
                this.$nextTick(() => this.scrollToFirstError());
                return;
            }

            this.submitError = null;
            this.firstErrorStep = null;
            this.uploadProgress = 0;
            this.submitting = true;
            // Re-enable if the page is restored from bfcache (back nav).
            window.addEventListener('pageshow', () => { this.submitting = false; }, { once: true });

            try {
                let res = await this.postForm(form, action);

                // Session/CSRF expired while the form sat open: fetch a fresh token
                // and retry the submission once.
                if (res.status === 419 && await this.refreshCsrf(form)) {
                    this.uploadProgress = 0;
                    res = await this.postForm(form, action);
                }

                // Success: the controller 302-redirects to the thank-you page and
                // XHR transparently follows it, so we land on a 200 at that URL.
                if (res.status === 200) {
                    sessionStorage.removeItem('ssbc_form');
                    window.location.assign(res.url || window.location.href);
                    return;
                }

                // Validation errors — surface them inline against each field.
                if (res.status === 422) {
                    let data = {};
                    try { data = JSON.parse(res.body); } catch (e) {}
                    this.applyServerErrors(data.errors, data.message);
                    this.submitting = false;
                    return;
                }

                // Attachment(s) too large for the server to accept.
                if (res.status === 413) {
                    this.submitError = 'Your attached file is too large to upload. Please attach a smaller file (up to 100 MB) and try again.';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    this.submitting = false;
                    return;
                }

                // Still 419 after a token refresh — most often an attachment larger
                // than the server accepts (PHP discards the whole request, token and
                // all), or a session that is fully gone.
                if (res.status === 419) {
                    this.submitError = 'We couldn’t verify your session. This usually means an attached file is too large, or the form was open too long. Your answers are saved — please check any attachments, reload the page, and submit again.';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    this.submitting = false;
                    return;
                }

                // Anything else (0 = blocked/offline, 500, 503, …) — show it, keep answers.
                this.submitError = res.status
                    ? 'Something went wrong on our side (error ' + res.status + '). Your answers are saved — please try again in a moment.'
                    : 'We couldn’t reach the server. Your answers are saved — please check your connection and try again.';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                this.submitting = false;
            } catch (e) {
                this.submitError = e.message === 'timeout'
                    ? 'The upload timed out — your connection may be slow or the file very large. Your answers are saved; please try again.'
                    : 'We couldn’t reach the server. Your answers are saved — please check your connection and try again.';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                this.submitting = false;
            }
        },
    };
}
</script>

@endsection

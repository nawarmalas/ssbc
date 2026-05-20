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
@endphp

@section('title', __('join.hero.heading') . ' — ' . __('common.site_name'))

@section('title', $pageTitle . ' - ' . __('common.site_name'))

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
                                    </div>
                                </div>
                            </template>

                            {{-- All repeats in DOM; x-show hides non-active repeat for repeatable sections --}}
                            <template x-for="ri in (section.is_repeatable ? (repeats[section.id] || 1) : 1)" :key="ri">
                                <div x-show="!section.is_repeatable || activeRepeat === ri - 1"
                                     class="space-y-6">
                                    <template x-for="field in section.fields" :key="field.id">
                                        <div x-show="fieldIsVisible(field, ri-1)">
                                            {{-- Label --}}
                                            <label class="ssbc-label" :for="'f_' + field.id + '_' + (ri-1)">
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
                                                    :name="'answers[' + field.id + '][' + (ri-1) + ']'"
                                                    :placeholder="(locale === 'ar' ? field.placeholder_ar : field.placeholder_en) || ''"
                                                    :required="step === sIdx && field.is_required && ri === 1"
                                                    :min="field.field_type === 'number' ? (field.validation_rules?.min ?? null) : null"
                                                    :max="field.field_type === 'number' ? (field.validation_rules?.max ?? null) : null"
                                                    x-model="answers[field.id + '_' + (ri-1)]"
                                                    @blur="validateField(field, ri-1)"
                                                    class="ssbc-input"
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
                                                                x-model="dateParts[dateKey(field, ri-1)].month"
                                                                @change="validateField(field, ri-1)"
                                                                :aria-label="'Month for ' + (locale === 'ar' ? field.label_ar : field.label_en)">
                                                            <option value="">Month</option>
                                                            <template x-for="month in months" :key="month.value">
                                                                <option :value="month.value" x-text="month.label"></option>
                                                            </template>
                                                        </select>
                                                        <select class="ssbc-input"
                                                                x-model="dateParts[dateKey(field, ri-1)].day"
                                                                @change="validateField(field, ri-1)"
                                                                :aria-label="'Day for ' + (locale === 'ar' ? field.label_ar : field.label_en)">
                                                            <option value="">Day</option>
                                                            <template x-for="day in dateDaysFor(field, ri-1)" :key="day">
                                                                <option :value="day" x-text="day"></option>
                                                            </template>
                                                        </select>
                                                        <select class="ssbc-input"
                                                                x-model="dateParts[dateKey(field, ri-1)].year"
                                                                @change="validateField(field, ri-1)"
                                                                :aria-label="'Year for ' + (locale === 'ar' ? field.label_ar : field.label_en)">
                                                            <option value="">Year</option>
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
                                                               x-text="'.' + (field.file_config?.accepted_types || ['pdf']).join(', .') + ' — max ' + (field.file_config?.max_size_mb || 5) + ' MB'"></p>
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
                                                        <span class="text-sm text-ssbc-dark leading-relaxed"
                                                              x-text="locale === 'ar' ? field.label_ar : field.label_en"></span>
                                                    </label>
                                                </div>
                                            </template>

                                            {{-- Field error --}}
                                            <p x-show="stepErrors[field.id + '_' + (ri-1)]"
                                               x-text="stepErrors[field.id + '_' + (ri-1)]"
                                               class="text-red-500 text-xs mt-1"></p>
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
                </form>
            </div>
        </div>
    </div>
</section>

<script>
function dynamicForm(sectionsJson) {
    const sections = sectionsJson;
    const locale = document.documentElement.lang || 'en';

    // International phone: +<digits, 8-15>
    const phoneRegex = /^\+[1-9]\d{7,14}$/;
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
        submitting: false,
        submitError: null,
        firstErrorStep: null,
        codeToId: {},
        months: [
            { value: 1, label: 'Jan' }, { value: 2, label: 'Feb' }, { value: 3, label: 'Mar' },
            { value: 4, label: 'Apr' }, { value: 5, label: 'May' }, { value: 6, label: 'Jun' },
            { value: 7, label: 'Jul' }, { value: 8, label: 'Aug' }, { value: 9, label: 'Sep' },
            { value: 10, label: 'Oct' }, { value: 11, label: 'Nov' }, { value: 12, label: 'Dec' },
        ],

        init() {
            sections.forEach(s => {
                if (s.is_repeatable) this.repeats[s.id] = 1;
                this.initDatePartsForSection(s, 1);
                (s.fields || []).forEach(f => {
                    if (f.code) this.codeToId[f.code] = f.id;
                });
            });
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
            const maxBytes = (field.file_config?.max_size_mb || 5) * 1024 * 1024;
            const accepted = (field.file_config?.accepted_types || ['pdf']).map(t => '.' + t);
            const ext = '.' + file.name.split('.').pop().toLowerCase();

            if (!accepted.includes(ext)) {
                this.fileErrors[key] = 'File type not accepted. Allowed: ' + accepted.join(', ');
                this.fileNames[key] = null;
                return;
            }
            if (file.size > maxBytes) {
                this.fileErrors[key] = 'File too large. Max ' + (field.file_config?.max_size_mb || 5) + ' MB.';
                this.fileNames[key] = null;
                return;
            }
            this.fileErrors[key] = null;
            this.fileNames[key] = file.name;
        },

        // Inline format check for one field — runs on blur. Sets stepErrors[key].
        validateField(field, repeatIndex) {
            const key = field.id + '_' + repeatIndex;
            const val = field.field_type === 'date' ? this.dateValue(field, repeatIndex) : this.answers[key];
            if (!val) { delete this.stepErrors[key]; return true; }

            if (field.field_type === 'email') {
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                    this.stepErrors[key] = 'Please enter a valid email address.';
                    return false;
                }
            }
            if (field.field_type === 'tel') {
                if (!phoneRegex.test(String(val).replace(/\s/g, ''))) {
                    this.stepErrors[key] = 'Include country code, e.g. +966 50 000 0000';
                    return false;
                }
            }
            if (field.field_type === 'url') {
                if (!urlRegex.test(val)) {
                    this.stepErrors[key] = 'Please enter a full URL (https://example.com).';
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
                        if (requiredHere && !this.fileNames[key]) {
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

        nextStep() {
            if (!this.validateCurrentStep()) return;
            this.step++;
            this.activeRepeat = 0;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        prevStep() {
            this.step--;
            this.activeRepeat = 0;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        onSubmit(event) {
            if (this.submitting) return;

            const failingStep = this.validateAllSections();
            if (failingStep !== -1) {
                this.firstErrorStep = failingStep;
                this.submitError = 'Please complete all required fields before submitting.';
                this.step = failingStep;
                this.activeRepeat = 0;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            // All client-side checks passed — release to native submit.
            this.submitError = null;
            this.firstErrorStep = null;
            this.submitting = true;
            // Re-enable if the page is restored from bfcache (back nav).
            window.addEventListener('pageshow', () => { this.submitting = false; }, { once: true });
            event.target.submit();
        },
    };
}
</script>

@endsection

@extends('layouts.app')

@php $locale = app()->getLocale(); @endphp

@section('title', __('join.hero.heading') . ' — ' . __('common.site_name'))

@section('content')

@include('partials.page-hero', [
    'eyebrow' => __('join.hero.eyebrow'),
    'heading' => __('join.hero.heading'),
    'body'    => __('join.intro'),
])

<section class="bg-white">
    <div class="ssbc-container py-16">
        <div class="max-w-3xl mx-auto">

            {{-- Institutional header --}}
            <div class="flex flex-col items-center mb-12 text-center">
                <img src="{{ asset('images/logos/logo-light.png') }}"
                     alt="{{ __('common.site_name') }}"
                     class="h-16 md:h-20 w-auto mb-4" width="800" height="346" loading="lazy">
                <div class="w-16 h-px bg-ssbc-gold"></div>
            </div>

            @if(isset($preview) && $preview)
                <div class="mb-8 bg-amber-50 border border-amber-300 px-4 py-3 text-sm text-amber-800 text-center">
                    Preview Mode — this form cannot be submitted from here.
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-8 border border-red-300 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold mb-2">Please correct the following errors:</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div x-data="dynamicForm({{ $form->toJson() }})" x-init="init()">

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
                      action="{{ isset($preview) && $preview ? '#' : route('join.store', ['locale' => $locale]) }}"
                      enctype="multipart/form-data"
                      @submit.prevent="onSubmit">
                    @csrf

                    {{-- Hidden _repeats fields --}}
                    <template x-for="(count, sectionId) in repeats" :key="sectionId">
                        <input type="hidden" :name="'_repeats[' + sectionId + ']'" :value="count">
                    </template>

                    {{-- Current section --}}
                    <template x-if="currentSection">
                        <div>
                            <h2 class="text-2xl font-display font-bold text-ssbc-green mb-2"
                                x-text="locale === 'ar' ? currentSection.title_ar : currentSection.title_en"></h2>
                            <div class="w-12 h-px bg-ssbc-gold mb-8"></div>

                            {{-- Repeatable section tabs --}}
                            <template x-if="currentSection.is_repeatable">
                                <div>
                                    <div class="flex gap-2 flex-wrap mb-6">
                                        <template x-for="r in repeatCount" :key="r">
                                            <button type="button"
                                                    @click="activeRepeat = r - 1"
                                                    :class="activeRepeat === r - 1
                                                        ? 'bg-ssbc-gold text-ssbc-green border-ssbc-gold'
                                                        : 'bg-white text-ssbc-sage border-ssbc-green/20'"
                                                    class="px-4 py-1.5 rounded-full text-sm font-semibold border transition-colors">
                                                <span x-text="(locale === 'ar' ? currentSection.title_ar : currentSection.title_en) + ' ' + r"></span>
                                            </button>
                                        </template>
                                        <button type="button"
                                                x-show="repeatCount < currentSection.max_repeats"
                                                @click="addRepeat()"
                                                class="px-4 py-1.5 rounded-full text-sm border border-dashed border-ssbc-gold text-ssbc-gold">
                                            + Add another
                                        </button>
                                    </div>
                                </div>
                            </template>

                            {{-- Fields --}}
                            <div class="space-y-6">
                                <template x-for="field in currentSection.fields" :key="field.id">
                                    <div>
                                        {{-- Label --}}
                                        <label class="ssbc-label" :for="'f_' + field.id + '_' + activeRepeat">
                                            <span x-text="locale === 'ar' ? field.label_ar : field.label_en"></span>
                                            <span x-show="field.is_required" class="text-red-500 ml-0.5">*</span>
                                        </label>

                                        {{-- text / email / tel / number / url --}}
                                        <template x-if="['text','email','tel','number','url'].includes(field.field_type)">
                                            <input
                                                :id="'f_' + field.id + '_' + activeRepeat"
                                                :type="field.field_type"
                                                :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                :placeholder="(locale === 'ar' ? field.placeholder_ar : field.placeholder_en) || ''"
                                                :required="field.is_required && activeRepeat === 0"
                                                x-model="answers[field.id + '_' + activeRepeat]"
                                                class="ssbc-input"
                                            >
                                        </template>

                                        {{-- textarea --}}
                                        <template x-if="field.field_type === 'textarea'">
                                            <textarea
                                                :id="'f_' + field.id + '_' + activeRepeat"
                                                :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                :placeholder="(locale === 'ar' ? field.placeholder_ar : field.placeholder_en) || ''"
                                                :required="field.is_required && activeRepeat === 0"
                                                x-model="answers[field.id + '_' + activeRepeat]"
                                                rows="3"
                                                class="ssbc-input"
                                            ></textarea>
                                        </template>

                                        {{-- date --}}
                                        <template x-if="field.field_type === 'date'">
                                            <input
                                                :id="'f_' + field.id + '_' + activeRepeat"
                                                type="date"
                                                :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                :required="field.is_required && activeRepeat === 0"
                                                x-model="answers[field.id + '_' + activeRepeat]"
                                                class="ssbc-input"
                                            >
                                        </template>

                                        {{-- select --}}
                                        <template x-if="field.field_type === 'select'">
                                            <select
                                                :id="'f_' + field.id + '_' + activeRepeat"
                                                :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                :required="field.is_required && activeRepeat === 0"
                                                x-model="answers[field.id + '_' + activeRepeat]"
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
                                                               :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                               :value="opt.value"
                                                               x-model="answers[field.id + '_' + activeRepeat]"
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
                                                                   :value="opt.value"
                                                                   :checked="(checkboxAnswers[field.id + '_' + activeRepeat] || []).includes(opt.value)"
                                                                   @change="toggleCheckbox(field.id, activeRepeat, opt.value)"
                                                                   class="mt-0.5 shrink-0 text-ssbc-gold focus:ring-ssbc-gold">
                                                            <span x-text="locale === 'ar' ? opt.label_ar : opt.label_en"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                                {{-- Hidden serialized value --}}
                                                <input type="hidden"
                                                       :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                       :value="JSON.stringify(checkboxAnswers[field.id + '_' + activeRepeat] || [])">
                                            </div>
                                        </template>

                                        {{-- file --}}
                                        <template x-if="field.field_type === 'file'">
                                            <div>
                                                <div class="border-2 border-dashed border-ssbc-green/20 p-6 text-center hover:border-ssbc-gold transition-colors relative"
                                                     @dragover.prevent
                                                     @drop.prevent="handleFileDrop(field, activeRepeat, $event)">
                                                    <input type="file"
                                                           :id="'f_' + field.id + '_' + activeRepeat"
                                                           :name="'files[' + field.id + '][' + activeRepeat + ']'"
                                                           :accept="'.' + (field.file_config?.accepted_types || ['pdf']).join(',.')"
                                                           :required="field.is_required && activeRepeat === 0"
                                                           @change="handleFileSelect(field, activeRepeat, $event)"
                                                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                                    <div x-show="!fileNames[field.id + '_' + activeRepeat]">
                                                        <p class="text-sm text-ssbc-sage">Drag & drop or click to browse</p>
                                                        <p class="text-xs text-ssbc-sage/70 mt-1"
                                                           x-text="'.' + (field.file_config?.accepted_types || ['pdf']).join(', .') + ' — max ' + (field.file_config?.max_size_mb || 5) + ' MB'"></p>
                                                    </div>
                                                    <div x-show="fileNames[field.id + '_' + activeRepeat]"
                                                         class="flex items-center justify-center gap-2">
                                                        <span class="text-sm text-ssbc-green font-semibold"
                                                              x-text="fileNames[field.id + '_' + activeRepeat]"></span>
                                                        <span class="text-xs text-ssbc-sage">✓</span>
                                                    </div>
                                                </div>
                                                <p x-show="fileErrors[field.id + '_' + activeRepeat]"
                                                   x-text="fileErrors[field.id + '_' + activeRepeat]"
                                                   class="text-red-500 text-xs mt-1"></p>
                                            </div>
                                        </template>

                                        {{-- declaration --}}
                                        <template x-if="field.field_type === 'declaration'">
                                            <div class="border border-ssbc-green/15 bg-ssbc-beige/50 p-6">
                                                <label class="flex items-start gap-3 cursor-pointer">
                                                    <input type="checkbox"
                                                           :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                           value="1"
                                                           :required="field.is_required"
                                                           x-model="answers[field.id + '_' + activeRepeat]"
                                                           class="mt-1 rounded-none border-ssbc-green/40 text-ssbc-gold focus:ring-ssbc-gold">
                                                    <span class="text-sm text-ssbc-dark leading-relaxed"
                                                          x-text="locale === 'ar' ? field.label_ar : field.label_en"></span>
                                                </label>
                                            </div>
                                        </template>

                                        {{-- Field error --}}
                                        <p x-show="stepErrors[field.id + '_' + activeRepeat]"
                                           x-text="stepErrors[field.id + '_' + activeRepeat]"
                                           class="text-red-500 text-xs mt-1"></p>
                                    </div>
                                </template>
                            </div>
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
                        <button type="submit" class="ssbc-btn-primary"
                                x-show="step === sections.length - 1">
                            {{ __('join.submit') }}
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

    return {
        sections,
        locale,
        step: 0,
        activeRepeat: 0,
        answers: {},
        checkboxAnswers: {},
        repeats: {},
        fileNames: {},
        fileErrors: {},
        stepErrors: {},

        init() {
            sections.forEach(s => {
                if (s.is_repeatable) this.repeats[s.id] = 1;
            });
        },

        get currentSection() {
            return this.sections[this.step] || null;
        },

        get repeatCount() {
            if (!this.currentSection?.is_repeatable) return 1;
            return this.repeats[this.currentSection.id] || 1;
        },

        addRepeat() {
            const s = this.currentSection;
            if (!s?.is_repeatable) return;
            const current = this.repeats[s.id] || 1;
            if (current < s.max_repeats) {
                this.repeats[s.id] = current + 1;
                this.activeRepeat = current;
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

        validateCurrentStep() {
            const s = this.currentSection;
            if (!s) return true;
            const count = s.is_repeatable ? (this.repeats[s.id] || 1) : 1;
            let valid = true;
            this.stepErrors = {};

            for (const field of s.fields) {
                if (!field.is_required) continue;
                for (let r = 0; r < count; r++) {
                    if (r > 0 && !s.is_repeatable) break;
                    const key = field.id + '_' + r;

                    if (field.field_type === 'checkbox_group') {
                        if (!(this.checkboxAnswers[key]?.length)) {
                            if (r === 0) { this.stepErrors[key] = 'Please select at least one option.'; valid = false; }
                        }
                    } else if (field.field_type === 'file') {
                        if (!this.fileNames[key] && r === 0) {
                            this.stepErrors[key] = 'This file is required.'; valid = false;
                        }
                    } else if (field.field_type !== 'declaration') {
                        const val = this.answers[key];
                        if (!val || val === '') {
                            if (r === 0) { this.stepErrors[key] = 'This field is required.'; valid = false; }
                        }
                    }
                }
            }
            return valid;
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
            if (!this.validateCurrentStep()) { event.preventDefault(); return; }
            event.target.submit();
        },
    };
}
</script>

@endsection

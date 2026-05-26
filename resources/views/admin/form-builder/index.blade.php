@extends('layouts.admin')

@section('title', 'Form Builder — Join Us')

@section('page_title', 'Form Builder - ' . $formDefinition->title_en)

@section('content')
<div class="w-12 h-px bg-ssbc-gold mb-4"></div>
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-display font-bold text-ssbc-green">Form Builder — Join Us</h1>
    <div class="flex gap-3">
        <a href="{{ route('admin.forms.index') }}"
           class="ssbc-btn-outline-dark text-sm">← Back to Forms</a>
        <a href="{{ route('admin.forms.preview', $formDefinition) }}" target="_blank"
           class="ssbc-btn-outline-dark text-sm">Preview Form ↗</a>
    </div>
</div>

<div x-data="formBuilder()" x-init="init()">
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-ssbc-sage">
            Drag sections or fields to rearrange them. Order changes save automatically.
        </p>
        <p x-show="builderStatus" x-cloak
           class="text-xs font-semibold text-ssbc-green"
           x-text="builderStatus"></p>
    </div>

    {{-- Add Section --}}
    <div class="mb-4 flex justify-end">
        <button type="button" @click="openSectionModal(null)"
                class="ssbc-btn-primary text-sm">+ Add Section</button>
    </div>

    {{-- Sections accordion --}}
    <div x-ref="sectionsList" class="space-y-3">
        <template x-for="section in sections" :key="section.id">
            <div class="ssbc-admin-card" :data-id="section.id">

                {{-- Section header --}}
                <div class="flex items-center gap-3 px-4 py-3 cursor-pointer select-none"
                     @click="toggleSection(section.id)">
                    <span class="drag-handle cursor-grab text-ssbc-sage/60 hover:text-ssbc-sage text-lg leading-none">⠿</span>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-ssbc-dark text-sm" x-text="section.title_en"></p>
                        <p class="text-xs text-ssbc-sage" dir="rtl" x-text="section.title_ar"></p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span x-show="section.is_repeatable"
                              class="text-xs bg-ssbc-gold/20 text-ssbc-green px-2 py-0.5 rounded-full">Repeatable</span>
                        <span class="text-xs text-ssbc-sage" x-text="(section.all_fields?.length ?? 0) + ' fields'"></span>
                        <button type="button" @click.stop="openSectionModal(section)"
                                class="text-xs text-ssbc-sage hover:text-ssbc-green px-2">Edit</button>
                        <button type="button" @click.stop="confirmDeleteSection(section)"
                                class="text-xs text-red-500 hover:text-red-700 px-2">Delete</button>
                        <span class="text-ssbc-sage" x-text="openSections.includes(section.id) ? '▲' : '▼'"></span>
                    </div>
                </div>

                {{-- Section body (fields) --}}
                <div x-show="openSections.includes(section.id)" x-cloak
                     class="border-t border-ssbc-green/10 px-4 py-4">

                    {{-- Fields list --}}
                    <div :id="'fields-' + section.id" class="space-y-2 mb-4">
                        <template x-for="field in section.all_fields" :key="field.id">
                            <div class="flex items-center gap-2 p-3 bg-ssbc-light border border-ssbc-green/10 rounded"
                                 :data-id="field.id">
                                <span class="field-drag-handle cursor-grab text-ssbc-sage/60 hover:text-ssbc-sage">⠿</span>
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm text-ssbc-dark" x-text="field.label_en"></span>
                                    <span class="ml-2 text-xs bg-ssbc-green/10 text-ssbc-green px-1.5 py-0.5 rounded"
                                          x-text="field.field_type"></span>
                                    <span x-show="field.is_required"
                                          class="ml-1 text-xs text-red-500">required</span>
                                    <span x-show="!field.is_active"
                                          class="ml-1 text-xs bg-gray-200 text-gray-500 px-1.5 py-0.5 rounded">inactive</span>
                                    <span x-show="fieldIsSystemManaged(field)"
                                          class="ml-1 text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded border border-amber-300">🔒 system</span>
                                    <span x-show="field.options_source === 'sectors' && !fieldIsSystemManaged(field)"
                                          class="ml-1 text-xs bg-ssbc-gold/20 text-ssbc-green px-1.5 py-0.5 rounded">↻ Sectors</span>
                                </div>
                                <button type="button" @click="openFieldModal(field, section.id)"
                                        class="text-xs text-ssbc-sage hover:text-ssbc-green px-2">Edit</button>
                                <a x-show="fieldIsSystemManaged(field) || field.options_source === 'sectors'"
                                   href="{{ route('admin.sectors.index') }}"
                                   class="text-xs text-ssbc-gold hover:underline px-2">Manage Sectors ↗</a>
                                <button x-show="!fieldIsSystemManaged(field)"
                                        type="button" @click="confirmDeleteField(field, section)"
                                        class="text-xs text-red-500 hover:text-red-700 px-2">Delete</button>
                            </div>
                        </template>
                        <p x-show="!section.all_fields?.length"
                           class="text-xs text-ssbc-sage italic py-2">No fields yet.</p>
                    </div>

                    <button type="button" @click="openFieldModal(null, section.id)"
                            class="text-sm text-ssbc-gold hover:underline">+ Add Field</button>
                </div>
            </div>
        </template>
    </div>

    {{-- ── Section Modal ──────────────────────────────────────────────────── --}}
    <div x-show="sectionModalOpen" x-cloak
         class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
        <div class="bg-white w-full max-w-md p-6 shadow-xl" @click.outside="sectionModalOpen = false">
            <h2 class="text-lg font-bold text-ssbc-green mb-5"
                x-text="editingSection?.id ? 'Edit Section' : 'Add Section'"></h2>

            <div x-show="sectionError" x-cloak class="mb-4 border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">
                <span x-text="sectionError"></span>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="ssbc-label">Title (English) *</label>
                    <input type="text" x-model="sectionForm.title_en" class="ssbc-input">
                </div>
                <div>
                    <label class="ssbc-label">Title (Arabic) *</label>
                    <input type="text" x-model="sectionForm.title_ar" class="ssbc-input" dir="rtl">
                </div>
                <div class="flex items-center gap-3">
                    <label class="ssbc-label mb-0">Repeatable section?</label>
                    <input type="checkbox" x-model="sectionForm.is_repeatable" class="rounded border-ssbc-green/40">
                </div>
                <div x-show="sectionForm.is_repeatable">
                    <label class="ssbc-label">Max Repeats</label>
                    <input type="number" x-model.number="sectionForm.max_repeats" min="2" max="10" class="ssbc-input w-24">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="sectionModalOpen = false" class="ssbc-btn-outline-dark text-sm">Cancel</button>
                <button type="button" @click="saveSection()" :disabled="saving" class="ssbc-btn-primary text-sm">
                    <span x-text="saving ? 'Saving…' : 'Save'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Field Modal ────────────────────────────────────────────────────── --}}
    <div x-show="fieldModalOpen" x-cloak
         class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-2xl p-6 shadow-xl my-8" @click.outside="fieldModalOpen = false">
            <h2 class="text-lg font-bold text-ssbc-green mb-5"
                x-text="editingField?.id ? 'Edit Field' : 'Add Field'"></h2>

            <div x-show="fieldError" x-cloak class="mb-4 border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">
                <span x-text="fieldError"></span>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="ssbc-label">Label (English) *</label>
                    <input type="text" x-model="fieldForm.label_en" class="ssbc-input">
                </div>
                <div>
                    <label class="ssbc-label">Label (Arabic) *</label>
                    <input type="text" x-model="fieldForm.label_ar" class="ssbc-input" dir="rtl">
                </div>
                {{-- Placeholder is the grey hint text inside an empty input —
                     only meaningful for free-text field types. --}}
                <div x-show="['text','textarea','email','tel','number','url'].includes(fieldForm.field_type)">
                    <label class="ssbc-label">Placeholder (English)</label>
                    <input type="text" x-model="fieldForm.placeholder_en" class="ssbc-input">
                </div>
                <div x-show="['text','textarea','email','tel','number','url'].includes(fieldForm.field_type)">
                    <label class="ssbc-label">Placeholder (Arabic)</label>
                    <input type="text" x-model="fieldForm.placeholder_ar" class="ssbc-input" dir="rtl">
                </div>
                <div>
                    <label class="ssbc-label">Field Type *</label>
                    <div class="relative" @click.outside="fieldTypeMenuOpen = false">
                        <button type="button"
                                @click="fieldTypeMenuOpen = !fieldTypeMenuOpen"
                                :disabled="fieldIsSystemManaged(editingField)"
                                class="ssbc-input flex items-center justify-between text-left disabled:cursor-not-allowed disabled:opacity-60">
                            <span x-text="fieldTypeLabel(fieldForm.field_type)"></span>
                            <span class="text-ssbc-sage">▾</span>
                        </button>
                        <div x-show="fieldTypeMenuOpen" x-cloak
                             class="absolute left-0 right-0 z-50 mt-1 max-h-64 overflow-y-auto border border-gray-300 bg-white shadow-lg">
                            <template x-for="type in fieldTypes" :key="type">
                                <button type="button"
                                        @click="setFieldType(type); fieldTypeMenuOpen = false"
                                        class="block w-full px-3 py-2 text-left text-sm hover:bg-ssbc-beige/60"
                                        :class="fieldForm.field_type === type ? 'bg-ssbc-gold/20 font-semibold text-ssbc-green' : 'text-ssbc-dark'">
                                    <span x-text="fieldTypeLabel(type)"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="flex gap-4 items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                        <input type="checkbox" x-model="fieldForm.is_required" class="rounded border-ssbc-green/40">
                        Required
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                        <input type="checkbox" x-model="fieldForm.is_active" class="rounded border-ssbc-green/40">
                        Active
                    </label>
                </div>
            </div>

            {{-- Sector-filled options toggle (select / radio / checkbox_group) --}}
            <label x-show="['select','radio','checkbox_group'].includes(fieldForm.field_type) && !fieldIsSystemManaged(editingField)"
                   x-cloak
                   class="mt-6 flex items-center gap-2 cursor-pointer text-sm text-ssbc-dark">
                <input type="checkbox"
                       :checked="fieldForm.options_source === 'sectors'"
                       @change="fieldForm.options_source = $event.target.checked ? 'sectors' : 'manual'"
                       class="rounded border-ssbc-green/40">
                Fill options automatically from the Sectors list
            </label>

            {{-- Options builder (select / radio / checkbox_group) --}}
            <div x-show="['select','radio','checkbox_group'].includes(fieldForm.field_type) && !fieldIsSystemManaged(editingField) && fieldForm.options_source !== 'sectors'" class="mt-6">
                <div class="flex items-center justify-between mb-3">
                    <label class="ssbc-label mb-0">Options</label>
                    <button type="button" @click="addOption()" class="text-xs text-ssbc-gold hover:underline">+ Add Option</button>
                </div>
                <div x-show="fieldForm.options.length > 0" class="flex gap-2 text-xs font-semibold text-ssbc-sage mb-1 px-0.5">
                    <span class="flex-1">Label (English)</span>
                    <span class="flex-1">Label (Arabic)</span>
                    <span class="w-28">Value</span>
                    <span class="w-5"></span>
                </div>
                <div class="space-y-2">
                    <template x-for="(opt, i) in fieldForm.options" :key="i">
                        <div class="flex gap-2 items-center">
                            <input type="text" x-model="opt.label_en" placeholder="e.g. Technology"
                                   class="ssbc-input text-sm flex-1">
                            <input type="text" x-model="opt.label_ar" placeholder="e.g. تكنولوجيا" dir="rtl"
                                   class="ssbc-input text-sm flex-1">
                            <input type="text" x-model="opt.value" placeholder="tech"
                                   class="ssbc-input text-sm w-28">
                            <button type="button" @click="fieldForm.options.splice(i,1)"
                                    class="text-red-500 hover:text-red-700 shrink-0">✕</button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Sectors-managed notice (system field, or sector-filled options) --}}
            <div x-show="fieldIsSystemManaged(editingField) || fieldForm.options_source === 'sectors'" x-cloak
                 class="mt-4 border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Options for this field are filled automatically from the <a href="{{ route('admin.sectors.index') }}" class="underline font-medium">Sectors list</a> and stay in sync. Labels, placeholders, and required status can still be edited here.
            </div>

            {{-- File config --}}
            <div x-show="fieldForm.field_type === 'file'" class="mt-6">
                <label class="ssbc-label">Accepted File Types</label>
                <div class="flex flex-wrap gap-3 mt-2">
                    <template x-for="type in ['pdf','jpg','jpeg','png','doc','docx']" :key="type">
                        <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                            <input type="checkbox"
                                   :value="type"
                                   :checked="fieldForm.file_config.accepted_types?.includes(type)"
                                   @change="toggleFileType(type)"
                                   class="rounded border-ssbc-green/40">
                            <span x-text="type.toUpperCase()"></span>
                        </label>
                    </template>
                </div>
                <div class="mt-3">
                    <label class="ssbc-label">Max File Size (MB)</label>
                    <input type="number" x-model.number="fieldForm.file_config.max_size_mb"
                           min="1" max="50" class="ssbc-input w-24">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="fieldModalOpen = false" class="ssbc-btn-outline-dark text-sm">Cancel</button>
                <button type="button" @click="saveField()" :disabled="saving" class="ssbc-btn-primary text-sm">
                    <span x-text="saving ? 'Saving…' : 'Save Field'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Delete Confirmation Modal ────────────────────────────────────── --}}
    <div x-show="confirmModal.open" x-cloak
         class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
        <div class="bg-white w-full max-w-sm p-6 shadow-xl">
            <p class="text-sm text-ssbc-dark mb-1" x-text="confirmModal.message"></p>
            <p x-show="confirmModal.warning" class="text-xs text-red-600 mt-2 mb-4" x-text="confirmModal.warning"></p>
            <div class="mt-4 flex justify-end gap-3">
                <button type="button" @click="confirmModal.open = false" class="ssbc-btn-outline-dark text-sm">Cancel</button>
                <button type="button" @click="confirmModal.action()" class="bg-red-600 text-white text-sm px-4 py-2">Confirm Delete</button>
            </div>
        </div>
    </div>

</div>

{{-- SortableJS --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>

<script>
function formBuilder() {
    return {
        sections: @json(json_decode($sectionsJson)),
        openSections: [],
        saving: false,
        sectionError: null,
        fieldError: null,
        builderStatus: null,
        builderStatusTimer: null,
        fieldTypeMenuOpen: false,
        fieldTypes: @json($fieldTypes),

        sectionModalOpen: false,
        editingSection: null,
        sectionForm: { title_en: '', title_ar: '', is_repeatable: false, max_repeats: 5 },

        fieldModalOpen: false,
        editingField: null,
        editingFieldSectionId: null,
        fieldForm: {
            section_id: null, label_en: '', label_ar: '',
            placeholder_en: '', placeholder_ar: '',
            field_type: 'text', is_required: true, is_active: true,
            options: [], options_source: 'manual',
            validation_rules: {}, file_config: { accepted_types: ['pdf'], max_size_mb: 10 },
        },

        confirmModal: { open: false, message: '', warning: '', action: () => {} },

        csrf: document.querySelector('meta[name="csrf-token"]').content,
        endpoints: {
            sections: @json(route('admin.forms.sections.store', $formDefinition)),
            reorderSections: @json(route('admin.forms.sections.reorder', $formDefinition)),
            fields: @json(route('admin.forms.fields.store', $formDefinition)),
            reorderFields: @json(route('admin.forms.fields.reorder', $formDefinition)),
        },

        init() {
            this.openSections = this.sections.length ? [this.sections[0].id] : [];
            this.$nextTick(() => this.initSortable());
        },

        toggleSection(id) {
            if (this.openSections.includes(id)) {
                this.openSections = this.openSections.filter(s => s !== id);
            } else {
                this.openSections.push(id);
            }
            this.$nextTick(() => this.initFieldSortable(id));
        },

        initSortable() {
            const list = this.$refs.sectionsList;
            if (!list) return;
            if (list._ssbcSortable) return;
            list._ssbcSortable = true;
            new Sortable(list, {
                animation: 150,
                handle: '.drag-handle',
                onEnd: () => this.reorderSections(),
            });
            this.sections.forEach(s => this.initFieldSortable(s.id));
        },

        initFieldSortable(sectionId) {
            const el = document.getElementById('fields-' + sectionId);
            if (!el) return;
            if (el._ssbcSortable) return;
            el._ssbcSortable = true;
            new Sortable(el, {
                animation: 150,
                handle: '.field-drag-handle',
                onEnd: () => this.reorderFields(sectionId),
            });
        },

        openSectionModal(section) {
            this.editingSection = section;
            this.sectionForm = section
                ? { title_en: section.title_en, title_ar: section.title_ar, is_repeatable: !!section.is_repeatable, max_repeats: section.max_repeats || 5 }
                : { title_en: '', title_ar: '', is_repeatable: false, max_repeats: 5 };
            this.sectionModalOpen = true;
        },

        async saveSection() {
            this.saving = true;
            this.sectionError = null;
            const url = this.editingSection
                ? `${this.endpoints.sections}/${this.editingSection.id}`
                : this.endpoints.sections;
            const method = this.editingSection ? 'PUT' : 'POST';

            try {
                const res = await this.requestJson(url, method, this.sectionForm);

                if (res.success) {
                    if (this.editingSection) {
                        const s = this.sections.find(s => s.id === this.editingSection.id);
                        if (s) Object.assign(s, res.data);
                    } else {
                        this.sections.push({ ...res.data, all_fields: [] });
                    }
                    this.sectionModalOpen = false;
                }
            } catch (error) {
                this.sectionError = error.message || 'Unable to save section.';
            } finally {
                this.saving = false;
            }
        },

        confirmDeleteSection(section) {
            this.confirmModal = {
                open: true,
                message: `Delete section "${section.title_en}"?`,
                warning: (section.all_fields?.length || 0) > 0
                    ? `This section has ${section.all_fields.length} field(s). All fields and their saved answers will be permanently deleted.`
                    : '',
                action: () => this.deleteSection(section),
            };
        },

        async deleteSection(section) {
            this.confirmModal.open = false;
            const res = await fetch(`${this.endpoints.sections}/${section.id}?force=1`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf },
            }).then(r => r.json());

            if (res.success) {
                this.sections = this.sections.filter(s => s.id !== section.id);
            }
        },

        async reorderSections() {
            const items = this.sortableItems(this.$refs.sectionsList).map((el, i) => ({
                id: Number(el.dataset.id),
                order_index: i,
            }));
            if (!items.length) return;
            try {
                await this.requestJson(this.endpoints.reorderSections, 'POST', { items });
                this.setBuilderStatus('Section order saved.');
            } catch (error) {
                this.setBuilderStatus(error.message || 'Unable to save section order.');
            }
        },

        openFieldModal(field, sectionId) {
            this.editingField = field;
            this.editingFieldSectionId = sectionId;
            this.fieldError = null;
            this.fieldTypeMenuOpen = false;
            this.fieldForm = field ? {
                section_id: field.section_id,
                label_en: field.label_en, label_ar: field.label_ar,
                placeholder_en: field.placeholder_en || '',
                placeholder_ar: field.placeholder_ar || '',
                field_type: field.field_type,
                is_required: !!field.is_required,
                is_active: !!field.is_active,
                options: field.options ? JSON.parse(JSON.stringify(field.options)) : [],
                options_source: field.options_source || 'manual',
                validation_rules: field.validation_rules || {},
                file_config: field.file_config || { accepted_types: ['pdf'], max_size_mb: 10 },
            } : {
                section_id: sectionId,
                label_en: '', label_ar: '',
                placeholder_en: '', placeholder_ar: '',
                field_type: 'text', is_required: true, is_active: true,
                options: [], options_source: 'manual', validation_rules: {},
                file_config: { accepted_types: ['pdf'], max_size_mb: 10 },
            };
            this.fieldModalOpen = true;
        },

        addOption() {
            this.fieldForm.options.push({ label_en: '', label_ar: '', value: '' });
        },

        setFieldType(type) {
            if (this.fieldIsSystemManaged(this.editingField)) return;

            this.fieldForm.field_type = type;

            if (!['select','radio','checkbox_group'].includes(type)) {
                this.fieldForm.options_source = 'manual';
            }

            if (['select','radio','checkbox_group'].includes(type)
                && this.fieldForm.options_source !== 'sectors'
                && !this.fieldForm.options.length) {
                this.fieldForm.options = [
                    { label_en: '', label_ar: '', value: '' },
                    { label_en: '', label_ar: '', value: '' },
                ];
            }
        },

        fieldTypeLabel(type) {
            return String(type || '').replaceAll('_', ' ');
        },

        fieldIsSystemManaged(field) {
            return field?.is_system_managed === true
                || field?.is_system_managed === 1
                || field?.is_system_managed === '1';
        },

        toggleFileType(type) {
            const types = this.fieldForm.file_config.accepted_types || [];
            if (types.includes(type)) {
                this.fieldForm.file_config.accepted_types = types.filter(t => t !== type);
            } else {
                this.fieldForm.file_config.accepted_types = [...types, type];
            }
        },

        async saveField() {
            const payload = this.normalizedFieldPayload();
            const clientError = this.validateFieldPayload(payload);
            if (clientError) {
                this.fieldError = clientError;
                return;
            }

            this.saving = true;
            this.fieldError = null;
            const url = this.editingField
                ? `${this.endpoints.fields}/${this.editingField.id}`
                : this.endpoints.fields;
            const method = this.editingField ? 'PUT' : 'POST';

            try {
                const res = await this.requestJson(url, method, payload);

                if (res.success) {
                    const section = this.sections.find(s => s.id === this.editingFieldSectionId);
                    if (section) {
                        if (this.editingField) {
                            const idx = section.all_fields.findIndex(f => f.id === this.editingField.id);
                            if (idx >= 0) section.all_fields[idx] = res.data;
                        } else {
                            section.all_fields.push(res.data);
                            this.$nextTick(() => this.initFieldSortable(section.id));
                        }
                    }
                    this.fieldModalOpen = false;
                }
            } catch (error) {
                this.fieldError = error.message || 'Unable to save field.';
            } finally {
                this.saving = false;
            }
        },

        normalizedFieldPayload() {
            const payload = JSON.parse(JSON.stringify(this.fieldForm));
            if (payload.options_source === 'sectors') {
                payload.options = [];
            } else if (Array.isArray(payload.options)) {
                payload.options = payload.options
                    .map(opt => ({
                        label_en: (opt.label_en || '').trim(),
                        label_ar: (opt.label_ar || '').trim(),
                        value: (opt.value || '').trim(),
                    }))
                    .filter(opt => opt.label_en || opt.label_ar || opt.value);
            }
            return payload;
        },

        validateFieldPayload(payload) {
            if (!payload.label_en?.trim() || !payload.label_ar?.trim()) {
                return 'Both English and Arabic labels are required.';
            }
            if (['select','radio','checkbox_group'].includes(payload.field_type)
                && payload.options_source !== 'sectors') {
                if (!payload.options?.length) {
                    return 'Add at least one option for this field type.';
                }
                const invalid = payload.options.find(opt => !opt.label_en || !opt.label_ar || !opt.value);
                if (invalid) {
                    return 'Each option needs an English label, Arabic label, and value.';
                }
            }
            return null;
        },

        async requestJson(url, method, payload = null) {
            const response = await fetch(url, {
                method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                },
                body: payload ? JSON.stringify(payload) : null,
            });

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json') ? await response.json() : null;

            if (!response.ok) {
                if (data?.errors) {
                    throw new Error(Object.values(data.errors).flat().join(' '));
                }
                throw new Error(data?.message || 'The server could not save this change.');
            }

            return data || {};
        },

        confirmDeleteField(field, section) {
            this.confirmModal = {
                open: true,
                message: `Delete field "${field.label_en}"?`,
                warning: '',
                action: () => this.deleteField(field, section),
            };
        },

        async deleteField(field, section) {
            this.confirmModal.open = false;
            const res = await fetch(`${this.endpoints.fields}/${field.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf },
            }).then(r => r.json());

            if (res.success) {
                section.all_fields = section.all_fields.filter(f => f.id !== field.id);
            }
        },

        async reorderFields(sectionId) {
            const el = document.getElementById('fields-' + sectionId);
            if (!el) return;
            const items = this.sortableItems(el).map((div, i) => ({
                id: Number(div.dataset.id),
                order_index: i,
            }));
            if (!items.length) return;
            try {
                await this.requestJson(this.endpoints.reorderFields, 'POST', { items });
                this.setBuilderStatus('Field order saved.');
            } catch (error) {
                this.setBuilderStatus(error.message || 'Unable to save field order.');
            }
        },

        setBuilderStatus(message) {
            this.builderStatus = message;
            clearTimeout(this.builderStatusTimer);
            this.builderStatusTimer = setTimeout(() => {
                this.builderStatus = null;
            }, 3000);
        },

        sortableItems(container) {
            return Array.from(container?.children || [])
                .filter(el => el.dataset?.id && Number.isInteger(Number(el.dataset.id)));
        },
    };
}
</script>
@endsection

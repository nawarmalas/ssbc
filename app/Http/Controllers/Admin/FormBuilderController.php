<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\FormDefinition;
use App\Models\FormSection;
use App\Models\Sector;
use App\Services\FormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FormBuilderController extends Controller
{
    private const FIELD_TYPES = [
        'text', 'textarea', 'email', 'tel', 'number', 'date',
        'select', 'radio', 'checkbox_group', 'file', 'url', 'declaration',
    ];

    // ── Index ────────────────────────────────────────────────────────────────

    public function index(FormDefinition $formDefinition)
    {
        $sections = FormSection::with('allFields')
            ->where('form_id', $formDefinition->form_id)
            ->orderBy('order_index')
            ->get();

        return view('admin.form-builder.index', [
            'sectionsJson' => $sections->toJson(),
            'fieldTypes'   => self::FIELD_TYPES,
            'formDefinition' => $formDefinition,
        ]);
    }

    public function preview(FormDefinition $formDefinition)
    {
        $form = FormService::getActiveForm($formDefinition->form_id);

        return view('join.create', [
            'form' => $form,
            'formDefinition' => $formDefinition,
            'preview' => true,
        ]);
    }

    // ── Sections ─────────────────────────────────────────────────────────────

    public function storeSection(Request $request, FormDefinition $formDefinition): JsonResponse
    {
        $data = $request->validate([
            'title_en'      => ['required', 'string', 'max:255'],
            'title_ar'      => ['required', 'string', 'max:255'],
            'is_repeatable' => ['boolean'],
            'max_repeats'   => ['integer', 'min:2', 'max:10'],
        ]);

        $maxOrder = FormSection::where('form_id', $formDefinition->form_id)->max('order_index') ?? -1;

        $section = FormSection::create(array_merge($data, [
            'form_id'     => $formDefinition->form_id,
            'order_index' => $maxOrder + 1,
        ]));

        FormService::invalidateCache($formDefinition->form_id);

        return response()->json(['success' => true, 'data' => $section]);
    }

    public function updateSection(Request $request, FormDefinition $formDefinition, FormSection $section): JsonResponse
    {
        $this->ensureSectionBelongsToForm($section, $formDefinition);

        $data = $request->validate([
            'title_en'      => ['required', 'string', 'max:255'],
            'title_ar'      => ['required', 'string', 'max:255'],
            'is_repeatable' => ['boolean'],
            'max_repeats'   => ['integer', 'min:2', 'max:10'],
        ]);

        $section->update($data);
        FormService::invalidateCache($formDefinition->form_id);

        return response()->json(['success' => true, 'data' => $section]);
    }

    public function destroySection(Request $request, FormDefinition $formDefinition, FormSection $section): JsonResponse
    {
        $this->ensureSectionBelongsToForm($section, $formDefinition);

        $fieldCount = $section->allFields()->count();

        if ($fieldCount > 0 && ! $request->boolean('force')) {
            return response()->json(['success' => false, 'has_fields' => true, 'count' => $fieldCount]);
        }

        $section->delete();
        FormService::invalidateCache($formDefinition->form_id);

        return response()->json(['success' => true]);
    }

    public function reorderSections(Request $request, FormDefinition $formDefinition): JsonResponse
    {
        $items = $request->validate([
            'items'               => ['required', 'array'],
            'items.*.id'          => ['required', 'integer'],
            'items.*.order_index' => ['required', 'integer'],
        ])['items'];

        foreach ($items as $item) {
            FormSection::where('form_id', $formDefinition->form_id)
                ->where('id', $item['id'])
                ->update(['order_index' => $item['order_index']]);
        }

        FormService::invalidateCache($formDefinition->form_id);

        return response()->json(['success' => true]);
    }

    // ── Fields ───────────────────────────────────────────────────────────────

    public function storeField(Request $request, FormDefinition $formDefinition): JsonResponse
    {
        $data = $this->validateField($request);
        $section = FormSection::findOrFail($data['section_id']);
        $this->ensureSectionBelongsToForm($section, $formDefinition);

        $maxOrder = FormField::where('section_id', $data['section_id'])->max('order_index') ?? -1;
        $data['order_index'] = $maxOrder + 1;

        $field = FormField::create($data);
        FormService::invalidateCache($formDefinition->form_id);

        return response()->json(['success' => true, 'data' => $field->fresh()]);
    }

    public function updateField(Request $request, FormDefinition $formDefinition, FormField $field): JsonResponse
    {
        $this->ensureFieldBelongsToForm($field, $formDefinition);

        $data = $this->validateField($request, $field);
        $section = FormSection::findOrFail($data['section_id']);
        $this->ensureSectionBelongsToForm($section, $formDefinition);

        $field->update($data);
        FormService::invalidateCache($formDefinition->form_id);

        return response()->json(['success' => true, 'data' => $field->fresh()]);
    }

    public function destroyField(FormDefinition $formDefinition, FormField $field): JsonResponse
    {
        $this->ensureFieldBelongsToForm($field, $formDefinition);

        if ($field->is_system_managed) {
            return response()->json(['success' => false, 'error' => 'System-managed fields cannot be deleted.'], 422);
        }

        $field->delete();
        FormService::invalidateCache($formDefinition->form_id);

        return response()->json(['success' => true]);
    }

    public function reorderFields(Request $request, FormDefinition $formDefinition): JsonResponse
    {
        $items = $request->validate([
            'items'               => ['required', 'array'],
            'items.*.id'          => ['required', 'integer'],
            'items.*.order_index' => ['required', 'integer'],
        ])['items'];

        foreach ($items as $item) {
            FormField::whereHas('section', fn ($query) => $query->where('form_id', $formDefinition->form_id))
                ->where('id', $item['id'])
                ->update(['order_index' => $item['order_index']]);
        }

        FormService::invalidateCache($formDefinition->form_id);

        return response()->json(['success' => true]);
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function validateField(Request $request, ?FormField $existing = null): array
    {
        $data = $request->validate([
            'section_id'         => ['required', 'integer', 'exists:form_sections,id'],
            'label_en'           => ['required', 'string', 'max:500'],
            'label_ar'           => ['required', 'string', 'max:500'],
            'placeholder_en'     => ['nullable', 'string', 'max:255'],
            'placeholder_ar'     => ['nullable', 'string', 'max:255'],
            'field_type'         => ['required', Rule::in(self::FIELD_TYPES)],
            'is_required'        => ['boolean'],
            'is_active'          => ['boolean'],
            'options'            => ['nullable', 'array'],
            'options.*.label_en' => ['required_with:options', 'string'],
            'options.*.label_ar' => ['required_with:options', 'string'],
            'options.*.value'    => ['required_with:options', 'string'],
            'options_source'     => ['nullable', Rule::in(['manual', 'sectors'])],
            'validation_rules'   => ['nullable', 'array'],
            'file_config'        => ['nullable', 'array'],
            'file_config.accepted_types' => ['nullable', 'array'],
            'file_config.max_size_mb'    => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        foreach (['label_en', 'label_ar', 'placeholder_en', 'placeholder_ar'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = strip_tags($data[$key]);
            }
        }

        $data['options_source'] = $data['options_source'] ?? 'manual';

        // Sectors-backed fields: options are owned by the Sector list. Fill them
        // server-side so they always match — any client-submitted options are ignored.
        if ($data['options_source'] === 'sectors') {
            if (! in_array($data['field_type'], ['select', 'radio', 'checkbox_group'], true)) {
                throw ValidationException::withMessages([
                    'options_source' => 'Sector-filled options are only available for select, radio, or checkbox group fields.',
                ]);
            }
            $data['options'] = Sector::activeFieldOptions();
        }

        // System-managed fields: only allow safe keys to be updated
        if ($existing && $existing->is_system_managed) {
            $allowed = ['label_en', 'label_ar', 'placeholder_en', 'placeholder_ar',
                        'is_required', 'is_active', 'order_index', 'section_id'];
            $data = array_intersect_key($data, array_flip($allowed));
        }

        return $data;
    }

    private function ensureSectionBelongsToForm(FormSection $section, FormDefinition $formDefinition): void
    {
        abort_unless($section->form_id === $formDefinition->form_id, 404);
    }

    private function ensureFieldBelongsToForm(FormField $field, FormDefinition $formDefinition): void
    {
        $field->loadMissing('section');
        abort_unless($field->section?->form_id === $formDefinition->form_id, 404);
    }
}

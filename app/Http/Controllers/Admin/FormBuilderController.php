<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\FormSection;
use App\Services\FormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FormBuilderController extends Controller
{
    private const FORM_ID = 'join-us';

    private const FIELD_TYPES = [
        'text', 'textarea', 'email', 'tel', 'number', 'date',
        'select', 'radio', 'checkbox_group', 'file', 'url', 'declaration',
    ];

    // ── Index ────────────────────────────────────────────────────────────────

    public function index()
    {
        $sections = FormSection::with('allFields')
            ->where('form_id', self::FORM_ID)
            ->orderBy('order_index')
            ->get();

        return view('admin.form-builder.index', [
            'sectionsJson' => $sections->toJson(),
            'fieldTypes'   => self::FIELD_TYPES,
        ]);
    }

    public function preview()
    {
        $form = FormService::getActiveForm(self::FORM_ID);
        return view('join.create', ['form' => $form, 'preview' => true]);
    }

    // ── Sections ─────────────────────────────────────────────────────────────

    public function storeSection(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title_en'      => ['required', 'string', 'max:255'],
            'title_ar'      => ['required', 'string', 'max:255'],
            'is_repeatable' => ['boolean'],
            'max_repeats'   => ['integer', 'min:2', 'max:10'],
        ]);

        $maxOrder = FormSection::where('form_id', self::FORM_ID)->max('order_index') ?? -1;

        $section = FormSection::create(array_merge($data, [
            'form_id'     => self::FORM_ID,
            'order_index' => $maxOrder + 1,
        ]));

        FormService::invalidateCache();

        return response()->json(['success' => true, 'data' => $section]);
    }

    public function updateSection(Request $request, FormSection $section): JsonResponse
    {
        $data = $request->validate([
            'title_en'      => ['required', 'string', 'max:255'],
            'title_ar'      => ['required', 'string', 'max:255'],
            'is_repeatable' => ['boolean'],
            'max_repeats'   => ['integer', 'min:2', 'max:10'],
        ]);

        $section->update($data);
        FormService::invalidateCache();

        return response()->json(['success' => true, 'data' => $section]);
    }

    public function destroySection(Request $request, FormSection $section): JsonResponse
    {
        $fieldCount = $section->allFields()->count();

        if ($fieldCount > 0 && ! $request->boolean('force')) {
            return response()->json(['success' => false, 'has_fields' => true, 'count' => $fieldCount]);
        }

        $section->delete();
        FormService::invalidateCache();

        return response()->json(['success' => true]);
    }

    public function reorderSections(Request $request): JsonResponse
    {
        $items = $request->validate([
            'items'               => ['required', 'array'],
            'items.*.id'          => ['required', 'integer'],
            'items.*.order_index' => ['required', 'integer'],
        ])['items'];

        foreach ($items as $item) {
            FormSection::where('id', $item['id'])->update(['order_index' => $item['order_index']]);
        }

        FormService::invalidateCache();

        return response()->json(['success' => true]);
    }

    // ── Fields ───────────────────────────────────────────────────────────────

    public function storeField(Request $request): JsonResponse
    {
        $data = $this->validateField($request);

        $maxOrder = FormField::where('section_id', $data['section_id'])->max('order_index') ?? -1;
        $data['order_index'] = $maxOrder + 1;

        $field = FormField::create($data);
        FormService::invalidateCache();

        return response()->json(['success' => true, 'data' => $field]);
    }

    public function updateField(Request $request, FormField $field): JsonResponse
    {
        $data = $this->validateField($request, $field);
        $field->update($data);
        FormService::invalidateCache();

        return response()->json(['success' => true, 'data' => $field->fresh()]);
    }

    public function destroyField(FormField $field): JsonResponse
    {
        $field->delete();
        FormService::invalidateCache();

        return response()->json(['success' => true]);
    }

    public function reorderFields(Request $request): JsonResponse
    {
        $items = $request->validate([
            'items'               => ['required', 'array'],
            'items.*.id'          => ['required', 'integer'],
            'items.*.order_index' => ['required', 'integer'],
        ])['items'];

        foreach ($items as $item) {
            FormField::where('id', $item['id'])->update(['order_index' => $item['order_index']]);
        }

        FormService::invalidateCache();

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

        return $data;
    }
}

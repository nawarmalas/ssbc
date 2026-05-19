<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormDefinitionController extends Controller
{
    public function index()
    {
        $forms = FormDefinition::withCount('submissions')
            ->orderByRaw("visibility = 'public' desc")
            ->orderBy('title_en')
            ->get();

        return view('admin.forms.index', compact('forms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'description_ar' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach (['title_en', 'title_ar', 'description_en', 'description_ar'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = strip_tags($data[$key]);
            }
        }

        $slug = FormDefinition::uniqueSlug($data['title_en']);

        $form = FormDefinition::create([
            ...$data,
            'slug' => $slug,
            'form_id' => 'private-'.$slug,
            'visibility' => FormDefinition::VISIBILITY_PRIVATE,
            'access_token' => Str::random(48),
            'is_active' => true,
        ]);

        return redirect()->route('admin.forms.builder', $form)
            ->with('status', 'Private form created. Add sections and fields, then share the private link.');
    }

    public function update(Request $request, FormDefinition $formDefinition)
    {
        if ($formDefinition->visibility === FormDefinition::VISIBILITY_PUBLIC) {
            abort(403);
        }

        $data = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);

        foreach (['title_en', 'title_ar', 'description_en', 'description_ar'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = strip_tags($data[$key]);
            }
        }

        $data['is_active'] = $request->boolean('is_active');
        $formDefinition->update($data);

        return redirect()->route('admin.forms.index')->with('status', 'Private form updated.');
    }

    public function joinUs()
    {
        $form = FormDefinition::where('form_id', 'join-us')->firstOrFail();

        return redirect()->route('admin.forms.builder', $form);
    }
}

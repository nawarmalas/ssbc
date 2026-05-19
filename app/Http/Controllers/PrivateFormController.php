<?php

namespace App\Http\Controllers;

use App\Models\FormDefinition;
use App\Services\FormService;
use App\Services\FormSubmissionService;
use Illuminate\Http\Request;

class PrivateFormController extends Controller
{
    public function show(string $locale, FormDefinition $form, string $token)
    {
        $this->authorizePrivateForm($form, $token);

        $sections = FormService::getActiveForm($form->form_id);

        return view('join.create', [
            'form' => $sections,
            'formDefinition' => $form,
            'formAction' => route('private-forms.store', [
                'locale' => $locale,
                'form' => $form->slug,
                'token' => $token,
            ]),
            'preview' => false,
        ]);
    }

    public function store(Request $request, string $locale, FormDefinition $form, string $token, FormSubmissionService $submissions)
    {
        $this->authorizePrivateForm($form, $token);

        $submissions->store($request, $form->form_id);

        return redirect()->route('private-forms.thanks', [
            'locale' => $locale,
            'form' => $form->slug,
            'token' => $token,
        ]);
    }

    public function thanks(string $locale, FormDefinition $form, string $token)
    {
        $this->authorizePrivateForm($form, $token);

        return view('forms.thanks', ['formDefinition' => $form]);
    }

    private function authorizePrivateForm(FormDefinition $form, string $token): void
    {
        abort_unless(
            $form->is_active
            && $form->isPrivate()
            && $form->access_token
            && hash_equals($form->access_token, $token),
            404
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\FormService;
use App\Services\FormSubmissionService;
use Illuminate\Http\Request;

class JoinController extends Controller
{
    public function create(string $locale, bool $preview = false)
    {
        $form = FormService::getActiveForm('join-us');

        return view('join.create', compact('form', 'preview'));
    }

    public function store(Request $request, string $locale, FormSubmissionService $submissions)
    {
        $submissions->store($request, 'join-us', sendApplicantConfirmation: true);

        return redirect()->route('join.thanks', ['locale' => $locale]);
    }

    public function thanks(string $locale)
    {
        return view('join.thanks');
    }
}

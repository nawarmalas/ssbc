<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function create(string $locale)
    {
        return view('contact.create');
    }

    public function store(Request $request, string $locale)
    {
        // Honeypot — silently drop bot submissions.
        if (filled($request->input('website'))) {
            return redirect()->route('contact.thanks', ['locale' => $locale]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactSubmission::create([
            ...$validated,
            'status' => 'new',
        ]);

        return redirect()->route('contact.thanks', ['locale' => $locale]);
    }

    public function thanks(string $locale)
    {
        return view('contact.thanks');
    }
}

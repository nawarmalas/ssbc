<?php

namespace App\Http\Controllers;

use App\Models\MembershipApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JoinController extends Controller
{
    /**
     * Strategic sectors — keep in sync with the Strategic Pillars on the home page.
     */
    public const SECTORS = [
        'agriculture_livestock',
        'industry_manufacturing',
        'energy_oil',
        'construction_real_estate',
        'tourism',
        'trade_logistics',
    ];

    public function create(string $locale)
    {
        return view('join.create', [
            'sectors' => self::SECTORS,
        ]);
    }

    public function store(Request $request, string $locale)
    {
        $sectors = self::SECTORS;

        $validated = $request->validate([
            'full_name_en' => ['required', 'string', 'max:255'],
            'full_name_ar' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'position' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
            'home_address' => ['nullable', 'string', 'max:1000'],
            'linked_in' => ['nullable', 'url', 'max:255'],

            'companies' => ['required', 'array', 'min:1'],
            'companies.*.name' => ['required', 'string', 'max:255'],
            'companies.*.registration_number' => ['required', 'string', 'max:255'],
            'companies.*.country' => ['required', 'string', 'max:255'],
            'companies.*.sector' => ['required', 'string', 'in:'.implode(',', $sectors)],

            'id_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'company_documents' => ['required', 'array', 'min:1'],
            'company_documents.*' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:8192'],
            'company_profile' => ['nullable', 'file', 'mimes:pdf', 'max:8192'],

            'declaration' => ['accepted'],
        ]);

        // Reserve a UUID-style folder
        $uuid = (string) Str::uuid();
        $base = 'membership/'.$uuid;

        $idPath = $request->file('id_document')->store($base, 'public');

        $companyDocPaths = [];
        foreach ($request->file('company_documents', []) as $doc) {
            $companyDocPaths[] = $doc->store($base, 'public');
        }

        $profilePath = null;
        if ($request->hasFile('company_profile')) {
            $profilePath = $request->file('company_profile')->store($base, 'public');
        }

        MembershipApplication::create([
            'full_name_en' => $validated['full_name_en'],
            'full_name_ar' => $validated['full_name_ar'],
            'date_of_birth' => $validated['date_of_birth'],
            'position' => $validated['position'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'],
            'home_address' => $validated['home_address'] ?? null,
            'linked_in' => $validated['linked_in'] ?? null,
            'companies' => $validated['companies'],
            'id_document_path' => $idPath,
            'company_document_paths' => $companyDocPaths,
            'company_profile_url' => $profilePath,
            'status' => 'new',
        ]);

        return redirect()->route('join.thanks', ['locale' => $locale]);
    }

    public function thanks(string $locale)
    {
        return view('join.thanks');
    }
}

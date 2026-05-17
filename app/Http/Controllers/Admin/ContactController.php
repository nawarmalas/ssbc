<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        $submissions = ContactSubmission::orderByDesc('created_at')->get();

        return view('admin.contact.index', compact('submissions'));
    }

    public function show(ContactSubmission $contactSubmission)
    {
        return view('admin.contact.show', ['submission' => $contactSubmission]);
    }

    public function update(Request $request, ContactSubmission $contactSubmission)
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,reviewed,contacted'],
        ]);

        $contactSubmission->update($data);

        return redirect()->route('admin.contact.show', $contactSubmission)
            ->with('status', __('admin.status_updated'));
    }

    public function destroy(ContactSubmission $contactSubmission)
    {
        $contactSubmission->delete();

        return redirect()->route('admin.contact.index')->with('status', __('admin.deleted'));
    }
}

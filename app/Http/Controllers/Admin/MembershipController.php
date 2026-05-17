<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MembershipController extends Controller
{
    public function index()
    {
        $applications = MembershipApplication::orderByDesc('created_at')->get();

        return view('admin.membership.index', compact('applications'));
    }

    public function show(MembershipApplication $membershipApplication)
    {
        return view('admin.membership.show', ['application' => $membershipApplication]);
    }

    public function update(Request $request, MembershipApplication $membershipApplication)
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,reviewed,contacted,approved,rejected'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $membershipApplication->update($data);

        return redirect()->route('admin.membership.show', $membershipApplication)
            ->with('status', __('admin.status_updated'));
    }

    public function destroy(MembershipApplication $membershipApplication)
    {
        // Clean up uploaded files
        if ($membershipApplication->id_document_path) {
            Storage::disk('public')->delete($membershipApplication->id_document_path);
        }
        foreach ($membershipApplication->company_document_paths ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }
        if ($membershipApplication->company_profile_url) {
            Storage::disk('public')->delete($membershipApplication->company_profile_url);
        }

        $membershipApplication->delete();

        return redirect()->route('admin.membership.index')->with('status', __('admin.deleted'));
    }
}

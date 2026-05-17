<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JoinSubmission;
use Illuminate\Http\Request;

class JoinController extends Controller
{
    public function index()
    {
        $submissions = JoinSubmission::orderByDesc('created_at')->get();

        return view('admin.join.index', compact('submissions'));
    }

    public function show(JoinSubmission $joinSubmission)
    {
        return view('admin.join.show', ['submission' => $joinSubmission]);
    }

    public function update(Request $request, JoinSubmission $joinSubmission)
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,reviewed,contacted'],
        ]);

        $joinSubmission->update($data);

        return redirect()->route('admin.join.show', $joinSubmission)
            ->with('status', __('admin.status_updated'));
    }

    public function destroy(JoinSubmission $joinSubmission)
    {
        $joinSubmission->delete();

        return redirect()->route('admin.join.index')->with('status', __('admin.deleted'));
    }
}

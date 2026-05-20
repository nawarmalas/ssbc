<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BoardMemberController extends Controller
{
    public function index()
    {
        $members = BoardMember::orderBy('sort_order')->get();

        return view('admin.board-members.index', compact('members'));
    }

    public function create()
    {
        return view('admin.board-members.create', [
            'member' => new BoardMember(['sort_order' => 0, 'is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateMember($request);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('board-members', 'public');
        }

        BoardMember::create($data);

        return redirect()->route('admin.board-members.index')
            ->with('status', 'Board member created.');
    }

    public function edit(BoardMember $boardMember)
    {
        return view('admin.board-members.edit', ['member' => $boardMember]);
    }

    public function update(Request $request, BoardMember $boardMember)
    {
        $data = $this->validateMember($request);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('photo')) {
            if ($boardMember->photo) {
                Storage::disk('public')->delete($boardMember->photo);
            }
            $data['photo'] = $request->file('photo')->store('board-members', 'public');
        }

        $boardMember->fill($data)->save();

        return redirect()->route('admin.board-members.index')
            ->with('status', 'Board member updated.');
    }

    public function destroy(BoardMember $boardMember)
    {
        if ($boardMember->photo) {
            Storage::disk('public')->delete($boardMember->photo);
        }

        $boardMember->delete();

        return redirect()->route('admin.board-members.index')
            ->with('status', 'Board member deleted.');
    }

    protected function validateMember(Request $request): array
    {
        return $request->validate([
            'name_ar'    => ['required', 'string', 'max:255'],
            'name_en'    => ['required', 'string', 'max:255'],
            'role_ar'    => ['required', 'string', 'max:255'],
            'role_en'    => ['required', 'string', 'max:255'],
            'bio_ar'     => ['required', 'string', 'max:1000'],
            'bio_en'     => ['required', 'string', 'max:1000'],
            'photo'      => ['nullable', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ]);
    }
}

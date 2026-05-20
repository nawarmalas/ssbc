<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use Illuminate\Http\Request;

class SectorController extends Controller
{
    public function index()
    {
        $sectors = Sector::orderBy('sort_order')->get();

        return view('admin.sectors.index', compact('sectors'));
    }

    public function create()
    {
        return view('admin.sectors.create', [
            'sector' => new Sector(['sort_order' => 0, 'is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSector($request);
        $data['is_active'] = $request->boolean('is_active');

        Sector::create($data);

        return redirect()->route('admin.sectors.index')
            ->with('status', 'Sector created.');
    }

    public function edit(Sector $sector)
    {
        return view('admin.sectors.edit', compact('sector'));
    }

    public function update(Request $request, Sector $sector)
    {
        $data = $this->validateSector($request);
        $data['is_active'] = $request->boolean('is_active');

        $sector->fill($data)->save();

        return redirect()->route('admin.sectors.index')
            ->with('status', 'Sector updated.');
    }

    public function destroy(Sector $sector)
    {
        $sector->delete();

        return redirect()->route('admin.sectors.index')
            ->with('status', 'Sector deleted.');
    }

    protected function validateSector(Request $request): array
    {
        return $request->validate([
            'name_ar'        => ['required', 'string', 'max:255'],
            'name_en'        => ['required', 'string', 'max:255'],
            'description_ar' => ['required', 'string', 'max:1000'],
            'description_en' => ['required', 'string', 'max:1000'],
            'sort_order'     => ['required', 'integer', 'min:0'],
            'is_active'      => ['nullable', 'boolean'],
        ]);
    }
}

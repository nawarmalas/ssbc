<?php

namespace App\Http\Controllers;

use App\Models\BoardMember;
use App\Models\Sector;

class AboutController extends Controller
{
    public function index(string $locale)
    {
        return view('pages.about', [
            'boardMembers' => BoardMember::active()->get(),
            'sectors'      => Sector::active()->get(),
        ]);
    }
}

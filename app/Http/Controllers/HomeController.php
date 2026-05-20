<?php

namespace App\Http\Controllers;

use App\Models\BoardMember;
use App\Models\NewsPost;
use App\Models\Sector;

class HomeController extends Controller
{
    public function index(string $locale)
    {
        $posts        = NewsPost::published()->take(3)->get();
        $boardMembers = BoardMember::active()->get();
        $sectors      = Sector::active()->get();

        return view('pages.home', compact('posts', 'boardMembers', 'sectors'));
    }
}

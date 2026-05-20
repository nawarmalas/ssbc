<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;

class HomeController extends Controller
{
    public function index(string $locale)
    {
        $posts        = NewsPost::published()->take(3)->get();
        $boardMembers = \App\Models\BoardMember::active()->get();

        return view('pages.home', compact('posts', 'boardMembers'));
    }
}

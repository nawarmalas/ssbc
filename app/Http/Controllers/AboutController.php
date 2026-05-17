<?php

namespace App\Http\Controllers;

class AboutController extends Controller
{
    public function index(string $locale)
    {
        return view('pages.about');
    }
}

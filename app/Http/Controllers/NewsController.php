<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NewsController extends Controller
{
    public function index(string $locale)
    {
        $posts = NewsPost::published()->paginate(9);

        return view('news.index', compact('posts'));
    }

    public function show(string $locale, string $slug)
    {
        $post = NewsPost::published()->with('images')->where('slug', $slug)->first();

        if (! $post) {
            throw new NotFoundHttpException;
        }

        return view('news.show', compact('post'));
    }
}

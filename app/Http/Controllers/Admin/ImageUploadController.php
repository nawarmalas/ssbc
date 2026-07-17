<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $ext  = $request->file('image')->getClientOriginalExtension() ?: 'jpg';
        $name = Str::uuid() . '.' . $ext;
        $path = $request->file('image')->storeAs('editor-uploads', $name, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}

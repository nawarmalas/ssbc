<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\FormSubmission;
use App\Models\NewsPost;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'published_posts'  => NewsPost::where('status', 'published')->count(),
            'pending_submissions' => FormSubmission::where('status', 'pending')->count(),
            'new_contact'      => ContactSubmission::where('status', 'new')->count(),
        ];

        $recent = collect()
            ->merge(FormSubmission::latest('submitted_at')->limit(5)->get()->map(fn ($r) => [
                'type'       => 'submission',
                'type_label' => __('admin.type_submission'),
                'name'       => $r->display_name,
                'date'       => $r->submitted_at ?? $r->created_at,
                'status'     => $r->status,
                'url'        => route('admin.submissions.show', $r),
            ]))
            ->merge(ContactSubmission::latest()->limit(5)->get()->map(fn ($r) => [
                'type'       => 'contact',
                'type_label' => __('admin.type_contact'),
                'name'       => $r->name,
                'date'       => $r->created_at,
                'status'     => $r->status,
                'url'        => route('admin.contact.show', $r),
            ]))
            ->sortByDesc('date')
            ->take(5)
            ->values();

        return view('admin.dashboard', compact('stats', 'recent'));
    }
}

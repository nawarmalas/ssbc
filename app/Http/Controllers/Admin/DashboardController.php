<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\JoinSubmission;
use App\Models\MembershipApplication;
use App\Models\NewsPost;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'published_posts' => NewsPost::where('status', 'published')->count(),
            'new_join' => JoinSubmission::where('status', 'new')->count(),
            'new_contact' => ContactSubmission::where('status', 'new')->count(),
            'new_membership' => MembershipApplication::where('status', 'new')->count(),
        ];

        $recent = collect()
            ->merge(JoinSubmission::latest()->limit(5)->get()->map(fn ($r) => [
                'type'   => 'join',
                'type_label' => 'Join',
                'name'   => $r->name,
                'date'   => $r->created_at,
                'status' => $r->status,
                'url'    => route('admin.join.show', $r),
            ]))
            ->merge(ContactSubmission::latest()->limit(5)->get()->map(fn ($r) => [
                'type'   => 'contact',
                'type_label' => 'Contact',
                'name'   => $r->name,
                'date'   => $r->created_at,
                'status' => $r->status,
                'url'    => route('admin.contact.show', $r),
            ]))
            ->merge(MembershipApplication::latest()->limit(5)->get()->map(fn ($r) => [
                'type'   => 'membership',
                'type_label' => 'Membership',
                'name'   => $r->full_name_en,
                'date'   => $r->created_at,
                'status' => $r->status,
                'url'    => route('admin.membership.show', $r),
            ]))
            ->sortByDesc('date')
            ->take(5)
            ->values();

        return view('admin.dashboard', compact('stats', 'recent'));
    }
}

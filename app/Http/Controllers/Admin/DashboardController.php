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

        return view('admin.dashboard', compact('stats'));
    }
}

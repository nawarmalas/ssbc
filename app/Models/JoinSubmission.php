<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JoinSubmission extends Model
{
    protected $fillable = [
        'name',
        'organization',
        'role',
        'country',
        'email',
        'phone',
        'message',
        'status',
    ];
}

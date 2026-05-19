<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_NEWS_SUBADMIN = 'news_subadmin';

    public const ROLES = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_NEWS_SUBADMIN => 'News Subadmin',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isNewsSubadmin(): bool
    {
        return $this->role === self::ROLE_NEWS_SUBADMIN;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
}

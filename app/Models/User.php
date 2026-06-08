<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUBADMIN = 'subadmin';

    public const ROLES = [
        self::ROLE_ADMIN    => 'Admin',
        self::ROLE_SUBADMIN => 'Subadmin',
    ];

    /**
     * Granular permissions a Subadmin can be granted. Admins implicitly have
     * every permission. Add new keys here + a matching `admin.permission:<key>`
     * route gate to expand the system later.
     */
    public const PERMISSIONS = [
        'news_write'         => 'News — write drafts',
        'news_publish'       => 'News — publish & edit published posts',
        'site_customization' => 'Site Customization',
        'view_submissions'   => 'Submissions — view & manage submissions',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
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
            'password'    => 'hashed',
            'is_active'   => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSubadmin(): bool
    {
        return $this->role === self::ROLE_SUBADMIN;
    }

    /**
     * True for admins (implicitly all permissions) and for subadmins whose
     * `permissions` array contains the given key.
     */
    public function hasPermission(string $key): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        if (! $this->isSubadmin()) {
            return false;
        }

        return in_array($key, (array) $this->permissions, true);
    }

    public function canManageNews(): bool
    {
        return $this->hasPermission('news_write') || $this->hasPermission('news_publish');
    }

    public function canPublishNews(): bool
    {
        return $this->hasPermission('news_publish');
    }

    public function canCustomizeSite(): bool
    {
        return $this->hasPermission('site_customization');
    }

    public function canViewSubmissions(): bool
    {
        return $this->hasPermission('view_submissions');
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /**
     * Human labels for the permissions currently held. Useful for the admin
     * users index chip list.
     */
    public function permissionLabels(): array
    {
        if ($this->isAdmin()) {
            return ['All permissions'];
        }
        $labels = [];
        foreach ((array) $this->permissions as $key) {
            if (isset(self::PERMISSIONS[$key])) {
                $labels[] = self::PERMISSIONS[$key];
            }
        }
        return $labels;
    }
}

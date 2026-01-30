<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * System user constants and helpers
     * Services can use User::SYSTEM_EMAIL or User::systemUserId() to get the system user's id.
     */
    public const SYSTEM_EMAIL = 'system@acadops.com';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'gender',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    /**
     * Attribute casting for Eloquent
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the name attribute for the user.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}"
        );
    }

    /**
     * Determine if the user has the 'admin' role.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Determine if the user has the 'academic_advisor' role.
     *
     * @return bool
     */
    public function isAcademicAdvisor(): bool
    {
        return $this->hasRole('advisor');
    }


    /**
     * Return the system user model instance if it exists.
     * This is a small convenience used by services that need the system account.
     *
     * @return static|null
     */
    public static function systemUser(): ?self
    {
        static $user = false;

        if ($user === false) {
            $user = static::where('email', self::SYSTEM_EMAIL)->first();
        }

        return $user;
    }

    /**
     * Return the system user's id or null if not present.
     * Caches the result for the request lifecycle.
     *
     * @return int|null
     */
    public static function systemUserId(): ?int
    {
        static $id = false;

        if ($id === false) {
            $id = static::where('email', self::SYSTEM_EMAIL)->value('id');
            if ($id === null) {
                $id = null;
            }
        }

        return $id;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public const THEME_LIGHT = 'light';
    public const THEME_DARK = 'dark';

    protected $fillable = [
        'department_id',
        'manager_id',
        'name',
        'first_name',
        'last_name',
        'email',
        'azure_oid',
        'password',
        'location',
        'holiday_country',
        'theme_preference',
        'is_admin',
        'is_active',
        'is_department_overridden',
        'is_location_overridden',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_admin' => 'bool',
        'is_active' => 'bool',
        'is_department_overridden' => 'bool',
        'is_location_overridden' => 'bool',
    ];

    protected $hidden = [
        'password',
    ];

    public static function supportedThemePreferences(): array
    {
        return [
            self::THEME_LIGHT,
            self::THEME_DARK,
        ];
    }

    public function prefersDarkTheme(): bool
    {
        return $this->theme_preference === self::THEME_DARK;
    }

    public function fullName(): string
    {
        $fullName = trim(implode(' ', array_filter([$this->first_name, $this->last_name])));

        return $fullName !== '' ? $fullName : $this->name;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function isManualAccount(): bool
    {
        return filled($this->password) && blank($this->azure_oid);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('is_admin', true);
    }

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function reports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function absences(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Absence::class);
    }
}

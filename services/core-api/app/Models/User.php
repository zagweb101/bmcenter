<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id',
        'branch_id',
        'person_id',
        'is_active',
        'mfa_enabled',
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
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'mfa_enabled' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot('branch_id');
    }

    /**
     * فحص صلاحية مفروض على الخادم (PRD §6, §22) عبر أدوار المستخدم.
     */
    public function hasPermission(string $permissionKey): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('key', $permissionKey))
            ->exists();
    }

    /**
     * حد الخصم الأقصى للمستخدم عبر أدواره. PRD §15.
     * null = غير محدود (إن كان لأحد الأدوار حد null).
     */
    public function maxDiscountLimit(): ?string
    {
        $limits = $this->roles()->pluck('max_discount_amount');

        if ($limits->isEmpty() || $limits->contains(null)) {
            return null; // غير محدود
        }

        return (string) $limits->max();
    }
}

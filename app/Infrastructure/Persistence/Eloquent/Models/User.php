<?php

namespace Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\UserFactory;
use Domain\Shared\Enums\AgentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_title',
        'photo',
        'status',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'status' => AgentStatus::class,
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'agent_departments');
    }

    public function assignedConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'assigned_to');
    }

    /** @var array<string, bool>|null */
    private ?array $permissionCache = null;

    /** @var array<string, bool>|null */
    private ?array $roleCache = null;

    /** @var list<int>|null */
    private ?array $departmentIdCache = null;

    public function hasPermission(string $slug): bool
    {
        $this->permissionCache ??= $this->roles()
            ->with('permissions:id,slug')
            ->get()
            ->flatMap(fn ($role) => $role->permissions->pluck('slug'))
            ->unique()
            ->mapWithKeys(fn ($permission) => [(string) $permission => true])
            ->all();

        return $this->permissionCache[$slug] ?? false;
    }

    public function hasRole(string $slug): bool
    {
        $this->roleCache ??= $this->roles()
            ->pluck('slug')
            ->mapWithKeys(fn (string $role) => [$role => true])
            ->all();

        return $this->roleCache[$slug] ?? false;
    }

    /** @return list<int> */
    public function departmentIds(): array
    {
        return $this->departmentIdCache ??= $this->departments()->pluck('departments.id')->map(fn ($id) => (int) $id)->all();
    }

    public function isOnline(): bool
    {
        return $this->status === AgentStatus::Online
            && $this->last_seen_at?->greaterThan(now()->subSeconds(150)) === true;
    }
}

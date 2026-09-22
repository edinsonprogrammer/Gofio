<?php

/**
 * Modelo Eloquent del usuario autenticado.
 * Representa la cuenta, perfil, karma, saldo, verificación, suscripción Creator Plus y relaciones sociales.
 */

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{

    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'nick',
        'email',
        'password',
        'rango_id',
        'rango_locked',
        'karma',
        'balance_monedas',
        'tipo_verificacion',
        'is_admin',
        'is_banned',
        'banned_until',
        'ban_reason',
        'is_active',
        'creator_plus_expires_at',
        'theme_id',
        'avatar_url',
        'banner_url',
        'banner_offset_x',
        'banner_offset_y',
        'country',
        'country_code',
        'age',
        'bio',
        'bio_gif_url',
        'whatsapp',
        'instagram',
        'facebook',
        'social_x',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'creator_plus_expires_at' => 'datetime',
            'password' => 'hashed',
            'balance_monedas' => 'decimal:2',
            'karma' => 'integer',
            'followers_count' => 'integer',
            'following_count' => 'integer',
            'is_admin' => 'boolean',
            'rango_locked' => 'boolean',
            'is_banned' => 'boolean',
            'is_active' => 'boolean',
            'banned_until' => 'datetime',
            'age' => 'integer',
        ];
    }

    /**
     * Relación con el rango actual del usuario.
     */
    public function rango(): BelongsTo
    {
        return $this->belongsTo(RolRango::class, 'rango_id');
    }

    /**
     * Publicaciones creadas por el usuario.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Comentarios escritos por el usuario.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Historial de votos emitidos por el usuario.
     */
    public function voteLogs(): HasMany
    {
        return $this->hasMany(VoteLog::class);
    }

    /**
     * Usuarios que siguen a este usuario.
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    /**
     * Usuarios que este usuario sigue.
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    /**
     * Solicitudes de verificación de identidad enviadas.
     */
    public function identityVerificationRequests(): HasMany
    {
        return $this->hasMany(IdentityVerificationRequest::class);
    }

    /**
     * Historial de suscripciones Creator Plus.
     */
    public function creatorSubscriptions(): HasMany
    {
        return $this->hasMany(CreatorSubscription::class);
    }

    /**
     * Tema visual seleccionado por el usuario.
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Medallas otorgadas al usuario.
     */
    public function medals(): BelongsToMany
    {
        return $this->belongsToMany(Medal::class)
            ->withPivot(['granted_by', 'note', 'granted_at']);
    }

    /**
     * Premios otorgados al usuario.
     */
    public function awards(): BelongsToMany
    {
        return $this->belongsToMany(Award::class)
            ->withPivot(['granted_by', 'note', 'granted_at']);
    }

    /**
     * Indica si el usuario tiene un rango de staff (moderador o superior).
     */
    public function hasStaffRank(): bool
    {
        return (bool) $this->rango?->is_staff;
    }

    /**
     * Pestañas del panel admin permitidas según rol global o rango staff.
     */
    public function adminTabs(): array
    {
        return \App\Support\AdminPermissions::tabsForUser($this);
    }

    /**
     * Comprueba si el usuario puede acceder a una pestaña concreta del panel admin.
     */
    public function canAccessAdminTab(string $tab): bool
    {
        return in_array($tab, $this->adminTabs(), true);
    }

    /**
     * Indica si el usuario tiene privilegios de administrador global.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Indica si el usuario muestra badge de verificado (user_verified o Creator Plus activo).
     */
    public function isVerified(): bool
    {
        return $this->tipo_verificacion === 'user_verified' || $this->isCreatorPlus();
    }

    /**
     * Indica si la suscripción Creator Plus está activa y no ha expirado.
     */
    public function isCreatorPlus(): bool
    {
        return $this->tipo_verificacion === 'creator_plus'
            && $this->creator_plus_expires_at
            && $this->creator_plus_expires_at->isFuture();
    }

    /**
     * Indica si el usuario puede cambiar tema y apariencia (Creator Plus, admin o staff).
     */
    public function canCustomizeAppearance(): bool
    {
        return $this->isCreatorPlus()
            || $this->isAdmin()
            || $this->hasStaffRank();
    }

    /**
     * Indica si existe una solicitud de verificación aprobada.
     */
    public function hasApprovedIdentityVerification(): bool
    {
        return $this->identityVerificationRequests()
            ->where('status', 'approved')
            ->exists();
    }

    /**
     * Indica si el usuario está verificado por tipo o por solicitud aprobada.
     */
    public function hasIdentityVerification(): bool
    {
        return $this->tipo_verificacion === 'user_verified'
            || $this->hasApprovedIdentityVerification();
    }

    /**
     * Indica si el staff recibe verificación automática sin solicitud manual.
     */
    public function hasStaffAutoVerification(): bool
    {
        return $this->hasStaffRank() && ! $this->isAdmin();
    }

    /**
     * Indica si el usuario puede enviar una nueva solicitud de verificación.
     */
    public function canSubmitIdentityVerification(): bool
    {
        if ($this->hasStaffAutoVerification()) {
            return false;
        }

        if ($this->hasIdentityVerification()) {
            return false;
        }

        return true;
    }

    /**
     * Normaliza el apodo eliminando espacios y el prefijo @.
     */
    public function normalizeNick(?string $value): ?string
    {
        $value = trim((string) $value);
        $value = ltrim($value, '@');

        return $value === '' ? null : $value;
    }

    /**
     * Devuelve el tipo de verificación visible: creator_plus, user_verified o none.
     */
    public function displayVerificationTipo(): string
    {
        if ($this->isCreatorPlus()) {
            return 'creator_plus';
        }

        if ($this->tipo_verificacion === 'user_verified' || $this->hasApprovedIdentityVerification()) {
            return 'user_verified';
        }

        return 'none';
    }
}

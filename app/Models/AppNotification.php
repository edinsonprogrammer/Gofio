<?php

/**
 * Modelo Eloquent de notificaciones in-app.
 * Define tipos de evento y almacena título, cuerpo, enlace de acción y estado de lectura.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    public const TYPE_POST_FROM_FOLLOWING = 'post_from_following';

    public const TYPE_POST_REMOVED = 'post_removed';

    public const TYPE_RANK_UP = 'rank_up';

    public const TYPE_MEDAL_RECEIVED = 'medal_received';

    public const TYPE_AWARD_RECEIVED = 'award_received';

    public const TYPE_VERIFIED = 'verified';

    public const TYPE_MESSAGE_RECEIVED = 'message_received';

    public const TYPE_TIP_RECEIVED = 'tip_received';

    public const TYPE_COMMENT_RECEIVED = 'comment_received';

    public const TYPE_REACTION_RECEIVED = 'reaction_received';

    public const TYPE_FOLLOWING_UPDATED = 'following_updated';

    public const TYPE_NEW_FOLLOWER = 'new_follower';

    public const TYPE_USER_REPORTED = 'user_reported';

    public const TYPE_POST_REPORTED = 'post_reported';

    public const TYPE_CONTENT_REPORTED = 'content_reported';

    public const TYPE_SPAM_DETECTED = 'spam_detected';

    public const TYPE_MODERATOR_ACTION = 'moderator_action';

    public const TYPE_PROFILE_VISIT = 'profile_visit';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'action_url',
        'data',
        'read_at',
        'seen_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'seen_at' => 'datetime',
        ];
    }

    /**
     * Usuario destinatario de la notificación.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

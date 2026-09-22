<?php

/**
 * Job en cola que evalúa si un post alcanzó la popularidad para otorgar karma.
 */

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use App\Services\KarmaRuleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EvaluatePostPopularityJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public int $postId,
        public int $reactionTotal,
    ) {}

    /**
     * Evalúa si el post cumple el umbral de reacciones para otorgar karma de popularidad.
     */
    public function handle(KarmaRuleService $karmaRuleService): void
    {
        $post = Post::query()->with('user')->find($this->postId);

        if ($post?->user instanceof User) {
            $karmaRuleService->evaluatePopularPost($post->user, $post, $this->reactionTotal);
        }
    }
}

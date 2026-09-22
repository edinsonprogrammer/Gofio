<?php

/**
 * Controlador de perfiles públicos: muestra la información, medallas y estadísticas de un usuario.
 */

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateProfileRequest;
use App\Models\User;
use App\Services\AwardService;
use App\Services\FollowService;
use App\Services\MedalService;
use App\Services\ProfileContentService;
use App\Services\ProfileService;
use App\Services\ProfileVisitService;
use App\Services\RankService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly RankService $rankService,
        private readonly MedalService $medalService,
        private readonly AwardService $awardService,
        private readonly ProfileService $profileService,
        private readonly FollowService $followService,
        private readonly ProfileVisitService $profileVisitService,
        private readonly SeoService $seoService,
        private readonly ProfileContentService $profileContentService,
    ) {}

    /**
     * GET /perfil/{username} — responde con la vista Inertia Profile/Show y el perfil público del usuario.
     */
    public function show(string $username): Response
    {
        $user = User::with('rango')
            ->withCount(['posts', 'comments'])
            ->where('username', $username)
            ->firstOrFail();

        $profile = $this->profileService->formatPublicProfile($user);
        $profile['rango'] = $this->rankService->formatRango($user->rango);
        $profile['medals'] = $this->medalService->formatForProfile($user);
        $profile['awards'] = $this->awardService->formatForProfile($user);
        $profile = array_merge($profile, $this->followService->statsFor($user, auth()->user()));

        $viewer = auth()->user();
        if ($viewer && $viewer->id !== $user->id) {
            $this->profileVisitService->recordVisit($viewer, $user);
        }

        // Renderiza el perfil público con permisos según el visitante
        return Inertia::render('Profile/Show', [
            'profile' => $profile,
            'isOwnProfile' => auth()->id() === $user->id,
            'isCreatorPlus' => $user->isCreatorPlus(),
            'canPrioritySupport' => auth()->id() === $user->id && $user->isCreatorPlus(),
            'canViewProfileVisits' => auth()->id() === $user->id && $user->isCreatorPlus(),
            'seo' => $this->seoService->forProfile($user),
            'recentPosts' => $this->profileContentService->recentPosts($user)->values()->all(),
            'postsTotal' => $this->profileContentService->postsTotal($user),
            'recentVidu' => $this->profileContentService->recentViduVideos($user)->values()->all(),
            'viduTotal' => $this->profileContentService->viduTotal($user),
        ]);
    }

    /**
     * GET /perfil/{username}/posts — historial paginado de publicaciones del usuario.
     */
    public function posts(string $username): Response
    {
        $user = User::query()->where('username', $username)->firstOrFail();
        $page = max(1, (int) request()->query('page', 1));

        return Inertia::render('Profile/Posts', [
            'profile' => $this->profileContentService->profileHeader($user),
            'posts' => $this->profileContentService->paginatedPosts($user, $page),
            'isOwnProfile' => auth()->id() === $user->id,
        ]);
    }

    /**
     * GET /perfil/{username}/vidu — historial paginado de videos Vidu del usuario.
     */
    public function vidu(string $username): Response
    {
        $user = User::query()->where('username', $username)->firstOrFail();
        $page = max(1, (int) request()->query('page', 1));

        return Inertia::render('Profile/Vidu', [
            'profile' => $this->profileContentService->profileHeader($user),
            'videos' => $this->profileContentService->paginatedViduVideos($user, $page),
            'isOwnProfile' => auth()->id() === $user->id,
        ]);
    }
}

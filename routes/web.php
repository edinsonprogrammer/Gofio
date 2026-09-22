<?php

/**
 * Rutas web de Gofio: autenticación, páginas Inertia, panel admin y API interna.
 * Todas las rutas protegidas exigen sesión activa y usuario no suspendido.
 */

use App\Http\Controllers\Admin\AwardAdminController;
use App\Http\Controllers\Admin\BadWordAdminController;
use App\Http\Controllers\Admin\CategoryAdminController;
use App\Http\Controllers\Admin\DashboardAdminController;
use App\Http\Controllers\Admin\IconPackAdminController;
use App\Http\Controllers\Admin\MedalAdminController;
use App\Http\Controllers\Admin\ModerationAdminController;
use App\Http\Controllers\Admin\PostAdminController;
use App\Http\Controllers\Admin\RankAdminController;
use App\Http\Controllers\Admin\SettingsAdminController;
use App\Http\Controllers\Admin\TipAdminController;
use App\Http\Controllers\Admin\TicketAdminController;
use App\Http\Controllers\Admin\KarmaRuleAdminController;
use App\Http\Controllers\Admin\ThemeAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\VerificationAdminController;
use App\Http\Controllers\Admin\ViduAdAdminController;
use App\Http\Controllers\Api\ChatApiController;
use App\Http\Controllers\Api\FollowApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\CommentApiController;
use App\Http\Controllers\Api\PostApiController;
use App\Http\Controllers\Api\GiphyApiController;
use App\Http\Controllers\Api\CommentImageApiController;
use App\Http\Controllers\Api\CreatorPlusApiController;
use App\Http\Controllers\Api\PostImageApiController;
use App\Http\Controllers\Api\ProfileImageApiController;
use App\Http\Controllers\Api\SubscriptionApiController;
use App\Http\Controllers\Api\ThemeApiController;
use App\Http\Controllers\Api\VerificationApiController;
use App\Http\Controllers\Api\WalletApiController;
use App\Http\Controllers\Api\ViduApiController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IconPackAssetController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ViduController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Canales de broadcasting con autenticación de sesión
Broadcast::routes(['middleware' => ['web', 'auth']]);

// SEO público: robots, sitemaps, IAs, RSS e IndexNow (indexable sin login)
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemapIndex'])->name('seo.sitemap.index');
Route::get('/sitemaps/{name}', [SeoController::class, 'sitemapPart'])->where('name', '[a-z0-9\\-\\.]+')->name('seo.sitemap.part');
Route::get('/llms.txt', [SeoController::class, 'llmsTxt'])->name('seo.llms');
Route::get('/ai.txt', [SeoController::class, 'llmsTxt'])->name('seo.ai');
Route::get('/feed.xml', [SeoController::class, 'rssFeed'])->name('seo.rss');
Route::get('/{key}.txt', [SeoController::class, 'indexNowKey'])->where('key', '[a-f0-9]{32}')->name('seo.indexnow.key');

// Contenido público indexable por buscadores (posts, perfiles y videos Vidu individuales)
Route::get('/post/{slug}', [PostController::class, 'show'])->name('posts.show');
Route::get('/perfil/{username}/posts', [ProfileController::class, 'posts'])->name('profile.posts');
Route::get('/perfil/{username}/vidu', [ProfileController::class, 'vidu'])->name('profile.vidu');
Route::get('/perfil/{username}', [ProfileController::class, 'show'])->name('profile.show');
Route::get('/vidu/{video}', [ViduController::class, 'show'])->name('vidu.show')->whereNumber('video');

// Invitados: formularios y envío de login y registro
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/registro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registro', [AuthController::class, 'register']);
});

// Usuarios autenticados: navegación principal, ajustes y APIs del frontend
Route::middleware(['auth', 'not_banned'])->group(function () {
    // Recursos estáticos de paquetes de iconos
    Route::get('/icon-packs/assets/{pack}/{path}', [IconPackAssetController::class, 'show'])
        ->where('path', '.*')
        ->name('icon-packs.asset');

    // Páginas de la aplicación (feed y búsqueda requieren sesión)
    Route::get('/', HomeController::class)->name('home');
    Route::get('/buscar', [SearchController::class, 'index'])->name('search');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Vidu: feed de videos cortos estilo Reels
    Route::get('/vidu', [ViduController::class, 'index'])->name('vidu.index');
    Route::get('/vidu/crear', [ViduController::class, 'crear'])->name('vidu.crear');

    // Ajustes de cuenta del usuario autenticado
    Route::prefix('configuracion')->group(function () {
        Route::get('/perfil', [SettingsController::class, 'profile'])->name('settings.profile');
        Route::put('/perfil', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
        Route::get('/verificacion', [SettingsController::class, 'verification'])->name('settings.verification');
        Route::get('/creator-plus', [SettingsController::class, 'creatorPlus'])->name('settings.creator-plus');
        Route::get('/apariencia', [SettingsController::class, 'appearance'])->name('settings.appearance');
    });

    // Panel de administración: staff con permisos por pestaña o administradores globales
    Route::prefix('admin')->middleware(['staff', 'admin.panel'])->name('admin.')->group(function () {
        Route::get('/', [DashboardAdminController::class, 'index'])->name('dashboard');

        // Moderación de denuncias y alertas
        Route::get('/moderacion', [ModerationAdminController::class, 'index'])->name('moderation.index');
        Route::post('/moderacion/denuncias/{report}', [ModerationAdminController::class, 'resolveReport'])->name('moderation.report');
        Route::post('/moderacion/denuncias/{report}/accion', [ModerationAdminController::class, 'applyAction'])->name('moderation.report.action');
        Route::post('/moderacion/denuncias/{report}/editar', [ModerationAdminController::class, 'editContent'])->name('moderation.report.edit');
        Route::post('/moderacion/alertas/{alert}', [ModerationAdminController::class, 'resolveAlert'])->name('moderation.alert');

        // Configuración global del sitio
        Route::get('/configuracion', [SettingsAdminController::class, 'index'])->name('settings.index');
        Route::put('/configuracion', [SettingsAdminController::class, 'update'])->name('settings.update');
        Route::post('/configuracion/logo', [SettingsAdminController::class, 'uploadLogo'])->name('settings.logo.upload');
        Route::delete('/configuracion/logo', [SettingsAdminController::class, 'removeLogo'])->name('settings.logo.remove');

        // Gestión de usuarios
        Route::get('/usuarios', [UserAdminController::class, 'index'])->name('users.index');
        Route::get('/usuarios/buscar', [UserAdminController::class, 'search'])->name('users.search');
        Route::post('/usuarios/{user}/banear', [UserAdminController::class, 'ban'])->name('users.ban');
        Route::post('/usuarios/{user}/desbanear', [UserAdminController::class, 'unban'])->name('users.unban');
        Route::put('/usuarios/{user}', [UserAdminController::class, 'update'])->name('users.update');
        Route::post('/usuarios/{user}/quitar-verificado', [UserAdminController::class, 'revokeVerification'])->name('users.revoke-verification');
        Route::post('/usuarios/{user}/quitar-creator-plus', [UserAdminController::class, 'revokeCreatorPlus'])->name('users.revoke-creator-plus');

        // Gestión de publicaciones
        Route::get('/posts', [PostAdminController::class, 'index'])->name('posts.index');
        Route::post('/posts/{post}/banear', [PostAdminController::class, 'ban'])->name('posts.ban');
        Route::post('/posts/{post}/publicar', [PostAdminController::class, 'publish'])->name('posts.publish');
        Route::post('/posts/{post}/destacar', [PostAdminController::class, 'toggleFeatured'])->name('posts.feature');
        Route::post('/posts/{post}/sticky', [PostAdminController::class, 'toggleSticky'])->name('posts.sticky');

        // Categorías de contenido
        Route::get('/categorias', [CategoryAdminController::class, 'index'])->name('categories.index');
        Route::post('/categorias', [CategoryAdminController::class, 'store'])->name('categories.store');
        Route::put('/categorias/{category}', [CategoryAdminController::class, 'update'])->name('categories.update');
        Route::delete('/categorias/{category}', [CategoryAdminController::class, 'destroy'])->name('categories.destroy');

        // Filtro de palabras prohibidas
        Route::get('/palabras', [BadWordAdminController::class, 'index'])->name('badwords.index');
        Route::post('/palabras', [BadWordAdminController::class, 'store'])->name('badwords.store');
        Route::delete('/palabras/{badWord}', [BadWordAdminController::class, 'destroy'])->name('badwords.destroy');

        // Tickets de soporte
        Route::get('/tickets', [TicketAdminController::class, 'index'])->name('tickets.index');
        Route::put('/tickets/{ticket}', [TicketAdminController::class, 'update'])->name('tickets.update');

        // Verificación de identidad
        Route::get('/verificaciones', [VerificationAdminController::class, 'index'])->name('verifications.index');
        Route::post('/verificaciones/lote', [VerificationAdminController::class, 'bulk'])->name('verifications.bulk');
        Route::post('/verificaciones/{verification}/aprobar', [VerificationAdminController::class, 'approve'])->name('verifications.approve');
        Route::post('/verificaciones/{verification}/rechazar', [VerificationAdminController::class, 'reject'])->name('verifications.reject');
        Route::post('/verificaciones/usuarios/{user}/revocar', [VerificationAdminController::class, 'revokeUser'])->name('verifications.revoke-user');
        Route::get('/verificaciones/{verification}/documento', [VerificationAdminController::class, 'document'])->name('verifications.document');

        // Reglas de karma — CRUD completo
        Route::get('/karma', [KarmaRuleAdminController::class, 'index'])->name('karma-rules.index');
        Route::post('/karma', [KarmaRuleAdminController::class, 'store'])->name('karma-rules.store');
        Route::put('/karma/{rule}', [KarmaRuleAdminController::class, 'update'])->name('karma-rules.update');
        Route::delete('/karma/{rule}', [KarmaRuleAdminController::class, 'destroy'])->name('karma-rules.destroy');

        // Temas visuales
        Route::get('/temas', [ThemeAdminController::class, 'index'])->name('themes.index');
        Route::post('/temas/sincronizar', [ThemeAdminController::class, 'sync'])->name('themes.sync');
        Route::post('/temas', [ThemeAdminController::class, 'store'])->name('themes.store');
        Route::put('/temas/{theme}', [ThemeAdminController::class, 'update'])->name('themes.update');
        Route::delete('/temas/{theme}', [ThemeAdminController::class, 'destroy'])->name('themes.destroy');

        // Paquetes de iconos
        Route::get('/iconos', [IconPackAdminController::class, 'index'])->name('icon-packs.index');
        Route::get('/iconos/biblioteca', [IconPackAdminController::class, 'library'])->name('icon-packs.library');
        Route::post('/iconos/sincronizar', [IconPackAdminController::class, 'sync'])->name('icon-packs.sync');

        // Rangos y permisos por karma
        Route::get('/rangos', [RankAdminController::class, 'index'])->name('ranks.index');
        Route::post('/rangos', [RankAdminController::class, 'store'])->name('ranks.store');
        Route::put('/rangos/{rank}', [RankAdminController::class, 'update'])->name('ranks.update');
        Route::delete('/rangos/{rank}', [RankAdminController::class, 'destroy'])->name('ranks.destroy');
        Route::post('/rangos/{rank}/asignar', [RankAdminController::class, 'assign'])->name('ranks.assign');
        Route::post('/rangos/usuarios/{user}/desbloquear', [RankAdminController::class, 'unlock'])->name('ranks.unlock');

        // Economía de propinas y monedas
        Route::get('/propinas', [TipAdminController::class, 'index'])->name('tips.index');
        Route::put('/propinas/comision', [TipAdminController::class, 'updateFee'])->name('tips.fee');
        Route::post('/propinas/depositos', [TipAdminController::class, 'registerDeposit'])->name('tips.deposit');

        // Medallas de logro
        Route::get('/medallas', [MedalAdminController::class, 'index'])->name('medals.index');
        Route::post('/medallas', [MedalAdminController::class, 'store'])->name('medals.store');
        Route::put('/medallas/{medal}', [MedalAdminController::class, 'update'])->name('medals.update');
        Route::post('/medallas/{medal}/asignar', [MedalAdminController::class, 'assign'])->name('medals.assign');
        Route::delete('/medallas/{medal}/usuarios/{user}', [MedalAdminController::class, 'revoke'])->name('medals.revoke');

        // Premios y categorías de premios
        Route::get('/premios', [AwardAdminController::class, 'index'])->name('awards.index');
        Route::post('/premios/categorias', [AwardAdminController::class, 'storeCategory'])->name('awards.categories.store');
        Route::post('/premios', [AwardAdminController::class, 'storeAward'])->name('awards.store');
        Route::put('/premios/{award}', [AwardAdminController::class, 'updateAward'])->name('awards.update');
        Route::post('/premios/{award}/otorgar', [AwardAdminController::class, 'grant'])->name('awards.grant');
        Route::delete('/premios/{award}/usuarios/{user}', [AwardAdminController::class, 'revoke'])->name('awards.revoke');

        // Pausas publicitarias Vidu
        // Publicidad Vidu
        Route::get('/vidu-publicidad', [ViduAdAdminController::class, 'index'])->name('vidu-ads.index');
        Route::put('/vidu-publicidad/configuracion', [ViduAdAdminController::class, 'updateSettings'])->name('vidu-ads.settings');
        // Creativos de video (rotación aleatoria de activos)
        Route::post('/vidu-publicidad/creativos', [ViduAdAdminController::class, 'uploadCreative'])->name('vidu-ads.creatives.store');
        Route::put('/vidu-publicidad/creativos/{creative}', [ViduAdAdminController::class, 'toggleCreative'])->name('vidu-ads.creatives.toggle');
        Route::delete('/vidu-publicidad/creativos/{creative}', [ViduAdAdminController::class, 'destroyCreative'])->name('vidu-ads.creatives.destroy');
        // Banners laterales
        Route::post('/vidu-publicidad/banners', [ViduAdAdminController::class, 'uploadBanner'])->name('vidu-ads.banners.store');
        Route::put('/vidu-publicidad/banners/{banner}', [ViduAdAdminController::class, 'updateBanner'])->name('vidu-ads.banners.update');
        Route::delete('/vidu-publicidad/banners/{banner}', [ViduAdAdminController::class, 'destroyBanner'])->name('vidu-ads.banners.destroy');
        // Alcance por video
        Route::post('/vidu-publicidad/videos/masivo', [ViduAdAdminController::class, 'bulkVideos'])->name('vidu-ads.videos.bulk');
        Route::put('/vidu-publicidad/videos/{video}', [ViduAdAdminController::class, 'updateVideo'])->name('vidu-ads.videos.update');
    });

    // API JSON consumida por el frontend Inertia (misma sesión web)
    Route::prefix('api')->group(function () {
        // Publicaciones: feed, creación, votos y reacciones
        Route::get('/posts', [PostApiController::class, 'index']);
        Route::get('/posts/composer-config', [PostApiController::class, 'composerConfig']);
        Route::post('/posts', [PostApiController::class, 'store']);
        Route::get('/posts/{slug}', [PostApiController::class, 'show']);
        Route::post('/posts/{post}/vote', [PostApiController::class, 'vote']);
        Route::post('/posts/{post}/view', [PostApiController::class, 'recordView']);
        Route::post('/posts/{post}/react', [PostApiController::class, 'react']);
        Route::get('/posts/{slug}/comments', [PostApiController::class, 'comments']);
        Route::post('/posts/{slug}/comments', [PostApiController::class, 'storeComment']);
        Route::post('/comments/{comment}/vote', [CommentApiController::class, 'vote']);

        // Subida de imágenes para posts, comentarios y perfil
        Route::post('/uploads/post-image', [PostImageApiController::class, 'store']);
        Route::post('/uploads/comment-image', [CommentImageApiController::class, 'store']);
        Route::post('/uploads/profile-avatar', [ProfileImageApiController::class, 'storeAvatar']);
        Route::post('/uploads/profile-banner', [ProfileImageApiController::class, 'storeBanner']);

        // Buscador de GIFs vía proxy GIPHY (API key solo en servidor)
        Route::get('/giphy/status', [GiphyApiController::class, 'status']);
        Route::get('/giphy/trending', [GiphyApiController::class, 'trending']);
        Route::get('/giphy/search', [GiphyApiController::class, 'search']);

        // Monedero virtual y propinas
        Route::get('/wallet', [WalletApiController::class, 'summary']);
        Route::post('/posts/{post}/tip', [WalletApiController::class, 'sendTip']);

        // Mensajería privada entre usuarios
        Route::get('/chat/conversations', [ChatApiController::class, 'index']);
        Route::get('/chat/contacts', [ChatApiController::class, 'contacts']);
        Route::post('/chat/presence', [ChatApiController::class, 'presence']);
        Route::post('/chat/conversations', [ChatApiController::class, 'store']);
        Route::get('/chat/conversations/{conversation}/messages', [ChatApiController::class, 'messages']);
        Route::post('/chat/conversations/{conversation}/messages', [ChatApiController::class, 'sendMessage']);

        // Verificación de identidad del usuario
        Route::get('/verification/status', [VerificationApiController::class, 'status']);
        Route::post('/verification/submit', [VerificationApiController::class, 'submit']);

        // Suscripción Creator Plus
        Route::get('/subscription/status', [SubscriptionApiController::class, 'status']);
        Route::post('/subscription/subscribe', [SubscriptionApiController::class, 'subscribe']);

        // Beneficios exclusivos Creator Plus
        Route::post('/creator-plus/priority-ticket', [CreatorPlusApiController::class, 'priorityTicket']);
        Route::get('/creator-plus/profile-visits', [CreatorPlusApiController::class, 'profileVisits']);

        // Personalización de tema visual
        Route::get('/themes/status', [ThemeApiController::class, 'status']);
        Route::post('/themes/select', [ThemeApiController::class, 'select']);

        // Notificaciones del usuario
        Route::get('/notifications', [NotificationApiController::class, 'index']);
        Route::post('/notifications/{notification}/read', [NotificationApiController::class, 'markRead']);
        Route::post('/notifications/mark-seen', [NotificationApiController::class, 'markSeen']);

        // Seguimiento entre usuarios
        Route::post('/users/{username}/follow', [FollowApiController::class, 'follow']);
        Route::delete('/users/{username}/follow', [FollowApiController::class, 'unfollow']);

        // Denuncias de contenido o usuarios
        Route::get('/reports/reasons', [ReportApiController::class, 'reasons']);
        Route::post('/reports', [ReportApiController::class, 'store']);

        // API Vidu: feed, subida, acciones e historial de videos cortos
        Route::get('/vidu/feed', [ViduApiController::class, 'feed']);
        Route::get('/vidu/saved', [ViduApiController::class, 'saved']);
        Route::get('/vidu/mine', [ViduApiController::class, 'mine']);
        Route::post('/vidu/upload', [ViduApiController::class, 'upload']);
        Route::post('/vidu/{video}/like', [ViduApiController::class, 'like'])->whereNumber('video');
        Route::post('/vidu/{video}/save', [ViduApiController::class, 'save'])->whereNumber('video');
        Route::post('/vidu/{video}/view', [ViduApiController::class, 'view'])->whereNumber('video');
        Route::delete('/vidu/{video}', [ViduApiController::class, 'destroy'])->whereNumber('video');
    });
});

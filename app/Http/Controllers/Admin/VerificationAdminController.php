<?php



/**

 * Controlador admin de verificación de identidad: revisión de solicitudes, aprobación y descarga de documentos.

 */



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\IdentityVerificationRequest;

use App\Models\User;

use App\Repositories\VerificationRepository;

use App\Services\AdminLogService;

use App\Services\UserAdminService;

use App\Services\VerificationService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;

use Inertia\Inertia;

use Inertia\Response;

use Symfony\Component\HttpFoundation\StreamedResponse;



class VerificationAdminController extends Controller

{

    public function __construct(

        private readonly VerificationRepository $verificationRepository,

        private readonly VerificationService $verificationService,

        private readonly UserAdminService $userAdminService,

        private readonly AdminLogService $adminLogService,

    ) {}



    /**

     * GET /admin/verificaciones — panel con pestañas, búsqueda y listados paginados.

     */

    public function index(Request $request): Response

    {

        $search = trim((string) $request->input('q', ''));

        $searchFilter = $search !== '' ? $search : null;

        $tab = in_array($request->input('tab'), ['pending', 'verified'], true)

            ? $request->input('tab')

            : 'pending';



        // Renderiza el panel de revisión con filtros para gestionar grandes volúmenes

        return Inertia::render('Admin/Verifications/Index', [

            'filters' => [

                'q'   => $search,

                'tab' => $tab,

            ],

            'counts' => [

                'pending'  => $this->verificationRepository->countPending(),

                'verified' => $this->verificationRepository->countVerifiedUsers(),

            ],

            'pending' => $this->verificationRepository->getPendingPaginated(20, $searchFilter)

                ->through(fn (IdentityVerificationRequest $req) => [

                    'id'            => $req->id,

                    'full_name'     => $req->full_name,

                    'document_type' => $req->document_type,

                    'user_notes'    => $req->user_notes,

                    'created_at'    => $req->created_at?->toISOString(),

                    'user'          => [

                        'id'       => $req->user->id,

                        'username' => $req->user->username,

                        'email'    => $req->user->email,

                    ],

                ]),

            'verified' => $this->verificationRepository->getVerifiedUsersPaginated(20, $searchFilter)

                ->through(fn (User $user) => [

                    'id'                        => $user->id,

                    'username'                  => $user->username,

                    'email'                     => $user->email,

                    'display_verification'      => $user->displayVerificationTipo(),

                    'is_creator_plus'           => $user->isCreatorPlus(),

                    'has_identity_verification' => $user->hasApprovedIdentityVerification(),

                ]),

            'recent' => $this->verificationRepository->getRecentReviewed(8)->map(fn ($req) => [

                'id'          => $req->id,

                'username'    => $req->user->username,

                'full_name'   => $req->full_name,

                'status'      => $req->status,

                'reviewed_at' => $req->reviewed_at?->toISOString(),

            ]),

        ]);

    }



    /**

     * POST /admin/verificaciones/{verification}/aprobar — aprueba la solicitud y redirige de vuelta.

     */

    public function approve(Request $request, IdentityVerificationRequest $verification): RedirectResponse

    {

        $request->validate([

            'admin_notes' => ['nullable', 'string', 'max:500'],

        ]);



        $this->verificationService->approve(

            $verification,

            auth()->user(),

            $request->input('admin_notes'),

        );

        $this->adminLogService->log(auth()->user(), 'approve_verification', 'verification', $verification->id, $verification->user->username);

        return back()->with('success', "Verificación de @{$verification->user->username} aprobada.");

    }



    /**

     * POST /admin/verificaciones/{verification}/rechazar — rechaza la solicitud y redirige de vuelta.

     */

    public function reject(Request $request, IdentityVerificationRequest $verification): RedirectResponse

    {

        $request->validate([

            'admin_notes' => ['nullable', 'string', 'max:500'],

        ]);



        $this->verificationService->reject(

            $verification,

            auth()->user(),

            $request->input('admin_notes'),

        );

        $this->adminLogService->log(auth()->user(), 'reject_verification', 'verification', $verification->id, $verification->user->username);

        return back()->with('success', "Solicitud de @{$verification->user->username} rechazada.");

    }



    /**

     * POST /admin/verificaciones/lote — aprueba o rechaza múltiples solicitudes pendientes a la vez.

     */

    public function bulk(Request $request): RedirectResponse

    {

        $data = $request->validate([

            'action'      => ['required', 'in:approve,reject'],

            'ids'         => ['required', 'array', 'min:1', 'max:50'],

            'ids.*'       => ['integer', 'exists:identity_verification_requests,id'],

            'admin_notes' => ['nullable', 'string', 'max:500'],

        ]);



        $result = $this->verificationService->bulkReview(

            auth()->user(),

            $data['ids'],

            $data['action'],

            $data['admin_notes'] ?? null,

        );



        $verb = $data['action'] === 'approve' ? 'aprobadas' : 'rechazadas';

        $message = "{$result['processed']} solicitud(es) {$verb}.";



        if ($result['skipped'] > 0) {

            $message .= " {$result['skipped']} omitida(s) por ya estar revisadas.";

        }

        $this->adminLogService->log(
            auth()->user(),
            $data['action'] === 'approve' ? 'bulk_approve_verifications' : 'bulk_reject_verifications',
            'verification',
            null,
            "{$result['processed']} solicitud(es)",
        );

        return back()->with('success', $message);

    }



    /**

     * POST /admin/verificaciones/usuarios/{user}/revocar — revoca la verificación de un usuario ya aprobado.

     */

    public function revokeUser(Request $request, User $user): RedirectResponse

    {

        $data = $request->validate([

            'reason' => ['nullable', 'string', 'max:500'],

        ]);



        $this->userAdminService->revokeVerification(auth()->user(), $user, $data['reason'] ?? null);



        return back()->with('success', "Verificación de @{$user->username} revocada.");

    }



    /**

     * GET /admin/verificaciones/{verification}/documento — responde con la descarga del documento adjunto o 404.

     */

    public function document(IdentityVerificationRequest $verification): StreamedResponse

    {

        abort_unless(Storage::disk('local')->exists($verification->document_path), 404);



        return Storage::disk('local')->download(

            $verification->document_path,

            'verificacion-'.$verification->user->username.'-'.$verification->id.'.'.pathinfo($verification->document_path, PATHINFO_EXTENSION),

        );

    }

}



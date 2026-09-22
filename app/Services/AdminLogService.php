<?php

namespace App\Services;

/**
 * Registra acciones administrativas con contexto de auditoría (IP, metadatos, objetivo).
 */

use App\Models\User;
use App\Repositories\AdminLogRepository;
use Illuminate\Http\Request;

class AdminLogService
{
    /**
     * Inyecta el repositorio de logs de administración.
     */
    public function __construct(
        private readonly AdminLogRepository $adminLogRepository,
    ) {}

    /**
     * Persiste una entrada de log con la acción, objetivo opcional y datos de la petición HTTP.
     */
    public function log(
        User $admin,
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?string $reason = null,
        ?array $metadata = null,
        ?Request $request = null,
    ): void {
        $this->adminLogRepository->create([
            'admin_id' => $admin->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'reason' => $reason,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'created_at' => now(),
        ]);
    }
}

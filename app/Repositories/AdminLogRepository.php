<?php

namespace App\Repositories;

/**
 * Acceso a datos de logs de acciones administrativas y su consulta paginada.
 */

use App\Models\AdminActionLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminLogRepository
{
    /**
     * Inserta un nuevo registro de acción administrativa en la base de datos.
     */
    public function create(array $data): AdminActionLog
    {
        return AdminActionLog::create($data);
    }

    /**
     * Obtiene los logs más recientes con el admin asociado, limitados por cantidad.
     */
    public function getRecent(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return AdminActionLog::query()
            ->with(['admin:id,username,rango_id', 'admin.rango:id,nombre,slug'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Lista logs administrativos paginados, ordenados del más reciente al más antiguo.
     */
    public function paginate(int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return AdminActionLog::query()
            ->with(['admin:id,username,rango_id', 'admin.rango:id,nombre,slug'])
            ->latest('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}

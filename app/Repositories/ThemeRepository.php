<?php

namespace App\Repositories;

/**
 * Consultas y mutaciones de temas visuales, incluyendo sincronización desde carpetas del filesystem.
 */

use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ThemeRepository
{
    /**
     * Busca un tema por identificador numérico.
     */
    public function findById(int $id): ?Theme
    {
        return Theme::query()->find($id);
    }

    /**
     * Busca un tema por slug único.
     */
    public function findBySlug(string $slug): ?Theme
    {
        return Theme::query()->where('slug', $slug)->first();
    }

    /**
     * Devuelve el tema marcado como default activo o el primer tema activo disponible.
     */
    public function getDefault(): ?Theme
    {
        return Theme::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first()
            ?? Theme::query()->where('is_active', true)->orderBy('id')->first();
    }

    /**
     * Lista temas activos disponibles para el usuario, ocultando premium si no tiene acceso.
     */
    public function getAvailableForUser(User $user): Collection
    {
        $includePremium = $user->isCreatorPlus()
            || $user->isAdmin()
            || $user->hasStaffRank();

        return Theme::query()
            ->where('is_active', true)
            ->when(! $includePremium, fn ($q) => $q->where('requires_creator_plus', false))
            ->orderBy('requires_creator_plus')
            ->orderByRaw("CASE WHEN slug = 'inicio-login' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtiene todos los temas para el panel administrativo ordenados por default y nombre.
     */
    public function getAllForAdmin(): Collection
    {
        return Theme::query()->orderByDesc('is_default')->orderBy('name')->get();
    }

    /**
     * Crea un nuevo tema en base de datos con los atributos proporcionados.
     */
    public function create(array $data): Theme
    {
        return Theme::create($data);
    }

    /**
     * Actualiza un tema existente y devuelve la instancia refrescada desde la base de datos.
     */
    public function update(Theme $theme, array $data): Theme
    {
        $theme->update($data);

        return $theme->fresh();
    }

    /**
     * Quita el flag is_default de todos los temas antes de designar uno nuevo como predeterminado.
     */
    public function clearDefaultFlag(): void
    {
        Theme::query()->where('is_default', true)->update(['is_default' => false]);
    }

    /**
     * Cuenta usuarios que tienen seleccionado un tema concreto.
     */
    public function countUsersWithTheme(Theme $theme): int
    {
        return User::query()->where('theme_id', $theme->id)->count();
    }

    /**
     * Limpia la preferencia de tema de los usuarios que tenían seleccionado el tema eliminado.
     */
    public function clearUserSelections(Theme $theme): void
    {
        User::query()->where('theme_id', $theme->id)->update(['theme_id' => null]);
    }

    /**
     * Elimina un tema de la base de datos.
     */
    public function delete(Theme $theme): void
    {
        $theme->delete();
    }

    /**
     * Inserta o actualiza un tema importado desde carpeta, indicando si fue creado o modificado.
     */
    public function upsertFolderTheme(string $slug, array $designAttributes, bool $isDefault, bool $requiresCreatorPlus): array
    {
        $existing = Theme::query()->where('slug', $slug)->first();

        if ($existing) {
            $existing->update($designAttributes);

            return [$existing->fresh(), false];
        }

        $created = Theme::create([
            ...$designAttributes,
            'slug' => $slug,
            'is_active' => true,
            'is_default' => $isDefault,
            'requires_creator_plus' => $requiresCreatorPlus,
        ]);

        return [$created, true];
    }
}

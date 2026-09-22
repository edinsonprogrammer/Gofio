<?php

/**
 * Seeder que crea los temas visuales predefinidos de Gofio.
 */

namespace Database\Seeders;

use App\Models\Theme;
use App\Support\ThemeVariables;
use Illuminate\Database\Seeder;

class ThemesSeeder extends Seeder
{
    /**
     * Crea ocho temas visuales incluyendo Menta Tecnológico como predeterminado.
     */
    public function run(): void
    {
        $loginCustomCss = file_exists(base_path('database/seeders/assets/inicio-login.css'))
            ? file_get_contents(base_path('database/seeders/assets/inicio-login.css'))
            : null;

        $themes = [
            [
                'name' => 'Menta Tecnológico',
                'slug' => 'mint',
                'description' => 'Verde menta profesional con neutros slate — identidad principal de Gofio.',
                'is_default' => true,
                'requires_creator_plus' => false,
                'variables' => ThemeVariables::defaults(),
            ],
            [
                'name' => 'Facebook Clásico',
                'slug' => 'classic',
                'description' => 'Look retro azul Facebook 2008–2010.',
                'is_default' => false,
                'requires_creator_plus' => false,
                'variables' => ThemeVariables::merge([
                    '--bg-principal' => '#E9EBEE',
                    '--text-principal' => '#1D2129',
                    '--color-brand' => '#3B5998',
                    '--color-border' => '#DADDE1',
                    '--color-muted' => '#65676B',
                    '--color-link' => '#385898',
                    '--color-topbar-dark' => '#29487D',
                    '--color-panel' => '#F5F6F7',
                    '--color-brand-hover' => '#2d4373',
                    '--color-input-border' => '#bdc7d8',
                    '--color-btn-secondary-bg' => '#f5f6f7',
                    '--color-btn-secondary-border' => '#ccd0d5',
                    '--color-btn-secondary-text' => '#4b4f56',
                    '--color-topbar-gradient-from' => '#3B5998',
                    '--color-topbar-gradient-to' => '#3B5998',
                    '--color-accent' => '#8B9DC3',
                    '--color-accent-soft' => '#E9EBEE',
                ]),
            ],
            [
                'name' => 'Inicio (Login)',
                'slug' => 'inicio-login',
                'description' => 'Header negro, botones azul oscuro pill y «!» cyan — igual que la pantalla de login.',
                'is_default' => false,
                'requires_creator_plus' => false,
                'variables' => ThemeVariables::merge([
                    '--bg-principal' => '#E9EBEE',
                    '--text-principal' => '#1D2129',
                    '--color-brand' => '#3B5998',
                    '--color-border' => '#DADDE1',
                    '--color-muted' => '#65676B',
                    '--color-link' => '#385898',
                    '--color-topbar-dark' => '#171717',
                    '--color-panel' => '#F5F6F7',
                    '--color-brand-hover' => '#29487D',
                    '--color-input-border' => '#BDC7D8',
                    '--color-btn-secondary-bg' => '#F5F6F7',
                    '--color-btn-secondary-border' => '#CCD0D5',
                    '--color-btn-secondary-text' => '#4B4F56',
                    '--color-topbar-gradient-from' => '#3B5998',
                    '--color-topbar-gradient-to' => '#29487D',
                    '--color-accent' => '#00EAFF',
                    '--color-accent-soft' => '#E9EBEE',
                ]),
                'custom_css' => $loginCustomCss,
            ],
            [
                'name' => 'Modo Oscuro',
                'slug' => 'dark',
                'description' => 'Fondo oscuro con acentos menta.',
                'is_default' => false,
                'requires_creator_plus' => false,
                'variables' => ThemeVariables::merge([
                    '--bg-principal' => '#0F172A',
                    '--text-principal' => '#F1F5F9',
                    '--color-brand' => '#2DD4BF',
                    '--color-surface' => '#1E293B',
                    '--color-border' => '#334155',
                    '--color-muted' => '#94A3B8',
                    '--color-link' => '#5EEAD4',
                    '--color-topbar-dark' => '#0F766E',
                    '--color-panel' => '#1E293B',
                    '--color-brand-hover' => '#14B8A6',
                    '--color-input-border' => '#475569',
                    '--color-btn-secondary-bg' => '#1E293B',
                    '--color-btn-secondary-border' => '#334155',
                    '--color-btn-secondary-text' => '#E2E8F0',
                    '--color-topbar-gradient-from' => '#115E59',
                    '--color-topbar-gradient-to' => '#0F766E',
                    '--color-accent' => '#5EEAD4',
                    '--color-accent-soft' => '#134E4A',
                ]),
            ],
            [
                'name' => 'Océano',
                'slug' => 'ocean',
                'description' => 'Cyan profundo que complementa la menta.',
                'is_default' => false,
                'requires_creator_plus' => false,
                'variables' => ThemeVariables::merge([
                    '--color-brand' => '#0891B2',
                    '--color-link' => '#0E7490',
                    '--color-topbar-dark' => '#155E75',
                    '--color-brand-hover' => '#0E7490',
                    '--color-topbar-gradient-from' => '#0E7490',
                    '--color-topbar-gradient-to' => '#06B6D4',
                    '--color-panel' => '#ECFEFF',
                    '--color-accent' => '#22D3EE',
                    '--color-accent-soft' => '#CFFAFE',
                ]),
            ],
            [
                'name' => 'Creator Plus',
                'slug' => 'creator-plus',
                'description' => 'Menta premium con gradiente esmeralda para suscriptores.',
                'is_default' => false,
                'requires_creator_plus' => true,
                'variables' => ThemeVariables::merge([
                    '--color-brand' => '#059669',
                    '--color-link' => '#047857',
                    '--color-topbar-dark' => '#065F46',
                    '--color-brand-hover' => '#047857',
                    '--color-border' => '#A7F3D0',
                    '--color-panel' => '#ECFDF5',
                    '--color-topbar-gradient-from' => '#064E3B',
                    '--color-topbar-gradient-to' => '#10B981',
                    '--color-accent' => '#34D399',
                    '--color-accent-soft' => '#D1FAE5',
                ]),
            ],
            [
                'name' => 'Medianoche',
                'slug' => 'midnight',
                'description' => 'Teal profundo nocturno — exclusivo Creator Plus.',
                'is_default' => false,
                'requires_creator_plus' => true,
                'variables' => ThemeVariables::merge([
                    '--bg-principal' => '#042F2E',
                    '--text-principal' => '#F0FDFA',
                    '--color-brand' => '#14B8A6',
                    '--color-surface' => '#134E4A',
                    '--color-border' => '#115E59',
                    '--color-muted' => '#99F6E4',
                    '--color-link' => '#5EEAD4',
                    '--color-topbar-dark' => '#0D9488',
                    '--color-panel' => '#115E59',
                    '--color-brand-hover' => '#0D9488',
                    '--color-input-border' => '#115E59',
                    '--color-btn-secondary-bg' => '#134E4A',
                    '--color-btn-secondary-border' => '#0F766E',
                    '--color-btn-secondary-text' => '#CCFBF1',
                    '--color-topbar-gradient-from' => '#042F2E',
                    '--color-topbar-gradient-to' => '#0F766E',
                    '--color-accent' => '#2DD4BF',
                    '--color-accent-soft' => '#134E4A',
                ]),
            ],
        ];

        Theme::query()->update(['is_default' => false]);

        foreach ($themes as $theme) {
            Theme::updateOrCreate(
                ['slug' => $theme['slug']],
                [
                    'name' => $theme['name'],
                    'description' => $theme['description'],
                    'variables' => $theme['variables'],
                    'custom_css' => $theme['custom_css'] ?? null,
                    'is_default' => $theme['is_default'],
                    'requires_creator_plus' => $theme['requires_creator_plus'],
                    'is_active' => true,
                    'source' => 'database',
                ]
            );
        }

        Theme::query()->whereNotIn('slug', collect($themes)->pluck('slug'))->where('source', 'database')->delete();
    }
}

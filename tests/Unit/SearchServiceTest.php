<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_user_by_nick(): void
    {
        User::factory()->create([
            'username' => 'usuario_real',
            'nick' => 'elcrack',
        ]);

        User::factory()->create([
            'username' => 'otro_user',
            'nick' => 'diferente',
        ]);

        $results = app(SearchService::class)->search('elcrack');

        $this->assertCount(1, $results['users']);
        $this->assertSame('usuario_real', $results['users']->first()->username);
    }

    public function test_finds_user_by_nick_with_at_prefix(): void
    {
        User::factory()->create([
            'username' => 'maria99',
            'nick' => 'maria_nick',
        ]);

        $results = app(SearchService::class)->search('@maria_nick');

        $this->assertCount(1, $results['users']);
        $this->assertSame('maria99', $results['users']->first()->username);
    }
}

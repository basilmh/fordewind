<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class FrontendPagesTest extends TestCase
{
    #[Test]
    #[TestDox('отображает jQuery страницу голосования на главном маршруте')]
    public function rendersVotingPageOnTheHomeRoute(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="vote-app"', false)
            ->assertSee('id="vote-cycle-modal"', false)
            ->assertSee('id="vote-restart-cycle"', false)
            ->assertSee('Голосование');
    }

    #[Test]
    #[TestDox('отображает отдельную Vue страницу статистики')]
    public function rendersStatisticsPage(): void
    {
        $this->get(route('statistics'))
            ->assertOk()
            ->assertSee('id="statistics-app"', false)
            ->assertSee('Живой рейтинг');
    }
}

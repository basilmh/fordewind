<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

class ExampleTest extends TestCase
{
    #[Test]
    #[TestDox('базовое утверждение истинно')]
    public function basicAssertionIsTrue(): void
    {
        $this->assertTrue(true);
    }
}

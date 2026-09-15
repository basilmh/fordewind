<?php

namespace App\ValueObjects;

use App\Enums\Currency;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class Money
{
    public const int SCALE = 2;

    public function __construct(
        public BigDecimal $amount,
        public Currency $currency = Currency::USD,
    ) {}

    public static function fromDecimal(int|float|string $amount, Currency $currency = Currency::USD): self
    {
        return new self(
            BigDecimal::of((string) $amount)->toScale(self::SCALE, RoundingMode::UNNECESSARY),
            $currency,
        );
    }

    public function toDecimal(): string
    {
        return (string) $this->amount->toScale(self::SCALE, RoundingMode::UNNECESSARY);
    }
}

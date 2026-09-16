<?php

namespace App\Data\Requests;

use Illuminate\Validation\Validator;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
#[MergeValidationRules]
final class StatisticsFilterData extends Data
{
    public function __construct(
        #[Validation\Nullable, Validation\StringType, Validation\Max(100), Validation\Exists('cars', 'make')]
        public ?string $make,
        #[Validation\Nullable, Validation\StringType, Validation\Max(100), Validation\Exists('cars', 'model')]
        public ?string $model,
        #[Validation\Nullable, Validation\IntegerType, Validation\Min(1900)]
        public ?int $yearFrom,
        #[Validation\Nullable, Validation\IntegerType, Validation\Min(1900)]
        public ?int $yearTo,
        #[Validation\IntegerType, Validation\Min(1)]
        public int $page = 1,
        #[Validation\IntegerType, Validation\Between(1, 100)]
        public int $perPage = 24,
    ) {}

    public static function rules(): array
    {
        $currentYear = (int) now()->year;

        return [
            'year_from' => [new Validation\Max($currentYear)],
            'year_to' => [new Validation\Max($currentYear)],
        ];
    }

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $values = $validator->getData();

            if (!isset($values['year_from'], $values['year_to'])) {
                return;
            }

            if ((int) $values['year_from'] > (int) $values['year_to']) {
                $validator->errors()->add('year_to', 'The year to field must be greater than or equal to year from.');
            }
        });
    }
}

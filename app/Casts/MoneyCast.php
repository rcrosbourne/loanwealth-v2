<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Money\Currency;
use Money\Money;

/**
 * Cast database values to Money objects
 *
 * PHASE 1 LIMITATION: This cast currently assumes all monetary values are in JMD (Jamaican Dollar).
 * This is intentional for Phase 1 to simplify the initial implementation.
 *
 * FUTURE ENHANCEMENT (Phase 2+):
 * For multi-currency support, we will need to:
 * 1. Add a 'currency' column to tables storing monetary values
 * 2. Create a parametrized cast that accepts the currency column name
 * 3. Update all models using this cast to specify their currency column
 *
 * Example future implementation:
 * protected function casts(): array {
 *     return ['amount' => MoneyCast::class.':currency'];
 * }
 *
 * @implements CastsAttributes<Money, mixed>
 */
final class MoneyCast implements CastsAttributes
{
    /**
     * Cast the given value to Money object
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        /** @var int|numeric-string $value */
        // TODO: Phase 2 - Read currency from model's currency column instead of hardcoding JMD
        return new Money($value, new Currency('JMD'));
    }

    /**
     * Prepare the given value for storage
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        throw_unless($value instanceof Money, InvalidArgumentException::class, 'Value must be an instance of Money');

        return $value->getAmount();
    }
}

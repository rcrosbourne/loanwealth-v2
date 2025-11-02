<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Money\Currency;
use Money\Money;

/**
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

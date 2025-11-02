<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\SupportedCurrency;
use Money\Currency;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;

final class Funds
{
    /**
     * Create Money in JMD (default currency)
     */
    public static function of(int|string $amount): Money
    {
        return new Money((string) $amount, new Currency('JMD'));
    }

    /**
     * Create Money builder for specific currency
     */
    public static function in(SupportedCurrency $currency): FundsBuilder
    {
        return new FundsBuilder($currency);
    }

    /**
     * Format Money object to readable string
     */
    public static function format(Money $money): string
    {
        $currencyCode = $money->getCurrency()->getCode();
        $locale = match ($currencyCode) {
            'JMD' => 'en_JM',
            'USD' => 'en_US',
            default => 'en_US',
        };

        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

        return $moneyFormatter->format($money);
    }

    /**
     * Allocate money by ratios (for loan distributions)
     *
     * @param  array<int|string>  $ratios
     * @return array<Money>
     */
    public static function allocateByRatio(Money $amount, array $ratios): array
    {
        return $amount->allocate($ratios);
    }

    /**
     * Allocate money by percentages (must total 100)
     *
     * @param  array<int>  $percentages
     * @return array<Money>
     */
    public static function allocateByPercentage(Money $amount, array $percentages): array
    {
        $total = array_sum($percentages);

        if ($total !== 100) {
            throw new \InvalidArgumentException('Percentages must total 100');
        }

        return $amount->allocate($percentages);
    }

    /**
     * Calculate allocation ratios from Money objects
     *
     * @param  array<Money>  $investments
     * @return array<int>
     */
    public static function calculateRatios(array $investments): array
    {
        return array_map(
            fn (Money $money) => (int) $money->getAmount(),
            $investments
        );
    }
}

/**
 * Builder for creating Money in specific currencies
 */
final class FundsBuilder
{
    public function __construct(
        private readonly SupportedCurrency $currency
    ) {}

    /**
     * Create Money in the selected currency
     */
    public function of(int|string $amount): Money
    {
        return new Money((string) $amount, new Currency($this->currency->value));
    }
}

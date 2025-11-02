<?php

declare(strict_types=1);

use App\Enums\SupportedCurrency;

it('returns correct label for JMD currency', function (): void {
    expect(SupportedCurrency::JMD->label())->toBe('Jamaican Dollar');
});

it('returns correct label for USD currency', function (): void {
    expect(SupportedCurrency::USD->label())->toBe('US Dollar');
});

it('returns correct symbol for JMD currency', function (): void {
    expect(SupportedCurrency::JMD->symbol())->toBe('$');
});

it('returns correct symbol for USD currency', function (): void {
    expect(SupportedCurrency::USD->symbol())->toBe('$');
});

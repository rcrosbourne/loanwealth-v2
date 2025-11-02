<?php

declare(strict_types=1);

use App\Enums\SupportedCurrency;
use App\Support\Funds;
use Money\Currency;
use Money\Money;

it('can create Money objects using Funds::of with default JMD currency', function (): void {
    $money = Funds::of(10000);

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->getAmount())->toBe('10000')
        ->and($money->getCurrency()->getCode())->toBe('JMD');
});

it('can create Money objects with specific currency using Funds::in', function (): void {
    $money = Funds::in(SupportedCurrency::USD)->of(10000);

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->getAmount())->toBe('10000')
        ->and($money->getCurrency()->getCode())->toBe('USD');
});

it('Funds::of handles zero amounts', function (): void {
    $money = Funds::of(0);

    expect($money->isZero())->toBeTrue();
});

it('Funds::of handles string amounts', function (): void {
    $money = Funds::of('25000');

    expect($money->getAmount())->toBe('25000');
});

it('can create USD funds using in() method', function (): void {
    $usd = Funds::in(SupportedCurrency::USD)->of(50000);

    expect($usd->getCurrency()->getCode())->toBe('USD');
});

it('can format Money using Funds::format with JMD', function (): void {
    $money = Funds::of(150050);

    $formatted = Funds::format($money);

    expect($formatted)->toContain('1,500.50')
        ->and($formatted)->toContain('$');
});

it('can format Money using Funds::format with USD', function (): void {
    $money = Funds::in(SupportedCurrency::USD)->of(250075);

    $formatted = Funds::format($money);

    expect($formatted)->toContain('2,500.75')
        ->and($formatted)->toContain('$');
});

it('Money objects support all native operations', function (): void {
    $money1 = Funds::of(10000);
    $money2 = Funds::of(20000);
    $money3 = Funds::of(10000);

    expect($money1->equals($money3))->toBeTrue()
        ->and($money1->equals($money2))->toBeFalse()
        ->and($money1->lessThan($money2))->toBeTrue()
        ->and($money2->greaterThan($money1))->toBeTrue();
});

it('Money objects can be added', function (): void {
    $money1 = Funds::of(10000);
    $money2 = Funds::of(5000);

    $result = $money1->add($money2);

    expect($result)->toBeInstanceOf(Money::class)
        ->and($result->getAmount())->toBe('15000');
});

it('Money objects can be subtracted', function (): void {
    $money1 = Funds::of(10000);
    $money2 = Funds::of(3000);

    $result = $money1->subtract($money2);

    expect($result)->toBeInstanceOf(Money::class)
        ->and($result->getAmount())->toBe('7000');
});

it('Money objects can be multiplied', function (): void {
    $money = Funds::of(10000);

    $result = $money->multiply(2);

    expect($result)->toBeInstanceOf(Money::class)
        ->and($result->getAmount())->toBe('20000');
});

it('Money objects can be divided', function (): void {
    $money = Funds::of(10000);

    $result = $money->divide(2);

    expect($result)->toBeInstanceOf(Money::class)
        ->and($result->getAmount())->toBe('5000');
});

it('can allocate funds by ratios for loan distributions', function (): void {
    // Borrower repays 100,000 JMD to be distributed among 3 lenders
    // who invested in 4:3:2 ratio (40k, 30k, 20k out of 90k total)
    $repayment = Funds::of(100000);
    $investmentRatios = [40000, 30000, 20000];

    $allocations = Funds::allocateByRatio($repayment, $investmentRatios);

    expect($allocations)->toHaveCount(3)
        ->and($allocations[0])->toBeInstanceOf(Money::class)
        ->and($allocations[0]->getAmount())->toBe('44445') // Money lib rounds remainder to first
        ->and($allocations[1]->getAmount())->toBe('33333')
        ->and($allocations[2]->getAmount())->toBe('22222');
});

it('can allocate funds by percentages for interest distribution', function (): void {
    // Distribute 10,000 JMD interest among lenders with 40%, 35%, 25% ownership
    $interest = Funds::of(10000);
    $percentages = [40, 35, 25];

    $allocations = Funds::allocateByPercentage($interest, $percentages);

    expect($allocations)->toHaveCount(3)
        ->and($allocations[0])->toBeInstanceOf(Money::class)
        ->and($allocations[0]->getAmount())->toBe('4000')
        ->and($allocations[1]->getAmount())->toBe('3500')
        ->and($allocations[2]->getAmount())->toBe('2500');
});

it('allocation by ratio handles rounding correctly', function (): void {
    $amount = Funds::of(10000);
    $ratios = [3, 3, 3];

    $allocations = Funds::allocateByRatio($amount, $ratios);

    $total = array_reduce($allocations, fn ($carry, $money) => $carry->add($money), Funds::of(0));

    expect($total->equals($amount))->toBeTrue();
});

it('can calculate allocation ratios from Money amounts', function (): void {
    $investments = [Funds::of(40000), Funds::of(35000), Funds::of(25000)];

    $ratios = Funds::calculateRatios($investments);

    expect($ratios)->toBe([40000, 35000, 25000]);
});

it('handles single allocation correctly', function (): void {
    $amount = Funds::of(100000);
    $ratios = [100];

    $allocations = Funds::allocateByRatio($amount, $ratios);

    expect($allocations)->toHaveCount(1)
        ->and($allocations[0]->equals($amount))->toBeTrue();
});

it('handles zero allocation correctly', function (): void {
    $amount = Funds::of(0);
    $ratios = [40, 30, 30];

    $allocations = Funds::allocateByRatio($amount, $ratios);

    expect($allocations)->toHaveCount(3)
        ->and($allocations[0]->isZero())->toBeTrue()
        ->and($allocations[1]->isZero())->toBeTrue()
        ->and($allocations[2]->isZero())->toBeTrue();
});

it('allocation by percentage validates total is 100', function (): void {
    $amount = Funds::of(10000);
    $invalidPercentages = [40, 40, 30];

    expect(fn (): array => Funds::allocateByPercentage($amount, $invalidPercentages))
        ->toThrow(InvalidArgumentException::class, 'Percentages must total 100');
});

it('can allocate with many participants for diverse loan portfolios', function (): void {
    $repayment = Funds::of(1000000);
    $ratios = [100000, 150000, 75000, 200000, 50000, 125000, 175000, 25000, 50000, 50000];

    $allocations = Funds::allocateByRatio($repayment, $ratios);

    expect($allocations)->toHaveCount(10);

    $total = array_reduce($allocations, fn ($carry, $money) => $carry->add($money), Funds::of(0));

    expect($total->equals($repayment))->toBeTrue();
});

it('can format after Money operations', function (): void {
    $money = Funds::of(10000);
    $result = $money->multiply(2);

    $formatted = Funds::format($result);

    expect($formatted)->toContain('200.00');
});

it('supports native Money method chaining', function (): void {
    $result = Funds::of(50000)
        ->add(Funds::of(25000))
        ->subtract(Funds::of(10000));

    expect($result->getAmount())->toBe('65000');
});

it('can work with USD allocations for international loans', function (): void {
    $usdLoan = Funds::in(SupportedCurrency::USD)->of(100000);
    $ratios = [60, 40];

    $allocations = Funds::allocateByRatio($usdLoan, $ratios);

    expect($allocations)->toHaveCount(2)
        ->and($allocations[0]->getCurrency()->getCode())->toBe('USD')
        ->and($allocations[1]->getCurrency()->getCode())->toBe('USD')
        ->and($allocations[0]->getAmount())->toBe('60000')
        ->and($allocations[1]->getAmount())->toBe('40000');
});

it('can format Money with unsupported currency using default locale', function (): void {
    $money = new Money('50000', new Currency('EUR'));

    $formatted = Funds::format($money);

    expect($formatted)->toContain('500.00');
});

<?php

declare(strict_types=1);

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Money\Currency;
use Money\Money;

it('casts database value to Money object', function (): void {
    $cast = new MoneyCast();
    $model = new class extends Model
    {
        use Illuminate\Database\Eloquent\Factories\HasFactory;
    };

    $money = $cast->get($model, 'amount', '10000', []);

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->getAmount())->toBe('10000')
        ->and($money->getCurrency()->getCode())->toBe('JMD');
});

it('casts Money object to database value', function (): void {
    $cast = new MoneyCast();
    $model = new class extends Model
    {
        use Illuminate\Database\Eloquent\Factories\HasFactory;
    };
    $money = new Money('10000', new Currency('JMD'));

    $value = $cast->set($model, 'amount', $money, []);

    expect($value)->toBe('10000');
});

it('handles null values when getting', function (): void {
    $cast = new MoneyCast();
    $model = new class extends Model
    {
        use Illuminate\Database\Eloquent\Factories\HasFactory;
    };

    $money = $cast->get($model, 'amount', null, []);

    expect($money)->toBeNull();
});

it('handles null values when setting', function (): void {
    $cast = new MoneyCast();
    $model = new class extends Model
    {
        use Illuminate\Database\Eloquent\Factories\HasFactory;
    };

    $value = $cast->set($model, 'amount', null, []);

    expect($value)->toBeNull();
});

it('handles zero amounts', function (): void {
    $cast = new MoneyCast();
    $model = new class extends Model
    {
        use Illuminate\Database\Eloquent\Factories\HasFactory;
    };

    $money = $cast->get($model, 'amount', '0', []);

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->getAmount())->toBe('0')
        ->and($money->isZero())->toBeTrue();
});

it('handles large amounts', function (): void {
    $cast = new MoneyCast();
    $model = new class extends Model
    {
        use Illuminate\Database\Eloquent\Factories\HasFactory;
    };

    $money = $cast->get($model, 'amount', '999999999', []);

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->getAmount())->toBe('999999999');
});

it('converts integer to string when setting', function (): void {
    $cast = new MoneyCast();
    $model = new class extends Model
    {
        use Illuminate\Database\Eloquent\Factories\HasFactory;
    };
    $money = new Money(10000, new Currency('JMD'));

    $value = $cast->set($model, 'amount', $money, []);

    expect($value)->toBe('10000');
});

it('throws exception when setting non-Money value', function (): void {
    $cast = new MoneyCast();
    $model = new class extends Model
    {
        use Illuminate\Database\Eloquent\Factories\HasFactory;
    };

    expect(fn (): ?string => $cast->set($model, 'amount', 'invalid', []))
        ->toThrow(InvalidArgumentException::class, 'Value must be an instance of Money');
});

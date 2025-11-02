<?php

declare(strict_types=1);

namespace App\Enums;

enum SupportedCurrency: string
{
    case JMD = 'JMD';
    case USD = 'USD';

    public function label(): string
    {
        return match ($this) {
            self::JMD => 'Jamaican Dollar',
            self::USD => 'US Dollar',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::JMD => '$',
            self::USD => '$',
        };
    }
}

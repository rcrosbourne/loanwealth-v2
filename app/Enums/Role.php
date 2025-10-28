<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case BORROWER = 'borrower';
    case LENDER = 'lender';
    case BACK_OFFICE = 'back_office';
    case SUPER_ADMIN = 'super_admin';

    /**
     * Get all role values as an array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    /**
     * Get all role names as an array
     *
     * @return array<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $case): string => $case->name, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::BORROWER => 'Borrower',
            self::LENDER => 'Lender',
            self::BACK_OFFICE => 'Back Office',
            self::SUPER_ADMIN => 'Super Admin',
        };
    }
}

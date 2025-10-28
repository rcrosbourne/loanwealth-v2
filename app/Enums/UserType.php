<?php

declare(strict_types=1);

namespace App\Enums;

enum UserType: string
{
    case BORROWER = 'borrower';
    case LENDER = 'lender';
    case BACK_OFFICE = 'back_office';
    case SUPER_ADMIN = 'super_admin';

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

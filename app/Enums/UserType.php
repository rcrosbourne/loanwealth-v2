<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * UserType represents the core identity/account type of a user.
 *
 * This enum defines WHO the user is in the system - their fundamental account category.
 * User types are typically assigned during registration and rarely change.
 *
 * This is distinct from Role (permission-based) which determines WHAT the user can DO.
 * A user has ONE UserType but can have MULTIPLE Roles.
 *
 * Examples:
 * - A BORROWER can request loans, view their loans, upload documents
 * - A LENDER can invest in loans, manage their wallet, view portfolios
 * - A BACK_OFFICE staff member can review/approve users and loans
 * - A SUPER_ADMIN can manage the entire system
 *
 * @see Role For permission-based role assignment
 */
enum UserType: string
{
    case Borrower = 'borrower';
    case Lender = 'lender';
    case BackOffice = 'back_office';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::Borrower => 'Borrower',
            self::Lender => 'Lender',
            self::BackOffice => 'Back Office',
            self::SuperAdmin => 'Super Admin',
        };
    }
}

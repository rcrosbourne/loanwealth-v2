<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Role represents a permission-based grouping using Spatie Laravel Permission.
 *
 * This enum defines WHAT a user can DO in the system - their assigned permissions.
 * Roles are used for authorization and can be assigned/revoked dynamically.
 *
 * This is distinct from UserType which represents WHO the user IS (their core identity).
 * A user can have MULTIPLE Roles but only ONE UserType.
 *
 * Examples:
 * - A user with BORROWER role can: request loans, view loans, manage profile, upload documents
 * - A user with LENDER role can: manage wallet, bid on loans, view portfolio, manage auto-invest
 * - A user with BACK_OFFICE role can: approve users, block users, review loans, view reports
 * - A user with SUPER_ADMIN role has ALL permissions across the system
 *
 * Use Cases:
 * - A user might have both BORROWER and LENDER roles if they both borrow and lend
 * - Temporary role assignment (e.g., giving BACK_OFFICE role to a LENDER for support purposes)
 * - Role-based UI/feature access control
 *
 * @see UserType For core user identity/account type
 * @see Permission For individual permissions assigned to roles
 */
enum Role: string
{
    case Borrower = 'borrower';
    case Lender = 'lender';
    case BackOffice = 'back_office';
    case SuperAdmin = 'super_admin';

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
            self::Borrower => 'Borrower',
            self::Lender => 'Lender',
            self::BackOffice => 'Back Office',
            self::SuperAdmin => 'Super Admin',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    // Borrower Permissions
    case BORROWER_LOANS_REQUEST = 'borrower.loans.request';
    case BORROWER_LOANS_VIEW = 'borrower.loans.view';
    case BORROWER_PROFILE_MANAGE = 'borrower.profile.manage';
    case BORROWER_DOCUMENTS_UPLOAD = 'borrower.documents.upload';

    // Lender Permissions
    case LENDER_WALLET_MANAGE = 'lender.wallet.manage';
    case LENDER_LOANS_BID = 'lender.loans.bid';
    case LENDER_PORTFOLIO_VIEW = 'lender.portfolio.view';
    case LENDER_AUTOINVEST_MANAGE = 'lender.autoinvest.manage';

    // Back Office Permissions
    case BACKOFFICE_USERS_APPROVE = 'backoffice.users.approve';
    case BACKOFFICE_USERS_BLOCK = 'backoffice.users.block';
    case BACKOFFICE_LOANS_REVIEW = 'backoffice.loans.review';
    case BACKOFFICE_REPORTS_VIEW = 'backoffice.reports.view';

    // Admin Permissions
    case ADMIN_USERS_DELETE = 'admin.users.delete';
    case ADMIN_SYSTEM_CONFIGURE = 'admin.system.configure';
    case ADMIN_REPORTS_FULL = 'admin.reports.full';

    /**
     * Get all permission values as an array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    /**
     * Get permissions for a specific role
     *
     * @return array<string>
     */
    public static function forRole(Role $role): array
    {
        return match ($role) {
            Role::BORROWER => [
                self::BORROWER_LOANS_REQUEST->value,
                self::BORROWER_LOANS_VIEW->value,
                self::BORROWER_PROFILE_MANAGE->value,
                self::BORROWER_DOCUMENTS_UPLOAD->value,
            ],
            Role::LENDER => [
                self::LENDER_WALLET_MANAGE->value,
                self::LENDER_LOANS_BID->value,
                self::LENDER_PORTFOLIO_VIEW->value,
                self::LENDER_AUTOINVEST_MANAGE->value,
            ],
            Role::BACK_OFFICE => [
                self::BACKOFFICE_USERS_APPROVE->value,
                self::BACKOFFICE_USERS_BLOCK->value,
                self::BACKOFFICE_LOANS_REVIEW->value,
                self::BACKOFFICE_REPORTS_VIEW->value,
            ],
            Role::SUPER_ADMIN => self::values(), // Admin gets all permissions
        };
    }

    /**
     * Get the group/prefix of this permission
     */
    public function group(): string
    {
        return explode('.', $this->value)[0];
    }

    /**
     * Get the action part of this permission
     */
    public function action(): string
    {
        $parts = explode('.', $this->value);

        return implode('.', array_slice($parts, 1));
    }
}

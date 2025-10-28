<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    // Borrower Permissions
    case BorrowerLoansRequest = 'borrower.loans.request';
    case BorrowerLoansView = 'borrower.loans.view';
    case BorrowerProfileManage = 'borrower.profile.manage';
    case BorrowerDocumentsUpload = 'borrower.documents.upload';

    // Lender Permissions
    case LenderWalletManage = 'lender.wallet.manage';
    case LenderLoansBid = 'lender.loans.bid';
    case LenderPortfolioView = 'lender.portfolio.view';
    case LenderAutoinvestManage = 'lender.autoinvest.manage';

    // Back Office Permissions
    case BackofficeUsersApprove = 'backoffice.users.approve';
    case BackofficeUsersBlock = 'backoffice.users.block';
    case BackofficeLoansReview = 'backoffice.loans.review';
    case BackofficeReportsView = 'backoffice.reports.view';

    // Admin Permissions
    case AdminUsersDelete = 'admin.users.delete';
    case AdminSystemConfigure = 'admin.system.configure';
    case AdminReportsFull = 'admin.reports.full';

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
            Role::Borrower => [
                self::BorrowerLoansRequest->value,
                self::BorrowerLoansView->value,
                self::BorrowerProfileManage->value,
                self::BorrowerDocumentsUpload->value,
            ],
            Role::Lender => [
                self::LenderWalletManage->value,
                self::LenderLoansBid->value,
                self::LenderPortfolioView->value,
                self::LenderAutoinvestManage->value,
            ],
            Role::BackOffice => [
                self::BackofficeUsersApprove->value,
                self::BackofficeUsersBlock->value,
                self::BackofficeLoansReview->value,
                self::BackofficeReportsView->value,
            ],
            Role::SuperAdmin => self::values(), // Admin gets all permissions
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

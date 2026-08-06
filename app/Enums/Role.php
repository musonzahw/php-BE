<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin   = 'super_admin';
    case Admin        = 'admin';
    case FinanceAdmin = 'finance_admin';
    case HrAdmin      = 'hr_admin';
    case Parent       = 'parent';
    case Student      = 'student';
    case Teacher      = 'teacher';

    /**
     * Roles that a regular admin is allowed to delete.
     */
    public static function adminDeletable(): array
    {
        return [
            self::Parent->value,
            self::Student->value,
        ];
    }
}

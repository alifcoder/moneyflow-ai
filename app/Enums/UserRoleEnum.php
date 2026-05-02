<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRoleEnum: string
{
    case USER = 'user';
    case SUPER_ADMIN = 'super_admin';
}

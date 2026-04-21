<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('reconciliation_admin')) {
            return false;
        }

        return $user->hasAnyRole(['customer_service', 'super_admin']);
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user->hasRole('reconciliation_admin')) {
            return false;
        }

        return $user->hasAnyRole(['customer_service', 'super_admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasRole('super_admin');
    }
}

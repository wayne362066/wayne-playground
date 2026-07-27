<?php

namespace App\Modules\Wishes\Policies;

use App\Core\Access\AuthorizationService;
use App\Models\User;
use App\Modules\Wishes\Models\Wish;

class WishPolicy
{
    public function __construct(
        private readonly AuthorizationService $authorization,
    ) {}

    public function viewAny(?User $user): bool
    {
        return $this->authorization->allows($user, 'wishes.view');
    }

    public function view(?User $user, Wish $wish): bool
    {
        return $this->authorization->allows($user, 'wishes.view');
    }

    public function create(?User $user): bool
    {
        return $this->authorization->allows($user, 'wishes.create');
    }

    public function update(?User $user, Wish $wish): bool
    {
        return $this->authorization->allows($user, 'wishes.update');
    }

    public function changeStatus(?User $user, Wish $wish): bool
    {
        return $this->authorization->allows($user, 'wishes.status.update');
    }

    public function hide(?User $user, Wish $wish): bool
    {
        return $this->authorization->allows($user, 'wishes.moderate');
    }

    public function delete(?User $user, Wish $wish): bool
    {
        return $this->authorization->allows($user, 'wishes.archive');
    }

    public function restore(?User $user, Wish $wish): bool
    {
        return $this->authorization->allows($user, 'wishes.restore');
    }

    public function viewManagement(?User $user): bool
    {
        return $this->authorization->allows($user, 'wishes.manage.view');
    }

    public function forceDelete(?User $user, Wish $wish): bool
    {
        return false;
    }
}

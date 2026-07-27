<?php

namespace App\Modules\Wishes\Policies;

use App\Models\User;
use App\Modules\Wishes\Models\Wish;

class WishPolicy
{
    public function viewAny(?User $user): bool
    {
        return $this->isOpen();
    }

    public function view(?User $user, Wish $wish): bool
    {
        return $this->isOpen();
    }

    public function create(?User $user): bool
    {
        return $this->isOpen();
    }

    public function update(?User $user, Wish $wish): bool
    {
        return $this->isOpen();
    }

    public function changeStatus(?User $user, Wish $wish): bool
    {
        return $this->isOpen();
    }

    public function hide(?User $user, Wish $wish): bool
    {
        return $this->isOpen();
    }

    public function delete(?User $user, Wish $wish): bool
    {
        return $this->isOpen();
    }

    public function restore(?User $user, Wish $wish): bool
    {
        return $this->isOpen();
    }

    public function viewManagement(?User $user): bool
    {
        return $this->isOpen();
    }

    public function forceDelete(?User $user, Wish $wish): bool
    {
        return false;
    }

    private function isOpen(): bool
    {
        return config('wishes.access_mode') === 'open';
    }
}

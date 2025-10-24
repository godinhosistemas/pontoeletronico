<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TimeEntryFile;

class TimeEntryFilePolicy
{
    /**
     * Determine if the user can view the file.
     */
    public function view(User $user, TimeEntryFile $file): bool
    {
        // Usuário deve ser do mesmo tenant
        return $user->tenant_id === $file->tenant_id;
    }

    /**
     * Determine if the user can delete the file.
     */
    public function delete(User $user, TimeEntryFile $file): bool
    {
        // Apenas admin do mesmo tenant pode deletar
        return $user->tenant_id === $file->tenant_id && $user->isAdmin();
    }
}

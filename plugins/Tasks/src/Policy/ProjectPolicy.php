<?php
declare(strict_types=1);

namespace Tasks\Policy;

use App\Model\Entity\User;
use Tasks\Model\Entity\Project;

/**
 * Project policy
 */
class ProjectPolicy
{
    /**
     * Check if $user can create Project
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Tasks\Model\Entity\Project $project
     * @return bool
     */
    public function canCreate(User $user, Project $project): bool
    {
        return true;
    }

    /**
     * Check if $user can update Project
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Tasks\Model\Entity\Project $project
     * @return bool
     */
    public function canEdit(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    /**
     * Check if $user can delete Project
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Tasks\Model\Entity\Project $project
     * @return bool
     */
    public function canArchive(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    /**
     * Check if $user can delete Project
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Tasks\Model\Entity\Project $project
     * @return bool
     */
    public function canDelete(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    /**
     * Check if $user can view Project
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Tasks\Model\Entity\Project $project
     * @return bool
     */
    public function canView(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }
}

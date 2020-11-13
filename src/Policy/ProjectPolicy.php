<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Project;
use Authorization\IdentityInterface;

/**
 * Project policy
 */
class ProjectPolicy
{
    /**
     * Check if $user can create Project
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\Project $project
     * @return bool
     */
    public function canCreate(IdentityInterface $user, Project $project)
    {
        return true;
    }

    /**
     * Check if $user can update Project
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\Project $project
     * @return bool
     */
    public function canUpdate(IdentityInterface $user, Project $project)
    {
        return $user->id === $project->user_id;
    }

    /**
     * Check if $user can delete Project
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\Project $project
     * @return bool
     */
    public function canDelete(IdentityInterface $user, Project $project)
    {
        return $user->id === $project->user_id;
    }

    /**
     * Check if $user can view Project
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\Project $project
     * @return bool
     */
    public function canView(IdentityInterface $user, Project $project)
    {
        return $user->id === $project->user_id;
    }
}

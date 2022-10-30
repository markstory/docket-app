<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Task;
use Authorization\IdentityInterface;
use RuntimeException;

/**
 * Task policy
 */
class TaskPolicy
{
    /**
     * Check if $user can create Task
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \App\Model\Entity\Task $task
     * @return bool
     */
    public function canAdd(IdentityInterface $user, Task $task)
    {
        return true;
    }

    /**
     * General check for project ownership
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \App\Model\Entity\Task $task
     * @return bool
     */
    public function ownsProject(IdentityInterface $user, Task $task)
    {
        if (empty($task->project)) {
            throw new RuntimeException('Cannot check todo item permission, no project is set.');
        }

        return $user->id === $task->project->user_id;
    }

    /**
     * Check if $user can edit Task
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \App\Model\Entity\Task $task
     * @return bool
     */
    public function canEdit(IdentityInterface $user, Task $task)
    {
        return $this->ownsProject($user, $task);
    }

    /**
     * Check if $user can delete Task
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \App\Model\Entity\Task $task
     * @return bool
     */
    public function canDelete(IdentityInterface $user, Task $task)
    {
        return $this->ownsProject($user, $task) && $task->deleted == null;
    }

    /**
     * Check if $user can undelete Task
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \App\Model\Entity\Task $task
     * @return bool
     */
    public function canUndelete(IdentityInterface $user, Task $task)
    {
        return $this->ownsProject($user, $task) && $task->deleted_at != null;
    }

    /**
     * Check if $user can view Task
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \App\Model\Entity\Task $task
     * @return bool
     */
    public function canView(IdentityInterface $user, Task $task)
    {
        return $this->ownsProject($user, $task);
    }
}

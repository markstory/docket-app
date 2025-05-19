<?php
declare(strict_types=1);

namespace Tasks\Policy;

use App\Model\Entity\User;
use RuntimeException;
use Tasks\Model\Entity\Task;

/**
 * Task policy
 */
class TaskPolicy
{
    /**
     * Check if $user can create Task
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Tasks\Model\Entity\Task $task
     * @return bool
     */
    public function canAdd(User $user, Task $task): bool
    {
        return true;
    }

    /**
     * General check for project ownership
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Tasks\Model\Entity\Task $task
     * @return bool
     */
    public function ownsProject(User $user, Task $task): bool
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
     * @param \Tasks\Model\Entity\Task $task
     * @return bool
     */
    public function canEdit(User $user, Task $task): bool
    {
        return $this->ownsProject($user, $task);
    }

    /**
     * Check if $user can delete Task
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Tasks\Model\Entity\Task $task
     * @return bool
     */
    public function canDelete(User $user, Task $task): bool
    {
        return $this->ownsProject($user, $task) && $task->deleted_at == null;
    }

    /**
     * Check if $user can undelete Task
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Tasks\Model\Entity\Task $task
     * @return bool
     */
    public function canUndelete(User $user, Task $task): bool
    {
        return $this->ownsProject($user, $task) && $task->deleted_at != null;
    }

    /**
     * Check if $user can view Task
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Tasks\Model\Entity\Task $task
     * @return bool
     */
    public function canView(User $user, Task $task): bool
    {
        return $this->ownsProject($user, $task);
    }
}

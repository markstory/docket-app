<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\TodoItem;
use Authorization\IdentityInterface;
use RuntimeException;

/**
 * TodoItem policy
 */
class TodoItemPolicy
{
    /**
     * Check if $user can create TodoItem
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\TodoItem $todoItem
     * @return bool
     */
    public function canAdd(IdentityInterface $user, TodoItem $todoItem)
    {
        return true;
    }

    /**
     * Check if $user can edit TodoItem
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\TodoItem $todoItem
     * @return bool
     */
    public function canEdit(IdentityInterface $user, TodoItem $todoItem)
    {
        if (empty($todoItem->project)) {
            throw new RuntimeException('Cannot check todo item permission, no project is set.');
        }
        return $user->id === $todoItem->project->user_id;
    }

    /**
     * Check if $user can delete TodoItem
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\TodoItem $todoItem
     * @return bool
     */
    public function canDelete(IdentityInterface $user, TodoItem $todoItem)
    {
        if (empty($todoItem->project)) {
            throw new RuntimeException('Cannot check todo item permission, no project is set.');
        }
        return $user->id === $todoItem->project->user_id;
    }

    /**
     * Check if $user can view TodoItem
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\TodoItem $todoItem
     * @return bool
     */
    public function canView(IdentityInterface $user, TodoItem $todoItem)
    {
        if (empty($todoItem->project)) {
            throw new RuntimeException('Cannot check todo item permission, no project is set.');
        }
        return $user->id === $todoItem->project->user_id;
    }
}

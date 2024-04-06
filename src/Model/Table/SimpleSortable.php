<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;

/**
 * Implements single scope sorting
 */
class SimpleSortable
{
    /**
     * @var \Cake\ORM\Table
     */
    protected $table;

    /**
     * @var array
     */
    protected $config;

    public function __construct(Table $table, array $config)
    {
        $this->table = $table;
        $this->config = $config + [
            'field' => 'ranking',
            'orderBy' => ['ranking', 'title'],
        ];
    }

    public function move(EntityInterface $record, $newIndex, array $scopeConditions)
    {
        $field = $this->config['field'];

        // We have to assume that all lists are not continuous ranges, and that the order
        // fields have holes in them. The holes can be introduced when items are
        // deleted/completed. Try to find the item at the target offset
        $query = $this->table->find()
            ->where($scopeConditions)
            ->offset($newIndex);

        foreach ($this->config['orderBy'] as $order) {
            $query->orderByAsc($order);
        }
        $currentTask = $query->first();

        // If we found a record at the current offset
        // use its order property for our update
        $targetOffset = $newIndex;
        if ($currentTask instanceof EntityInterface) {
            $targetOffset = $currentTask->get($field);
        }

        $query = $this->table->updateQuery()->where($scopeConditions);

        $current = $record->get($field);
        $record->set($field, $targetOffset);
        $difference = $current - $record->get($field);

        if ($difference >= 0) {
            // Move other items down, as the current item is going up
            // or is being moved from another group.
            $query
                ->set([$field => $query->newExpr("{$field} + 1")])
                ->where(function ($exp) use ($current, $field, $targetOffset) {
                    return $exp->between($field, $targetOffset, $current);
                });
        } elseif ($difference < 0) {
            // Move other items up, as current item is going down
            $query
                ->set([$field => $query->newExpr("{$field} - 1")])
                ->where(function ($exp) use ($current, $field, $targetOffset) {
                    return $exp->between($field, $current, $targetOffset);
                });
        }
        $this->table->getConnection()->transactional(function () use ($record, $query) {
            if ($query->clause('set')) {
                $query->execute();
            }
            $this->table->saveOrFail($record);
        });
    }
}

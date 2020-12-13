<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\TodoItem;
use App\Model\Entity\TodoSubtask;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;

/**
 * TodoSubtasks Controller
 *
 * @property \App\Model\Table\TodoSubtasksTable $TodoSubtasks
 * @method \App\Model\Entity\TodoSubtask[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TodoSubtasksController extends AppController
{
    public function initialize(): void 
    {
        parent::initialize();
        $this->loadModel('TodoItems');
        $this->loadModel('TodoSubtasks');
    }

    protected function getTodoItem(string $id): TodoItem
    {
        $todoItem = $this->TodoItems->get($id, ['contain' => ['Projects']]);
        $this->Authorization->authorize($todoItem, 'edit');
        return $todoItem;
    }

    protected function getTodoSubtask(string $todoItemId, string $id): TodoSubtask
    {
        $item = $this->getTodoItem($todoItemId);

        return $this->TodoSubtasks
            ->find()
            ->where(['TodoSubtasks.id' => $id, 'TodoSubtasks.todo_item_id' => $item->id])
            ->firstOrFail();
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add(string $todoItemId = null)
    {
        $this->request->allowMethod(['post']);
        $item = $this->getTodoItem($todoItemId);

        $todoSubtask = $this->TodoSubtasks->newEntity($this->request->getData());
        $todoSubtask->todo_item_id = $item->id;

        $this->TodoSubtasks->saveOrFail($todoSubtask);

        return $this->redirect($this->referer([
            'controller' => 'TodoItems',
            'action' => 'view',
            'id' => $todoItemId
        ]));
    }

    /**
     * Toggle a subtask as complete.
     *
     * @param string|null $todoId Todo Item id.
     * @param string|null $id Subtask id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function toggle($todoItemId, $id = null)
    {
        $this->request->allowMethod(['post']);
        $subtask = $this->getTodoSubtask($todoItemId, $id);

        $subtask->toggle();
        $this->TodoSubtasks->saveOrFail($subtask);

        return $this->redirect($this->referer([
            'controller' => 'TodoItems',
            'action' => 'view',
            'id' => $todoItemId
        ]));
    }

    /**
     * Edit method
     *
     * @param string $todoItemId Todo id.
     * @param string $id Todo Subtask id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(string $todoItemId, string $id)
    {
        $this->request->allowMethod(['post', 'put', 'patch']);

        $subtask = $this->getTodoSubtask($todoItemId, $id);
        $subtask = $this->TodoSubtasks->patchEntity($subtask, $this->request->getData());
        if ($this->TodoSubtasks->save($subtask)) {
            return $this->response->withStatus(200);
        }
        return $this->validationErrorResponse($subtask->getErrors());
    }

    /**
     * Delete method
     *
     * @param string|null $todoItemId Todo id.
     * @param string|null $id Todo Subtask id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(string $todoItemId, string $id)
    {
        $this->request->allowMethod(['post', 'delete']);
        $subtask = $this->getTodoSubtask($todoItemId, $id);

        if ($this->TodoSubtasks->delete($subtask)) {
            $this->Flash->success(__('The todo subtask has been deleted.'));
        } else {
            $this->Flash->error(__('The todo subtask could not be deleted. Please, try again.'));
        }

        return $this->redirect([
            'controller' => 'TodoItems',
            'action' => 'view',
            'id' => $todoItemId,
        ]);
    }

    public function reorder(string $todoItemId = null)
    {
        $todoItem = $this->getTodoItem($todoItemId);

        $itemIds = $this->request->getData('items');
        if (!is_array($itemIds)) {
            throw new BadRequestException('Invalid subtask list.');
        }
        $itemIds = array_values($itemIds);

        $query = $this->TodoSubtasks
            ->find()
            ->where([
                'TodoSubtasks.todo_item_id' => $todoItem->id,
                'TodoSubtasks.id IN' => $itemIds
            ]);

        $items = $query->toArray();
        if (count($items) != count($itemIds)) {
            throw new NotFoundException('Some of the requested items could not be found.');
        }
        $sorted = [];
        foreach ($items as $item) {
            $index = array_search($item->id, $itemIds);
            $sorted[$index] = $item;
        }
        ksort($sorted);
        $this->TodoSubtasks->reorder($sorted);

        return $this->redirect($this->referer([
            'controller' => 'TodoItems',
            'action' => 'view',
            'id' => $todoItem->id
        ]));
    }
}

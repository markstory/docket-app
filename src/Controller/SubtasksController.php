<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Task;
use Cake\View\JsonView;
use InvalidArgumentException;

/**
 * Subtasks Controller
 *
 * @property \App\Model\Table\TasksTable $Tasks
 * @property \App\Model\Table\SubtasksTable $Subtasks
 */
class SubtasksController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('Tasks');
        $this->loadModel('Subtasks');
    }

    protected function getTask(string $id): Task
    {
        $task = $this->Tasks->get($id, ['contain' => ['Projects']]);
        $this->Authorization->authorize($task, 'edit');

        return $task;
    }

    protected function getSubtask(string $taskId, string $id)
    {
        $item = $this->getTask($taskId);

        return $this->Subtasks
            ->find()
            ->where(['Subtasks.id' => $id, 'Subtasks.task_id' => $item->id])
            ->firstOrFail();
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add(string $taskId)
    {
        $this->request->allowMethod(['post']);
        $item = $this->getTask($taskId);

        $todoSubtask = $this->Subtasks->newEntity($this->request->getData());
        $todoSubtask->task_id = $item->id;
        $todoSubtask->ranking = $this->Subtasks->getNextRanking($item->id);

        $this->Subtasks->saveOrFail($todoSubtask);
        $this->Flash->success(__('Subtask updated.'));

        $this->set('subtask', $todoSubtask);
        $this->viewBuilder()
            ->setClassName(JsonView::class)
            ->setOption('serialize', ['subtask']);
    }

    /**
     * Toggle a subtask as complete.
     *
     * @param string $taskId Todo Item id.
     * @param string $id Subtask id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function toggle(string $taskId, string $id)
    {
        $this->request->allowMethod(['post']);
        $subtask = $this->getSubtask($taskId, $id);

        $subtask->toggle();
        $this->Subtasks->saveOrFail($subtask);

        return $this->redirect($this->referer([
            'controller' => 'Tasks',
            'action' => 'view',
            'id' => $taskId,
        ]));
    }

    /**
     * Edit method
     *
     * @param string $taskId Todo id.
     * @param string $id Todo Subtask id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(string $taskId, string $id)
    {
        $this->request->allowMethod(['post', 'put', 'patch']);

        $subtask = $this->getSubtask($taskId, $id);
        $subtask = $this->Subtasks->patchEntity($subtask, $this->request->getData());
        if (!$this->Subtasks->save($subtask)) {
            return $this->validationErrorResponse($subtask->getErrors());
        }
        $this->Flash->success(__('Subtask updated.'));
        $this->set('subtask', $subtask);
        $this->viewBuilder()
            ->setClassName(JsonView::class)
            ->setOption('serialize', ['subtask']);
    }

    /**
     * Delete method
     *
     * @param string $taskId Todo id.
     * @param string $id Todo Subtask id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(string $taskId, string $id)
    {
        $this->request->allowMethod(['post', 'delete']);
        $subtask = $this->getSubtask($taskId, $id);

        if ($this->Subtasks->delete($subtask)) {
            $this->Flash->success(__('Subtask deleted.'));
        } else {
            $this->Flash->error(__('Subtask could not be deleted. Please, try again.'));
        }

        return $this->redirect([
            'controller' => 'Tasks',
            'action' => 'view',
            'id' => $taskId,
        ]);
    }

    public function move(string $taskId, string $id)
    {
        $this->request->allowMethod(['post']);
        $item = $this->getTask($taskId);
        $this->Authorization->authorize($item, 'edit');
        $subtask = $this->getSubtask($taskId, $id);

        $operation = [
            'ranking' => $this->request->getData('ranking'),
        ];
        try {
            $this->Subtasks->move($subtask, $operation);
            $this->Flash->success(__('Subtask reordered.'));
        } catch (InvalidArgumentException $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect($this->referer([
            '_name' => 'tasks:view',
            'id' => $taskId,
        ]));
    }
}

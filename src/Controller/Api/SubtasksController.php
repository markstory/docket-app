<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use App\Model\Entity\Task;
use App\Model\Table\SubtasksTable;
use App\Model\Table\TasksTable;
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
    public TasksTable $Tasks;
    public SubtasksTable $Subtasks;

    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('Tasks');
        $this->loadModel('Subtasks');
    }

    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    protected function getTask(string $id): Task
    {
        $task = $this->Tasks->get($id, contain: ['Projects']);
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

        $this->set('subtask', $todoSubtask);

        return $this->respond([
            'success' => true,
            'serialize' => ['subtask'],
        ]);
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
        $this->set('subtask', $subtask);

        return $this->respond([
            'success' => true,
            'serialize' => ['subtask'],
        ]);
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
        $serialize = [];
        $success = false;
        if ($this->Subtasks->save($subtask)) {
            $success = true;
            $serialize[] = 'subtask';
            $this->set('subtask', $subtask);
        } else {
            $serialize[] = 'errors';
            $this->set('errors', $this->flattenErrors($subtask->getErrors()));
        }

        return $this->respond([
            'success' => $success,
            'serialize' => $serialize,
            'statusError' => 422,
        ]);
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
        $success = $this->Subtasks->delete($subtask);

        return $this->respond([
            'success' => $success,
            'serialize' => [],
            'statusError' => 422,
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
        $success = false;
        $serialize = [];
        try {
            $this->Subtasks->move($subtask, $operation);
            $success = true;
            $serialize[] = 'subtask';
            $this->set('subtask', $subtask);
        } catch (InvalidArgumentException $e) {
            $this->set('errors', [$e->getMessage()]);
            $serialize[] = 'errors';
        }

        return $this->respond([
            'success' => $success,
            'serialize' => $serialize,
            'statusError' => 422,
        ]);
    }
}

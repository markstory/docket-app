<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenDate;
use InvalidArgumentException;

/**
 * Tasks Controller
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \App\Model\Table\TasksTable $Tasks
 * @method \App\Model\Entity\Task[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TasksController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index(string $view = 'upcoming')
    {
        $identity = $this->request->getAttribute('identity');
        try {
            $start = new FrozenDate($this->request->getQuery('start', 'today'), $identity->timezone);
        } catch (\Exception $e) {
            throw new NotFoundException();
        }

        $query = $this->Tasks
            ->find('incomplete')
            ->contain('Projects');

        $query = $this->Authorization->applyScope($query);
        if ($view === 'today') {
            $query = $query->find('dueToday', ['timezone' => $identity->timezone]);
            // Set view component to use.
            $this->set('component', 'Tasks/Today');
        } else if ($view === 'upcoming') {
            $query = $query->find('upcoming', ['start' => $start]);
        }
        // $overdue = $this->Tasks->find('overdue')->limit(25);
        $tasks = $query->all();

        $this->set(compact('tasks', 'view'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $task = $this->Tasks->newEmptyEntity();

        if ($this->request->is('post')) {
            $task = $this->Tasks->patchEntity($task, $this->request->getData());

            $project = $this->Tasks->Projects->get($task->project_id);
            $this->Authorization->authorize($project, 'edit');
            $user = $this->request->getAttribute('identity');
            $this->Tasks->setNextOrderProperties($user, $task);

            if ($this->Tasks->save($task)) {
                $this->Flash->success(__('The todo item has been saved.'));

                return $this->redirect($this->referer(['_name' => 'tasks:today']));
            }
            // TODO this doesn't look like it will handle validation
            // errors well.
            $this->Flash->error(__('The todo item could not be saved. Please, try again.'));
        }
    }

    /**
     * Complete a todoitem as complete.
     *
     * @param string|null $id Todo Item id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function complete($id = null)
    {
        $task = $this->Tasks->get($id, [
            'contain' => ['Projects'],
        ]);
        $this->Authorization->can($task, 'edit');

        if ($this->request->is(['patch', 'post', 'put'])) {
            $task->complete();
            if (!$this->Tasks->save($task)) {
                $this->Flash->error(__('The todo item could not be saved. Please, try again.'));
            }
        }
        return $this->redirect($this->referer(['_name' => 'tasks:today']));
    }

    /**
     * Complete a todoitem as incomplete.
     *
     * @param string|null $id Todo Item id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function incomplete($id = null)
    {
        $task = $this->Tasks->get($id, [
            'contain' => ['Projects'],
        ]);
        $this->Authorization->can($task, 'edit');

        if ($this->request->is(['patch', 'post', 'put'])) {
            $task->incomplete();
            if (!$this->Tasks->save($task)) {
                $this->Flash->error(__('The todo item could not be saved. Please, try again.'));
            }
        }
        return $this->redirect($this->referer(['_name' => 'tasks:today']));
    }

    public function move(string $id)
    {
        $this->request->allowMethod(['post']);
        $task = $this->Tasks->get($id, ['contain' => ['Projects']]);
        $this->Authorization->authorize($task, 'edit');
        $operation = [
            'child_order' => $this->request->getData('child_order'),
            'day_order' => $this->request->getData('day_order'),
            'due_on' => $this->request->getData('due_on')
        ];
        try {
            $this->Tasks->move($task, $operation);
        } catch (InvalidArgumentException $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect($this->referer(['_name' => 'tasks:today']));
    }

    /**
     * Called as an XHR request from the view page.
     *
     * @param string|null $id Todo Item id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $task = $this->Tasks->get($id, [
            'contain' => ['Labels', 'Projects'],
        ]);
        $this->Authorization->authorize($task);

        $task = $this->Tasks->patchEntity($task, $this->request->getData());
        if ($this->Tasks->save($task)) {
            return $this->response->withStatus(200);
        }
        return $this->validationErrorResponse($task->getErrors());
    }

    /**
     * View method
     *
     * @param string|null $id Todo Item id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $task = $this->Tasks->get($id, [
            'contain' => ['Projects', 'Labels', 'TaskComments', 'Subtasks'],
        ]);
        $this->Authorization->authorize($task);

        $this->set(compact('task'));
        $this->set('referer', $this->getReferer('tasks:upcoming'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Todo Item id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $task = $this->Tasks->get($id, ['contain' => ['Projects']]);
        $this->Authorization->authorize($task);

        if ($this->Tasks->delete($task)) {
            $this->Flash->success(__('The todo item has been deleted.'));
        } else {
            $this->Flash->error(__('The todo item could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}

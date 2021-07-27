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
 * @property \App\Model\Table\CalendarItemsTable $CalendarItems
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
        $this->loadModel('CalendarItems');

        $identity = $this->request->getAttribute('identity');
        try {
            $startVal = $this->request->getQuery('start', 'today');
            if (!is_string($startVal)) {
                throw new BadRequestException(__('Invalid start value provided.'));
            }
            $start = new FrozenDate($startVal, $identity->timezone);
        } catch (\Exception $e) {
            throw new NotFoundException();
        }

        $query = $this->Tasks
            ->find('incomplete')
            ->contain('Projects');

        $query = $this->Authorization->applyScope($query);
        if ($view === 'today') {
            $this->set('component', 'Tasks/Today');

            $query = $query->find('dueToday', ['timezone' => $identity->timezone]);
            $eventsQuery = $this->CalendarItems->find('upcoming', [
                'start' => FrozenDate::parse('today', $identity->timezone),
                'end' => FrozenDate::parse('tomorrow', $identity->timezone),
            ]);
        } elseif ($view === 'upcoming') {
            $end = $start->modify('+28 days');
            $query = $query->find('upcoming', ['start' => $start, 'end' => $end]);
            $eventsQuery = $this->CalendarItems->find('upcoming', [
                'start' => $start,
                'end' => $end,
            ]);
        }
        $tasks = $query->all();
        $calendarItems = [];
        if (isset($eventsQuery)) {
            $calendarItems = $this->Authorization->applyScope($eventsQuery)->all();
        }

        $this->set(compact('tasks', 'view', 'calendarItems'));
        $this->set('start', $start->format('Y-m-d'));
        $this->set('nextStart', isset($end) ? $end->format('Y-m-d') : null);
        $this->set('generation', uniqid());

        // Work around for errors from inline add.
        $session = $this->request->getSession();
        if ($session->check('errors')) {
            $this->set('errors', $session->consume('errors'));
        }
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

            // Ensure the project belongs to the current user.
            $project = $this->Tasks->Projects->get($task->project_id);
            $this->Authorization->authorize($project, 'edit');

            $user = $this->request->getAttribute('identity');
            $this->Tasks->setNextOrderProperties($user, $task);

            if ($this->Tasks->save($task)) {
                $this->Flash->success(__('Task saved.'));

                return $this->redirect($this->referer(['_name' => 'tasks:today']));
            }
            $this->Flash->error(__('The task could not be saved. Please, try again.'));
            $this->request->getSession()->write('errors', $this->flattenErrors($task->getErrors()));

            return $this->redirect($this->referer(['_name' => 'tasks:today']));
        }
    }

    /**
     * Complete a task as complete.
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function complete($id = null)
    {
        $task = $this->Tasks->get($id, [
            'contain' => ['Projects'],
        ]);
        $this->Authorization->authorize($task, 'edit');

        if ($this->request->is(['patch', 'post', 'put'])) {
            $task->complete();
            if ($this->Tasks->save($task)) {
                $this->Flash->success(__('Task complete.'));
            } else {
                $this->Flash->error(__('The task could not be completed. Please, try again.'));
            }
        }

        return $this->redirect($this->referer(['_name' => 'tasks:today']));
    }

    /**
     * Complete a task as incomplete.
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function incomplete($id = null)
    {
        $task = $this->Tasks->get($id, [
            'contain' => ['Projects'],
        ]);
        $this->Authorization->authorize($task, 'edit');

        if ($this->request->is(['patch', 'post', 'put'])) {
            $task->incomplete();
            if ($this->Tasks->save($task)) {
                $this->Flash->error(__('The task could not be updated. Please, try again.'));
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
            'due_on' => $this->request->getData('due_on'),
            'evening' => $this->request->getData('evening') ?? null,
        ];
        if (array_key_exists('section_id', $this->request->getData())) {
            $operation['section_id'] = $this->request->getData('section_id');
        }
        try {
            $this->Tasks->move($task, $operation);
            $this->Flash->success(__('Task reordered.'));
        } catch (InvalidArgumentException $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect($this->referer(['_name' => 'tasks:today']));
    }

    /**
     * Called as an XHR request from the view page.
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $task = $this->Tasks->get($id, [
            'contain' => ['Projects'],
        ]);
        $this->Authorization->authorize($task);
        $task = $this->Tasks->patchEntity($task, $this->request->getData());

        // If the project has changed ensure the new project belongs
        // to the current user.
        if ($task->isDirty('project_id')) {
            $project = $this->Tasks->Projects->get($task->project_id);
            $this->Authorization->authorize($project, 'edit');
            $task->section_id = null;
        }

        if ($this->Tasks->save($task)) {
            $this->Flash->success(__('Task updated.'));

            return $this->response->withStatus(200);
        }

        return $this->validationErrorResponse($task->getErrors());
    }

    /**
     * View method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $task = $this->Tasks->get($id, [
            'contain' => ['Projects', 'Subtasks'],
        ]);
        $this->Authorization->authorize($task);

        $this->set(compact('task'));
        $this->set('referer', $this->getReferer('tasks:today'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $task = $this->Tasks->get($id, ['contain' => ['Projects']]);
        $this->Authorization->authorize($task);

        if ($this->Tasks->delete($task)) {
            $this->Flash->success(__('The task has been deleted.'));
        } else {
            $this->Flash->error(__('The task could not be deleted. Please, try again.'));
        }

        return $this->redirect($this->referer(['_name' => 'tasks:today']));
    }
}

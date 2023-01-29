<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\I18n\FrozenDate;
use Cake\View\JsonView;
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
    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    /**
     * Fetch tasks for a single day
     */
    public function daily(string $date)
    {
        $calendarItems = $this->fetchTable('CalendarItems');

        try {
            if ($date == 'today' || $date == 'tomorrow') {
                $identity = $this->request->getAttribute('identity');
                $date = new FrozenDate($date, $identity->timezone);
            } else {
                $date = new FrozenDate($date);
            }
        } catch (\Exception $e) {
            throw new BadRequestException('Invalid date value provided');
        }
        $overdue = (bool)$this->request->getQuery('overdue', false);

        $query = $this->Tasks
            ->find('incomplete')
            ->find('forDate', ['date' => $date, 'overdue' => $overdue])
            ->contain('Projects');
        $query = $this->Authorization->applyScope($query, 'index');

        $eventsQuery = $calendarItems->find('upcoming', [
            'start' => $date,
            'end' => $date->modify('+1 days'),
        ]);

        $this->set('component', 'Tasks/Daily');
        $this->set('date', $date->format('Y-m-d'));

        $serialize = ['projects', 'tasks', 'calendarItems', 'date'];

        $tasks = $query->all();
        $calendarItems = $this->Authorization
            ->applyScope($eventsQuery, 'index')
            ->all();
        $this->set(compact('tasks', 'calendarItems'));
        $this->set('generation', uniqid());

        // Work around for errors from inline add.
        $session = $this->request->getSession();
        if ($session->check('errors')) {
            $this->set('errors', $session->consume('errors'));
        }

        return $this->respond([
            'success' => true,
            'serialize' => $serialize,
        ]);
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index(string $view = 'upcoming')
    {
        $calendarItemsTable = $this->fetchTable('CalendarItems');

        // Multiple day view
        try {
            $start = new FrozenDate($this->request->getQuery('start', 'today'));
        } catch (\Exception $e) {
            throw new BadRequestException('Invalid date value provided.');
        }
        /** @var \Cake\I18n\FrozenDate $start */
        $end = $start->modify('+28 days');

        $query = $this->Tasks
            ->find('incomplete')
            ->find('upcoming', ['start' => $start, 'end' => $end])
            ->contain('Projects');
        $query = $this->Authorization->applyScope($query);

        $eventsQuery = $calendarItemsTable->find('upcoming', [
            'start' => $start,
            'end' => $end,
        ]);
        $this->set('start', $start->format('Y-m-d'));
        $this->set('nextStart', $end->format('Y-m-d'));
        $this->set('generation', uniqid());
        $this->set('tasks', $query->all());
        $this->set('calendarItems', $this->Authorization->applyScope($eventsQuery)->all());

        // Work around for errors from inline add.
        $session = $this->request->getSession();
        if ($session->check('errors')) {
            $this->set('errors', $session->consume('errors'));
        }

        $serialize = ['projects', 'tasks', 'calendarItems', 'start', 'nextStart'];

        return $this->respond([
            'success' => true,
            'serialize' => $serialize,
        ]);
    }

    /**
     * Fetch tasks that are soft-deleted
     */
    public function deleted()
    {
        $query = $this->Tasks
            ->find('all', ['deleted' => true])
            ->contain('Projects');
        $query = $this->Authorization->applyScope($query, 'index');

        $this->set('tasks', $query->all());
        $this->set('generation', uniqid());
        $this->set('component', 'Tasks/Deleted');

        $serialize = ['projects', 'tasks'];

        return $this->respond([
            'success' => true,
            'serialize' => $serialize,
        ]);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $task = $this->Tasks->newEmptyEntity();
        $success = false;
        $redirect = null;
        $errors = [];
        $serialize = [];

        if ($this->request->is('post')) {
            $task = $this->Tasks->patchEntity($task, $this->request->getData());

            // Ensure the project belongs to the current user.
            $project = $this->Tasks->Projects->get($task->project_id);
            $this->Authorization->authorize($project, 'edit');

            $user = $this->request->getAttribute('identity');
            $this->Tasks->setNextOrderProperties($user, $task);

            $task->project = $project;
            if ($this->Tasks->save($task)) {
                $success = true;
                $redirect = $this->referer(['_name' => 'tasks:today']);

                $serialize[] = 'task';
                $this->set('task', $task);
            } else {
                $redirect = $this->referer(['_name' => 'tasks:today']);

                $serialize[] = 'errors';
                $errors = $this->flattenErrors($task->getErrors());
                $this->set('errors', $errors);
            }
        }

        if ($errors) {
            $this->request->getSession()->write('errors', $errors);
        }

        return $this->respond([
            'success' => $success,
            'flashSuccess' => __('Task saved'),
            'flashError' => __('The task could not be saved. Please try again.'),
            'serialize' => $serialize,
            'redirect' => $redirect,
        ]);
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

        $success = false;
        if ($this->request->is(['patch', 'post', 'put'])) {
            $task->complete();
            $success = $this->Tasks->save($task);
        }

        return $this->respond([
            'success' => $success,
            'flashSuccess' => __('Task completed'),
            'flashError' => __('The task could not be completed. Please try again.'),
            'statusSuccess' => 204,
            'redirect' => $this->referer(['_name' => 'tasks:today']),
        ]);
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
        $success = false;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $task->incomplete();
            if ($this->Tasks->save($task)) {
                $success = true;
            }
        }

        return $this->respond([
            'success' => $success,
            'flashSuccess' => __('Task updated'),
            'flashError' => __('The task could not be updated. Please try again.'),
            'statusSuccess' => 204,
            'redirect' => $this->referer(['_name' => 'tasks:today']),
        ]);
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

        $serialize = [];
        $success = false;
        $error = null;
        try {
            $this->Tasks->move($task, $operation);
            $success = true;
            $this->set('task', $task);
            $serialize[] = 'task';
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
            $serialize[] = ['errors'];
            $this->set('errors', [$error]);
        }

        return $this->respond([
            'success' => $success,
            'serialize' => $serialize,
            'flashSuccess' => __('Task moved'),
            'flashError' => $error,
            'statusError' => 422,
            'redirect' => $this->referer(['_name' => 'tasks:today']),
        ]);
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
            $task->project = $project;
        }

        $success = false;
        $serialize = [];
        if ($this->Tasks->save($task)) {
            $success = true;
            $serialize[] = 'task';
            $this->set('task', $task);
        } else {
            $serialize[] = 'errors';
            $this->set('errors', $this->flattenErrors($task->getErrors()));
        }

        return $this->respond([
            'success' => $success,
            'serialize' => $serialize,
            'flashSuccess' => __('Task updated'),
            'flashError' => __('Task could not be updated.'),
            'statusError' => 422,
        ]);
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

        $this->set('task', $task);
        $this->set('referer', $this->getReferer('tasks:today'));

        return $this->respond([
            'success' => true,
            'serialize' => ['task'],
        ]);
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

        $success = false;
        $task->softDelete();
        if ($this->Tasks->saveOrFail($task)) {
            $success = true;
        }

        return $this->respond([
            'success' => $success,
            'serialize' => ['task'],
            'flashSuccess' => __('The task has been deleted.'),
            'flashError' => __('The task could not be deleted. Please, try again.'),
            'redirect' => $this->referer(['_name' => 'tasks:today']),
        ]);
    }

    /**
     * Undelete method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function undelete($id = null)
    {
        $this->request->allowMethod('post');
        $task = $this->Tasks->get($id, ['contain' => ['Projects'], 'deleted' => true]);
        $this->Authorization->authorize($task);

        $success = false;
        $task->undelete();
        if ($this->Tasks->saveOrFail($task)) {
            $success = true;
        }

        return $this->respond([
            'success' => $success,
            'serialize' => ['task'],
            'flashSuccess' => __('The task has been restored.'),
            'flashError' => __('The task could not be restored. Please, try again.'),
            'redirect' => $this->referer(['_name' => 'tasks:today']),
        ]);
    }
}

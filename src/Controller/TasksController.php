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

    protected function useInertia(): bool
    {
        if ($this->request->getParam('action') == 'view' && $this->request->getParam('mode')) {
            return false;
        }

        return !in_array($this->request->getParam('action'), ['complete', 'incomplete', 'deleteConfirm']);
    }

    protected function getDateParam($value, ?string $default = null, ?string $timezone = null): FrozenDate
    {
        if ($value !== null && !is_string($value)) {
            throw new BadRequestException('Invalid date. Value must be a string.');
        }
        if (empty($value) && $default) {
            return $this->getDateParam($default, null, $timezone);
        }
        try {
            return new FrozenDate($value, $timezone);
        } catch (\Exception $e) {
            throw new BadRequestException("Invalid date value of {$value}.");
        }
    }

    /**
     * Fetch tasks for a single day
     */
    public function daily(string $date)
    {
        $calendarItems = $this->fetchTable('CalendarItems');
        $overdue = (bool)$this->request->getQuery('overdue', false);
        if ($date === 'today') {
            $overdue = true;
        }

        $identity = $this->request->getAttribute('identity');
        $timezone = $identity->timezone;
        $date = $this->getDateParam($date, null, $timezone);

        $query = $this->Tasks
            ->find('incomplete')
            ->find('forDate', ['date' => $date, 'overdue' => $overdue])
            ->contain('Projects');
        $query = $this->Authorization->applyScope($query, 'index');

        $eventsQuery = $calendarItems->find('upcoming', [
            'start' => $date,
            'end' => $date,
            'timezone' => $timezone,
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
     * Supports two query string parameters. `start` indicates the start of the range.
     * `end` indicates the end. You cannot query more than 31 days at time.
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index(string $view = 'upcoming')
    {
        $calendarItemsTable = $this->fetchTable('CalendarItems');

        $identity = $this->request->getAttribute('identity');
        $timezone = $identity->timezone;

        $startParam = $this->request->getQuery('start', 'today');
        $start = $this->getDateParam($startParam, 'today', $timezone);

        $endParam = $this->request->getQuery('end');
        $end = $this->getDateParam($endParam, '+28 days', $timezone);
        if ($start->diffInDays($end) > 60) {
            throw new BadRequestException('Invalid date range. Choose a range that is less than 60 days.');
        }

        $query = $this->Tasks
            ->find('incomplete')
            ->find('upcoming', ['start' => $start, 'end' => $end])
            ->contain('Projects');
        $query = $this->Authorization->applyScope($query);

        $eventsQuery = $calendarItemsTable->find('upcoming', [
            'start' => $start,
            'end' => $end,
            'timezone' => $timezone,
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
            $options = ['associated' => ['Subtasks']];
            $task->setAccess('subtasks', true);
            $task = $this->Tasks->patchEntity($task, $this->request->getData(), $options);

            // Ensure the project belongs to the current user.
            $project = $this->Tasks->Projects->get($task->project_id);
            $this->Authorization->authorize($project, 'edit');

            $user = $this->request->getAttribute('identity');
            $this->Tasks->setNextOrderProperties($user, $task);

            $task->project = $project;
            if ($this->Tasks->save($task, $options)) {
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

        $template = null;
        $success = false;
        if ($this->request->is(['patch', 'post', 'put', 'delete'])) {
            $task->complete();
            $success = $this->Tasks->save($task);
        }
        $status = 204;
        $redirect = $this->referer(['_name' => 'tasks:today']);
        if ($this->request->is('htmx')) {
            $redirect = null;
            $template = 'delete_ok';
            $status = 200;
        }

        return $this->respond([
            'success' => $success,
            'flashError' => __('The task could not be completed. Please try again.'),
            'statusSuccess' => $status,
            'redirect' => $redirect,
            'template' => $template,
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
        $template = null;
        $success = false;

        if ($this->request->is(['delete', 'patch', 'post', 'put'])) {
            $task->incomplete();
            if ($this->Tasks->save($task)) {
                $success = true;
            }
        }
        $redirect = $this->referer(['_name' => 'tasks:today']);
        $status = 204;
        if ($this->request->is('htmx')) {
            $redirect = null;
            $template = 'delete_ok';
            $status = 200;
        }

        return $this->respond([
            'success' => $success,
            'flashError' => __('The task could not be updated. Please try again.'),
            'statusSuccess' => $status,
            'redirect' => $redirect,
            'template' => $template,
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
            $sectionId = $this->request->getData('section_id');
            $operation['section_id'] = $sectionId === '' ? null : $sectionId;
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

        $refresh = null;
        $success = false;
        $serialize = [];
        if ($this->Tasks->save($task)) {
            $success = true;
            $serialize[] = 'task';
            $refresh = $this->request->getData('refresh');

            $this->set('task', $task);
        } else {
            $serialize[] = 'errors';
            $this->set('errors', $this->flattenErrors($task->getErrors()));
        }
        if ($refresh !== null) {
            $this->response = $this->response->withHeader('Hx-Refresh', 'true');
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
    public function view($id = null, $mode = null)
    {
        $task = $this->Tasks->get($id, [
            'contain' => ['Projects', 'Subtasks'],
        ]);
        $this->Authorization->authorize($task);

        $template = 'view';
        if (in_array($mode, ['editproject'], true)) {
            $template = $mode;
        }
        if ($template === 'editproject') {
            $this->set('projects', $this->Tasks->Projects->find('active')->find('top'));
        }

        $this->set('task', $task);
        $this->set('referer', $this->getReferer('tasks:today'));

        return $this->respond([
            'success' => true,
            'serialize' => ['task'],
            'template' => $template,
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

    public function deleteConfirm(string $id)
    {
        $task = $this->Tasks->get($id, ['contain' => ['Projects']]);
        $this->Authorization->authorize($task, 'delete');

        $this->set('task', $task);
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

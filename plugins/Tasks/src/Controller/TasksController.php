<?php
declare(strict_types=1);

namespace Tasks\Controller;

use App\Controller\AppController;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\View\JsonView;
use Exception;
use InvalidArgumentException;
use Tasks\Model\Entity\Task;

/**
 * Tasks Controller
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Tasks\Model\Table\TasksTable $Tasks
 * @property \Calendar\Model\Table\CalendarItemsTable $CalendarItems
 */
class TasksController extends AppController
{
    public const EDIT_MODES = ['editproject', 'reschedule'];

    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    protected function getDateParam($value, ?string $default = null, ?string $timezone = null): Date
    {
        if ($value !== null && !is_string($value)) {
            throw new BadRequestException('Invalid date. Value must be a string.');
        }
        if (empty($value) && $default) {
            return $this->getDateParam($default, null, $timezone);
        }
        try {
            return new Date($value, $timezone);
        } catch (Exception $e) {
            throw new BadRequestException("Invalid date value of {$value}.");
        }
    }

    protected function getTask($id): Task
    {
        return $this->Tasks->get($id, contain: ['Projects', 'Subtasks']);
    }

    /**
     * Fetch tasks for a single day
     */
    public function daily(string $date)
    {
        $calendarItems = $this->fetchTable('Calendar.CalendarItems');
        $overdue = (bool)$this->request->getQuery('overdue', false);
        if ($date === 'today') {
            $overdue = true;
        }

        $identity = $this->request->getAttribute('identity');
        $timezone = $identity->timezone;
        $date = $this->getDateParam($date, null, $timezone);

        $query = $this->Tasks
            ->find('incomplete')
            ->find('forDate', date: $date, overdue: $overdue)
            ->contain('Projects');
        $query = $this->Authorization->applyScope($query, 'index');

        $eventsQuery = $calendarItems->find(
            'upcoming',
            start: $date,
            end: $date,
            timezone: $timezone
        );
        $this->set('date', $date);

        $tasks = $query->all();
        $calendarItems = $this->Authorization
            ->applyScope($eventsQuery, 'index')
            ->toArray();
        $this->set(compact('tasks', 'calendarItems'));
        $this->set('generation', uniqid());

        // Work around for errors from inline add.
        $session = $this->request->getSession();
        if ($session->check('errors')) {
            $this->set('errors', $session->consume('errors'));
        }

        if ($this->request->is('htmx')) {
            $this->response = $this->response->withHeader('Hx-Trigger', 'close');
        }

        return $this->respond([
            'success' => true,
        ]);
    }

    /**
     * Index method
     *
     * Supports two query string parameters. `start` indicates the start of the range.
     * `end` indicates the end. You cannot query more than 31 days at time.
     *
     * @return \Cake\Http\Response|null Renders view
     */
    public function index(string $view = 'upcoming'): ?Response
    {
        $calendarItemsTable = $this->fetchTable('Calendar.CalendarItems');

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
            ->find('upcoming', start: $start, end: $end)
            ->contain('Projects');
        $query = $this->Authorization->applyScope($query);

        $eventsQuery = $calendarItemsTable->find(
            'upcoming',
            start: $start,
            end: $end,
            timezone: $timezone
        );
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

        if ($this->request->is('htmx')) {
            $this->response = $this->response->withHeader('Hx-Trigger', 'close');
        }

        return $this->respond([
            'success' => true,
        ]);
    }

    /**
     * Fetch tasks that are soft-deleted
     */
    public function deleted()
    {
        $query = $this->Tasks
            ->find('all', deleted: true)
            ->contain('Projects');
        $query = $this->Authorization->applyScope($query, 'index');

        $this->set('tasks', $query->all());
        $this->set('generation', uniqid());
        $this->set('component', 'Tasks/Deleted');

        return $this->respond([
            'success' => true,
        ]);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $task = $this->Tasks->newEntity($this->request->getQueryParams());
        $task->subtasks = [];
        $task->evening ??= false;

        $success = true;
        $redirect = null;
        $errors = [];

        if ($this->request->is('post')) {
            $success = false;
            $options = ['associated' => ['Subtasks']];
            $task->setAccess('subtasks', true);
            $task = $this->Tasks->patchEntity($task, $this->request->getData(), $options);
            $subtaskTitle = $this->request->getData('_subtaskadd');
            if ($subtaskTitle) {
                $task->setDirty('subtasks');
                $task->subtasks[] = $this->Tasks->Subtasks->newEntity(['title' => $subtaskTitle]);
            }

            // Ensure the project belongs to the current user.
            $project = $this->Tasks->Projects->get($task->project_id);
            $this->Authorization->authorize($project, 'edit');

            $user = $this->request->getAttribute('identity');
            $this->Tasks->setNextOrderProperties($user, $task);

            $task->project = $project;
            if ($this->Tasks->save($task, $options)) {
                $success = true;
                $redirect = $this->referer(['_name' => 'tasks:today']);
            } else {
                $redirect = $this->referer(['_name' => 'tasks:today']);
                $errors = $this->flattenErrors($task->getErrors());
            }
        }

        if ($errors) {
            $this->request->getSession()->write('errors', $errors);
        }

        $projects = [];
        $sections = [];
        if (!$this->request->is('json')) {
            $projects = $this->Tasks->Projects->find('active')->find('top');
            $projects = $this->Authorization->applyScope($projects, 'index')->toArray();
            if (!$task->project_id && count($projects) > 0) {
                $task->project_id = $projects[0]->id;
            }
            if ($task->project_id) {
                $sections = $this->Tasks->Projects->Sections
                    ->find()
                    ->where(['Sections.project_id' => $task->project_id])
                    ->toArray();
            }
        }
        $this->set('projects', $projects);
        $this->set('sections', $sections);
        $this->set('task', $task);
        $this->set('errors', $errors);
        $this->set('referer', $this->request->referer());

        return $this->respond([
            'success' => $success,
            'flashSuccess' => __('Task saved'),
            'flashError' => __('The task could not be saved. Please try again.'),
            'redirect' => $redirect,
        ]);
    }

    /**
     * Complete a task as complete.
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function complete(?string $id = null): ?Response
    {
        $task = $this->getTask($id);
        $this->Authorization->authorize($task, 'edit');

        $success = false;
        if ($this->request->is(['patch', 'post', 'put', 'delete'])) {
            $task->complete();
            $success = $this->Tasks->save($task);
        }
        $status = 204;
        $redirect = $this->referer(['_name' => 'tasks:today']);
        if ($this->request->is('htmx')) {
            $status = 200;
        }

        return $this->respond([
            'success' => $success,
            'flashError' => __('The task could not be completed. Please try again.'),
            'flashSuccess' => __('Task complete'),
            'statusSuccess' => $status,
            'redirect' => $redirect,
        ]);
    }

    /**
     * Mark a task as incomplete.
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function incomplete(?string $id = null): ?Response
    {
        $task = $this->Tasks->get($id, contain: ['Projects']);
        $this->Authorization->authorize($task, 'edit');
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
            $status = 200;
        }

        return $this->respond([
            'success' => $success,
            'flashError' => __('The task could not be updated. Please try again.'),
            'flashSuccess' => __('Task incomplete'),
            'statusSuccess' => $status,
            'redirect' => $redirect,
        ]);
    }

    public function move(string $id)
    {
        $this->request->allowMethod(['post']);
        $task = $this->Tasks->get($id, contain: ['Projects']);
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

        $success = false;
        $error = null;
        try {
            $this->Tasks->move($task, $operation);
            $success = true;
            $this->set('task', $task);
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
            $this->set('errors', [$error]);
        }

        return $this->respond([
            'success' => $success,
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
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $task = $this->getTask($id);
        $this->Authorization->authorize($task);
        $data = $this->request->getData();

        // This is API specific behavior that mobile client relies on.
        if ($this->request->is('json')) {
            if (isset($data['subtasks']) && $data['subtasks'] === []) {
                unset($data['subtasks']);
            }
        }
        $task = $this->Tasks->patchEntity($task, $data, [
            'associated' => ['Subtasks'],
        ]);

        // If the project has changed ensure the new project belongs
        // to the current user.
        if ($task->isDirty('project_id')) {
            $project = $this->Tasks->Projects->get($task->project_id);
            $this->Authorization->authorize($project, 'edit');
            $task->section_id = null;
            $task->project = $project;
        }
        $subtaskTitle = $this->request->getData('_subtaskadd');
        if ($subtaskTitle) {
            $task->setDirty('subtasks');
            $task->subtasks[] = $this->Tasks->Subtasks->newEntity(['title' => $subtaskTitle, 'task_id' => $task->id]);
        }
        $task->setDueOnFromString($this->request->getData('due_on_string'));
        $task->removeTrailingEmptySubtask();

        $success = false;
        if ($this->Tasks->save($task, ['associated' => ['Subtasks']])) {
            $success = true;
            // Reload to get fresh counter cache values.
            $task = $this->getTask($task->id);
            $this->set('task', $task);
        } else {
            $this->set('errors', $this->flattenErrors($task->getErrors()));
        }
        $redirect = null;
        if (!$this->request->is('json')) {
            $redirect = ['_name' => 'tasks:view', 'id' => $task->id];
        }
        $hxRedirect = $this->sanitizeRedirect($this->request->getData('redirect'));
        if ($hxRedirect) {
            $redirect = $hxRedirect;
        }

        return $this->respond([
            'redirect' => $redirect,
            'success' => $success,
            'flashSuccess' => __('Task updated'),
            'flashError' => __('Task could not be updated.'),
            'statusError' => 422,
        ]);
    }

    /**
     * View method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null, $mode = null): ?Response
    {
        $task = $this->getTask($id);
        $this->Authorization->authorize($task);

        $template = 'view';
        if (in_array($mode, static::EDIT_MODES, true)) {
            $template = $mode;
        }
        if ($template === 'editproject' || $template === 'view') {
            $projects = $this->Tasks->Projects->find('active')->find('top');
            $projects = $this->Authorization->applyScope($projects, 'index');
            $this->set('projects', $projects);
        }
        $sections = [];
        if ($task->project_id) {
            $projectId = $this->request->getQuery('project_id', $task->project_id);
            $sections = $this->Tasks->Projects->Sections
                ->find()
                ->where(['Sections.project_id' => $projectId])
                ->toArray();
        }
        $this->set('sections', $sections);

        $this->set('task', $task);
        $this->set('referer', $this->getReferer('tasks:today'));

        return $this->respond([
            'success' => true,
            'template' => $template,
        ]);
    }

    /**
     * Delete method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $task = $this->Tasks->get($id, contain: ['Projects']);
        $this->Authorization->authorize($task);

        $success = false;
        $task->softDelete();
        if ($this->Tasks->saveOrFail($task)) {
            $success = true;
        }

        return $this->respond([
            'success' => $success,
            'flashSuccess' => __('The task has been deleted.'),
            'flashError' => __('The task could not be deleted. Please, try again.'),
            'redirect' => $this->referer(['_name' => 'tasks:today']),
        ]);
    }

    public function deleteConfirm(string $id): void
    {
        $task = $this->Tasks->get($id, contain: ['Projects']);
        $this->Authorization->authorize($task, 'delete');

        $this->set('task', $task);
    }

    /**
     * Undelete method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function undelete(?string $id = null): ?Response
    {
        $this->request->allowMethod('post');
        $task = $this->Tasks->get($id, contain: ['Projects'], deleted: true);
        $this->Authorization->authorize($task);

        $success = false;
        $task->undelete();
        if ($this->Tasks->saveOrFail($task)) {
            $success = true;
        }

        return $this->respond([
            'success' => $success,
            'flashSuccess' => __('The task has been restored.'),
            'flashError' => __('The task could not be restored. Please, try again.'),
            'redirect' => $this->referer(['_name' => 'tasks:today']),
        ]);
    }
}

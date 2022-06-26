<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Project;
use Cake\View\JsonView;
use InvalidArgumentException;

/**
 * Projects Controller
 *
 * @property \App\Model\Table\TasksTable $Tasks
 * @property \App\Model\Table\ProjectsTable $Projects
 */
class ProjectsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('Tasks');
    }

    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    protected function getProject($slug, array $contain = []): Project
    {
        $query = $this->Projects->findBySlug($slug);
        $query = $this->Authorization->applyScope($query, 'index');

        /** @var \App\Model\Entity\Project */
        return $query
            ->contain($contain)
            ->firstOrFail();
    }

    /**
     * Response generation helper
     *
     * Eases defining logic for common API response patterns like success/error states.
     *
     * ### Options
     *
     * - success - Whether or not the request completed successfully.
     * - flashSuccess - The flash message to show for HTML responses that were successful.
     * - flashError - The flash message to show for HTML responses that had errors.
     * - redirect - The redirect to use for HTML responses.
     * - statusSuccess - The HTTP status code for succesful API responses.
     * - statusError - The Http status code for error responses.
     * - serialize - The view variables to serialize into an API response.
     *
     * @TODO use this in other endpoints as well.
     * @return void|\App\Controller\Cake\Http\Response Either a response or null if we're not skipping view rendering.
     */
    protected function respond(array $config)
    {
        $config += [
            'success' => false,
            'flashSuccess' => null,
            'flashError' => null,
            'redirect' => null,
            'statusSuccess' => 200,
            'statusError' => 400,
            'serialize' => null,
        ];
        $isApi = $this->request->is('json');

        if ($isApi) {
            $this->viewBuilder()->setOption('serialize', $config['serialize']);
            $code = $config['success'] ? $config['statusSuccess'] : $config['statusError'];

            $this->response = $this->response->withStatus($code);
            if ($this->response->getStatusCode() == 204) {
                return $this->response;
            }

            return;
        }

        if ($config['success']) {
            $this->Flash->success($config['flashSuccess']);
        } else {
            $this->Flash->error($config['flashError']);
        }
        if ($config['redirect']) {
            return $this->redirect($config['redirect']);
        }
    }

    /**
     * Project list endpoint
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $projects = $this->Authorization
            ->applyScope($this->Projects->find(), 'index')
            ->contain('Sections');

        $projects = $this->paginate($projects);

        $this->set('projects', $projects);
        $this->respond([
            'success' => true,
            'serialize' => ['projects'],
        ]);
    }

    /**
     * View method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view(string $slug)
    {
        $project = $this->getProject($slug, ['Sections']);

        $tasks = $this->Authorization
            ->applyScope($this->Tasks->find(), 'index')
            ->contain('Projects') ->find('incomplete')
            ->find('forProjectDetails', ['slug' => $slug])
            ->limit(250);

        $completed = null;
        if ($this->request->getQuery('completed')) {
            $completedQuery = $this->Authorization
               ->applyScope($this->Tasks->find(), 'index')
               ->contain('Projects')
               ->find('complete')
               ->where(['Projects.slug' => $slug])
               ->orderDesc('Tasks.due_on')
               ->orderAsc('title');
            $completed = $this->paginate($completedQuery, ['scope' => 'completed']);
        }

        $this->set(compact('project', 'tasks', 'completed'));
        $this->respond([
            'success' => true,
            'serialize' => ['project', 'tasks'],
        ]);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $project = $this->Projects->newEmptyEntity();
        $this->Authorization->authorize($project, 'create');

        $referer = $this->getReferer();
        $this->set('referer', $referer);

        $success = false;
        $serialize = [];
        $redirect = null;

        if ($this->request->is('post')) {
            $userId = $this->request->getAttribute('identity')->getIdentifier();
            $project = $this->Projects->patchEntity($project, $this->request->getData());
            $project->user_id = $userId;
            $project->ranking = $this->Projects->getNextRanking($userId);

            if ($this->Projects->save($project)) {
                $success = true;
                $redirect = $referer;
                $serialize[] = 'project';
                $this->set('project', $project);
            } else {
                $serialize[] = 'errors';
                $this->set('errors', $this->flattenErrors($project->getErrors()));
            }
        }

        return $this->respond([
            'success' => $success,
            'serialize' => $serialize,
            'flashSuccess' => __('Project created'),
            'flashError' => __('Your project could not be saved'),
            'redirect' => $redirect,
        ]);
    }

    /**
     * Edit method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(string $slug)
    {
        $project = $this->getProject($slug);
        $this->Authorization->authorize($project);

        $referer = $this->getReferer();
        $success = false;
        $serialize = [];
        $redirect = null;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $project = $this->Projects->patchEntity($project, $this->request->getData());
            if ($this->Projects->save($project)) {
                $success = true;
                $serialize[] = 'project';
                $this->set('project', $project);

                $redirect = [
                    '_name' => 'projects:view',
                    'slug' => $project->slug,
                ];
            } else {
                $serialize[] = 'errors';
                $this->set('errors', $this->flattenErrors($project->getErrors()));
            }
        }
        $this->set('referer', $referer);
        $this->set('project', $project);

        return $this->respond([
            'success' => $success,
            'serialize' => $serialize,
            'flashSuccess' => __('Project updated'),
            'flashError' => __('Your project could not be saved'),
            'redirect' => $redirect,
        ]);
    }

    /**
     * Archived projects
     *
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function archived()
    {
        $query = $this->Projects->find('archived');
        $query = $this->Authorization->applyScope($query, 'index');

        $archived = $this->paginate($query);

        // Avoid clashing with 'projects' set in AppController.
        $this->set('archived', $archived);

        // API responses still use 'projects'
        $serialize = ['projects'];
        $this->set('projects', $archived);

        return $this->respond([
            'success' => true,
            'serialize' => $serialize,
        ]);
    }

    /**
     * Delete method
     *
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(string $slug)
    {
        $this->request->allowMethod(['post', 'delete']);
        $project = $this->getProject($slug);
        $this->Authorization->authorize($project);

        $success = false;
        $redirect = null;

        if ($this->Projects->delete($project)) {
            $success = true;
            $redirect = $this->redirect(['_name' => 'tasks:today']);
        }

        return $this->respond([
            'success' => $success,
            'statusSuccess' => 204,
            'flashSuccess' => __('Project deleted'),
            'flashError' => __('Could not delete project'),
            'redirect' => $redirect,
        ]);
    }

    public function archive(string $slug)
    {
        $project = $this->getProject($slug);
        $this->Authorization->authorize($project);

        $project->archive();
        $this->Projects->save($project);

        return $this->respond([
            'success' => true,
            'statusSuccess' => 204,
            'flashSuccess' => __('Project archived'),
            'redirect' => $this->referer(['_name' => 'tasks:today']),
        ]);
    }

    public function unarchive(string $slug)
    {
        $project = $this->getProject($slug);
        $this->Authorization->authorize($project, 'archive');

        $project->unarchive();
        $this->Projects->save($project);

        return $this->respond([
            'success' => true,
            'statusSuccess' => 204,
            'flashSuccess' => __('Project unarchived'),
            'redirect' => $this->referer(['_name' => 'tasks:today']),
        ]);
    }

    public function move(string $slug)
    {
        $this->request->allowMethod(['post']);
        $project = $this->getProject($slug);
        $this->Authorization->authorize($project, 'edit');

        $operation = [
            'ranking' => $this->request->getData('ranking'),
        ];
        $success = false;
        $error = null;
        $serialize = [];

        try {
            $this->Projects->move($project, $operation);
            $success = true;
            $serialize[] = 'project';
            $this->set('project', $project);
        } catch (InvalidArgumentException $e) {
            $error = [$e->getMessage()];
            $serialize[] = 'errors';
            $this->set('errors', [$error]);
        }

        return $this->respond([
            'success' => $success,
            'statusSuccess' => 204,
            'statusError' => 400,
            'flashSuccess' => __('Project moved'),
            'flashError' => $error,
            'redirect' => $this->referer(['_name' => 'tasks:today']),
        ]);
    }
}

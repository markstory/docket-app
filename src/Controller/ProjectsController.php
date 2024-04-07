<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Project;
use App\Model\Table\ProjectsTable;
use App\Model\Table\TasksTable;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\View\JsonView;
use InvalidArgumentException;

/**
 * Projects Controller
 */
class ProjectsController extends AppController
{
    public TasksTable $Tasks;
    public ProjectsTable $Projects;

    public function initialize(): void
    {
        parent::initialize();
        $this->Tasks = $this->fetchTable('Tasks');
        $this->Projects = $this->fetchTable('Projects');
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
     * View method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view(string $slug): Response|null|null
    {
        $project = $this->getProject($slug, ['Sections']);

        $completed = (int)$this->request->getQuery('completed', '0');
        if ($completed) {
            $query = $this->Authorization
               ->applyScope($this->Tasks->find(), 'index')
               ->contain('Projects')
               ->find('complete')
               ->where(['Projects.slug' => $slug])
               ->orderDesc('Tasks.due_on')
               ->orderAsc('title');
            $tasks = $this->paginate($query, ['scope' => 'completed']);

            $this->viewBuilder()->setTemplate('completed');
        } else {
            $tasks = $this->Authorization
                ->applyScope($this->Tasks->find(), 'index')
                ->contain('Projects')
                ->find('incomplete')
                ->find('forProjectDetails', slug: $slug)
                ->limit(250);
        }

        if ($this->request->is('htmx')) {
            // Close any open menus/modals
            $this->response = $this->response->withHeader('Hx-Trigger', 'close');
        }

        $this->set(compact('project', 'tasks', 'completed'));

        return $this->respond([
            'success' => true,
            'serialize' => ['project', 'tasks', 'completed'],
        ]);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add(): Response|null|null
    {
        $project = $this->Projects->newEmptyEntity();
        $this->Authorization->authorize($project, 'create');

        $referer = $this->getReferer();

        $success = null;
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
                $success = false;
                $serialize[] = 'errors';
                $this->set('errors', $this->flattenErrors($project->getErrors()));
            }
        }
        $this->set('project', $project);
        $this->set('referer', $referer);

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
    public function edit(string $slug): Response|null|null
    {
        $project = $this->getProject($slug);
        $this->Authorization->authorize($project);

        $referer = $this->getReferer();
        $success = null;
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
                $success = false;
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
    public function archived(): Response|null|null
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
     * Render a confirmation dialog for a delete operation.
     */
    public function deleteConfirm(string $slug): void
    {
        $project = $this->getProject($slug);
        $this->Authorization->authorize($project, 'delete');
        $this->set('project', $project);
    }

    /**
     * Delete method
     *
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(string $slug): Response|null|null
    {
        $this->request->allowMethod(['post', 'delete']);
        $project = $this->getProject($slug);
        $this->Authorization->authorize($project);

        $success = false;
        $redirect = null;

        if ($this->Projects->delete($project)) {
            $success = true;
            $redirect = ['_name' => 'tasks:today'];
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
            $error = $e->getMessage();
            $serialize[] = 'errors';
            $this->set('errors', [$error]);
        }

        return $this->respond([
            'success' => $success,
            'serialize' => $serialize,
            'statusError' => 400,
            'flashSuccess' => __('Project moved'),
            'flashError' => $error,
            'redirect' => $this->referer(['_name' => 'tasks:today']),
        ]);
    }

    /**
     * Reorder a users active projects as a group.
     */
    public function reorder(): void
    {
        $this->request->allowMethod(['post']);
        $ids = (array)$this->request->getData('id');

        $query = $this->Projects
            ->find('active')->find('top')
            ->where(['Projects.id IN' => $ids]);
        $query = $this->Authorization->applyScope($query, 'index');
        $projects = $query->all();
        if (count($projects) != count($ids)) {
            throw new BadRequestException('Invalid id values provided');
        }
        $this->Projects->reorder($ids);
    }
}

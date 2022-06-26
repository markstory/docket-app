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
     * View method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view(string $slug)
    {
        $project = $this->getProject($slug, ['Sections']);

        $tasks = $this->Authorization
            ->applyScope($this->Tasks->find(), 'index')
            ->contain('Projects')
            ->find('incomplete')
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
        if ($this->request->is('json')) {
            $this->viewBuilder()->setOption('serialized', ['project', 'tasks']);
        }
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

        $isJson = $this->request->is('json');
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
                $redirect = true;
                $serialize[] = 'project';
                $this->set('project', $project);
            } else {
                $serialize[] = 'errors';
                $this->set('errors', $this->flattenErrors($project->getErrors()));
            }
        }

        $referer = $this->getReferer();
        $this->set('referer', $referer);

        if ($isJson) {
            $this->viewBuilder()->setOption('serialize', $serialize);
        } else {
            if ($success) {
                $this->Flash->success(__('Project created'));
            } else {
                $this->Flash->error(__('The project could not be saved.'));
            }
        }
        if ($redirect && !$isJson) {
            return $this->redirect($referer);
        }
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

        $isJson = $this->request->is('json');
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

        if (!$isJson) {
            if ($success) {
                $this->Flash->success(__('Project saved.'));
            } else {
                $this->Flash->error(__('Project could not be saved. Please, try again.'));
            }
            if ($redirect) {
                return $this->redirect($redirect);
            }
        } else {
            $this->viewBuilder()->setOption('serialize', $serialize);
        }
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
        if ($this->request->is('json')) {
            $this->viewBuilder()->setOption('serialize', $serialize);
        }
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
        $isJson = $this->request->is('json');
        $success = false;
        $redirect = null;

        if ($this->Projects->delete($project)) {
            $success = true;
            $redirect = $this->redirect(['_name' => 'tasks:today']);
        }

        if (!$isJson) {
            if ($success) {
                $this->Flash->success(__('Project deleted'));
            } else {
                $this->Flash->error(__('Could not delete project'));
            }
            if ($redirect) {
                return $this->redirect($redirect);
            }
        }
        return $this->response->withStatus(204);
    }

    public function archive(string $slug)
    {
        $project = $this->getProject($slug);
        $this->Authorization->authorize($project);

        $project->archive();
        $this->Projects->save($project);

        if ($this->request->is('json')) {
            return $this->response->withStatus(200);
        }
        $this->Flash->success(__('Project archived'));

        return $this->redirect($this->referer(['_name' => 'tasks:today']));
    }

    public function unarchive(string $slug)
    {
        $project = $this->getProject($slug);
        $this->Authorization->authorize($project, 'archive');

        $project->unarchive();
        $this->Projects->save($project);
        if ($this->request->is('json')) {
            return $this->response->withStatus(200);
        }
        $this->Flash->success(__('Project unarchived'));

        return $this->redirect($this->referer(['_name' => 'tasks:today']));
    }

    public function move(string $slug)
    {
        $this->request->allowMethod(['post']);
        $project = $this->getProject($slug);
        $this->Authorization->authorize($project, 'edit');

        $operation = [
            'ranking' => $this->request->getData('ranking'),
        ];
        $isJson = $this->request->is('json');
        $success = false;
        $errors = [];
        $serialize = [];

        try {
            $this->Projects->move($project, $operation);
            $success = true;
            $serialize[] = 'project';
            $this->set('project', $project);
        } catch (InvalidArgumentException $e) {
            $serialize[] = 'errors';
            $errors = [$e->getMessage()];
            $this->set('errors', $errors);
        }
        if (!$isJson) {
            if ($success) {
                $this->Flash->success(__('Project reordered.'));
            } else {
                $this->Flash->error($errors[0]);
            }

            return $this->redirect($this->referer(['_name' => 'tasks:today']));
        }

        $this->viewBuilder()->setOption('serialize', $serialize);
        if (!$success) {
            $this->response = $this->response->withStatus(400);
        } else {
            return $this->response->withStatus(204);
        }
    }
}

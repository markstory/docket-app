<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Project;
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

        $query = $this->Authorization
            ->applyScope($this->Tasks->find(), 'index')
            ->contain('Projects')
            ->find('incomplete')
            ->find('forProject', ['slug' => $slug]);

        $tasks = $this->paginate($query);
        $completed = null;
        if ($this->request->getQuery('completed')) {
            $completedQuery = $this->Authorization
               ->applyScope($this->Tasks->find(), 'index')
               ->contain('Projects')
               ->find('complete')
               ->find('forProject', ['slug' => $slug])
               ->orderDesc('Tasks.due_on');
            $completed = $this->paginate($completedQuery, ['scope' => 'completed']);
        }

        $this->set(compact('project', 'tasks', 'completed'));
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

        if ($this->request->is('post')) {
            $userId = $this->request->getAttribute('identity')->getIdentifier();
            $project = $this->Projects->patchEntity($project, $this->request->getData());
            $project->user_id = $userId;
            $project->ranking = $this->Projects->getNextRanking($userId);

            if ($this->Projects->save($project)) {
                $this->Flash->success(__('Project created.'));

                return $this->redirect($referer);
            }
            $this->Flash->error(__('The project could not be saved. Please, try again.'));
            $this->set('errors', $this->flattenErrors($project->getErrors()));
        }
        $this->set('referer', $referer);
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

        if ($this->request->is(['patch', 'post', 'put'])) {
            $project = $this->Projects->patchEntity($project, $this->request->getData());
            if ($this->Projects->save($project)) {
                $this->Flash->success(__('Project saved.'));

                return $this->redirect([
                    '_name' => 'projects:view',
                    'slug' => $project->slug,
                ]);
            }
            $this->Flash->error(__('Project could not be saved. Please, try again.'));
            $this->set('errors', $this->flattenErrors($project->getErrors()));
        }
        $this->set('referer', $referer);
        $this->set('project', $project);
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

        $this->set('archived', $archived);
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

        if ($this->Projects->delete($project)) {
            $this->Flash->success(__('Project deleted'));

            return $this->redirect(['_name' => 'tasks:today']);
        }

        return $this->response->withStatus(400);
    }

    public function archive(string $slug)
    {
        $project = $this->getProject($slug);
        $this->Authorization->authorize($project);

        $project->archive();
        $this->Projects->save($project);
        $this->Flash->success(__('Project archived'));

        return $this->redirect($this->referer(['_name' => 'tasks:today']));
    }

    public function unarchive(string $slug)
    {
        $project = $this->getProject($slug);
        $this->Authorization->authorize($project, 'archive');

        $project->unarchive();
        $this->Projects->save($project);
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
        try {
            $this->Projects->move($project, $operation);
            $this->Flash->success(__('Project reordered.'));
        } catch (InvalidArgumentException $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect($this->referer(['_name' => 'tasks:today']));
    }
}

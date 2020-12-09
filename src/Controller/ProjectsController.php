<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;

/**
 * Projects Controller
 *
 * @property \App\Model\Table\ProjectsTable $Projects
 * @method \App\Model\Entity\Project[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ProjectsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('TodoItems');
    }

    protected function getProject()
    {
        $slug = $this->request->getParam('slug');

        return $this->Projects->findBySlug($slug)->firstOrFail();
    }

    /**
     * View method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view()
    {
        $project = $this->getProject();
        $this->Authorization->authorize($project);

        $query = $this->Authorization
            ->applyScope($this->TodoItems->find(), 'index')
            ->contain('Projects')
            ->find('incomplete')
            ->find('forProject', ['slug' => $this->request->getParam('slug')]);

        $todoItems = $this->paginate($query);

        $this->set(compact('project', 'todoItems'));
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
        if ($this->request->is('post')) {
            $project = $this->Projects->patchEntity($project, $this->request->getData());
            $project->user_id = $this->request->getAttribute('identity')->getIdentifier();

            if ($this->Projects->save($project)) {
                return $this->response->withStatus(201);
            }

            // TODO this should switch to a page load. Code is much simpler that way.
            return $this->validationErrorResponse($project->getErrors());
        }
    }

    /**
     * Edit method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit()
    {
        $project = $this->getProject();
        $this->Authorization->authorize($project);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $project = $this->Projects->patchEntity($project, $this->request->getData());
            if ($this->Projects->save($project)) {
                $this->Flash->success(__('The project has been saved.'));

                return $this->redirect(['_name' => 'todoitems:upcoming']);
            }
            $this->Flash->error(__('The project could not be saved. Please, try again.'));
            $this->set('errors', $this->flattenErrors($project->getErrors()));
        }
        $referer = $this->referer(['_name' => 'todoitems:upcoming']);
        $this->set(compact('project', 'referer'));
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
    public function delete()
    {
        $this->request->allowMethod(['post', 'delete']);
        $project = $this->getProject();
        $this->Authorization->authorize($project);

        if ($this->Projects->delete($project)) {
            return $this->redirect(['_name' => 'todoitems:today']);
        }
        return $this->response->withStatus(400);
    }

    public function archive()
    {
        $slug = $this->request->getParam('slug');
        $project = $this->Projects->findBySlug($slug)->first();
        $this->Authorization->authorize($project);

        $project->archive();
        $this->Projects->save($project);
        return $this->redirect($this->referer(['action' => 'index']));
    }

    public function unarchive()
    {
        $slug = $this->request->getParam('slug');
        $project = $this->Projects->findBySlug($slug)->first();
        $this->Authorization->authorize($project, 'archive');

        $project->unarchive();
        $this->Projects->save($project);
        return $this->redirect($this->referer(['action' => 'index']));
    }

    public function reorder()
    {
        $projectIds = $this->request->getData('projects');
        if (!is_array($projectIds)) {
            throw new BadRequestException('Invalid project list.');
        }
        $projectIds = array_values($projectIds);
        $query = $this->Projects
            ->find('active')
            ->where(['Projects.id IN' => $projectIds]);
        $query = $this->Authorization->applyScope($query, 'index');

        $items = $query->toArray();
        if (count($items) != count($projectIds)) {
            throw new NotFoundException('Some of the requested items could not be found.');
        }
        $sorted = [];
        foreach ($items as $item) {
            $index = array_search($item->id, $projectIds);
            $sorted[$index] = $item;
        }
        ksort($sorted);
        $this->Projects->reorder($sorted);

        return $this->redirect($this->referer(['action' => 'index']));
    }
}

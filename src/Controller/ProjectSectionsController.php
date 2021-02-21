<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\View\JsonView;

/**
 * ProjectSections Controller
 *
 * @property \App\Model\Table\ProjectSectionsTable $ProjectSections
 * @method \App\Model\Entity\ProjectSection[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ProjectSectionsController extends AppController
{
    protected function getProject(string $slug)
    {
        return $this->ProjectSections->Projects->findBySlug($slug)->firstOrFail();
    }

    public function add(string $projectSlug)
    {
        $project = $this->getProject($projectSlug);
        $this->Authorization->authorize($project, 'edit');

        $projectSection = $this->ProjectSections->newEmptyEntity();
        $referer = $this->getReferer();

        $projectSection = $this->ProjectSections->patchEntity(
            $projectSection,
            ['project_id' => $project->id] + $this->request->getData()
        );
        $projectSection->ranking = $this->ProjectSections->getNextRanking($project->id);

        if ($this->ProjectSections->save($projectSection)) {
            $this->Flash->success(__('The section has been saved.'));

            return $this->redirect($referer);
        }

        $this->Flash->error(__('The section could not be saved. Please, try again.'));

        $this->set('errors', $this->flattenErrors($projectSection->getErrors()));
        $this->viewBuilder()
            ->setClassName(JsonView::class)
            ->setOption('serialize', ['errors']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Project Section id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $projectSection = $this->ProjectSections->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $projectSection = $this->ProjectSections->patchEntity($projectSection, $this->request->getData());
            if ($this->ProjectSections->save($projectSection)) {
                $this->Flash->success(__('The project section has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The project section could not be saved. Please, try again.'));
        }
        $projects = $this->ProjectSections->Projects->find('list', ['limit' => 200]);
        $this->set(compact('projectSection', 'projects'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Project Section id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $projectSection = $this->ProjectSections->get($id);
        if ($this->ProjectSections->delete($projectSection)) {
            $this->Flash->success(__('The project section has been deleted.'));
        } else {
            $this->Flash->error(__('The project section could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}

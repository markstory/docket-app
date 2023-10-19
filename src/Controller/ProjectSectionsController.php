<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Project;
use Cake\View\JsonView;
use InvalidArgumentException;

/**
 * ProjectSections Controller
 *
 * @property \App\Model\Table\ProjectSectionsTable $ProjectSections
 */
class ProjectSectionsController extends AppController
{
    protected function getProject(string $slug): Project
    {
        $query = $this->ProjectSections->Projects->findBySlug($slug);
        $query = $this->Authorization->applyScope($query, 'index');

        /** @var \App\Model\Entity\Project */
        return $query->firstOrFail();
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

    public function edit(string $projectSlug, $id = null)
    {
        $referer = $this->getReferer();
        $project = $this->getProject($projectSlug);
        $this->Authorization->authorize($project, 'edit');

        $projectSection = $this->ProjectSections->patchEntity(
            $this->ProjectSections->get($id),
            $this->request->getData()
        );

        // If the project has changed ensure the new project belongs
        // to the current user.
        if ($projectSection->isDirty('project_id')) {
            $project = $this->ProjectSections->Projects->get($projectSection->project_id);
            $this->Authorization->authorize($project, 'edit');
        }

        if ($this->ProjectSections->save($projectSection)) {
            $this->Flash->success(__('The project section has been saved.'));

            return $this->redirect($referer);
        }
        $this->Flash->error(__('The project section could not be saved. Please, try again.'));
        $this->set('errors', $this->flattenErrors($projectSection->getErrors()));
        $this->viewBuilder()
            ->setClassName(JsonView::class)
            ->setOption('serialize', ['errors']);
    }

    public function archive(string $projectSlug, string $id)
    {
        $project = $this->getProject($projectSlug);
        $this->Authorization->authorize($project, 'edit');

        $section = $this->ProjectSections->get($id);
        $section->archive();
        $this->ProjectSections->save($section);

        $this->Flash->success(__('Project Section archived'));

        return $this->redirect($this->referer(['_name' => 'projects:view', $projectSlug]));
    }

    public function unarchive(string $projectSlug, string $id)
    {
        $project = $this->getProject($projectSlug);
        $this->Authorization->authorize($project, 'edit');

        $section = $this->ProjectSections->get($id);
        $section->unarchive();
        $this->ProjectSections->save($section);

        $this->Flash->success(__('Project Section unarchived'));

        return $this->redirect($this->referer(['_name' => 'projects:view', $projectSlug]));
    }

    public function deleteConfirm(string $projectSlug, string $id)
    {
        // TODO implement this.
        throw new InvalidArgumentException('This view is not done');
    }

    public function delete(string $projectSlug, string $id)
    {
        $project = $this->getProject($projectSlug);
        $this->Authorization->authorize($project, 'edit');

        $projectSection = $this->ProjectSections->get($id);

        if ($this->ProjectSections->delete($projectSection)) {
            $this->Flash->success(__('The project section has been deleted.'));
        } else {
            $this->Flash->error(__('The project section could not be deleted. Please, try again.'));
        }

        return $this->redirect(['_name' => 'projects:view', $projectSlug]);
    }

    public function move(string $projectSlug, string $id)
    {
        $project = $this->getProject($projectSlug);
        $this->Authorization->authorize($project, 'edit');

        $projectSection = $this->ProjectSections->get($id);
        $operation = [
            'ranking' => $this->request->getData('ranking'),
        ];
        try {
            $this->ProjectSections->move($projectSection, $operation);
            $this->Flash->success(__('Project section reordered.'));
        } catch (InvalidArgumentException $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect($this->referer(['_name' => 'projects:view', $projectSlug]));
    }
}

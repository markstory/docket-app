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
    protected function useInertia()
    {
        return !in_array($this->request->getParam('action'), ['edit', 'view']);
    }

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

        $section = $this->ProjectSections->newEmptyEntity();
        $referer = $this->getReferer();

        $section = $this->ProjectSections->patchEntity(
            $section,
            ['project_id' => $project->id] + $this->request->getData()
        );
        $section->ranking = $this->ProjectSections->getNextRanking($project->id);

        if ($this->ProjectSections->save($section)) {
            $this->Flash->success(__('The section has been saved.'));

            return $this->redirect($referer);
        }

        $this->Flash->error(__('The section could not be saved. Please, try again.'));

        $this->set('errors', $this->flattenErrors($section->getErrors()));
        $this->viewBuilder()
            ->setClassName(JsonView::class)
            ->setOption('serialize', ['errors']);
    }

    /**
     * Used by html views to load reload a section when editing
     * is cancelled.
     */
    public function view(string $projectSlug, int $id)
    {
        $project = $this->getProject($projectSlug);
        $section = $this->ProjectSections->get($id);
        $this->Authorization->authorize($project, 'edit');

        $this->set('project', $project);
        $this->set('section', $section);
    }

    /**
     * Used by both html and JSON API to update a section.
     */
    public function edit(string $projectSlug, int $id)
    {
        $referer = $this->getReferer();
        $project = $this->getProject($projectSlug);
        $section = $this->ProjectSections->get($id);
        $this->Authorization->authorize($project, 'edit');

        if ($this->request->is(['post', 'put'])) {
            $section = $this->ProjectSections->patchEntity(
                $section,
                $this->request->getData()
            );

            $serialize = [];
            $redirect = null;
            $success = false;
            if ($this->ProjectSections->save($section)) {
                $redirect = $referer;
                $success = true;
            } else {
                $this->set('errors', $this->flattenErrors($section->getErrors()));
                $serialize[] = 'errors';
            }
            return $this->respond([
                'success' => $success,
                'serialize' => $serialize,
                'flashSuccess' => __('The section has been saved.'),
                'flashError' => __('The section could not be saved.'),
                'redirect' => $redirect,
            ]);
        }

        $this->set('section', $section);
        $this->set('project', $project);
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

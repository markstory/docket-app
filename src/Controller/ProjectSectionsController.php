<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Project;
use Cake\Http\Exception\NotFoundException;
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
        return !in_array($this->request->getParam('action'), ['edit', 'view', 'deleteConfirm', 'add', 'options']);
    }

    protected function getProject(string $slug): Project
    {
        $query = $this->ProjectSections->Projects->findBySlug($slug);
        $query = $this->Authorization->applyScope($query, 'index');

        /** @var \App\Model\Entity\Project */
        return $query->firstOrFail();
    }

    public function options()
    {
        $projectId = $this->request->getQuery('project_id');
        if (!$projectId) {
            throw new NotFoundException();
        }
        $project = $this->ProjectSections->Projects->get($projectId);
        $this->Authorization->authorize($project, 'view');

        $sections = $this->ProjectSections
            ->find()
            ->where(['ProjectSections.project_id' => $project->id])
            ->toArray();
        $this->set('sections', $sections);
        $this->set('value', $sections);
        $this->viewBuilder()->setLayout('ajax');
    }

    public function add(string $projectSlug)
    {
        $project = $this->getProject($projectSlug);
        $this->Authorization->authorize($project, 'edit');

        $section = $this->ProjectSections->newEmptyEntity();
        $referer = $this->getReferer();

        $this->set('referer', $referer);
        $this->set('section', $section);
        $this->set('project', $project);

        if ($this->request->is(['post', 'put'])) {
            $serialize = [];
            $redirect = null;
            $success = false;

            $section = $this->ProjectSections->patchEntity(
                $section,
                ['project_id' => $project->id] + $this->request->getData()
            );
            $section->ranking = $this->ProjectSections->getNextRanking($project->id);
            if ($this->ProjectSections->save($section)) {
                $success = true;
                $redirect = $referer;
            }
            $this->set('errors', $this->flattenErrors($section->getErrors()));

            return $this->respond([
                'success' => $success,
                'serialize' => $serialize,
                'redirect' => $redirect,
                'flashSuccess' => __('The section has been saved.'),
                'flashError' => __('The section could not be saved. Please, try again.'),
            ]);
        }
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

        $this->set('section', $section);
        $this->set('project', $project);

        if ($this->request->is(['post', 'put'])) {
            $serialize = [];
            $redirect = null;
            $success = false;

            $section = $this->ProjectSections->patchEntity(
                $section,
                $this->request->getData()
            );
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
    }

    public function deleteConfirm(string $projectSlug, string $id)
    {
        $project = $this->getProject($projectSlug);
        $section = $this->ProjectSections->get($id);

        $this->Authorization->authorize($project, 'delete');
        $this->set('project', $project);
        $this->set('section', $section);
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

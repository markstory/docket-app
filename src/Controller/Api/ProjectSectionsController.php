<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
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
    public function viewClasses(): array
    {
        return [JsonView::class];
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
        $serialize = [];
        $success = false;
        if ($this->request->is(['post', 'put'])) {
            $section = $this->ProjectSections->patchEntity(
                $section,
                ['project_id' => $project->id] + $this->request->getData()
            );
            $section->ranking = $this->ProjectSections->getNextRanking($project->id);
            if ($this->ProjectSections->save($section)) {
                $success = true;
                $serialize[] = 'section';
            } else {
                $this->set('errors', $this->flattenErrors($section->getErrors()));
                $serialize[] = 'errors';
            }
        }
        $this->set('section', $section);

        return $this->respond([
            'success' => $success,
            'serialize' => $serialize,
            'statusError' => 422,
        ]);
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

        $this->set('section', $section);

        return $this->respond([
            'success' => true,
            'serialize' => ['section'],
        ]);
    }

    /**
     * Used by both html and JSON API to update a section.
     */
    public function edit(string $projectSlug, int $id)
    {
        $project = $this->getProject($projectSlug);
        $section = $this->ProjectSections->get($id);
        $this->Authorization->authorize($project, 'edit');

        $this->set('section', $section);

        if ($this->request->is(['post', 'put'])) {
            $serialize = [];
            $success = false;

            $section = $this->ProjectSections->patchEntity(
                $section,
                $this->request->getData()
            );
            if ($this->ProjectSections->save($section)) {
                $success = true;
                $this->set('section', $section);
                $serialize[] = 'section';
            } else {
                $this->set('errors', $this->flattenErrors($section->getErrors()));
                $serialize[] = 'errors';
            }

            return $this->respond([
                'success' => $success,
                'serialize' => $serialize,
                'statusError' => 422,
            ]);
        }
    }

    public function delete(string $projectSlug, string $id)
    {
        $project = $this->getProject($projectSlug);
        $this->Authorization->authorize($project, 'edit');

        $projectSection = $this->ProjectSections->get($id);

        $success = false;
        $serialize = null;
        if ($this->ProjectSections->delete($projectSection)) {
            $success = true;
        } else {
            $serialize = ['errors'];
            $this->set('errors', $this->flattenErrors($projectSection->getErrors()));
        }

        return $this->respond([
            'success' => $success,
            'statusSuccess' => 204,
            'statusError' => 422,
            'serialize' => $serialize,
        ]);
    }

    public function move(string $projectSlug, string $id)
    {
        $project = $this->getProject($projectSlug);
        $this->Authorization->authorize($project, 'edit');

        $projectSection = $this->ProjectSections->get($id);
        $operation = [
            'ranking' => $this->request->getData('ranking'),
        ];
        $success = false;
        $serialize = [];
        try {
            $this->ProjectSections->move($projectSection, $operation);
            $success = true;
            $serialize[] = 'section';
            $this->set('section', $projectSection);
        } catch (InvalidArgumentException $e) {
            $serialize[] = 'errors';
            $this->set('errors', $this->flattenErrors($projectSection->getErrors()));
        }

        return $this->respond([
            'success' => $success,
            'serialize' => $serialize,
            'statusError' => 422,
        ]);
    }
}

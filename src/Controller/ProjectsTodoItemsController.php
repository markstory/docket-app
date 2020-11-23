<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * ProjectTodoItems Controller
 *
 * @property \App\Model\Table\ProjectsTable $Projects
 * @property \App\Model\Table\TodoItemsTable $TodoItems
 * @method \App\Model\Entity\TodoItem[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ProjectsTodoItemsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('Projects');
        $this->loadModel('TodoItems');
    }

    /**
     * View method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view()
    {
        $slug = $this->request->getParam('slug');
        $project = $this->Projects->findBySlug($slug)->first();
        $this->Authorization->can($project);

        $query = $this->Authorization
            ->applyScope($this->TodoItems->find(), 'index')
            ->contain('Projects')
            ->find('incomplete')
            ->find('forProject', ['slug' => $slug])
            ->orderAsc('TodoItems.due_on')
            ->orderAsc('TodoItems.child_order');

        $todoItems = $this->paginate($query);

        $this->set(compact('project', 'todoItems'));
    }
}

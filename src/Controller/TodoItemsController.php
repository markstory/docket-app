<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenDate;
use InvalidArgumentException;

/**
 * TodoItems Controller
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \App\Model\Table\TodoItemsTable $TodoItems
 * @method \App\Model\Entity\TodoItem[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TodoItemsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index(string $view = 'upcoming')
    {
        try {
            $start = new FrozenDate($this->request->getQuery('start', 'today'));
        } catch (\Exception $e) {
            throw new NotFoundException();
        }

        $query = $this->TodoItems
            ->find('incomplete')
            ->contain('Projects');

        $query = $this->Authorization->applyScope($query);
        if ($view === 'today') {
            $query = $query->find('dueToday');
            // Set view component to use.
            $this->set('component', 'TodoItems/Today');
        } else if ($view === 'upcoming') {
            $query = $query->find('upcoming', ['start' => $start]);
        }
        // $overdue = $this->TodoItems->find('overdue')->limit(25);
        $todoItems = $query->all();

        $this->set(compact('todoItems', 'view'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $todoItem = $this->TodoItems->newEmptyEntity();

        if ($this->request->is('post')) {
            $todoItem = $this->TodoItems->patchEntity($todoItem, $this->request->getData());

            $project = $this->TodoItems->Projects->get($todoItem->project_id);
            $this->Authorization->authorize($project, 'edit');

            if ($this->TodoItems->save($todoItem)) {
                $this->Flash->success(__('The todo item has been saved.'));

                return $this->redirect($this->referer(['action' => 'index']));
            }
            // TODO this doesn't look like it will handle validation
            // errors well.
            $this->Flash->error(__('The todo item could not be saved. Please, try again.'));
        }
    }

    /**
     * Complete a todoitem as complete.
     *
     * @param string|null $id Todo Item id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function complete($id = null)
    {
        $todoItem = $this->TodoItems->get($id, [
            'contain' => ['Projects'],
        ]);
        $this->Authorization->can($todoItem, 'edit');

        if ($this->request->is(['patch', 'post', 'put'])) {
            $todoItem->complete();
            if (!$this->TodoItems->save($todoItem)) {
                $this->Flash->error(__('The todo item could not be saved. Please, try again.'));
            }
        }
        return $this->redirect($this->referer(['action' => 'index']));
    }

    /**
     * Complete a todoitem as incomplete.
     *
     * @param string|null $id Todo Item id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function incomplete($id = null)
    {
        $todoItem = $this->TodoItems->get($id, [
            'contain' => ['Projects'],
        ]);
        $this->Authorization->can($todoItem, 'edit');

        if ($this->request->is(['patch', 'post', 'put'])) {
            $todoItem->incomplete();
            if (!$this->TodoItems->save($todoItem)) {
                $this->Flash->error(__('The todo item could not be saved. Please, try again.'));
            }
        }
        return $this->redirect($this->referer(['action' => 'index']));
    }

    public function move(string $id)
    {
        $this->request->allowMethod(['post']);
        $todoItem = $this->TodoItems->get($id, ['contain' => ['Projects']]);
        $this->Authorization->authorize($todoItem, 'edit');
        $operation = [
            'child_order' => $this->request->getData('child_order'),
            'day_order' => $this->request->getData('day_order'),
            'due_on' => $this->request->getData('due_on')
        ];
        try {
            $this->TodoItems->move($todoItem, $operation);
        } catch (InvalidArgumentException $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect($this->referer(['action' => 'index']));
    }

    /**
     * Called as an XHR request from the view page.
     *
     * @param string|null $id Todo Item id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $todoItem = $this->TodoItems->get($id, [
            'contain' => ['TodoLabels', 'Projects'],
        ]);
        $this->Authorization->authorize($todoItem);

        $todoItem = $this->TodoItems->patchEntity($todoItem, $this->request->getData());
        if ($this->TodoItems->save($todoItem)) {
            return $this->response->withStatus(200);
        }
        return $this->validationErrorResponse($todoItem->getErrors());
    }

    /**
     * View method
     *
     * @param string|null $id Todo Item id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $todoItem = $this->TodoItems->get($id, [
            'contain' => ['Projects', 'TodoLabels', 'TodoComments', 'TodoSubtasks'],
        ]);
        $this->Authorization->authorize($todoItem);

        $this->set(compact('todoItem'));
        $this->set('referer', $this->referer(['action' => 'index']));
    }

    /**
     * Delete method
     *
     * @param string|null $id Todo Item id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $todoItem = $this->TodoItems->get($id, ['contain' => ['Projects']]);
        $this->Authorization->authorize($todoItem);

        if ($this->TodoItems->delete($todoItem)) {
            $this->Flash->success(__('The todo item has been deleted.'));
        } else {
            $this->Flash->error(__('The todo item could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}

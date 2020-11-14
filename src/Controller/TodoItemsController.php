<?php
declare(strict_types=1);

namespace App\Controller;

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
    public function index(string $view = null)
    {
        $query = $this->TodoItems
            ->find('incomplete')
            ->contain('Projects')
            ->orderAsc('TodoItems.due_on')
            ->orderDesc('TodoItems.ranking');

        $query = $this->Authorization->applyScope($query);
        if ($view && in_array($view, ['today'], true)) {
            $query = $query->find('dueToday');
            // Set view component to use.
            $this->set('component', 'TodoItems/Today');
        }

        $todoItems = $this->paginate($query);

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
        $this->Authorization->can($todoItem);

        if ($this->request->is('post')) {
            $todoItem = $this->TodoItems->patchEntity($todoItem, $this->request->getData());
            if ($this->TodoItems->save($todoItem)) {
                $this->Flash->success(__('The todo item has been saved.'));

                return $this->redirect($this->referer(['action' => 'index']));
            }
            $this->Flash->error(__('The todo item could not be saved. Please, try again.'));
        }
        $projects = $this->TodoItems->Projects->find('list', ['limit' => 200]);
        $todoLabels = $this->TodoItems->TodoLabels->find('list', ['limit' => 200]);
        $this->set(compact('todoItem', 'projects', 'todoLabels'));
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
        return $this->redirect(['action' => 'index']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Todo Item id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $todoItem = $this->TodoItems->get($id, [
            'contain' => ['TodoLabels', 'Projects'],
        ]);
        $this->Authorization->can($todoItem);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $todoItem = $this->TodoItems->patchEntity($todoItem, $this->request->getData());
            if ($this->TodoItems->save($todoItem)) {
                $this->Flash->success(__('The todo item has been saved.'));
            } else {
                $this->Flash->error(__('The todo item could not be saved. Please, try again.'));
            }
        }
        return $this->redirect(['action' => 'index']);
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

        $this->set(compact('todoItem'));
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
        $this->Authorization->can($todoItem);

        if ($this->TodoItems->delete($todoItem)) {
            $this->Flash->success(__('The todo item has been deleted.'));
        } else {
            $this->Flash->error(__('The todo item could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}

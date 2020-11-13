<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * TodoItems Controller
 *
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
    public function index()
    {
        $query = $this->TodoItems->find()->contain('Projects');
        $query = $this->Authorization->applyScope($query);

        $todoItems = $this->paginate($query);

        $this->set(compact('todoItems'));
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

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The todo item could not be saved. Please, try again.'));
        }
        $projects = $this->TodoItems->Projects->find('list', ['limit' => 200]);
        $todoLabels = $this->TodoItems->TodoLabels->find('list', ['limit' => 200]);
        $this->set(compact('todoItem', 'projects', 'todoLabels'));
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

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The todo item could not be saved. Please, try again.'));
        }
        $projects = $this->TodoItems->Projects->find('list', ['limit' => 200]);
        $todoLabels = $this->TodoItems->TodoLabels->find('list', ['limit' => 200]);
        $this->set(compact('todoItem', 'projects', 'todoLabels'));
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

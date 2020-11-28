<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenDate;

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
            ->contain('Projects')
            ->orderAsc('TodoItems.due_on')
            ->orderAsc('TodoItems.day_order');

        $query = $this->Authorization->applyScope($query);
        if ($view === 'today') {
            $query = $query->find('dueToday');
            // Set view component to use.
            $this->set('component', 'TodoItems/Today');
        } else if ($view === 'upcoming') {
            $query = $query->find('upcoming', ['start' => $start]);
        }
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
        return $this->redirect($this->referer(['action' => 'index']));
    }

    /**
     * Reorder a set of items.
     */
    public function reorder()
    {
        $scope = $this->request->getData('scope');
        if (!in_array($scope, ['day', 'child'])) {
            throw new BadRequestException('Invalid scope parameter');
        }
        $itemIds = $this->request->getData('items');
        if (!is_array($itemIds)) {
            throw new BadRequestException('Invalid item list.');
        }
        $itemIds = array_values($itemIds);
        $query = $this->TodoItems
            ->find('incomplete')
            ->where(['TodoItems.id IN' => $itemIds]);
        $query = $this->Authorization->applyScope($query, 'index');

        $items = $query->toArray();
        if (count($items) != count($itemIds)) {
            throw new NotFoundException('Some of the requested items could not be found.');
        }
        $sorted = [];
        foreach ($items as $item) {
            $index = array_search($item->id, $itemIds);
            $sorted[$index] = $item;
        }
        ksort($sorted);
        $this->TodoItems->reorder($scope, $sorted);

        return $this->redirect($this->referer(['action' => 'index']));
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
        return $this->redirect($this->referer(['action' => 'index']));
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

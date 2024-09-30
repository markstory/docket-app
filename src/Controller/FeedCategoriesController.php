<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * FeedCategories Controller
 *
 * @property \App\Model\Table\FeedCategoriesTable $FeedCategories
 */
class FeedCategoriesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->FeedCategories->find()
            ->contain(['Users']);
        $query = $this->Authorization->applyScope($query);
        $feedCategories = $this->paginate($query);

        $this->set(compact('feedCategories'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $feedCategory = $this->FeedCategories->newEmptyEntity();
        if ($this->request->is('post')) {
            $feedCategory = $this->FeedCategories->patchEntity($feedCategory, $this->request->getData());
            $feedCategory->user_id = $this->request->getAttribute('identity')->getIdentifier();
            $this->Authorization->authorize($feedCategory);
            if ($this->FeedCategories->save($feedCategory)) {
                $this->Flash->success(__('Feed category saved.'));

                return $this->redirect(['_name' => 'feedsubscriptions:index']);
            }
            $this->Flash->error(__('Feed category could not be saved. Please try again.'));
        }
        $this->Authorization->authorize($feedCategory);

        $referer = $this->request->referer();
        $this->set(compact('feedCategory', 'referer'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Feed Category id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $feedCategory = $this->FeedCategories->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $feedCategory = $this->FeedCategories->patchEntity($feedCategory, $this->request->getData());
            $this->Authorization->authorize($feedCategory);
            if ($this->FeedCategories->save($feedCategory)) {
                $this->Flash->success(__('Feed category saved.'));

                return $this->redirect(['_name' => 'feedsubscriptions:index']);
            }
            $this->Flash->error(__('Feed category could not be saved. Please try again.'));
        }
        $this->Authorization->authorize($feedCategory);

        $referer = $this->request->referer();
        $this->set(compact('feedCategory', 'referer'));
    }

    /**
     * Delete confirmation
     *
     * @param string|null $id Feed Category id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function deleteConfirm($id = null)
    {
        $feedCategory = $this->FeedCategories->get($id);
        $this->Authorization->authorize($feedCategory, 'delete');

        $this->set('feedCategory', $feedCategory);
    }

    /**
     * Delete method
     *
     * @param string|null $id Feed Category id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $feedCategory = $this->FeedCategories->get($id);
        $this->Authorization->authorize($feedCategory);
        if ($this->FeedCategories->delete($feedCategory)) {
            $this->Flash->success(__('Feed category has been deleted.'));
        } else {
            $this->Flash->error(__('Feed category could not be deleted. Please, try again.'));
        }

        return $this->redirect(['_name' => 'feedsubscriptions:index']);
    }
}

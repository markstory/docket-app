<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * FeedSubscriptions Controller
 *
 * @property \App\Model\Table\FeedSubscriptionsTable $FeedSubscriptions
 */
class FeedSubscriptionsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->FeedSubscriptions->find()
            ->contain(['Feeds', 'Users', 'FeedCategories']);
        $feedSubscriptions = $this->paginate($query);

        $this->set(compact('feedSubscriptions'));
    }

    /**
     * View method
     *
     * @param string|null $id Feed Subscription id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $feedSubscription = $this->FeedSubscriptions->get($id, contain: ['Feeds', 'Users', 'FeedCategories', 'FeedItems', 'SavedFeedItems']);
        $this->set(compact('feedSubscription'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $feedSubscription = $this->FeedSubscriptions->newEmptyEntity();
        if ($this->request->is('post')) {
            $feedSubscription = $this->FeedSubscriptions->patchEntity($feedSubscription, $this->request->getData());
            if ($this->FeedSubscriptions->save($feedSubscription)) {
                $this->Flash->success(__('The feed subscription has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The feed subscription could not be saved. Please, try again.'));
        }
        $feeds = $this->FeedSubscriptions->Feeds->find('list', limit: 200)->all();
        $users = $this->FeedSubscriptions->Users->find('list', limit: 200)->all();
        $feedCategories = $this->FeedSubscriptions->FeedCategories->find('list', limit: 200)->all();
        $feedItems = $this->FeedSubscriptions->FeedItems->find('list', limit: 200)->all();
        $this->set(compact('feedSubscription', 'feeds', 'users', 'feedCategories', 'feedItems'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Feed Subscription id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $feedSubscription = $this->FeedSubscriptions->get($id, contain: ['FeedItems']);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $feedSubscription = $this->FeedSubscriptions->patchEntity($feedSubscription, $this->request->getData());
            if ($this->FeedSubscriptions->save($feedSubscription)) {
                $this->Flash->success(__('The feed subscription has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The feed subscription could not be saved. Please, try again.'));
        }
        $feeds = $this->FeedSubscriptions->Feeds->find('list', limit: 200)->all();
        $users = $this->FeedSubscriptions->Users->find('list', limit: 200)->all();
        $feedCategories = $this->FeedSubscriptions->FeedCategories->find('list', limit: 200)->all();
        $feedItems = $this->FeedSubscriptions->FeedItems->find('list', limit: 200)->all();
        $this->set(compact('feedSubscription', 'feeds', 'users', 'feedCategories', 'feedItems'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Feed Subscription id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $feedSubscription = $this->FeedSubscriptions->get($id);
        if ($this->FeedSubscriptions->delete($feedSubscription)) {
            $this->Flash->success(__('The feed subscription has been deleted.'));
        } else {
            $this->Flash->error(__('The feed subscription could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}

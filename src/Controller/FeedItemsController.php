<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * FeedItems Controller
 *
 * @property \App\Model\Table\FeedItemsTable $FeedItems
 */
class FeedItemsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->FeedItems->find()
            ->contain(['Feeds']);
        $feedItems = $this->paginate($query);

        $this->set(compact('feedItems'));
    }

    /**
     * View method
     *
     * @param string|null $subscription SubscriptionFeed
     * @param string|null $id Feed Item id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($feedId, $id)
    {
        $identity = $this->Authentication->getIdentity();
        $feedItem = $this->FeedItems->find(
            'forFeed',
            feedId: $feedId,
            userId: $identity->getIdentifier(),
            id: $id,
        )->firstOrFail();
        $this->Authorization->authorize($feedItem);

        $this->set(compact('feedItem'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $feedItem = $this->FeedItems->newEmptyEntity();
        if ($this->request->is('post')) {
            $feedItem = $this->FeedItems->patchEntity($feedItem, $this->request->getData());
            if ($this->FeedItems->save($feedItem)) {
                $this->Flash->success(__('The feed item has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The feed item could not be saved. Please, try again.'));
        }
        $feeds = $this->FeedItems->Feeds->find('list', limit: 200)->all();
        $feedSubscriptions = $this->FeedItems->FeedSubscriptions->find('list', limit: 200)->all();
        $this->set(compact('feedItem', 'feeds', 'feedSubscriptions'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Feed Item id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $feedItem = $this->FeedItems->get($id, contain: ['FeedSubscriptions']);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $feedItem = $this->FeedItems->patchEntity($feedItem, $this->request->getData());
            if ($this->FeedItems->save($feedItem)) {
                $this->Flash->success(__('The feed item has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The feed item could not be saved. Please, try again.'));
        }
        $feeds = $this->FeedItems->Feeds->find('list', limit: 200)->all();
        $feedSubscriptions = $this->FeedItems->FeedSubscriptions->find('list', limit: 200)->all();
        $this->set(compact('feedItem', 'feeds', 'feedSubscriptions'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Feed Item id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $feedItem = $this->FeedItems->get($id);
        if ($this->FeedItems->delete($feedItem)) {
            $this->Flash->success(__('The feed item has been deleted.'));
        } else {
            $this->Flash->error(__('The feed item could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}

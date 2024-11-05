<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\FeedSubscriptionsTable;
use App\Service\FeedService;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\DateTime;

/**
 * FeedSubscriptions Controller
 *
 * @property \App\Model\Table\FeedSubscriptionsTable $FeedSubscriptions
 * @property \App\Model\Table\FeedItemsTable $FeedItems
 */
class FeedSubscriptionsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->FeedItems = $this->fetchTable('FeedItems');
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->FeedSubscriptions->find()
            ->contain(['FeedCategories']);
        $query = $this->Authorization->applyScope($query);
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
        $feedSubscription = $this->FeedSubscriptions->get($id, contain: FeedSubscriptionsTable::VIEW_CONTAIN);
        $this->Authorization->authorize($feedSubscription);

        $feedItems = $this->FeedItems->find(
            'forSubscription',
            subscription: $feedSubscription,
        );
        $feedItems = $this->paginate($feedItems);

        $this->set(compact('feedSubscription', 'feedItems'));
    }

    /**
     * View item method
     *
     * @param int $id Feed Subscription id.
     * @param int $itemId Feed Item id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function viewItem(int $id, int $itemId)
    {
        $feedSubscription = $this->FeedSubscriptions->get($id);
        $this->Authorization->authorize($feedSubscription, 'view');
        $identity = $this->Authentication->getIdentity();

        $feedItem = $this->FeedItems
            ->findById($itemId)
            ->find('forSubscription', subscription: $feedSubscription)
            ->firstOrFail();

        $this->FeedSubscriptions->FeedItems->markRead(
            $feedSubscription->user_id,
            $feedItem
        );
        $this->set(compact('feedItem'));
    }

    /**
     * Bulk read endpoint
     *
     * Mark a list of items as read.
     */
    public function itemsMarkRead(int $id)
    {
        $this->request->allowMethod(['POST']);
        $feedSubscription = $this->FeedSubscriptions->get($id);

        // This is view because viewItem is as well
        $this->Authorization->authorize($feedSubscription, 'view');

        $query = $this->FeedItems->find(
            'forSubscription',
            subscription: $feedSubscription,
        );
        $ids = (array)$this->request->getData('id');
        // TODO more validation
        if (count($ids) >= 100) {
            throw new BadRequestException('Too many ids provided. Max is 100');
        }
        if (!$ids) {
            throw new BadRequestException('Missing required parameter id');
        }

        // Scope the ids to those in the subscription
        $query = $query
            ->select(['FeedItems.id'])
            ->where(['FeedItems.id IN' => $ids]);

        $allowedIds = $query->all()->extract('id')->toList();
        if (count($allowedIds) !== count($ids)) {
            throw new BadRequestException('Invalid records requested');
        }

        $this->FeedItems->markManyRead($feedSubscription->user_id, $allowedIds);

        $this->redirect($this->referer());
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
            $feedSubscription->feed = $this->FeedSubscriptions->Feeds->findByUrlOrNew($this->request->getData('url'));
            $feedSubscription->user_id = $this->request->getAttribute('identity')->getIdentifier();
            $feedSubscription->ranking = $this->FeedSubscriptions->getNextRanking($feedSubscription->feed_category_id);

            $this->Authorization->authorize($feedSubscription);
            if ($this->FeedSubscriptions->save($feedSubscription)) {
                $this->Flash->success(__('Feed subscription added'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The feed subscription could not be saved. Please, try again.'));
        }
        $feedSubscription->user_id = $this->request->getAttribute('identity')->getIdentifier();

        $this->Authorization->authorize($feedSubscription);
        $referer = $this->request->referer();
        $feedCategories = $this->FeedSubscriptions->FeedCategories->find('list', limit: 200)->all();
        $this->set(compact('feedSubscription', 'feedCategories', 'referer'));
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
        $feedSubscription = $this->FeedSubscriptions->get($id, contain: FeedSubscriptionsTable::VIEW_CONTAIN);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $this->Authorization->authorize($feedSubscription);
            $feedSubscription = $this->FeedSubscriptions->patchEntity($feedSubscription, $this->request->getData());
            if ($this->FeedSubscriptions->save($feedSubscription)) {
                $this->Flash->success(__('The feed subscription has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The feed subscription could not be saved. Please, try again.'));
        }
        $feedCategories = $this->FeedSubscriptions->FeedCategories->find('list', limit: 200)->all();
        $this->Authorization->authorize($feedSubscription);
        $this->set(compact('feedSubscription', 'feedCategories'));
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
        $this->Authorization->authorize($feedSubscription);
        if ($this->FeedSubscriptions->delete($feedSubscription)) {
            $this->Flash->success(__('The feed subscription has been deleted.'));
        } else {
            $this->Flash->error(__('The feed subscription could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function deleteConfirm($id = null)
    {
        $feedSubscription = $this->FeedSubscriptions->get($id);
        $this->Authorization->authorize($feedSubscription, 'delete');

        $this->set('feedCategory', $feedSubscription);
    }

    public function sync($id, FeedService $feedService)
    {
        // TODO add rate-limit/abuse
        $subscription = $this->FeedSubscriptions->get($id, contain: ['Feeds']);
        $this->Authorization->authorize($subscription, 'view');

        $feedService->refreshFeed($subscription->feed);

        $this->Flash->success(__('Feed refresh complete'));
        $this->redirect(['action' => 'view', 'id' => $id]);
    }
}

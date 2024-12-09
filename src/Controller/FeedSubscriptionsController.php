<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\FeedCategoriesTable;
use App\Model\Table\FeedItemsTable;
use App\Model\Table\FeedSubscriptionsTable;
use App\Service\FeedService;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\DateTime;
use Laminas\Diactoros\Exception\InvalidArgumentException as DiactorosInvalidArgumentException;

/**
 * FeedSubscriptions Controller
 *
 * @property \App\Model\Table\FeedSubscriptionsTable $FeedSubscriptions
 * @property \App\Model\Table\FeedItemsTable $FeedItems
 * @property \App\Model\Table\FeedCategoriesTable $FeedCategories
 */
class FeedSubscriptionsController extends AppController
{
    protected FeedCategoriesTable $FeedCategories;
    protected FeedItemsTable $FeedItems;

    public function initialize(): void
    {
        parent::initialize();
        $this->FeedItems = $this->fetchTable('FeedItems');
        $this->FeedCategories = $this->fetchTable('FeedCategories');
    }

    /**
     * Home page for feeds.
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function home()
    {
        $query = $this->FeedSubscriptions->find()
            ->select(['id'])
            ->limit(500);
        $query = $this->Authorization->applyScope($query, 'index');
        $subIds = $query->all()->extract('id')->toList();

        $identity = $this->Authentication->getIdentity();
        $feedItems = $this->FeedSubscriptions->FeedItems->find(
            'subscribed',
            userId: $identity->id,
            feedSubscriptionIds: $subIds
        );
        $feedItems = $this->paginate($feedItems);

        $this->set(compact('feedItems'));
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
     * Mark an item as read and visit the link
     *
     * @param int $id Feed Subscription id.
     * @param int $itemId Feed Item id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function readVisit(int $id, int $itemId)
    {
        $feedSubscription = $this->FeedSubscriptions->get($id, contain: FeedSubscriptionsTable::VIEW_CONTAIN);
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
        $this->FeedSubscriptions->updateUnreadItemCount($feedSubscription);
        $this->FeedSubscriptions->FeedCategories->updateUnreadItemCount($feedSubscription->feed_category);

        // This is a semi-open redirect.
        // But we're just link stealing
        return $this->redirect($feedItem->url);
    }

    /**
     * Bulk read endpoint
     *
     * Mark a list of items as read.
     */
    public function itemsMarkRead(int $id)
    {
        $this->request->allowMethod(['POST']);
        $feedSubscription = $this->FeedSubscriptions->get($id, contain: FeedSubscriptionsTable::VIEW_CONTAIN);

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

        $this->FeedSubscriptions->updateUnreadItemCount($feedSubscription);
        $this->FeedSubscriptions->FeedCategories->updateUnreadItemCount($feedSubscription->feed_category);

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
            if (!$feedSubscription->feed->favicon_url) {
                // TODO: This could be abused in shared instances. Might need to fix this later
                $feedSubscription->feed->favicon_url = $this->request->getData('favicon_url');
            }
            $feedSubscription->user_id = $this->request->getAttribute('identity')->getIdentifier();
            $feedSubscription->ranking = $this->FeedSubscriptions->getNextRanking($feedSubscription->feed_category_id);

            $this->Authorization->authorize($feedSubscription);
            if ($this->FeedSubscriptions->save($feedSubscription, ['associated' => ['Feeds']])) {
                $this->Flash->success(__('Feed subscription added'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The feed subscription could not be saved. Please, try again.'));
        }
        $feedSubscription->user_id = $this->request->getAttribute('identity')->getIdentifier();

        $this->Authorization->authorize($feedSubscription);
        $referer = $this->request->referer();

        $categoriesTable = $this->fetchTable('FeedCategories');
        $query = $categoriesTable->find('list', limit: 200);
        $feedCategories = $this->Authorization->applyScope($query)->all();
        $this->set(compact('feedSubscription', 'feedCategories', 'referer'));
    }

    public function discover(FeedService $feedService)
    {
        // Validate add permission with a throw away record
        $feedSubscription = $this->FeedSubscriptions->newEmptyEntity();
        $feedSubscription->user_id = (int)$this->Authentication->getIdentifier();
        $this->Authorization->authorize($feedSubscription, 'add');

        $error = '';
        $feeds = [];
        if ($this->request->is('post')) {
            try {
                $feeds = $feedService->discoverFeeds($this->request->getData('url'));
            } catch (DiactorosInvalidArgumentException $e) {
                $error = $e->getMessage();
                $this->Flash->error($error);
            }
        }
        $query = $this->FeedCategories->find('list', limit: 200);
        $feedCategories = $this->Authorization->applyScope($query, 'index');
        $referer = $this->request->referer();

        $this->set(compact('error', 'feeds', 'feedCategories', 'referer'));
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

        $this->set('feedSubscription', $feedSubscription);
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

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
            'feedItem',
            feedId: $feedId,
            userId: $identity->getIdentifier(),
            id: $id,
        )->firstOrFail();
        $this->Authorization->authorize($feedItem);

        $this->set(compact('feedItem'));
    }
}

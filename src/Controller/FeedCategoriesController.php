<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;

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
    public function index(): Response|null|null
    {
        $query = $this->FeedCategories->find()
            ->contain(['Users']);
        $query = $this->Authorization->applyScope($query);
        $feedCategories = $this->paginate($query);

        $this->set(compact('feedCategories'));
    }

    public function view($id = null): void
    {
        // TODO add slug to feedcategory and use it
        $feedCategory = $this->FeedCategories->get($id);
        $this->Authorization->authorize($feedCategory);

        $itemsTable = $this->fetchTable('FeedItems');
        $itemsQuery = $itemsTable->find('forCategory', category: $feedCategory);
        $result = $this->paginate($itemsQuery);

        $this->set('feedCategory', $feedCategory);
        $this->set('feedItems', $result);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add(): Response|null|null
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
    public function edit(?string $id = null): Response|null|null
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
    public function deleteConfirm(?string $id = null): ?Response
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
    public function delete(?string $id = null): ?Response
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

    /**
     * Reorder feed categories for a user
     */
    public function reorder(): void
    {
        $this->request->allowMethod(['post']);
        $ids = (array)$this->request->getData('id');

        $query = $this->FeedCategories
            ->find()
            ->where(['FeedCategories.id IN' => $ids]);
        $query = $this->Authorization->applyScope($query, 'index');
        $categories = $query->all();
        if (count($categories) != count($ids)) {
            throw new BadRequestException('Invalid id values provided');
        }
        $this->FeedCategories->reorder($ids);
    }

    public function toggleExpanded(int $id): void
    {
        $this->request->allowMethod(['post', 'delete']);
        $feedCategory = $this->FeedCategories->get($id);
        $this->Authorization->authorize($feedCategory, 'edit');

        $feedCategory->expanded = !$feedCategory->expanded;

        $this->FeedCategories->saveOrFail($feedCategory);
    }
}

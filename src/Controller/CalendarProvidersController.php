<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * CalendarProviders Controller
 *
 * @property \App\Model\Table\CalendarProvidersTable $CalendarProviders
 */
class CalendarProvidersController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->CalendarProviders->find();
        $query = $this->Authorization->applyScope($query);
        $calendarProviders = $this->paginate($query);
        $referer = $this->getReferer('tasks:today');

        $this->set(compact('calendarProviders', 'referer'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Calendar Provider id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $calendarProvider = $this->CalendarProviders->get($id);
        $this->Authorization->authorize($calendarProvider);

        if ($this->CalendarProviders->delete($calendarProvider)) {
            $this->Flash->success(__('The calendar account has been deleted.'));
        } else {
            $this->Flash->error(__('The calendar account could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}

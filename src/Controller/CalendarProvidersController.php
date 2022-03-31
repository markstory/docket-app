<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CalendarService;

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
    public function index(CalendarService $service)
    {
        $query = $this->CalendarProviders->find()->contain('CalendarSources');
        $query = $this->Authorization->applyScope($query);
        $calendarProviders = $this->paginate($query)->toArray();
        $referer = $this->getReferer('tasks:today');

        $calendars = [];
        $activeProvider = null;
        // TODO add GET query support.
        if (!empty($calendarProviders)) {
            $activeProvider = $calendarProviders[0];
            $service->setAccessToken($activeProvider);
            $calendars = $service->listUnlinkedCalendars($activeProvider->calendar_sources);
        }
        $this->set('unlinked', $calendars);

        $this->set(compact('activeProvider', 'calendarProviders', 'referer'));
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

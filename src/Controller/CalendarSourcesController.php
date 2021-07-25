<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\CalendarSource;
use App\Service\CalendarService;

/**
 * CalendarSources Controller
 *
 * @property \App\Model\Table\CalendarSourcesTable $CalendarSources
 * @method \App\Model\Entity\CalendarSource[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CalendarSourcesController extends AppController
{
    protected function getSource(): CalendarSource
    {
        $query = $this->CalendarSources
            ->find()
            ->contain('CalendarProviders')
            ->where([
                // User id condition is applied with an authorization check.
                'CalendarSources.calendar_provider_id' => $this->request->getParam('providerId'),
                'CalendarSources.id' => $this->request->getParam('id'),
            ]);

        return $query->firstOrFail();
    }

    /**
     * Add method
     *
     * @param string|null $providerId Calendar Provider id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function add(CalendarService $service, $providerId = null)
    {
        $provider = $this->CalendarSources->CalendarProviders->get($providerId, [
            'contain' => ['CalendarSources'],
        ]);
        $this->Authorization->authorize($provider, 'edit');
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['calendar_provider_id'] = $providerId;

            $source = $this->CalendarSources->newEntity($data);
            if ($this->CalendarSources->save($source)) {
                $service->createSubscription($source);

                $this->Flash->success(__('Your calendar will now be automatically synced.'));
                return $this->redirect(['_name' => 'calendarsources:add', 'providerId' => $providerId]);
            } else {
                $this->Flash->error(__('Could not add that calendar.'));
            }
        }
        $service->setAccessToken($provider);
        $calendars = $service->listUnlinkedCalendars($provider->calendar_sources);

        $this->set('calendarProvider', $provider);
        $this->set('unlinked', $calendars);
        $this->set('referer', $this->getReferer('tasks:today'));
    }

    public function sync(CalendarService $service)
    {
        $source = $this->getSource();
        $this->Authorization->authorize($source->calendar_provider, 'sync');

        $service->setAccessToken($source->calendar_provider);
        try {
            $service->syncEvents($source);
            $this->Flash->success(__('Calendar events refreshed'));
        } catch (\Exception $e) {
            $this->Flash->error(__('Calendar could not be refreshed.'));
        }

        return $this->redirect([
            'action' => 'add',
            'providerId' => $this->request->getParam('providerId'),
            '?' => [
                'referer' => $this->request->getData('referer'),
            ],
        ]);
    }

    /**
     * Edit method
     *
     * @param string|null $id Calendar Source id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit()
    {
        // This might only need to update the color?
        $calendarSource = $this->getSource();
        $this->Authorization->authorize($calendarSource->calendar_provider);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $calendarSource = $this->CalendarSources->patchEntity($calendarSource, $this->request->getData(), [
                'fields' => ['color', 'name'],
            ]);
            if ($this->CalendarSources->save($calendarSource)) {
                $this->Flash->success(__('The calendar has been updated.'));
            } else {
                $this->Flash->error(__('The calendar could not be modified. Please, try again.'));
            }
        }

        return $this->redirect([
            'action' => 'add',
            'providerId' => $this->request->getParam('providerId'),
        ]);
    }

    /**
     * Delete method
     *
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete()
    {
        $this->request->allowMethod(['post', 'delete']);
        $calendarSource = $this->getSource();
        $this->Authorization->authorize($calendarSource->calendar_provider);

        if ($this->CalendarSources->delete($calendarSource)) {
            $this->Flash->success(__('The calendar source has been deleted.'));
        } else {
            $this->Flash->error(__('The calendar source could not be deleted. Please, try again.'));
        }

        return $this->redirect([
            'action' => 'add',
            'providerId' => $this->request->getParam('providerId')
        ]);
    }
}

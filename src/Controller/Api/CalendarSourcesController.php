<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use App\Model\Entity\CalendarSource;
use App\Service\CalendarService;
use Cake\View\JsonView;
use RuntimeException;

/**
 * CalendarSources Controller
 *
 * @property \App\Model\Table\CalendarSourcesTable $CalendarSources
 */
class CalendarSourcesController extends AppController
{
    public function viewClasses(): array
    {
        return [JsonView::class];
    }

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

        /** @var \App\Model\Entity\CalendarSource */
        return $query->firstOrFail();
    }

    protected function urlToProvider($providerId = null)
    {
        $providerId = $providerId ?? $this->request->getParam('providerId');

        return [
            '_name' => 'calendarproviders:index',
            '?' => [
                'provider' => $providerId,
            ],
        ];
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function add(CalendarService $service, $providerId = null)
    {
        $provider = $this->CalendarSources->CalendarProviders->get($providerId, [
            'contain' => ['CalendarSources'],
        ]);
        $this->Authorization->authorize($provider, 'edit');
        $serialize = [];
        $success = false;
        $error = '';

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['calendar_provider_id'] = $providerId;

            $source = $this->CalendarSources->newEntity($data);
            if ($this->CalendarSources->save($source)) {
                $serialize = ['source'];
                $this->set('source', $source);

                $service->setAccessToken($provider);
                try {
                    $service->createSubscription($source);
                    $success = true;
                } catch (RuntimeException $e) {
                    $error = __('Your calendar was added but will not automatically synchronize.');
                }
            } else {
                $error = __('Could not add that calendar.');
            }
        }
        if ($error) {
            $serialize[] = 'error';
            $this->set('error', $error);
        }

        return $this->respond([
            'success' => $success,
            'flashSuccess' => __('Your calendar was added and will be synced.'),
            'flashError' => $error,
            'serialize' => $serialize,
            'redirect' => $this->urlToProvider($provider->id),
        ]);
    }

    public function sync(CalendarService $service)
    {
        $source = $this->getSource();
        $this->Authorization->authorize($source->calendar_provider, 'sync');

        // Force a resync and clear the local database. This could result in
        // a blank calendar list should the sync fail. While I could design around
        // this, I want to see if it happens first.
        $source->sync_token = null;
        $this->CalendarSources->CalendarItems->deleteAll(['calendar_source_id' => $source->id]);

        $service->setAccessToken($source->calendar_provider);
        $success = true;
        $serialize = [];
        try {
            $service->syncEvents($source);
            $this->set('source', $source);
            $serialize[] = 'source';
        } catch (\Exception $e) {
            debug('error');
            $success = false;
            $error = __('Calendar not refreshed. %s', $e->getMessage());
            $this->set('errors', [$error]);
        }

        return $this->respond([
            'success' => $success,
            'serialize' => $serialize,
        ]);
    }

    /**
     * Edit method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit()
    {
        $calendarSource = $this->getSource();
        $this->Authorization->authorize($calendarSource->calendar_provider);

        $serialize = [];
        $success = false;
        if ($this->request->is(['patch', 'post', 'put'])) {
            // Only a subset of fields are user editable.
            $calendarSource = $this->CalendarSources->patchEntity($calendarSource, $this->request->getData(), [
                'fields' => ['color', 'name'],
            ]);
            if ($this->CalendarSources->save($calendarSource)) {
                $success = true;
                $serialize = ['source'];
                $this->set('source', $calendarSource);
            } else {
                $serialize = ['errors'];
                $this->set('errors', $this->flattenErrors($calendarSource->getErrors()));
            }
        }

        return $this->respond([
            'success' => $success,
            'serialize' => $serialize,
            'flashSuccess' => __('The calendar has been updated.'),
            'flashError' => __('The calendar could not be modified. Please, try again.'),
            'redirect' => $this->urlToProvider($calendarSource->calendar_provider_id),
        ]);
    }

    /**
     * Delete method
     *
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(CalendarService $service)
    {
        $this->request->allowMethod(['post', 'delete']);
        $calendarSource = $this->getSource();
        $this->Authorization->authorize($calendarSource->calendar_provider);

        $service->setAccessToken($calendarSource->calendar_provider);
        $service->cancelSubscriptions($calendarSource);

        $success = true;
        if (!$this->CalendarSources->delete($calendarSource)) {
            $success = false;
        }

        return $this->respond([
            'success' => $success,
            'statusSuccess' => 204,
            'statusError' => 400,
        ]);
    }

    public function deleteConfirm()
    {
        $calendarSource = $this->getSource();
        $this->Authorization->authorize($calendarSource->calendar_provider, 'edit');

        $this->set('calendarSource', $calendarSource);
    }
}

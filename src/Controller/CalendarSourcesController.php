<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CalendarService;

/**
 * CalendarSources Controller
 *
 * @property \App\Model\Table\CalendarSourcesTable $CalendarSources
 * @method \App\Model\Entity\CalendarSource[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CalendarSourcesController extends AppController
{
    public function sync(CalendarService $service, $sourceId)
    {
        $this->loadModel('CalendarProviders');
        $this->loadModel('CalendarSources');

        $user = $this->request->getAttribute('identity');
        $source = $this->CalendarSources->get($sourceId, ['contain' => ['CalendarProviders']]);
        $this->Authorization->authorize($source->calendar_provider, 'sync')

        $provider = $this->CalendarProviders
            ->find('all')
            ->where([
                'CalendarProviders.kind' => 'google',
                'CalendarProviders.user_id' => $user->id,
            ])
            ->firstOrFail();
        $service->setAccessToken($provider->access_token);

        // TODO add policy check.
        $service->syncEvents($user, $source);
    }

    /**
     * View method
     *
     * @param string|null $id Calendar Source id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $calendarSource = $this->CalendarSources->get($id, [
            'contain' => ['CalendarProviders'],
        ]);
        $this->Authorization->authorize($calendarSource);

        $this->set(compact('calendarSource'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Calendar Source id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $calendarSource = $this->CalendarSources->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $calendarSource = $this->CalendarSources->patchEntity($calendarSource, $this->request->getData());
            if ($this->CalendarSources->save($calendarSource)) {
                $this->Flash->success(__('The calendar source has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The calendar source could not be saved. Please, try again.'));
        }
        $calendarProviders = $this->CalendarSources->CalendarProviders->find('list', ['limit' => 200]);
        $providers = $this->CalendarSources->Providers->find('list', ['limit' => 200]);
        $this->set(compact('calendarSource', 'calendarProviders', 'providers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Calendar Source id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $calendarSource = $this->CalendarSources->get($id);
        if ($this->CalendarSources->delete($calendarSource)) {
            $this->Flash->success(__('The calendar source has been deleted.'));
        } else {
            $this->Flash->error(__('The calendar source could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}

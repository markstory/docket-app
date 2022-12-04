<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CalendarService;
use Cake\View\JsonView;

/**
 * CalendarProviders Controller
 *
 * @property \App\Model\Table\CalendarProvidersTable $CalendarProviders
 */
class CalendarProvidersController extends AppController
{
    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index(CalendarService $service)
    {
        $query = $this->CalendarProviders->find()->contain('CalendarSources');
        $query = $this->Authorization->applyScope($query);

        // There is no UI to go past 50 providers. I see no reasonable use case for this scenario.
        $providers = $this->paginate($query)->toArray();
        $referer = $this->getReferer('tasks:today');

        $calendars = [];
        $activeProvider = null;

        // API responses don't use activeProvider as the list and details views are separate.
        if (!empty($providers) && !$this->request->is('json')) {
            if ($this->request->getQuery('provider')) {
                $id = (int)$this->request->getQuery('provider', null);
                // Relying on the single page number of results to find the 'active' provider.
                // The active provider is rendered with a calendar list.
                $activeProvider = array_filter($providers, function ($item) use ($id) {
                    return $item->id === $id;
                });
                $activeProvider = array_pop($activeProvider);
            }
            if (!$activeProvider) {
                $activeProvider = $providers[0];
            }
            $service->setAccessToken($activeProvider);
            $calendars = $service->listUnlinkedCalendars($activeProvider->calendar_sources);
        }
        $this->set('unlinked', $calendars);

        $this->set(compact('activeProvider', 'providers', 'referer'));

        return $this->respond([
            'success' => true,
            'serialize' => ['providers'],
        ]);
    }

    /**
     * View method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view(string $id, CalendarService $service)
    {
        $provider = $this->CalendarProviders->get($id, ['contain' => ['CalendarSources']]);
        $this->Authorization->authorize($provider, 'view');

        $service->setAccessToken($provider);
        $calendars = $service->listUnlinkedCalendars($provider->calendar_sources ?? []);

        $this->set(compact('provider', 'calendars'));

        return $this->respond([
            'success' => true,
            'serialize' => ['provider', 'calendars'],
        ]);
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

        $success = false;
        if ($this->CalendarProviders->delete($calendarProvider)) {
            $success = true;
        }

        return $this->respond([
            'success' => $success,
            'flashSuccess' => __('The calendar account has been deleted.'),
            'flashError' => __('The calendar account could not be deleted. Please try again.'),
            'redirect' => ['action' => 'index'],
        ]);
    }
}

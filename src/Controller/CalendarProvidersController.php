<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CalendarService;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\FrozenTime;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\View\JsonView;
use Google\Client as GoogleClient;
use Google\Exception as GoogleException;
use Google\Service\Oauth2 as GoogleOauth2;

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

    public function createFromGoogle(GoogleClient $client)
    {
        $token = $this->request->getData('accessToken');
        $refresh = $this->request->getData('refreshToken');

        $client->setAccessToken($token);
        try {
            $oauth2 = new GoogleOauth2($client);
            $googleUser = $oauth2->userinfo->get();
        } catch (GoogleException $e) {
            throw new BadRequestException('Could not fetch user profile data.');
        }
        $user = $this->request->getAttribute('identity');

        $serialize = [];
        $success = false;
        try {
            $provider = $this->CalendarProviders->findOrCreate([
                'user_id' => $user->id,
                'kind' => 'google',
                'identifier' => $googleUser->id,
            ], function ($entity) use ($token, $refresh, $googleUser) {
                $entity->display_name = "{$googleUser->name} ({$googleUser->email})";
                $entity->access_token = $token;
                $entity->refresh_token = $refresh;
                $entity->token_expiry = FrozenTime::parse('+1800 seconds');
            });
            $this->Authorization->authorize($provider, 'edit');

            $this->CalendarProviders->saveOrFail($provider);

            $success = true;
            $serialize[] = 'provider';
            $this->set('provider', $provider);
        } catch (PersistenceFailedException $e) {
            $this->set(
                'error',
                __('Could not link google account. Try removing authorization in google and re-connecting')
            );
            $serialize[] = 'error';
        }
        // end service block.

        return $this->respond([
            'success' => $success,
            'serialize' => $serialize,
            'flashSuccess' => __('Provider created'),
            'flashError' => __('Provider not created.'),
        ]);
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
                // TODO this should be moved to a sync method.
                // The sync could be called from a dropdown action.
                // Synced unlinked calendars will have local state stored in the database (expand CalendarSource)
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
            try {
                $calendars = $service->listUnlinkedCalendars($activeProvider->calendar_sources);
            } catch (BadRequestException $e) {
                $activeProvider->broken_auth = true;
            }
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
        try {
            $calendars = $service->listUnlinkedCalendars($provider->calendar_sources ?? []);
        } catch (BadRequestException $e) {
            $calendars = [];
            $provider->broken_auth = true;
        }

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
            'statusSuccess' => 204,
            'flashSuccess' => __('The calendar account has been deleted.'),
            'flashError' => __('The calendar account could not be deleted. Please try again.'),
            'redirect' => ['action' => 'index'],
        ]);
    }
}

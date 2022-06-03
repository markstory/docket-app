<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\View\JsonView;

/**
 * ApiTokens Controller
 *
 * @property \App\Model\Table\ApiTokensTable $ApiTokens
 * @method \App\Model\Entity\ApiToken[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ApiTokensController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['add']);
    }

    /**
     * Views for content-type negotiation
     */
    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Authorization->applyScope($this->ApiTokens->find());
        $apiTokens = $this->paginate($query);

        $this->set(compact('apiTokens'));
        $this->viewBuilder()->setOption('serialize', ['apiTokens']);
    }

    /**
     * Add a new API token. Currently the only use case we have for creating
     * API tokens is for the login in the future mobile app.
     *
     * ## Parameters
     *
     * - email - string
     * - password - string
     *
     * ## Response Codes
     *
     * 200 - Created a new token. See the `apiToken.token` response attribute.
     * 401 - Incorrect credentials.
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->Authorization->skipAuthorization();
        $this->request->allowMethod(['post']);

        // This action is configured to perform a 'login'
        $result = $this->Authentication->getResult();
        if (!$result->isValid()) {
            $this->set('errors', ['Authentication required']);
            $this->viewBuilder()->setOption('serialize', ['errors']);
            $this->response = $this->response->withStatus(401);

            return;
        }

        $serialize = ['apiToken'];
        if ($this->request->is('post')) {
            $apiToken = $this->ApiTokens->generateApiToken($this->request->getAttribute('identity'));
            if ($apiToken->getErrors()) {
                $this->set('errors', $apiToken->getErrors());
                $serialize[] = 'apiToken';
            }
            $this->set('apiToken', $apiToken);
        }
        $this->viewBuilder()->setOption('serialize', $serialize);
    }

    /**
     * Delete method
     *
     * @param string|null $token Api Token token.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($token = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $apiToken = $this->ApiTokens->find('byToken', [$token])->firstOrFail();
        $this->Authorization->authorize($apiToken, 'delete');

        if (!$this->ApiTokens->delete($apiToken)) {
            $this->response = $this->response->withStatus(400);
            $this->set('errors', $apiToken->getErrors());
            $this->viewBuilder()->setOption('serialize', ['errors']);

            return;
        }

        $this->response = $this->response->withStatus(204);
    }
}

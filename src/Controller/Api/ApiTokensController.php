<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\View\JsonView;

/**
 * ApiTokens Controller
 *
 * @property \App\Model\Table\ApiTokensTable $ApiTokens
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
     * API tokens is for login in the mobile app.
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
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->Authorization->skipAuthorization();
        $this->request->allowMethod(['post']);

        // This action is configured to perform a 'login'
        $result = $this->Authentication->getResult();
        if (!$result || !$result->isValid()) {
            $this->set('errors', ['Authentication required']);
            $this->viewBuilder()->setOption('serialize', ['errors']);
            $this->response = $this->response->withStatus(401);

            return null;
        }

        if ($this->request->is('post')) {
            $apiToken = $this->ApiTokens->generateApiToken($this->request->getAttribute('identity'));
            $this->set('apiToken', $apiToken);
        }
        $this->viewBuilder()->setOption('serialize', ['apiToken']);
    }

    /**
     * Delete method
     *
     * @param string $token Api Token token.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(string $token)
    {
        $this->request->allowMethod(['post', 'delete']);
        /** @var \App\Model\Entity\ApiToken $apiToken */
        $apiToken = $this->ApiTokens->find('byToken', [$token])->firstOrFail();
        $this->Authorization->authorize($apiToken, 'delete');

        $this->ApiTokens->delete($apiToken);

        return $this->response->withStatus(204);
    }
}

<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Utility\Text;
use Cake\View\JsonView;

/**
 * ApiTokens Controller
 *
 * @property \App\Model\Table\ApiTokensTable $ApiTokens
 * @method \App\Model\Entity\ApiToken[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ApiTokensController extends AppController
{
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
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $user = $this->request->getAttribute('identity');

        $serialize = ['apiToken'];
        if ($this->request->is('post')) {
            $apiToken = $this->ApiTokens->generateApiToken($user);
            $this->Authorization->authorize($apiToken);
            if ($apiToken->getErrors()) {
                $this->set('errors', $apiToken->getErrors());
                $serialize[] = 'apiToken';
            }
        }
        $this->set(compact('apiToken'));
        $this->viewBuilder()->setOption('serialize', $serialize);

        if (!$this->request->is('json')) {
            return $this->redirect(['_name' => 'apitokens:index']);
        }
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

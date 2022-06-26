<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Routing\Router;
use Inertia\Controller\InertiaResponseTrait;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\ProjectsTable $Projects
 */
class AppController extends Controller
{
    use InertiaResponseTrait {
        beforeRender as protected inertiaBeforeRender;
    }

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
        $this->loadComponent('Authorization.Authorization');
    }

    protected function useInertia()
    {
        return true;
    }

    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);

        $this->viewBuilder()->addHelper('ViteAsset');

        // Load common data.
        $identity = $this->request->getAttribute('identity');
        $this->set('identity', $identity);

        $isApiResponse = !empty($this->viewBuilder()->getOption('serialize'));
        if (!$isApiResponse && $identity) {
            // Use a function to defer query exection on partial loads.
            $this->set('projects', function () use ($identity) {
                $this->loadModel('Projects');

                return $identity->applyScope('index', $this->Projects->find('active')->find('top'));
            });
        }

        // Use inertia if we aren't making a custom JSON response.
        if ($this->useInertia() && !$isApiResponse) {
            $this->inertiaBeforeRender($event);
        }
    }

    protected function flattenErrors(array $errors): array
    {
        $flattened = [];
        foreach ($errors as $field => $error) {
            $flattened[$field] = implode(', ', $error);
        }

        return $flattened;
    }

    protected function validationErrorResponse(array $errors)
    {
        return $this->response
            ->withStatus(422)
            ->withStringBody(json_encode(['errors' => $this->flattenErrors($errors)]));
    }

    protected function getReferer($default = 'tasks:today')
    {
        $defaultUrl = Router::url(['_name' => $default]);

        $get = $this->request->getQuery('referer');
        $post = $this->request->getData('referer');
        $header = $this->referer($defaultUrl);
        foreach ([$post, $get, $header] as $option) {
            if (is_string($option) && strlen($option) && $option[0] === '/') {
                return $option;
            }
        }

        return $defaultUrl;
    }

    /**
     * Response generation helper
     *
     * Eases defining logic for common API response patterns like success/error states.
     *
     * ### Options
     *
     * - success - Whether or not the request completed successfully.
     * - flashSuccess - The flash message to show for HTML responses that were successful.
     * - flashError - The flash message to show for HTML responses that had errors.
     * - redirect - The redirect to use for HTML responses.
     * - statusSuccess - The HTTP status code for succesful API responses.
     * - statusError - The Http status code for error responses.
     * - serialize - The view variables to serialize into an API response.
     *
     * @TODO use this in other endpoints as well.
     * @return void|\App\Controller\Cake\Http\Response Either a response or null if we're not skipping view rendering.
     */
    protected function respond(array $config)
    {
        $config += [
            'success' => false,
            'flashSuccess' => null,
            'flashError' => null,
            'redirect' => null,
            'statusSuccess' => 200,
            'statusError' => 400,
            'serialize' => null,
        ];
        $isApi = $this->request->is('json');

        if ($isApi) {
            $this->viewBuilder()->setOption('serialize', $config['serialize']);
            $code = $config['success'] ? $config['statusSuccess'] : $config['statusError'];

            $this->response = $this->response->withStatus($code);
            if ($this->response->getStatusCode() == 204) {
                return $this->response;
            }

            return;
        }

        if ($config['success']) {
            $this->Flash->success($config['flashSuccess']);
        } else {
            $this->Flash->error($config['flashError']);
        }
        if ($config['redirect']) {
            return $this->redirect($config['redirect']);
        }
    }
}

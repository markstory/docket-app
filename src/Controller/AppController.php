<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\ProjectsTable;
use App\View\Widget\ColorPickerWidget;
use App\View\Widget\DueOnWidget;
use App\View\Widget\ProjectPickerWidget;
use Authentication\Authenticator\SessionAuthenticator;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Routing\Router;

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
    public ProjectsTable $Projects;

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

    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);

        $identity = $this->request->getAttribute('identity');

        $this->viewBuilder()
            ->addHelper('ViteAsset')
            ->addHelper('Form', [
                'templates' => 'formtemplates',
                'widgets' => [
                    'colorpicker' => [ColorPickerWidget::class, '_view'],
                    'projectpicker' => [ProjectPickerWidget::class, '_view'],
                    'dueon' => [DueOnWidget::class, '_view'],
                ],
            ])
            ->addHelper('Date', ['timezone' => $identity->timezone ?? 'UTC']);

        // Load common data.
        $this->set('identity', $identity);
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

    protected function getReferer($default = 'tasks:today'): string
    {
        $defaultUrl = Router::url(['_name' => $default]);

        $get = $this->request->getQuery('referer');
        $post = $this->request->getData('referer');
        $header = $this->referer($defaultUrl);
        foreach ([$post, $get, $header] as $option) {
            if (is_string($option) && strlen($option) && $option[0] === '/') {
                $pathOnly = $this->sanitizeRedirect($option);
                if ($pathOnly === null) {
                    continue;
                }

                return $pathOnly;
            }
        }

        return $defaultUrl;
    }

    protected function sanitizeRedirect(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        $parsed = parse_url($url);
        if (
            $parsed === false ||
            empty($parsed['path']) ||
            (isset($parsed['host']) && $parsed['host'] !== $this->request->host())
        ) {
            return null;
        }
        $pathOnly = $parsed['path'];
        if (!empty($parsed['query'])) {
            $pathOnly .= '?' . $parsed['query'];
        }
        if (!empty($parsed['fragment'])) {
            $pathOnly .= '#' . $parsed['fragment'];
        }

        return $pathOnly;
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
     * - template - The view template to use if one is.
     *
     * @return null|\Cake\Http\Response Either a response or null if we're not skipping view rendering.
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
            'template' => null,
        ];
        $authenticator = $this->Authentication
            ->getAuthenticationService()
            ->getAuthenticationProvider();

        $setFlashMessages = (!$this->request->is('get') && $authenticator instanceof SessionAuthenticator);
        $viewBuilder = $this->viewBuilder();
        $isApi = $this->request->is('json');

        if ($config['template']) {
            $viewBuilder->setTemplate($config['template']);
        }

        $success = $config['success'] ?? false;
        if ($setFlashMessages) {
            if ($success && !empty($config['flashSuccess'])) {
                $this->Flash->success($config['flashSuccess']);
            }
            if ($success === false && !empty($config['flashError'])) {
                $this->Flash->error($config['flashError']);
            }
        }

        $code = $success ? $config['statusSuccess'] : $config['statusError'];
        if ($isApi) {
            $this->viewBuilder()->setOption('serialize', $config['serialize']);

            $this->response = $this->response->withStatus($code);
            if ($this->response->getStatusCode() == 204) {
                return $this->response;
            }

            return;
        }
        if ($this->request->is('htmx') && $config['statusSuccess'] === 204) {
            $this->response = $this->response->withStatus($code);
            if ($this->response->getStatusCode() == 204) {
                return $this->response;
            }
        }

        if ($config['redirect']) {
            return $this->redirect($config['redirect']);
        }
    }
}

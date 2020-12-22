<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
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
 * @link https://book.cakephp.org/4/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    use InertiaResponseTrait {
        beforeRender as protected inertiaBeforeRender;
    }

    protected function getReferer($default = 'tasks:today')
    {
        $defaultUrl = Router::url(['_name' => $default]);

        $get = $this->request->getQuery('referer');
        $post = $this->request->getData('referer');
        $header = $this->referer($defaultUrl);
        foreach ([$post, $get, $header] as $option) {
            if ($option && strlen($option) && $option[0] === '/') {
                return $option;
            }
        }

        return $defaultUrl;
    }

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
        $this->loadComponent('Authorization.Authorization');
    }

    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);

        $this->viewBuilder()
            ->addHelper('AssetMix.AssetMix');

        // Load common data.
        $identity = $this->request->getAttribute('identity');
        $this->set('identity', $identity);
        if ($identity) {
            $this->loadModel('Projects');
            $this->set('projects', $identity->applyScope('index', $this->Projects->find('active')->find('top')));
        }

        // Use inertia if we aren't making a custom JSON response.
        if (!$this->viewBuilder()->getOption('serialize')) {
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
}

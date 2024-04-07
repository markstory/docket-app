<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Mailer\MailerAwareTrait;
use Cake\View\JsonView;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    use MailerAwareTrait;

    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated([
            'login', 'resetPassword', 'newPassword',
        ]);
    }

    /**
     * Edit method
     *
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(): void
    {
        $identity = $this->request->getAttribute('identity');
        $user = $this->Users->get($identity->id);
        $this->Authorization->authorize($user);

        $success = true;
        $serialize = ['user'];
        if ($this->request->is(['patch', 'post', 'put'])) {
            $allowedFields = ['name', 'timezone', 'theme'];
            $user = $this->Users->patchEntity($user, $this->request->getData(), [
                'fields' => $allowedFields,
            ]);
            $email = $this->request->getData('unverified_email');
            if ($email) {
                $user->unverified_email = $email;
            }

            $emailChanged = $user->isDirty('unverified_email');
            if ($this->Users->save($user)) {
                if ($emailChanged) {
                    $this->getMailer('Users')->send('verifyEmail', [$user]);
                }
                $success = true;
            } else {
                $success = false;
                $serialize[] = 'errors';
                $this->set('errors', $this->flattenErrors($user->getErrors()));
            }
        }
        $this->set('user', $user);

        $this->respond([
            'success' => $success,
            'serialize' => $serialize,
            'statusError' => 422,
        ]);
    }
}

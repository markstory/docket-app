<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Model\Entity\User;
use Cake\Mailer\Mailer;

/**
 * Users mailer.
 */
class UsersMailer extends Mailer
{
    /**
     * Mailer's name.
     *
     * @var string
     */
    public static $name = 'Users';

    public function resetPassword(User $user)
    {
        $token = $user->passwordResetToken();
        $this->setTo($user->email)
            ->setEmailFormat('text')
            ->setSubject('Password Reset')
            ->setViewVars([
                'name' => $user->name,
                'email' => $user->email,
                'token' => $token,
            ]);
    }

    public function verifyEmail(User $user)
    {
        $token = $user->emailVerificationToken();
        $unverified = $user->email_verified ? $user->unverified_email : $user->email;
        $this->setTo($unverified)
            ->setEmailFormat('text')
            ->setSubject('Verify your email address')
            ->setViewVars([
                'name' => $user->name,
                'email' => $user->email,
                'unverifiedEmail' => $unverified,
                'token' => $token,
                'firstTime' => $unverified === $user->email,
            ]);
    }
}

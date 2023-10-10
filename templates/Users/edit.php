<?php
declare(strict_types=1);

use Cake\I18n\FrozenTime;
/**
 * @var \App\Model\Entity\User $user
 * @var string $referrer
 */

$this->setLayout('sidebar');

?>
<h2>Edit Profile</h2>
<?php
echo $this->Form->create($user);
echo $this->Form->control('name');
echo $this->Form->control('unverified_email', [
    'label' => 'Email',
    'placeholder' => $user->email,
    'templateVars' => [
        'help' => $user->unverified_email
            ? '<p class="form-help">You have a pending email address change that needs to be verified.</p>'
            : '<p class="form-help">Until your new email address is verified, you must use the current email to login.</p>'
    ]
]);
echo $this->Form->control('timezone', [
    'type' => 'select',
    'options' => FrozenTime::listTimezones(),
    'templateVars' => [
        'help' => '<p class="form-help">This will update on each login, so today and tomorrow are correct.</p>',
    ]
]);
echo $this->Form->control('theme', [
    'type' => 'select',
    'options' => [
        'system' => 'System',
        'light' => 'Light',
        'dark' => 'Dark',
    ],
    'templateVars' => [
        'help' => '<p class="form-help">The "system" theme inherits light/dark from your operating system when possible.</p>',
    ]
]);
?>
<div class="button-bar">
    <?= $this->Form->submit('Save', ['class' => 'button button-primary']) ?>
    <?= $this->Html->link('Cancel', $referer, ['class' => 'button button-muted']) ?>
</div>

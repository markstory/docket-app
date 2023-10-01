<?php
declare(strict_types=1);

/**
 * @var string $token
 * @var \App\Model\Entity\User $user
 */
$this->setLayout('card');
$this->assign('title', 'Reset your password');
?>
<h1>Reset your password</h1>
<p>Update your password. Your password must be at least 10 characters long.</p>
<?= $this->Form->create(
    $user,
    [
        'type' => 'post',
        'url' => ['controller' => 'Users', 'action' => 'newPassword', $token],
    ],
) ?>
<?= $this->Form->control('password', [
    'type' => 'password',
    'value' => '',
    'required' => true,
]) ?>
<?= $this->Form->control('confirm_password', [
    'type' => 'password',
    'value' => '',
    'required' => true,
]) ?>
<div class="button-bar">
    <?= $this->Form->submit('Reset Password', ['class' => 'button button-primary']) ?>
    <?= $this->Html->link(
        'Log In',
        ['_path' => 'Users::login'],
        ['class' => 'button button-muted']
    ) ?>
</div>


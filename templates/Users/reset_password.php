<?php
declare(strict_types=1);

$this->setLayout('card');
$this->assign('title', 'Forgot your password');
?>
<h1>Forgot your password</h1>
<p>We will send you an email with instructions to reset it.</p>
<?= $this->Form->create(
    null,
    [
        'class' => 'form-narrow',
        'url' => ['controller' => 'Users', 'action' => 'resetPassword'],
    ],
) ?>
<?= $this->Form->control('email', ['required' => true, 'type' => 'email']) ?>
<div class="button-bar">
    <?= $this->Form->submit('Reset Password', ['class' => 'button button-primary']) ?>
    <?= $this->Html->link('Login', ['_path' => 'Users::login'], ['class' => 'button button-muted']) ?>
</div>
<?= $this->Form->end() ?>

<?php
declare(strict_types=1);

$this->setLayout('card');
$this->assign('title', 'Register a new account');
?>
<h1>Register</h1>
<p>Get started tracking tasks and subtasks in projects today.</p>

<?= $this->Form->create(
    null,
    ['url' => ['controller' => 'Users', 'action' => 'add']]
) ?>
<?= $this->Form->control('name', [
    'type' => 'text',
    'required' => true,
    'templateVars' => [
        'help' => '<p class="form-help">The name used in the site and in emails.</p>'
    ]
]) ?>
<?= $this->Form->control('email', [
    'type' => 'email',
    'required' => true,
    'templateVars' => [
        'help' => '<p class="form-help">Used to email you and to login.</p>'
    ]
]) ?>
<?= $this->Form->control('password', [
    'type' => 'password',
    'required' => true,
    'templateVars' => [
        'help' => '<p class="form-help">More than 10 characters long.</p>',
    ]
]) ?>
<?= $this->Form->control('confirm_password', [
    'label' => 'Confirm Password',
    'type' => 'password',
    'required' => true,
    'templateVars' => [
        'help' => '<p class="form-help">One more time please.</p>',
    ],
]) ?>

<div class="button-bar">
    <?= $this->Form->button('Sign Up', ['class' => 'button button-primary']) ?>
    <?= $this->Html->link(
        'Log in',
        ['controller' => 'Users', 'action' => 'login'],
        ['class' => 'button button-muted']
    ) ?>
</div>
<?= $this->Form->end() ?>

<?php
$this->setLayout('card');
$this->assign('title', 'Login');
?>
<h1 class="heading-logo">
    <?= $this->Html->image('docket-logo.svg', ['width' => 45, 'height' => 45]) ?>
    Login
</h1>
<?= $this->Form->create(null, [
    'class' => 'form-narrow',
    'url' => ['controller' => 'Users', 'action' => 'login', '?' => $this->getRequest()->getQueryParams()],
]) ?>
<?= $this->Form->control('email', ['type' => 'email', 'required' => true]) ?>
<?= $this->Form->control('password', ['type' => 'password', 'required' => true]) ?>
<?= $this->Form->hidden('timezone', ['id' => 'input-timezone']) ?>
<div class="button-bar">
    <?= $this->Form->button('Login', ['class' => 'button button-primary']) ?>
    <?= $this->Html->link(
        'Forgot Password?',
        ['_path' => 'Users::resetPassword'],
        ['class' => 'button button-muted']
    ) ?>
</div>
<div class="button-bar">
    Don't have an account?
    <?= $this->Html->link('Sign up', ['_path' => 'Users::add'], ['class' => 'button button-muted']) ?>
</div>
<?= $this->Form->end() ?>

<?= $this->Html->scriptStart() ?>
try {
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    document.getElementById('input-timezone').value = timezone;
} catch (e) {
    // Do nothing we'll use their last timezone.
}
<?= $this->Html->scriptEnd() ?>

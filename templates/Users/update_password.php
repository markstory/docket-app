<?php
declare(strict_types=1);
/**
 * @var \App\Model\User $user
 * @var string $referer
 */

$this->setLayout('sidebar');
$this->assign('title', 'Update Password');
?>
<h1>Update Password</h1>
<?= $this->Form->create(
    $user,
    [
        'class' => 'form-narrow',
        'url' => ['controller' => 'Users', 'action' => 'updatePassword'],
    ]
) ?>
<?= $this->Form->control('current_password', [
    'label' => 'Current Password',
    'type' => 'password',
    'required' => true,
]) ?>
<?= $this->Form->control('password', [
    'label' => 'New Password',
    'type' => 'password',
    // don't use entity state.
    'value' => $this->request->getData('password', ''),
    'required' => true,
]) ?>
<?= $this->Form->control('confirm_password', [
    'label' => 'Confirm Password',
    'type' => 'password',
    'required' => true,
]) ?>
<div class="button-bar">
    <?= $this->Form->submit('Save', ['class' => 'button button-primary']) ?>
    <?= $this->Html->link(
        'Cancel',
        $referer,
        ['class' => 'button button-muted']
    ) ?>
</div>
<?= $this->Form->end() ?>

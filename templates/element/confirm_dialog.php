<?php
declare(strict_types=1);
/**
 * A generic confirmation dialog.
 *
 * @var string $target The url to submit to
 * @var string $title The title of the dialog
 * @var string $description Body copy
 */
?>
<dialog class="confirm-dialog">
    <?= $this->Form->create(null, ['url' => $target]) ?>
    <h2><?= $title ?></h2>
    <p><?= $description ?></p>
    <div class="button-bar-right">
        <?= $this->Html->link('Cancel', '#', [
            'modal-close' => '1',
            'class' => 'button button-muted',
            'data-testid' => 'confirm-cancel',
            'tabindex' => 0,
        ]) ?>
        <?= $this->Form->button('Ok', [
            'type' => 'submit',
            'class' => 'button button-danger',
            'data-testid' => 'confirm-proceed'
        ]) ?>
    </div>
    <?= $this->Form->end() ?>
</dialog>

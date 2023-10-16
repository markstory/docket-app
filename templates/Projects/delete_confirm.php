<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 */
$this->setLayout('ajax');

$target = $this->Url->build(['_name' => 'projects:delete', 'slug' => $project->slug]);

// TODO figure out how to close the modal on click.
?>
<div class="confirm-overlay">
    <dialog class="confirm-dialog" open="true">
        <?= $this->Form->create(null, ['url' => $target]) ?>
        <h2>Are you sure?</h2>
        <p>This will delete all the tasks in this project as well.</p>
        <div class="button-bar-right">
            <?= $this->Form->button('Cancel', [
                // TOOD need to close the modal?
                'class' => 'button button-muted',
                'data-testid' => 'confirm-cancel'
            ]) ?>
            <?= $this->Form->button('Ok', [
                'type' => 'submit',
                'class' => 'button button-danger',
                'data-testid' => 'confirm-proceed'
            ]) ?>
        </div>
        <?= $this->Form->end() ?>
    </dialog>
</div>

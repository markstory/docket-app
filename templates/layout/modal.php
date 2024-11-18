<?php
declare(strict_types=1);
/**
 * @var \Cake\View\View $this
 * @var \Cake\View\Helper\HtmlHelper $this->Html
 * @var array|null $dialogOptions additional options for the dialog element. deletion-confirm uses this.
*/
?>
<modal-window open="<?=h($open) ?>">
    <?= $this->Html->tag(
        'dialog',
        $this->fetch('content'),
        $dialogOptions ?? [],
    ) ?>
</modal-window>

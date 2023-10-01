<?php
/**
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
?>
<div class="flash-message flash-success">
    <?= $this->element('icons/checkcircle16') ?>
    <?= h($message) ?>
</div>

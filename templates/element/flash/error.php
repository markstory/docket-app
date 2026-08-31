<?php
/**
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
?>
<div class="flash-message flash-error" hx-flash-message>
    <?= $this->element('icons/alert16') ?>
    <?= h($message) ?>
</div>

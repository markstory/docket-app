<?php
/**
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
// While this fade out works. it requires compromising csp
// and feels like dirty code. Making an htmx extension
// should be a better path https://htmx.org/extensions/
?>
<div class="flash-message flash-success" hx-ext="flash-message">
    <?= $this->element('icons/checkcircle16') ?>
    <?= h($message) ?>
</div>

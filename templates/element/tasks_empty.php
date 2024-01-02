<?php
declare(strict_types=1);

$createParams = $this->get('globalAddContext') ?? [];
?>
<div class="tasks-empty">
    <span class="hero-icon">
        <?= $this->element('icons/trophy16', ['size' => 48]) ?>
    </span>
    <h2>All done</h2>
    <p>Congratulations! Create a task for what is next.</p>
    <p>
        <?= $this->Html->link(
            $this->element('icons/plus16') . 'Create a Task',
            ['_name' => 'tasks:add', '?' => $createParams],
            [
                'escape' => false,
                'class' => 'button-primary',
                'hx-get' => $this->Url->build(['_name' => 'tasks:add', '?' => $createParams]),
                'hx-target' => 'main.main',
                'hx-swap' => 'beforeend',
            ]
        ) ?>
    </p>
</div>

<?= $this->Form->control('body', [
    'id' => 'task-body-text',
    'label' => [
        'class' => 'form-section-heading icon-not-due',
        'text' => $this->element('icons/note16') . 'Notes',
        'escape' => false,
    ],
    'rows' => 5,
    'templates' => [
        'inputContainer' => '<markdown-text>{{content}}</markdown-text>',
    ],
]) ?>

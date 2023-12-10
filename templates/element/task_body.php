<?= $this->Form->control('body', [
    'id' => 'task-body-text',
    'label' => [
        'class' => 'form-section-heading icon-not-due',
        'text' => $this->element('icons/note16') . 'Notes',
        'escape' => false,
    ],
    'type' => 'textarea',
    'rows' => 1,
    'templates' => [
        'formGroup' => '{{label}}{{input}}{{error}}',
        'inputContainer' => '<markdown-text>{{content}}</markdown-text>',
    ],
]) ?>

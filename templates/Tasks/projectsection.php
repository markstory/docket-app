<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\ProjectSection[] $sections
 * @var \App\Model\Entity\Task $task
 */
if (!empty($sections)) :
    $options = collection($sections)->combine('id', 'name')->toArray();
    echo $this->Form->control('section_id', [
        'label' => [
            'class' => 'form-section-heading icon-week',
            'text' => $this->element('icons/directory-symlink16') . 'Section',
            'escape' => false,
        ],
        'options' => $options,
        'empty' => true,
        'value' => $task->section_id,
    ]);
endif;

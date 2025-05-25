<?php
declare(strict_types=1);

use function Cake\Collection\collection;

/**
 * @var \Cake\View\View $this
 * @var \App\Model\Entity\ProjectSection[] $sections
 * @var ?string $value
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
        'value' => $value,
    ]);
else :
    echo $this->Form->control('section_id', [
        'label' => [
            'class' => 'form-section-heading icon-week',
            'text' => $this->element('icons/directory-symlink16') . 'Section',
            'escape' => false,
        ],
        'type' => 'text',
        'disabled' => 'true',
        'placeholder' => 'No Sections',
        'empty' => true,
    ]);
endif;

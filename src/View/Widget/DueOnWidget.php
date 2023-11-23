<?php
declare(strict_types=1);

namespace App\View\Widget;

use App\Model\Entity\Task;
use Cake\View\Form\ContextInterface;
use Cake\View\StringTemplate;
use Cake\View\View;
use Cake\View\Widget\BasicWidget;
use RuntimeException;

class DueOnWidget extends BasicWidget
{
    /**
     * Data defaults.
     *
     * @var array<string, mixed>
     */
    protected $defaults = [
        'name' => '',
        'disabled' => null,
        'val' => null,
        'tabindex' => '-1',
        'templateVars' => [],
        'inputAttrs' => [],
    ];

    public function __construct(
    private StringTemplate $templates,
    private View $view,
    ) {
    }

    public function render(array $data, ContextInterface $context): string
    {
        $data = $this->mergeDefaults($data, $context);
        $task = $data['val'] ?? null;
        assert($task instanceof Task, '`val` is required to be a Task');

        $inputAttrs = $data['inputAttrs'] ?? [];
        unset($data['data-validity-message'], $data['oninvalid'], $data['oninput'], $data['inputAttrs']);

        $inputAttrs += ['style' => 'display:none'];

        $hidden = [
            $this->templates->format('input', [
                'name' => 'due_on',
                'value' => $task->due_on ? $task->due_on->format('Y-m-d') : null,
                'type' => 'text',
                'attrs' => $this->templates->formatAttributes($inputAttrs),
            ]),
            $this->templates->format('input', [
                'name' => 'evening',
                'value' => $task->evening,
                'type' => 'text',
                'attrs' => $this->templates->formatAttributes($inputAttrs),
            ]),
        ];
        $attrs = $this->templates->formatAttributes($data);

        return $this->templates->format('due-on', [
            'templateVars' => $data['templateVars'],
            'attrs' => $attrs,
            'hidden' => implode("\n", $hidden),
            'label' => h($task->getCompactDueOn()),
            'options' => $this->view->element('task_dueon_menu', [
                'task' => $task,
                'referer' => '',
                'calendarForm' => false,
                'itemFormatter' => function (string $title, string $icon, string $id, array $data) {
                    $title = $this->view->element("icons/{$icon}16") . ' ' . $title;
                    echo $this->view->Form->button($title, [
                        'escapeTitle' => false,
                        'data-testid' => $id,
                        'value' => $data['due_on'],
                        'data-evening' => $data['evening'] ?? 0,
                        'class' => 'menu-item-button',
                    ]);
                },
            ]),
        ]);
    }

    public function secureFields(array $data): array
    {
        return [$data['name']];
    }
}

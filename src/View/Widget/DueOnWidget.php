<?php
declare(strict_types=1);

namespace App\View\Widget;

use Cake\View\Form\ContextInterface;
use Cake\View\StringTemplate;
use Cake\View\View;
use Cake\View\Widget\BasicWidget;
use Tasks\Model\Entity\Task;

class DueOnWidget extends BasicWidget
{
    /**
     * Data defaults.
     *
     * @var array<string, mixed>
     */
    protected array $defaults = [
        'name' => '',
        'disabled' => null,
        'val' => null,
        'tabindex' => '-1',
        'templateVars' => [],
        'inputAttrs' => [],
    ];

    public function __construct(private StringTemplate $templates, private View $view)
    {
    }

    public function render(array $data, ContextInterface $context): string
    {
        $data = $this->mergeDefaults($data, $context);
        $task = $data['val'] ?? null;
        assert($task instanceof Task, '`val` is required to be a Task');

        $inputAttrs = $data['inputAttrs'] ?? [];
        unset($data['data-validity-message'], $data['oninvalid'], $data['oninput'], $data['inputAttrs'], $data['val']);

        $inputAttrs += ['style' => 'display:none'];
        $templateVars = $data['templateVars'] ?? [];

        $hidden = $this->templates->format('input', [
            'name' => 'due_on',
            'type' => 'text',
            'attrs' => $this->templates->formatAttributes(
                $inputAttrs + [
                    'value' => $task->due_on ? $task->due_on->format('Y-m-d') : null,
                ],
            ),
        ]);
        $id = $data['id'] ?? 'task-evening-' . uniqid();

        $templateVars += [
            'id' => $id,
            'iconEvening' => $this->view->element('icons/moon16'),
            'iconDay' => $this->view->element('icons/sun16'),
            'inputEvening' => $this->templates->format('input', [
                'name' => 'evening',
                'type' => 'checkbox',
                'attrs' => $this->templates->formatAttributes([
                    'id' => $id,
                    'value' => 1,
                    'checked' => $task->evening ? 1 : 0,
                ]),
            ]),
        ];

        $attrs = $this->templates->formatAttributes($data);
        $icon = '';
        if ($task->evening) {
            $icon = $this->view->element('icons/moon16');
        }

        return $this->templates->format('due-on', [
            'templateVars' => $templateVars,
            'attrs' => $attrs,
            'hidden' => $hidden,
            'label' => $icon . $this->view->Date->formatCompact($task->due_on, $task->evening),
            'options' => $this->view->element('task_dueon_menu', [
                'task' => $task,
                'referer' => '',
                'renderForms' => false,
                'itemFormatter' => function (string $title, array $options, array $data): void {
                    $options += ['icon' => 'sun', 'class' => '', 'testId' => ''];
                    $title = $this->view->element("icons/{$options['icon']}16") . ' ' . $title;
                    echo $this->view->Form->button($title, [
                        'role' => 'menuitem',
                        'escapeTitle' => false,
                        'data-testid' => $options['testId'],
                        'value' => $data['due_on'],
                        'data-evening' => $data['evening'] ?? 0,
                        'class' => "menu-item-button {$options['class']}",
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

<?php
declare(strict_types=1);

namespace App\View\Widget;

use Cake\View\Form\ContextInterface;
use Cake\View\StringTemplate;
use Cake\View\View;
use Cake\View\Widget\BasicWidget;
use RuntimeException;

class ProjectPickerWidget extends BasicWidget
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
        'projects' => [],
        'data-niceselect' => 1,
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
        if (empty($data['projects'])) {
            throw new RuntimeException('`projects` option is required');
        }
        $selected = $data['val'] ?? null;
        $projects = $data['projects'];
        $inputAttrs = $data['inputAttrs'] ?? [];
        unset(
            $data['projects'],
            $data['data-validity-message'],
            $data['oninvalid'],
            $data['oninput'],
            $data['inputAttrs']
        );

        $inputAttrs += ['style' => 'display:none'];

        $options = [];
        foreach ($projects as $project) {
            $optionBody = $this->view->element('icons/dot16', ['color' => $project->color_hex]) . h($project->name);
            $optAttrs = [
                'selected' => $project->id == $selected,
            ];

            $options[] = $this->templates->format('select-box-option', [
                'value' => $project->id,
                'text' => $optionBody,
                'attrs' => $this->templates->formatAttributes($optAttrs, ['text', 'value']),
            ]);
        }

        $hidden = $this->templates->format('input', [
            'name' => $data['name'],
            'value' => $selected,
            'type' => 'text',
            'attrs' => $this->templates->formatAttributes($inputAttrs),
        ]);
        $attrs = $this->templates->formatAttributes($data);

        return $this->templates->format('select-box', [
            'templateVars' => $data['templateVars'],
            'attrs' => $attrs,
            'hidden' => $hidden,
            'options' => implode('', $options),
        ]);
    }

    public function secureFields(array $data): array
    {
        return [$data['name']];
    }
}

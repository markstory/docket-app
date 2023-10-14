<?php
declare(strict_types=1);

namespace App\View\Widget;

use Cake\View\Form\ContextInterface;
use Cake\View\StringTemplate;
use Cake\View\View;
use Cake\View\Widget\BasicWidget;
use RuntimeException;

class ColorPickerWidget extends BasicWidget
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
        'colors' => [],
        'data-niceselect' => 1,
        'tabindex' => '-1',
    ];

    public function __construct(
        private StringTemplate $templates,
        private View $view,
    ) {
    }

    public function render(array $data, ContextInterface $context): string
    {
        $data = $this->mergeDefaults($data, $context);
        if (empty($data['colors'])) {
            throw new RuntimeException('`colors` option is required');
        }
        $selected = $data['val'] ?? null;
        $colors = $data['colors'];
        unset($data['colors']);

        $options = [];
        foreach ($colors as $color) {
            $optionBody = $this->view->element('icons/dot16', ['color' => $color['code']]) . h($color['name']);
            $optAttrs = [
                'selected' => $color['id'] == $selected,
            ];

            $options[] = $this->templates->format('select-box-option', [
                'value' => $color['id'],
                'text' => $optionBody,
                'attrs' => $this->templates->formatAttributes($optAttrs, ['text', 'value']),
            ]);
        }

        $hidden = $this->templates->format('input', [
            'name' => $data['name'],
            'value' => $selected,
            'type' => 'hidden',
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

<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;
use RuntimeException;

/**
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class ViteAssetHelper extends Helper
{
    /**
     * @var array<array-key, mixed>
     */
    protected array $helpers = ['Html'];

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'manifestFile' => WWW_ROOT . 'manifest.json',
    ];

    /**
     * @var array
     */
    protected array $manifest = [];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $manifestFile = $this->getConfig('manifestFile');
        if (file_exists($manifestFile)) {
            $contents = file_get_contents($manifestFile);
            if (!$contents) {
                throw new RuntimeException("Could not read manifest file `{$manifestFile}`");
            }
            $data = json_decode($contents, true);
            if (json_last_error()) {
                throw new RuntimeException("Could not parse JSON in `{$manifestFile}`");
            }
            $this->manifest = $data;
        }
    }

    public function script(string $name): string
    {
        if (!isset($this->manifest[$name])) {
            throw new RuntimeException("No known asset with `{$name}`");
        }
        $asset = $this->manifest[$name];
        if (empty($asset['file'])) {
            throw new RuntimeException("The `{$name}` asset has no file attribute in the manifest.");
        }

        return (string)$this->Html->script('/' . $asset['file'], ['type' => 'module']);
    }

    public function css(string $name): string
    {
        if (!isset($this->manifest[$name])) {
            throw new RuntimeException("No known asset with `{$name}`");
        }
        $asset = $this->manifest[$name];
        if (empty($asset['css'])) {
            throw new RuntimeException("The `{$name}` asset has no css attribute in the manifest.");
        }

        $css = [];
        foreach ($asset['css'] as $file) {
            $css[] = $this->Html->css('/' . $file);
        }

        return implode("\n", $css);
    }
}

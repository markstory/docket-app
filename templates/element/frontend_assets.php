<?php
declare(strict_types=1);

use Cake\Core\Configure;

$debug = Configure::read('debug');
?>
<?php $this->Html->scriptStart() ?>
var global = globalThis;
globalThis.regeneratorRuntime = undefined
<?= $this->Html->scriptEnd() ?>
<?php if ($debug) : ?>
    <?= $this->ViteAsset->css('assets/js/app.tsx') ?>
    <?= $this->Html->script('http://localhost:3000/@vite/client', ['type' => 'module']) ?>
    <?= $this->Html->script('http://localhost:3000/assets/js/app.tsx', ['type' => 'module']) ?>
    <?php $this->Html->scriptStart(['type' => 'module']) ?>
      import RefreshRuntime from 'http://localhost:3000/@react-refresh'
      RefreshRuntime.injectIntoGlobalHook(window)
      window.$RefreshReg$ = () => {}
      window.$RefreshSig$ = () => (type) => type
      window.__vite_plugin_react_preamble_installed__ = true
    <?= $this->Html->scriptEnd() ?>
<?php else : ?>
    <?= $this->ViteAsset->css('assets/js/app.tsx') ?>
    <?= $this->ViteAsset->script('assets/js/app.tsx') ?>
<?php endif ?>

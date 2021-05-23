<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 */

use Cake\Core\Configure;

$debug = Configure::read('debug');
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>
    <link rel="manifest" href="<?= $this->Url->build('/manifest.json') ?>" />

    <?= $this->fetch('meta') ?>

    <?= $this->fetch('script') ?>
    <?= $this->fetch('css') ?>

    <?php $this->Html->scriptStart() ?>
    var global = globalThis;
    <?= $this->Html->scriptEnd() ?>
<?php if ($debug) : ?>
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
</head>
<body>
    <main class="main">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>
    <footer>
    </footer>
</body>
</html>

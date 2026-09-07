<?php
declare(strict_types=1);

use Cake\Core\Configure;

$debug = Configure::read('debug');
?>
<?php if ($debug) : ?>
    <?= $this->ViteAsset->css('assets/js/app.ts') ?>
    <?= $this->Html->script('http://localhost:3000/@vite/client', ['type' => 'module']) ?>
    <?= $this->Html->script('http://localhost:3000/assets/js/app.ts', ['type' => 'module']) ?>
<?php else : ?>
    <?= $this->ViteAsset->css('assets/js/app.ts') ?>
    <?= $this->ViteAsset->script('assets/js/app.ts') ?>
<?php endif;

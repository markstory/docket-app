<?php
declare(strict_types=1);

use Cake\Core\Configure;

$debug = Configure::read('debug');
?>
<?php if ($debug) : ?>
    <?= $this->ViteAsset->css('assets/js/app.tsx') ?>
    <?= $this->Html->script('http://localhost:3000/@vite/client', ['type' => 'module']) ?>
    <?= $this->Html->script('http://localhost:3000/assets/js/app.tsx', ['type' => 'module']) ?>
<?php else : ?>
    <?= $this->ViteAsset->css('assets/js/app.tsx') ?>
    <?= $this->ViteAsset->script('assets/js/app.tsx') ?>
<?php endif;

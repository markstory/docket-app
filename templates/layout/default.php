<?php
declare(strict_types=1);

$class = '';
if (isset($identity)) :
    $class = "theme-{$identity->theme}";
endif;
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta id="csrf-token" name="csrf-token" content="<?= $this->request->getAttribute('csrfToken') ?>" />
    <title>
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>
    <link rel="manifest" href="<?= $this->Url->build('/manifest.json') ?>" />

    <?= $this->fetch('meta') ?>

    <?= $this->fetch('script') ?>
    <?= $this->fetch('css') ?>

    <?= $this->element('frontend_assets') ?>
</head>
<body hx-ext="ajax-header" class="<?= h($class) ?>">
    <main class="main">
        <div class="flash-messages">
            <?= $this->Flash->render() ?>
        </div>
        <?= $this->fetch('content') ?>
    </main>
    <footer>
    </footer>
</body>
</html>

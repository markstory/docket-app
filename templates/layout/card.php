<?php
/**
 * Renders the view within a card centered in the viewport.
 */
$this->extend('default');
?>
<div class="layout-card-bg">
    <main class="layout-card">
        <section class="content"><?= $this->fetch('content') ?></section>
    </main>
</div>

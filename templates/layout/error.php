<!DOCTYPE html>
<html>
<head>
  <?= $this->Html->charset() ?>
  <title>
      <?= $this->fetch('title') ?>
  </title>
  <?= $this->Html->meta('icon') ?>

  <?= $this->fetch('meta') ?>
  <?= $this->fetch('css') ?>
  <?= $this->fetch('script') ?>

  <?= $this->element('frontend_assets') ?>
</head>
<body>
  <div class="layout-card-bg">
    <main class="layout-card">
      <section class="content">
      <?= $this->Flash->render() ?>
      <?= $this->fetch('content') ?>
      <?= $this->Html->link(__('Back'), 'javascript:history.back()') ?>
      </section>
    </main>
  </div>
</body>
</html>

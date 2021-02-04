<!DOCTYPE html>
<html>
<head>
  <?= $this->Html->charset() ?>
  <title>
      <?= $this->fetch('title') ?>
  </title>
  <?= $this->Html->meta('icon') ?>

  <?= $this->AssetMix->css('app') ?>

  <?= $this->fetch('meta') ?>
  <?= $this->fetch('css') ?>
  <?= $this->fetch('script') ?>
</head>
<body>
  <div class="layout-card-bg">
    <main className="layout-card">
      <section className="content">
      <?= $this->Flash->render() ?>
      <?= $this->fetch('content') ?>
      <?= $this->Html->link(__('Back'), 'javascript:history.back()') ?>
      </section>
    </main>
  </div>
</body>
</html>

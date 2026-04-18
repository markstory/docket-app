<div class="layout-card-bg">
  <div class="layout-card">
    <h1>Docket</h1>
    <p>
        Simple, tasks, calendaring and feed reading. Visit
        <?= $this->Html->link('Github', 'https://github.com/markstory/docket-app') ?>
        to learn more about running your own Docket.
    </p>
    <div class="button-bar">
      <?= $this->Html->link('Login', ['_name' => 'users:login'], ['class' => 'button-primary']) ?>
      <?php if ($features->has('create-user')) : ?>
          <?= $this->Html->link('Create an Account', ['_name' => 'users:add'], ['class' => 'button-secondary']) ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="layout-card-bg">
  <div class="layout-card">
    <h1>Docket</h1>
    <p>Your personal todo list</p>
    <div class="button-bar">
      <?= $this->Html->link('Login', ['_name' => 'users:login'], ['class' => 'button-primary']) ?>
      <?= $this->Html->link('Create an Account', ['_name' => 'users:add'], ['class' => 'button-secondary']) ?>
    </div>
  </div>
</div>

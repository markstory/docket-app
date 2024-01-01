<?php
$isHtmx = $this->request->is('htmx');

$this->setLayout('sidebar');
if ($isHtmx) {
    $this->set('closable', true);
    $this->set('open', true);
    $this->setLayout('modal');
}

$this->assign('title', 'Docket Help');

if ($isHtmx) : ?>
<dialog>
<?php endif ?>
<div class="modal-title">
    <h2>Keyboard shortcuts</h2>
    <button class="modal-close" modal-close="true">&#x2715;</button>
</div>
<div class="help-view">
    <h3>Global shortcuts</h3>
    <dl class="shortcut-list">
        <dt><kbd>u</kbd></dt>
        <dd>Go to Upcoming View</dd>

        <dt><kbd>t</kbd></dt>
        <dd>Go to Today View</dd>

        <dt><kbd>c</kbd></dt>
        <dd>Create a task (if the global add action is visible)</dd>

        <dt><kbd>?</kbd></dt>
        <dd>Show this help dialog</dd>
    </dl>
    <h3>Task list shortcuts</h3>
    <dl class="shortcut-list">
        <dt><kbd>j</kbd></dt>
        <dd>Move focus down</dd>

        <dt><kbd>k</kbd></dt>
        <dd>Move focus up</dd>

        <dt><kbd>o</kbd></dt>
        <dd>Open focused task</dd>

        <dt><kbd>x</kbd></dt>
        <dd>Complete current task</dd>
    </dl>
</div>
<?php if ($isHtmx) : ?>
</dialog>
<?php endif ?>

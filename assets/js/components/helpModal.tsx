import Modal from './modal';

type Props = {
  onClose: () => void;
};

function HelpModal({onClose}: Props) {
  return (
    <Modal onClose={onClose} label="Help and Shortcuts">
      <h2>Help and Shortcuts</h2>
      <p>
        We all need a helping hand sometimes. Hopefully this makes your day a bit easier.
      </p>
      <h3>Keyboard shortcuts</h3>
      <p>Anywhere</p>
      <dl className="shortcut-list">
        <dt>
          <kbd>t</kbd>
        </dt>
        <dd>Go to &quot;Today&quot;</dd>
        <dt>
          <kbd>u</kbd>
        </dt>
        <dd>Go to &quot;Upcoming&quot;</dd>
        <dt>
          <kbd>?</kbd>
        </dt>
        <dd>This help screen</dd>
      </dl>
      <p>Views with Task Lists</p>
      <dl className="shortcut-list">
        <dt>
          <kbd>j</kbd>
        </dt>
        <dd>Move task selection down</dd>
        <dt>
          <kbd>k</kbd>
        </dt>
        <dd>Move task selection up</dd>
        <dt>
          <kbd>d</kbd>
        </dt>
        <dd>Mark task complete</dd>
        <dt>
          <kbd>o</kbd>
        </dt>
        <dd>View task details</dd>
      </dl>
      <p>Task Details</p>
      <dl className="shortcut-list">
        <dt>
          <kbd>e</kbd>
        </dt>
        <dd>Edit task details</dd>
        <dt>
          <kbd>n</kbd>
        </dt>
        <dd>Edit task notes</dd>
        <dt>
          <kbd>a</kbd>
        </dt>
        <dd>Add a subtask</dd>
      </dl>
    </Modal>
  );
}

export default HelpModal;

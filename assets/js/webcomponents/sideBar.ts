class SideBar extends HTMLElement {
  connectedCallback() {
    const button = this.querySelector('[data-expander]');
    if (!button) {
      console.error('Could not find expander for side-bar');
      return;
    }
    const sidebar = this;
    button.addEventListener('click', function (evt) {
      evt.preventDefault();
      const current = sidebar.dataset.expanded;
      sidebar.dataset.expanded = current === 'false' ? 'true' : 'false';
    });
  }
}

customElements.define('side-bar', SideBar);

export default SideBar;

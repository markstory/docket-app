import {marked} from 'marked';

class MarkdownText extends HTMLElement {
  private showPreview = true;
  private previewElement: HTMLElement | null = null;

  connectedCallback() {
    var input = this.querySelector('textarea');
    if (!input) {
      console.error('Missing textarea element');
      return;
    }
    // Setup input events.
    input.style.height = input.scrollHeight + 'px';
    input.addEventListener('input', evt => {
      const el = evt.target as HTMLInputElement;
      el.style.height = '0';
      el.style.height = el.scrollHeight + 'px';
    });
    input.addEventListener('blur', () => {
      this.showPreview = true;
      this.update();
    });

    const preview = this.getPreviewElement();
    preview.addEventListener('click', evt => {
      const target = evt.target;
      // Don't change modes on links
      if (target instanceof HTMLElement && target.nodeName == 'A' && !target.classList.contains('button-focusreveal')) {
        return;
      }
      // Toggle to update mode
      evt.preventDefault();
      this.showPreview = false;
      this.update();
    });
    preview.addEventListener('keyup', evt => {
      if (evt.key === ' ' || evt.key === 'Enter') {
        this.showPreview = false;
        this.update();
      }
    });

    this.update();
  }

  async update() {
    const input = this.querySelector('textarea');
    const preview = this.querySelector('.markdown-text-preview') as HTMLElement;
    if (!input || !preview) {
      console.error('Missing preview or input element');
      return;
    }
    if (this.showPreview) {
      let contents = '';
      if (input.value.trim() === '') {
        contents = `
<p>
  <span role="button" class="button-muted" tabindex="0"
    aria-label="Click or Press Enter to add notes to this task"
  >
    Add notes
  </span>
</p>`;
      } else {
        // TODO apply dom purify
        contents = await marked.parse(input.value);
        contents += `
<a tabindex="0" href="" class="button button-muted button-narrow button-focusreveal">Edit Body</a>`;
      }
      preview.innerHTML = contents;
      preview.style.display = 'block';
      input.style.display = 'none';
    } else {
      preview.style.display = 'none';
      input.style.display = 'block';
      input.focus();
    }
  }

  getPreviewElement() {
    if (this.previewElement) {
      return this.previewElement;
    }
    const preview = document.createElement('div');
    preview.classList.add('markdown-text-preview');
    preview.role = 'button';

    this.appendChild(preview);
    this.previewElement = preview;

    return preview;
  }
}

customElements.define('markdown-text', MarkdownText);

export default MarkdownText;

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
    input.addEventListener('keyup', evt => {
      var input = evt.target as HTMLTextAreaElement;
      input.style.height = input.scrollHeight + 'px';
    });
    input.addEventListener('blur', () => {
      this.showPreview = true;
      this.update();
    });

    const preview = this.getPreviewElement();
    preview.addEventListener('click', (evt) => {
      this.showPreview = false;
      this.update();
    });

    this.update();
  }

  update() {
    const input = this.querySelector('textarea');
    const preview = this.querySelector('.markdown-text-preview') as HTMLElement;
    if (!input || !preview) {
      console.error('Missing preview or input element');
      return;
    }
    if (this.showPreview) {
      preview.innerHTML = marked.parse(input.value);
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

    this.appendChild(preview);
    this.previewElement = preview;

    return preview;
  }
}

customElements.define('markdown-text', MarkdownText);

export default MarkdownText;

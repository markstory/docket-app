import DOMpurify from 'dompurify';
import marked from 'marked';

type Props = {
  text: null | string;
};
function MarkdownText({text}: Props): JSX.Element {
  let contents = '';
  if (text) {
    const html = marked(text);
    contents = DOMpurify.sanitize(html, {USE_PROFILES: {html: true}});
  }

  return <div className="markdown-text" dangerouslySetInnerHTML={{__html: contents}} />;
}

export default MarkdownText;

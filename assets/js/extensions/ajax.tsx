import htmx from 'htmx.org';

htmx.defineExtension('ajax-header', {
  onEvent: function (name, evt) {
    if (name === 'htmx:configRequest') {
      evt.detail.headers['X-Requested-With'] = 'XMLHttpRequest';
      evt.detail.headers['X-Csrf-Token'] = document.getElementById('csrf-token').getAttribute('content');
    }
  },
});

import htmx from 'htmx.org';

htmx.registerExtension('ajax-header', {
  htmx_config_request: function (evt: Event) {
    evt.detail.headers['X-Requested-With'] = 'XMLHttpRequest';
    evt.detail.headers['X-Csrf-Token'] = document.getElementById('csrf-token').getAttribute('content');
  },
});

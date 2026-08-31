import htmx from 'htmx.org';

htmx.registerExtension('ajax-header', {
  htmx_config_request: function (element: HTMLElement, detail) {
    detail.ctx.request.headers['X-Requested-With'] = 'XMLHttpRequest';
    detail.ctx.request.headers['X-Csrf-Token'] = document.getElementById('csrf-token').getAttribute('content');
  },
});

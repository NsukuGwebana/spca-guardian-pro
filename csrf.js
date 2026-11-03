/* Adds the signed-in user's security token to every form and script request on staff pages,
   so the server can tell the request really came from this site (CSRF protection). */
(function () {
  var token = null;
  var ready = fetch('csrf.php', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
    .then(function (r) { return r.json(); })
    .then(function (d) { token = d.token; addToForms(); })
    .catch(function () {});

  function addToForms() {
    if (!token) return;
    document.querySelectorAll('form').forEach(function (f) {
      if ((f.getAttribute('method') || '').toLowerCase() !== 'post') return;
      var input = f.querySelector('input[name="csrf_token"]');
      if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        f.appendChild(input);
      }
      input.value = token;
    });
  }
  document.addEventListener('DOMContentLoaded', addToForms);

  // If someone submits a form before the token has arrived, wait for it, then send the form.
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if ((f.getAttribute('method') || '').toLowerCase() !== 'post' || token || f.dataset.csrfWaited) return;
    e.preventDefault();
    f.dataset.csrfWaited = '1';
    var btn = e.submitter;   // keep the clicked button's name/value, which some pages check
    ready.then(function () {
      addToForms();
      if (f.requestSubmit) { f.requestSubmit(btn && btn.form === f ? btn : undefined); }
      else { HTMLFormElement.prototype.submit.call(f); }
    });
  }, true);

  // Requests made by page scripts (fetch and XMLHttpRequest) carry the token as a header.
  var origFetch = window.fetch;
  window.fetch = function (input, init) {
    init = init || {};
    var method = (init.method || 'GET').toUpperCase();
    if (method !== 'GET' && token) {
      var h = new Headers(init.headers || {});
      h.set('X-CSRF-Token', token);
      init.headers = h;
    }
    return origFetch.call(this, input, init);
  };
  var origOpen = XMLHttpRequest.prototype.open, origSend = XMLHttpRequest.prototype.send;
  XMLHttpRequest.prototype.open = function (method) { this._m = (method || 'GET').toUpperCase(); return origOpen.apply(this, arguments); };
  XMLHttpRequest.prototype.send = function () {
    if (this._m !== 'GET' && token) { try { this.setRequestHeader('X-CSRF-Token', token); } catch (e) {} }
    return origSend.apply(this, arguments);
  };
  window.SPCA_CSRF_READY = ready;
})();

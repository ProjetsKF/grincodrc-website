(function () {
  'use strict';

  var body = document.body;
  var sidebar = document.getElementById('admin-sidebar');
  var toggle = document.querySelector('[data-admin-sidebar-toggle]');
  var closeButtons = document.querySelectorAll('[data-admin-sidebar-close]');

  if (!body || !sidebar || !toggle) {
    return;
  }

  function setSidebarState(open) {
    body.classList.toggle('admin-sidebar-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  toggle.addEventListener('click', function () {
    setSidebarState(!body.classList.contains('admin-sidebar-open'));
  });

  Array.prototype.forEach.call(closeButtons, function (button) {
    button.addEventListener('click', function () {
      setSidebarState(false);
    });
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      setSidebarState(false);
    }
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth >= 992) {
      setSidebarState(false);
    }
  });
}());

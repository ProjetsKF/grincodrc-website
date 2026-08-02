(function () {
  'use strict';
  var app = document.querySelector('[data-quotes-app]');
  if (!app) { return; }
  var endpoint = app.getAttribute('data-endpoint');
  var detailBase = app.getAttribute('data-detail-base');
  var input = document.getElementById('quote-search');
  var form = input ? input.closest('form') : null;
  var body = document.getElementById('quotes-table-body');
  var pagination = document.getElementById('quotes-pagination');
  var summary = document.getElementById('quote-results-summary');
  var status = document.getElementById('quote-search-status');
  var timer = null;
  var controller = null;

  function cell(label, value, strong) {
    var td = document.createElement('td');
    td.setAttribute('data-label', label);
    var element = strong ? document.createElement('strong') : document.createTextNode(value);
    if (strong) { element.textContent = value; }
    td.appendChild(element);
    return td;
  }
  function renderRows(data) {
    body.textContent = '';
    if (!data.rows.length) {
      var tr = document.createElement('tr'); var td = document.createElement('td');
      tr.className = 'admin-empty-table-row'; td.colSpan = 8;
      td.innerHTML = '<i class="bi bi-file-earmark-text" aria-hidden="true"></i><strong>Aucune demande trouvée</strong><span>Modifiez votre recherche.</span>';
      tr.appendChild(td); body.appendChild(tr); return;
    }
    data.rows.forEach(function (quote, index) {
      var tr = document.createElement('tr');
      tr.appendChild(cell('N°', String(data.offset + index + 1)));
      tr.appendChild(cell('Client', quote.nom, true));
      tr.appendChild(cell('Entreprise', quote.entreprise || '—'));
      tr.appendChild(cell('Téléphone', quote.telephone));
      tr.appendChild(cell('E-mail', quote.email || '—'));
      var countCell = cell('Nombre de produits', ''); var badge = document.createElement('span'); badge.className = 'admin-count-badge'; badge.textContent = quote.nombre_produits; countCell.appendChild(badge); tr.appendChild(countCell);
      tr.appendChild(cell('Date de demande', quote.date_formatted));
      var actions = document.createElement('td'); actions.className = 'admin-actions-cell'; actions.setAttribute('data-label', 'Actions');
      var link = document.createElement('a'); link.className = 'admin-icon-button is-edit'; link.href = detailBase + '?id=' + encodeURIComponent(quote.id); link.title = 'Voir les détails'; link.setAttribute('aria-label', 'Voir les détails de la demande de ' + quote.nom); link.innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i>'; actions.appendChild(link); tr.appendChild(actions);
      body.appendChild(tr);
    });
  }
  function pages(current, total) {
    var result = [], page; if (total <= 7) { for (page = 1; page <= total; page += 1) { result.push(page); } return result; }
    result.push(1); if (current > 4) { result.push('start'); }
    for (page = Math.max(2, current - 1); page <= Math.min(total - 1, current + 1); page += 1) { result.push(page); }
    if (current < total - 3) { result.push('end'); } result.push(total); return result;
  }
  function pageUrl(page, search) { var url = new URL(endpoint, window.location.origin); url.searchParams.set('page', page); if (search) { url.searchParams.set('q', search); } return url.pathname + url.search; }
  function pageItem(label, page, search, disabled, active) {
    var li = document.createElement('li'); li.className = disabled ? 'is-disabled' : (active ? 'is-active' : '');
    var item = disabled ? document.createElement('span') : document.createElement('a'); item.textContent = label;
    if (!disabled) { item.href = pageUrl(page, search); item.setAttribute('data-page', page); } if (active) { item.setAttribute('aria-current', 'page'); } li.appendChild(item); return li;
  }
  function renderPagination(data, search) {
    pagination.textContent = ''; pagination.appendChild(pageItem('Précédent', data.page - 1, search, data.page <= 1, false));
    pages(data.page, data.total_pages).forEach(function (value) { if (typeof value === 'number') { pagination.appendChild(pageItem(String(value), value, search, false, value === data.page)); } else { var li = document.createElement('li'); li.className = 'is-ellipsis'; li.innerHTML = '<span>…</span>'; pagination.appendChild(li); } });
    pagination.appendChild(pageItem('Suivant', data.page + 1, search, data.page >= data.total_pages, false));
  }
  function load(page) {
    var search = input.value.trim(); var url = new URL(endpoint, window.location.origin); url.searchParams.set('ajax', '1'); url.searchParams.set('page', page); if (search) { url.searchParams.set('q', search); }
    if (controller) { controller.abort(); } controller = typeof AbortController !== 'undefined' ? new AbortController() : null; if (form) { form.classList.add('is-loading'); }
    fetch(url.toString(), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller ? controller.signal : undefined }).then(function (response) { if (!response.ok) { throw new Error('failed'); } return response.json(); }).then(function (payload) {
      if (!payload.success) { throw new Error('failed'); } renderRows(payload.data); renderPagination(payload.data, search); summary.textContent = payload.data.total ? (payload.data.offset + 1) + '–' + Math.min(payload.data.offset + payload.data.per_page, payload.data.total) + ' sur ' + payload.data.total : 'Aucune demande'; status.textContent = payload.data.total + ' demande' + (payload.data.total === 1 ? '' : 's') + ' trouvée' + (payload.data.total === 1 ? '' : 's') + '.'; window.history.replaceState({}, '', pageUrl(payload.data.page, search));
    }).catch(function (error) { if (error.name !== 'AbortError') { status.textContent = 'La recherche est indisponible.'; } }).then(function () { if (form) { form.classList.remove('is-loading'); } });
  }
  input.addEventListener('input', function () { window.clearTimeout(timer); timer = window.setTimeout(function () { load(1); }, 280); });
  form.addEventListener('submit', function (event) { event.preventDefault(); load(1); });
  pagination.addEventListener('click', function (event) { var link = event.target.closest('a[data-page]'); if (link) { event.preventDefault(); load(parseInt(link.getAttribute('data-page'), 10) || 1); } });
}());

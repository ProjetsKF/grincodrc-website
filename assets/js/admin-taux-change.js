(function () {
  'use strict';

  var application = document.querySelector('[data-exchange-rates-app]');
  if (!application) { return; }

  var endpoint = application.getAttribute('data-endpoint');
  var searchInput = document.getElementById('rate-search');
  var searchStatus = document.getElementById('rate-search-status');
  var resultsSummary = document.getElementById('rate-results-summary');
  var tableBody = document.getElementById('rates-table-body');
  var pagination = document.getElementById('rates-pagination');
  var searchField = searchInput ? searchInput.closest('.admin-search-field') : null;
  var requestController = null;
  var requestSequence = 0;
  var searchTimer = null;

  function createCell(label, content) {
    var cell = document.createElement('td');
    cell.setAttribute('data-label', label);
    if (content instanceof Node) { cell.appendChild(content); } else { cell.textContent = content; }
    return cell;
  }

  function createAction(type, rate) {
    var isEdit = type === 'edit';
    var button = document.createElement('button');
    var icon = document.createElement('i');
    var label = rate.devise_source + ' vers ' + rate.devise_destination;
    button.type = 'button';
    button.className = 'admin-icon-button ' + (isEdit ? 'is-edit' : 'is-delete');
    button.title = isEdit ? 'Modifier le taux' : 'Supprimer le taux';
    button.setAttribute('aria-label', (isEdit ? 'Modifier le taux ' : 'Supprimer le taux ') + label);
    button.setAttribute(isEdit ? 'data-rate-edit' : 'data-rate-delete', '');
    button.setAttribute('data-rate-id', rate.id);
    button.setAttribute('data-bs-toggle', 'modal');
    button.setAttribute('data-bs-target', isEdit ? '#edit-rate-modal' : '#delete-rate-modal');
    if (isEdit) {
      button.setAttribute('data-rate-source', rate.devise_source);
      button.setAttribute('data-rate-destination', rate.devise_destination);
      button.setAttribute('data-rate-value', rate.taux);
    } else {
      button.setAttribute('data-rate-label', rate.devise_source + ' → ' + rate.devise_destination);
    }
    icon.className = 'bi ' + (isEdit ? 'bi-pencil-square' : 'bi-trash');
    icon.setAttribute('aria-hidden', 'true');
    button.appendChild(icon);
    return button;
  }

  function renderRows(data) {
    tableBody.textContent = '';
    if (!data.rows.length) {
      var emptyRow = document.createElement('tr');
      var emptyCell = document.createElement('td');
      var icon = document.createElement('i');
      var title = document.createElement('strong');
      var text = document.createElement('span');
      emptyRow.className = 'admin-empty-table-row';
      emptyCell.colSpan = 6;
      icon.className = 'bi bi-currency-exchange';
      icon.setAttribute('aria-hidden', 'true');
      title.textContent = 'Aucun taux trouvé';
      text.textContent = 'Ajoutez un taux ou modifiez votre recherche.';
      emptyCell.appendChild(icon); emptyCell.appendChild(title); emptyCell.appendChild(text);
      emptyRow.appendChild(emptyCell); tableBody.appendChild(emptyRow);
      return;
    }

    data.rows.forEach(function (rate, index) {
      var row = document.createElement('tr');
      var source = document.createElement('span');
      var destination = document.createElement('span');
      var rateValue = document.createElement('strong');
      var conversion = document.createElement('span');
      var actions = document.createElement('td');
      source.className = destination.className = 'admin-currency-badge';
      source.textContent = rate.devise_source;
      destination.textContent = rate.devise_destination;
      rateValue.className = 'admin-rate-value';
      rateValue.textContent = rate.taux_formatted;
      conversion.className = 'admin-conversion-example';
      conversion.textContent = rate.conversion_example;
      actions.className = 'admin-actions-cell';
      actions.setAttribute('data-label', 'Actions');
      actions.appendChild(createAction('edit', rate));
      actions.appendChild(createAction('delete', rate));
      row.appendChild(createCell('N°', data.offset + index + 1));
      row.appendChild(createCell('Devise source', source));
      row.appendChild(createCell('Devise de destination', destination));
      row.appendChild(createCell('Taux', rateValue));
      row.appendChild(createCell('Conversion d’exemple', conversion));
      row.appendChild(actions);
      tableBody.appendChild(row);
    });
  }

  function paginationItems(currentPage, totalPages) {
    var items = [], page;
    if (totalPages <= 7) { for (page = 1; page <= totalPages; page += 1) { items.push(page); } return items; }
    items.push(1);
    if (currentPage > 4) { items.push('start'); }
    for (page = Math.max(2, currentPage - 1); page <= Math.min(totalPages - 1, currentPage + 1); page += 1) { items.push(page); }
    if (currentPage < totalPages - 3) { items.push('end'); }
    items.push(totalPages);
    return items;
  }

  function rateUrl(page, search) {
    var url = new URL(endpoint, window.location.origin);
    url.searchParams.set('page', page);
    if (search) { url.searchParams.set('q', search); }
    return url.pathname + url.search;
  }

  function paginationLink(label, page, search, disabled, active) {
    var item = document.createElement('li');
    var element = disabled ? document.createElement('span') : document.createElement('a');
    item.className = disabled ? 'is-disabled' : (active ? 'is-active' : '');
    element.textContent = label;
    if (!disabled) { element.href = rateUrl(page, search); element.setAttribute('data-page', page); }
    if (active) { element.setAttribute('aria-current', 'page'); }
    item.appendChild(element);
    return item;
  }

  function renderPagination(data, search) {
    pagination.textContent = '';
    pagination.appendChild(paginationLink('Précédent', data.page - 1, search, data.page <= 1, false));
    paginationItems(data.page, data.total_pages).forEach(function (item) {
      if (typeof item === 'number') {
        pagination.appendChild(paginationLink(String(item), item, search, false, item === data.page));
      } else {
        var ellipsisItem = document.createElement('li');
        var ellipsis = document.createElement('span');
        ellipsisItem.className = 'is-ellipsis'; ellipsis.textContent = '…'; ellipsis.setAttribute('aria-hidden', 'true');
        ellipsisItem.appendChild(ellipsis); pagination.appendChild(ellipsisItem);
      }
    });
    pagination.appendChild(paginationLink('Suivant', data.page + 1, search, data.page >= data.total_pages, false));
  }

  function updateContext(search, page) {
    Array.prototype.forEach.call(document.querySelectorAll('input[name="return_search"]'), function (input) { input.value = search; });
    Array.prototype.forEach.call(document.querySelectorAll('input[name="return_page"]'), function (input) { input.value = page; });
  }

  function setLoading(loading) {
    if (searchField) { searchField.classList.toggle('is-loading', loading); }
    if (searchInput) { searchInput.setAttribute('aria-busy', loading ? 'true' : 'false'); }
  }

  function loadRates(page) {
    var search = searchInput ? searchInput.value.trim() : '';
    var url = new URL(endpoint, window.location.origin);
    var currentRequest = requestSequence + 1;
    requestSequence = currentRequest;
    url.searchParams.set('ajax', '1'); url.searchParams.set('page', page);
    if (search) { url.searchParams.set('q', search); }
    if (requestController && typeof requestController.abort === 'function') { requestController.abort(); }
    requestController = typeof AbortController !== 'undefined' ? new AbortController() : null;
    setLoading(true);
    fetch(url.toString(), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: requestController ? requestController.signal : undefined })
      .then(function (response) { if (!response.ok) { throw new Error('request_failed'); } return response.json(); })
      .then(function (payload) {
        if (!payload.success) { throw new Error('response_failed'); }
        if (currentRequest !== requestSequence) { return; }
        renderRows(payload.data); renderPagination(payload.data, search); updateContext(search, payload.data.page);
        resultsSummary.textContent = payload.data.total ? (payload.data.offset + 1) + '–' + Math.min(payload.data.offset + payload.data.per_page, payload.data.total) + ' sur ' + payload.data.total : 'Aucun taux';
        searchStatus.textContent = payload.data.total + ' taux trouvé' + (payload.data.total > 1 ? 's' : '') + '.';
        window.history.replaceState({}, '', rateUrl(payload.data.page, search));
      })
      .catch(function (error) { if (error.name !== 'AbortError') { searchStatus.textContent = 'La recherche est indisponible.'; } })
      .then(function () { if (currentRequest === requestSequence) { setLoading(false); } });
  }

  if (searchInput) { searchInput.addEventListener('input', function () { window.clearTimeout(searchTimer); searchTimer = window.setTimeout(function () { loadRates(1); }, 280); }); }
  if (searchField) { searchField.addEventListener('submit', function (event) { event.preventDefault(); window.clearTimeout(searchTimer); loadRates(1); }); }
  if (pagination) { pagination.addEventListener('click', function (event) { var link = event.target.closest('a[data-page]'); if (link) { event.preventDefault(); loadRates(parseInt(link.getAttribute('data-page'), 10) || 1); } }); }
  if (tableBody) {
    tableBody.addEventListener('click', function (event) {
      var editButton = event.target.closest('[data-rate-edit]');
      var deleteButton = event.target.closest('[data-rate-delete]');
      if (editButton) {
        document.getElementById('edit-rate-id').value = editButton.getAttribute('data-rate-id');
        document.getElementById('edit-rate-source').value = editButton.getAttribute('data-rate-source');
        document.getElementById('edit-rate-destination').value = editButton.getAttribute('data-rate-destination');
        document.getElementById('edit-rate-value').value = editButton.getAttribute('data-rate-value');
      }
      if (deleteButton) {
        document.getElementById('delete-rate-id').value = deleteButton.getAttribute('data-rate-id');
        document.getElementById('delete-rate-label').textContent = deleteButton.getAttribute('data-rate-label');
      }
    });
  }
  Array.prototype.forEach.call(document.querySelectorAll('input[name="devise_source"], input[name="devise_destination"]'), function (input) {
    input.addEventListener('input', function () { input.value = input.value.toUpperCase().replace(/[^A-Z]/g, '').slice(0, 3); });
  });
}());

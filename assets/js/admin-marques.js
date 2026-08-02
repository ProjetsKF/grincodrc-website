(function () {
  'use strict';

  var application = document.querySelector('[data-brands-app]');
  if (!application) {
    return;
  }

  var endpoint = application.getAttribute('data-endpoint');
  var searchInput = document.getElementById('brand-search');
  var searchStatus = document.getElementById('brand-search-status');
  var resultsSummary = document.getElementById('brand-results-summary');
  var tableBody = document.getElementById('brands-table-body');
  var pagination = document.getElementById('brands-pagination');
  var searchField = searchInput ? searchInput.closest('.admin-search-field') : null;
  var requestController = null;
  var requestSequence = 0;
  var searchTimer = null;

  function createIconButton(type, brand) {
    var isEdit = type === 'edit';
    var button = document.createElement('button');
    var icon = document.createElement('i');

    button.type = 'button';
    button.className = 'admin-icon-button ' + (isEdit ? 'is-edit' : 'is-delete');
    button.title = isEdit ? 'Modifier la marque' : 'Supprimer la marque';
    button.setAttribute('aria-label', (isEdit ? 'Modifier la marque ' : 'Supprimer la marque ') + brand.nom);
    button.setAttribute(isEdit ? 'data-brand-edit' : 'data-brand-delete', '');
    button.setAttribute('data-brand-id', brand.id);
    button.setAttribute('data-brand-name', brand.nom);
    button.setAttribute('data-bs-toggle', 'modal');
    button.setAttribute('data-bs-target', isEdit ? '#edit-brand-modal' : '#delete-brand-modal');

    if (isEdit) {
      button.setAttribute('data-brand-description', brand.description || '');
    }

    icon.className = 'bi ' + (isEdit ? 'bi-pencil-square' : 'bi-trash');
    icon.setAttribute('aria-hidden', 'true');
    button.appendChild(icon);
    return button;
  }

  function createCell(label, content) {
    var cell = document.createElement('td');
    cell.setAttribute('data-label', label);
    if (content instanceof Node) {
      cell.appendChild(content);
    } else {
      cell.textContent = content;
    }
    return cell;
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
      emptyCell.colSpan = 4;
      icon.className = 'bi bi-bookmark-star';
      icon.setAttribute('aria-hidden', 'true');
      title.textContent = 'Aucune marque trouvée';
      text.textContent = 'Ajoutez une marque ou modifiez votre recherche.';
      emptyCell.appendChild(icon);
      emptyCell.appendChild(title);
      emptyCell.appendChild(text);
      emptyRow.appendChild(emptyCell);
      tableBody.appendChild(emptyRow);
      return;
    }

    data.rows.forEach(function (brand, index) {
      var row = document.createElement('tr');
      var name = document.createElement('strong');
      var description = document.createElement('span');
      var actions = document.createElement('td');

      name.textContent = brand.nom;
      description.className = 'admin-description-text';
      description.textContent = brand.description || '—';
      actions.className = 'admin-actions-cell';
      actions.setAttribute('data-label', 'Actions');
      actions.appendChild(createIconButton('edit', brand));
      actions.appendChild(createIconButton('delete', brand));

      row.appendChild(createCell('N°', data.offset + index + 1));
      row.appendChild(createCell('Nom', name));
      row.appendChild(createCell('Description', description));
      row.appendChild(actions);
      tableBody.appendChild(row);
    });
  }

  function paginationItems(currentPage, totalPages) {
    var items = [];
    var page;
    if (totalPages <= 7) {
      for (page = 1; page <= totalPages; page += 1) {
        items.push(page);
      }
      return items;
    }
    items.push(1);
    if (currentPage > 4) {
      items.push('ellipsis-start');
    }
    for (page = Math.max(2, currentPage - 1); page <= Math.min(totalPages - 1, currentPage + 1); page += 1) {
      items.push(page);
    }
    if (currentPage < totalPages - 3) {
      items.push('ellipsis-end');
    }
    items.push(totalPages);
    return items;
  }

  function brandUrl(page, search) {
    var url = new URL(endpoint, window.location.origin);
    url.searchParams.set('page', page);
    if (search) {
      url.searchParams.set('q', search);
    }
    return url.pathname + url.search;
  }

  function createPaginationLink(label, page, search, options) {
    var item = document.createElement('li');
    var element;
    options = options || {};
    item.className = options.disabled ? 'is-disabled' : (options.active ? 'is-active' : '');
    element = options.disabled ? document.createElement('span') : document.createElement('a');
    element.textContent = label;
    if (!options.disabled) {
      element.href = brandUrl(page, search);
      element.setAttribute('data-page', page);
    }
    if (options.active) {
      element.setAttribute('aria-current', 'page');
    }
    item.appendChild(element);
    return item;
  }

  function renderPagination(data, search) {
    pagination.textContent = '';
    pagination.appendChild(createPaginationLink('Précédent', data.page - 1, search, { disabled: data.page <= 1 }));
    paginationItems(data.page, data.total_pages).forEach(function (item) {
      if (typeof item === 'number') {
        pagination.appendChild(createPaginationLink(String(item), item, search, { active: item === data.page }));
      } else {
        var ellipsisItem = document.createElement('li');
        var ellipsis = document.createElement('span');
        ellipsisItem.className = 'is-ellipsis';
        ellipsis.textContent = '…';
        ellipsis.setAttribute('aria-hidden', 'true');
        ellipsisItem.appendChild(ellipsis);
        pagination.appendChild(ellipsisItem);
      }
    });
    pagination.appendChild(createPaginationLink('Suivant', data.page + 1, search, { disabled: data.page >= data.total_pages }));
  }

  function updateSummary(data) {
    resultsSummary.textContent = data.total
      ? (data.offset + 1) + '–' + Math.min(data.offset + data.per_page, data.total) + ' sur ' + data.total
      : 'Aucune marque';
  }

  function updateFormContext(search, page) {
    Array.prototype.forEach.call(document.querySelectorAll('input[name="return_search"]'), function (input) {
      input.value = search;
    });
    Array.prototype.forEach.call(document.querySelectorAll('input[name="return_page"]'), function (input) {
      input.value = page;
    });
  }

  function setLoading(loading) {
    if (searchField) {
      searchField.classList.toggle('is-loading', loading);
    }
    if (searchInput) {
      searchInput.setAttribute('aria-busy', loading ? 'true' : 'false');
    }
  }

  function loadBrands(page) {
    var search = searchInput ? searchInput.value.trim() : '';
    var url = new URL(endpoint, window.location.origin);
    var currentRequest = requestSequence + 1;
    requestSequence = currentRequest;
    url.searchParams.set('ajax', '1');
    url.searchParams.set('page', page);
    if (search) {
      url.searchParams.set('q', search);
    }

    if (requestController && typeof requestController.abort === 'function') {
      requestController.abort();
    }
    requestController = typeof AbortController !== 'undefined' ? new AbortController() : null;
    setLoading(true);

    fetch(url.toString(), {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      signal: requestController ? requestController.signal : undefined
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('request_failed');
        }
        return response.json();
      })
      .then(function (payload) {
        if (!payload.success) {
          throw new Error('response_failed');
        }
        if (currentRequest !== requestSequence) {
          return;
        }
        renderRows(payload.data);
        renderPagination(payload.data, search);
        updateSummary(payload.data);
        updateFormContext(search, payload.data.page);
        searchStatus.textContent = payload.data.total + ' marque' + (payload.data.total === 1 ? '' : 's') + ' trouvée' + (payload.data.total === 1 ? '' : 's') + '.';
        window.history.replaceState({}, '', brandUrl(payload.data.page, search));
      })
      .catch(function (error) {
        if (error.name !== 'AbortError') {
          searchStatus.textContent = 'La recherche ne peut pas être effectuée pour le moment.';
        }
      })
      .then(function () {
        if (currentRequest === requestSequence) {
          setLoading(false);
        }
      });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(function () {
        loadBrands(1);
      }, 280);
    });
  }

  if (searchField) {
    searchField.addEventListener('submit', function (event) {
      event.preventDefault();
      window.clearTimeout(searchTimer);
      loadBrands(1);
    });
  }

  if (pagination) {
    pagination.addEventListener('click', function (event) {
      var link = event.target.closest('a[data-page]');
      if (link) {
        event.preventDefault();
        loadBrands(parseInt(link.getAttribute('data-page'), 10) || 1);
      }
    });
  }

  if (tableBody) {
    tableBody.addEventListener('click', function (event) {
      var editButton = event.target.closest('[data-brand-edit]');
      var deleteButton = event.target.closest('[data-brand-delete]');
      if (editButton) {
        document.getElementById('edit-brand-id').value = editButton.getAttribute('data-brand-id');
        document.getElementById('edit-brand-name').value = editButton.getAttribute('data-brand-name');
        document.getElementById('edit-brand-description').value = editButton.getAttribute('data-brand-description') || '';
      }
      if (deleteButton) {
        document.getElementById('delete-brand-id').value = deleteButton.getAttribute('data-brand-id');
        document.getElementById('delete-brand-name').textContent = deleteButton.getAttribute('data-brand-name');
      }
    });
  }
}());

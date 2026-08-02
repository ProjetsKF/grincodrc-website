(function () {
  'use strict';

  var application = document.querySelector('[data-products-app]');
  if (!application) {
    return;
  }

  var endpoint = application.getAttribute('data-endpoint');
  var searchInput = document.getElementById('product-search');
  var searchStatus = document.getElementById('product-search-status');
  var resultsSummary = document.getElementById('product-results-summary');
  var tableBody = document.getElementById('products-table-body');
  var pagination = document.getElementById('products-pagination');
  var searchField = searchInput ? searchInput.closest('.admin-search-field') : null;
  var requestController = null;
  var requestSequence = 0;
  var searchTimer = null;

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

  function createProductName(product) {
    var summary = document.createElement('span');
    var thumbnail;
    var wrapper = document.createElement('span');
    var name = document.createElement('strong');
    summary.className = 'admin-product-summary';
    if (product.image_url) {
      thumbnail = document.createElement('img');
      thumbnail.className = 'admin-product-thumbnail';
      thumbnail.src = product.image_url;
      thumbnail.alt = 'Image principale de ' + product.nom;
      thumbnail.loading = 'lazy';
    } else {
      thumbnail = document.createElement('span');
      thumbnail.className = 'admin-product-thumbnail is-empty';
      thumbnail.setAttribute('aria-hidden', 'true');
      var placeholderIcon = document.createElement('i');
      placeholderIcon.className = 'bi bi-image';
      thumbnail.appendChild(placeholderIcon);
    }
    wrapper.className = 'admin-product-name';
    name.textContent = product.nom;
    wrapper.appendChild(name);
    if (product.modele) {
      var model = document.createElement('small');
      model.textContent = product.modele;
      wrapper.appendChild(model);
    }
    summary.appendChild(thumbnail);
    summary.appendChild(wrapper);
    return summary;
  }

  function createProductPrice(product) {
    var wrapper = document.createElement('span');
    var usd = document.createElement('strong');
    var cny = document.createElement('small');
    wrapper.className = 'admin-price-stack';
    usd.className = 'admin-price-value';
    usd.textContent = product.prix_formatted;
    cny.textContent = product.prix_cny_formatted;
    if (product.prix_cny_formatted === 'Taux non configuré') {
      cny.className = 'is-missing';
    }
    wrapper.appendChild(usd);
    wrapper.appendChild(cny);
    return wrapper;
  }

  function createIconButton(type, product) {
    var isEdit = type === 'edit';
    var button = document.createElement('button');
    var icon = document.createElement('i');

    button.type = 'button';
    button.className = 'admin-icon-button ' + (isEdit ? 'is-edit' : 'is-delete');
    button.title = isEdit ? 'Modifier le produit' : 'Supprimer le produit';
    button.setAttribute('aria-label', (isEdit ? 'Modifier le produit ' : 'Supprimer le produit ') + product.nom);
    button.setAttribute(isEdit ? 'data-product-edit' : 'data-product-delete', '');
    button.setAttribute('data-product-id', product.id);
    button.setAttribute('data-product-name', product.nom);
    button.setAttribute('data-bs-toggle', 'modal');
    button.setAttribute('data-bs-target', isEdit ? '#edit-product-modal' : '#delete-product-modal');

    if (isEdit) {
      button.setAttribute('data-product-category', product.categorie_id);
      button.setAttribute('data-product-brand', product.marque_id);
      button.setAttribute('data-product-reference', product.reference);
      button.setAttribute('data-product-model', product.modele || '');
      button.setAttribute('data-product-price', product.prix);
      button.setAttribute('data-product-description', product.description || '');
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
      var message = document.createElement('span');
      emptyRow.className = 'admin-empty-table-row';
      emptyCell.colSpan = 9;
      icon.className = 'bi bi-box-seam';
      icon.setAttribute('aria-hidden', 'true');
      title.textContent = 'Aucun produit trouvé';
      message.textContent = 'Ajoutez un produit ou modifiez votre recherche.';
      emptyCell.appendChild(icon);
      emptyCell.appendChild(title);
      emptyCell.appendChild(message);
      emptyRow.appendChild(emptyCell);
      tableBody.appendChild(emptyRow);
      return;
    }

    data.rows.forEach(function (product, index) {
      var row = document.createElement('tr');
      var reference = document.createElement('span');
      var actions = document.createElement('td');
      reference.className = 'admin-reference-value';
      reference.textContent = product.reference;
      actions.className = 'admin-actions-cell';
      actions.setAttribute('data-label', 'Actions');
      actions.appendChild(createIconButton('edit', product));
      actions.appendChild(createIconButton('delete', product));

      row.appendChild(createCell('N°', data.offset + index + 1));
      row.appendChild(createCell('Référence', reference));
      row.appendChild(createCell('Produit', createProductName(product)));
      row.appendChild(createCell('Catégorie', product.categorie_nom));
      row.appendChild(createCell('Marque', product.marque_nom));
      row.appendChild(createCell('Prix (USD / CNY)', createProductPrice(product)));
      row.appendChild(createCell('Administrateur', product.administrateur_nom));
      row.appendChild(createCell('Date de création', product.date_creation_formatted));
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

  function productUrl(page, search) {
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
      element.href = productUrl(page, search);
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
      : 'Aucun produit';
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

  function loadProducts(page) {
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
        searchStatus.textContent = payload.data.total + ' produit' + (payload.data.total === 1 ? '' : 's') + ' trouvé' + (payload.data.total === 1 ? '' : 's') + '.';
        window.history.replaceState({}, '', productUrl(payload.data.page, search));
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
        loadProducts(1);
      }, 280);
    });
  }

  if (searchField) {
    searchField.addEventListener('submit', function (event) {
      event.preventDefault();
      window.clearTimeout(searchTimer);
      loadProducts(1);
    });
  }

  if (pagination) {
    pagination.addEventListener('click', function (event) {
      var link = event.target.closest('a[data-page]');
      if (link) {
        event.preventDefault();
        loadProducts(parseInt(link.getAttribute('data-page'), 10) || 1);
      }
    });
  }

  if (tableBody) {
    tableBody.addEventListener('click', function (event) {
      var editButton = event.target.closest('[data-product-edit]');
      var deleteButton = event.target.closest('[data-product-delete]');
      if (editButton) {
        document.getElementById('edit-product-id').value = editButton.getAttribute('data-product-id');
        document.getElementById('edit-product-category').value = editButton.getAttribute('data-product-category');
        document.getElementById('edit-product-brand').value = editButton.getAttribute('data-product-brand');
        document.getElementById('edit-product-reference').value = editButton.getAttribute('data-product-reference');
        document.getElementById('edit-product-name').value = editButton.getAttribute('data-product-name');
        document.getElementById('edit-product-model').value = editButton.getAttribute('data-product-model') || '';
        document.getElementById('edit-product-price').value = editButton.getAttribute('data-product-price');
        document.getElementById('edit-product-description').value = editButton.getAttribute('data-product-description') || '';
      }
      if (deleteButton) {
        document.getElementById('delete-product-id').value = deleteButton.getAttribute('data-product-id');
        document.getElementById('delete-product-name').textContent = deleteButton.getAttribute('data-product-name');
      }
    });
  }
}());

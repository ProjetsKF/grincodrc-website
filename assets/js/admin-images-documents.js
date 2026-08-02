(function () {
  'use strict';

  var application = document.querySelector('[data-media-app]');
  if (!application) {
    return;
  }

  var productSelector = document.querySelector('[data-product-selector]');
  var productSelectorForm = document.querySelector('[data-product-selector-form]');
  var imageInput = document.querySelector('[data-image-files]');
  var primarySelect = document.querySelector('[data-primary-image-select]');

  if (productSelector && productSelectorForm) {
    productSelector.addEventListener('change', function () {
      productSelectorForm.submit();
    });
  }

  if (imageInput && primarySelect) {
    imageInput.addEventListener('change', function () {
      var files = imageInput.files || [];
      var automatic = document.createElement('option');
      primarySelect.textContent = '';
      automatic.value = '';
      automatic.textContent = 'Automatique';
      primarySelect.appendChild(automatic);

      Array.prototype.forEach.call(files, function (file, index) {
        var option = document.createElement('option');
        option.value = index;
        option.textContent = 'Image ' + (index + 1) + ' — ' + file.name;
        primarySelect.appendChild(option);
      });

      if (files.length > 10) {
        imageInput.setCustomValidity('Vous pouvez sélectionner au maximum 10 images.');
      } else {
        imageInput.setCustomValidity('');
      }
    });
  }

  Array.prototype.forEach.call(document.querySelectorAll('input[type="file"][name="documents[]"]'), function (input) {
    input.addEventListener('change', function () {
      var files = input.files || [];
      input.setCustomValidity(files.length > 10 ? 'Vous pouvez sélectionner au maximum 10 documents.' : '');
    });
  });

  application.addEventListener('click', function (event) {
    var imageDeleteButton = event.target.closest('[data-image-delete]');
    var documentDeleteButton = event.target.closest('[data-document-delete]');

    if (imageDeleteButton) {
      document.getElementById('delete-image-id').value = imageDeleteButton.getAttribute('data-image-id');
      document.getElementById('delete-image-name').textContent = imageDeleteButton.getAttribute('data-image-name');
    }
    if (documentDeleteButton) {
      document.getElementById('delete-document-id').value = documentDeleteButton.getAttribute('data-document-id');
      document.getElementById('delete-document-name').textContent = documentDeleteButton.getAttribute('data-document-name');
    }
  });

  Array.prototype.forEach.call(document.querySelectorAll('[data-bs-toggle="tab"]'), function (tabButton) {
    tabButton.addEventListener('shown.bs.tab', function (event) {
      var isDocuments = event.target.getAttribute('data-bs-target') === '#documents-panel';
      var tabName = isDocuments ? 'documents' : 'images';
      var hiddenTab = productSelectorForm ? productSelectorForm.querySelector('input[name="tab"]') : null;
      if (hiddenTab) {
        hiddenTab.value = tabName;
      }
      if (window.history && typeof window.history.replaceState === 'function') {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url.pathname + url.search);
      }
    });
  });
}());

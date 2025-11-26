// JS scaffold for the search section with expandable filters
// - Collects form data and exposes a hook for AJAX submission.
// - Currently logs payload to console and updates #search-results with a placeholder.

(function(){
  'use strict';

  function serializeForm(form){
    const data = new FormData(form);
    const obj = {};
    for (const [k,v] of data.entries()){
      // convert empty strings to null for clarity
      obj[k] = v === '' ? null : v;
    }
    return obj;
  }

  function renderLoading(container){
    container.innerHTML = '<div class="text-center py-4">Loading results&hellip;</div>';
  }

  function renderPlaceholder(container, payload){
    container.innerHTML = '<div class="card p-3"><pre style="white-space:pre-wrap;">' +
      'AJAX payload (placeholder):\n' + JSON.stringify(payload, null, 2) +
      '</pre></div>';
  }

  document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('search-form');
    const results = document.getElementById('search-results');
    const clearBtn = document.getElementById('clear-filters');
    const filterToggle = form ? form.querySelector('[data-bs-toggle="collapse"][data-bs-target]') : null;

    if (!form || !results) return;

    form.addEventListener('submit', function(e){
      e.preventDefault();
      const payload = serializeForm(form);
      // show loading
      renderLoading(results);

      // TODO: Replace this with real AJAX (fetch/XHR) to your search endpoint.
      // Example:
      // fetch('/search', { method: 'POST', body: new URLSearchParams(payload) })
      //   .then(r => r.text())
      //   .then(html => { results.innerHTML = html })
      //   .catch(err => { results.innerHTML = '<div class="text-danger">Error</div>' });

      // For now show payload for integration testing
    //   setTimeout(function(){
    //     console.log('Search payload:', payload);
    //     renderPlaceholder(results, payload);
    //   }, 400);
    });

    if (clearBtn){
      clearBtn.addEventListener('click', function(){
        form.reset();
        // collapse filters (if using bootstrap collapse)
        const collapseEl = document.getElementById('search-filters');
        if (collapseEl && typeof bootstrap !== 'undefined'){
          const bsCollapse = bootstrap.Collapse.getInstance(collapseEl) || new bootstrap.Collapse(collapseEl, {toggle:false});
          bsCollapse.hide();
        }
        // trigger an empty search or clear results
        results.innerHTML = '<div class="text-muted">Filters cleared.</div>';
      });
    }

    // Lightweight toggle for the Filters button: works even if Bootstrap JS isn't initialized yet.
    if (filterToggle){
      filterToggle.addEventListener('click', function(e){
        // determine target selector and element
        const targetSel = filterToggle.getAttribute('data-bs-target') || filterToggle.getAttribute('data-target');
        if (!targetSel) return;
        const collapseEl = document.querySelector(targetSel);
        if (!collapseEl) return;

        // If Bootstrap is available, use its Collapse to preserve animations/aria
        if (typeof bootstrap !== 'undefined' && bootstrap.Collapse){
          const bsCollapse = bootstrap.Collapse.getInstance(collapseEl) || new bootstrap.Collapse(collapseEl, {toggle:false});
          // toggle via Bootstrap API
          bsCollapse._isShown() ? bsCollapse.hide() : bsCollapse.show();
          return;
        }

        // Fallback: toggle the show class and aria-expanded attribute
        const isShown = collapseEl.classList.contains('show');
        if (isShown){
          collapseEl.classList.remove('show');
          collapseEl.setAttribute('aria-hidden', 'true');
          filterToggle.setAttribute('aria-expanded', 'false');
        } else {
          collapseEl.classList.add('show');
          collapseEl.setAttribute('aria-hidden', 'false');
          filterToggle.setAttribute('aria-expanded', 'true');
        }
      });
    }
  });

})();

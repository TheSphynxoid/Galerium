// JS pour la recherche et filtrage par AJAX
// Gère les formulaires avec l'attribut data-ajax-url

(function(){
  'use strict';

  function serializeForm(form){
    const data = new FormData(form);
    const params = new URLSearchParams();
    for (const [k, v] of data.entries()){
      if (v !== '') {
        params.append(k, v);
      }
    }
    return params;
  }

  function renderLoading(container){
    container.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
  }

  function renderError(container, message){
    container.innerHTML = '<div class="alert alert-danger">' + (message || 'Une erreur est survenue lors de la recherche.') + '</div>';
  }

  // Gestion des formulaires de recherche AJAX
  function initAjaxSearch(formId, resultsId, ajaxUrl) {
    const form = document.getElementById(formId);
    const results = document.getElementById(resultsId);

    if (!form || !results) return;

    form.addEventListener('submit', function(e){
      e.preventDefault();
      
      const params = serializeForm(form);
      params.append('ajax', '1'); // Indicateur AJAX
      
      // Afficher le chargement
      renderLoading(results);

      // Requête AJAX
      fetch(ajaxUrl + '?' + params.toString(), {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Erreur réseau');
        }
        return response.text();
      })
      .then(html => {
        // Créer un élément temporaire pour parser le HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Extraire uniquement le contenu de la section des résultats
        const resultsContent = tempDiv.querySelector('#' + resultsId);
        if (resultsContent) {
          results.innerHTML = resultsContent.innerHTML;
        } else {
          // Si la section n'est pas trouvée, utiliser tout le HTML
          results.innerHTML = html;
        }
      })
      .catch(err => {
        console.error('Erreur AJAX:', err);
        renderError(results);
      });
    });

    // Recherche en temps réel lors de la saisie (optionnel, avec debounce)
    const searchInput = form.querySelector('input[name="title"]');
    if (searchInput) {
      let timeout;
      searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
          if (this.value.length >= 2 || this.value.length === 0) {
            form.dispatchEvent(new Event('submit'));
          }
        }, 500); // Attendre 500ms après la dernière frappe
      });
    }
  }

  // Gestion du formulaire de recherche générique (_search_section.html.twig)
  function initGenericSearch() {
    const form = document.getElementById('search-form');
    const results = document.getElementById('search-results');
    const clearBtn = document.getElementById('clear-filters');

    if (!form || !results) return;

    form.addEventListener('submit', function(e){
      e.preventDefault();
      const payload = serializeForm(form);
      renderLoading(results);

      // TODO: Configurer l'URL AJAX pour ce formulaire générique
      // Pour l'instant, on garde le comportement par défaut
      console.log('Search payload:', payload);
    });

    if (clearBtn){
      clearBtn.addEventListener('click', function(){
        form.reset();
        const collapseEl = document.getElementById('search-filters');
        if (collapseEl && typeof bootstrap !== 'undefined'){
          const bsCollapse = bootstrap.Collapse.getInstance(collapseEl) || new bootstrap.Collapse(collapseEl, {toggle:false});
          bsCollapse.hide();
        }
        results.innerHTML = '<div class="text-muted">Filtres réinitialisés.</div>';
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function(){
    // Initialiser les formulaires de recherche AJAX spécifiques
    const concoursForm = document.getElementById('search-form-concours');
    if (concoursForm) {
      const ajaxUrl = concoursForm.getAttribute('data-ajax-url');
      if (ajaxUrl) {
        initAjaxSearch('search-form-concours', 'search-results-concours', ajaxUrl);
      }
    }

    const visiteurForm = document.getElementById('search-form-visiteur');
    if (visiteurForm) {
      const ajaxUrl = visiteurForm.getAttribute('data-ajax-url');
      if (ajaxUrl) {
        initAjaxSearch('search-form-visiteur', 'search-results-visiteur', ajaxUrl);
      }
    }

    const artistevForm = document.getElementById('search-form-artistev');
    if (artistevForm) {
      const ajaxUrl = artistevForm.getAttribute('data-ajax-url');
      if (ajaxUrl) {
        initAjaxSearch('search-form-artistev', 'search-results-artistev', ajaxUrl);
      }
    }

    // Initialiser le formulaire générique
    initGenericSearch();

    // Gestion du toggle des filtres (Bootstrap collapse)
    const filterToggle = document.querySelector('[data-bs-toggle="collapse"][data-bs-target]');
    if (filterToggle){
      filterToggle.addEventListener('click', function(e){
        const targetSel = filterToggle.getAttribute('data-bs-target') || filterToggle.getAttribute('data-target');
        if (!targetSel) return;
        const collapseEl = document.querySelector(targetSel);
        if (!collapseEl) return;

        if (typeof bootstrap !== 'undefined' && bootstrap.Collapse){
          const bsCollapse = bootstrap.Collapse.getInstance(collapseEl) || new bootstrap.Collapse(collapseEl, {toggle:false});
          bsCollapse._isShown() ? bsCollapse.hide() : bsCollapse.show();
          return;
        }

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

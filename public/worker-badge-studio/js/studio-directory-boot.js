/**
 * Amorçage autonome du répertoire studio (départements + ouvriers validés).
 * Fonctionne même si admin.js est en cache ancien.
 */
(function () {
  'use strict';

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text ?? '');
    return div.innerHTML;
  }

  function apiUrl() {
    if (typeof window.CMP_STUDIO_WORKERS_API_PATH === 'string' && window.CMP_STUDIO_WORKERS_API_PATH.startsWith('/')) {
      return window.CMP_STUDIO_WORKERS_API_PATH;
    }
    if (typeof window.CMP_STUDIO_WORKERS_API === 'string' && window.CMP_STUDIO_WORKERS_API !== '') {
      return window.CMP_STUDIO_WORKERS_API;
    }
    const prefix = window.location.pathname.startsWith('/public/') ? '/public' : '';
    return `${prefix}/admin/worker-badge-studio/workers`;
  }

  function setStatus(message, isError) {
    const el = document.getElementById('studioDirectoryStatus');
    if (!el) return;
    el.textContent = message || '';
    el.style.color = isError ? '#fca5a5' : '';
  }

  function fillDepartments(departments) {
    const select = document.getElementById('departmentFilter');
    if (!select) return;
    const current = select.value || '';
    const rows = Array.isArray(departments) ? departments.slice() : [];
    rows.sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'fr'));
    select.innerHTML = `<option value="">Tous les départements</option>${rows.map((d) => `
      <option value="${escapeHtml(String(d.id))}" ${String(d.id) === String(current) ? 'selected' : ''}>
        ${escapeHtml(d.name || ('Département #' + d.id))}
      </option>
    `).join('')}`;
  }

  function fillWorkersList(workers) {
    const list = document.getElementById('participantsList');
    if (!list) return;

    // Si admin.js moderne est chargé, lui laisser le rendu final.
    if (typeof window.applyStudioDirectoryPayload === 'function' && typeof window.Admin === 'object') {
      window.applyStudioDirectoryPayload({
        departments: window.CMP_STUDIO_BOOTSTRAP?.departments || [],
        workers: workers,
      }, 'boot');
      return;
    }

    const rows = Array.isArray(workers) ? workers : [];
    if (!rows.length) {
      list.innerHTML = `
        <div class="admin-empty">
          <i class="bi bi-inbox"></i>
          <span>Aucun ouvrier validé trouvé. Validez un dossier dans Admin → Ouvriers, puis cliquez « Actualiser validés ».</span>
        </div>`;
      return;
    }

    list.innerHTML = rows.map((worker) => {
      const name = [worker.prenom, worker.nom].filter(Boolean).join(' ') || 'Sans nom';
      const meta = [worker.departmentName || worker.category || '', worker.departmentRole || worker.chambre || '']
        .filter(Boolean)
        .join(' · ');
      const color = worker.departmentColor || '#7b1d3e';
      const first = String(worker.prenom || '').trim();
      const last = String(worker.nom || '').trim();
      const initials = `${first.charAt(0)}${last.charAt(0) || (first.charAt(1) || '')}`.toUpperCase() || '?';
      const photo = String(worker.photo || '').trim();
      const avatar = photo
        ? `<span class="admin-person-avatar" style="--avatar-color:${escapeHtml(color)}"><img src="${escapeHtml(photo)}" alt="${escapeHtml(name)}"></span>`
        : `<span class="admin-person-avatar admin-person-avatar--initials" style="--avatar-color:${escapeHtml(color)}" aria-hidden="true">${escapeHtml(initials)}</span>`;
      return `
        <div class="admin-person" data-id="${escapeHtml(String(worker.id))}" data-studio-boot="1">
          <span class="admin-person-color" style="background:${escapeHtml(color)}"></span>
          ${avatar}
          <button type="button" class="admin-person-main" data-action="select">
            <strong>${escapeHtml(name)}</strong>
            <small>${escapeHtml(meta || 'Ouvrier validé')}</small>
          </button>
          <span class="admin-person-delete" style="opacity:.35;pointer-events:none;" title="Ouvrier validé">
            <i class="bi bi-shield-check"></i>
          </span>
        </div>`;
    }).join('');
  }

  function applyPayload(payload, label) {
    const departments = Array.isArray(payload?.departments) ? payload.departments : [];
    const workers = Array.isArray(payload?.workers) ? payload.workers : [];

    window.CMP_STUDIO_BOOTSTRAP = {
      departments,
      workers,
    };

    if (typeof window.Admin === 'object' && window.Admin) {
      window.Admin.departments = departments;
      window.Admin.validatedWorkers = workers.map((w) => ({ ...w, source: 'validated', departmentSlug: w.category }));
    }

    if (typeof window.applyStudioDirectoryPayload === 'function') {
      window.applyStudioDirectoryPayload({ departments, workers }, label || 'boot');
    } else {
      fillDepartments(departments);
      fillWorkersList(workers);
      setStatus(`${departments.length} département(s) · ${workers.length} ouvrier(s) validé(s)${label ? ` (${label})` : ''}`);
    }

    if (typeof window.syncDepartmentsAsCategories === 'function') {
      window.syncDepartmentsAsCategories(departments);
    }
    if (typeof window.refreshAdminCategorySelect === 'function') {
      window.refreshAdminCategorySelect(document.getElementById('adminCategory')?.value || 'participant');
    }
  }

  async function refreshFromApi() {
    const url = apiUrl();
    setStatus('Chargement…');
    try {
      const response = await fetch(url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        cache: 'no-store',
      });
      if (!response.ok) {
        throw new Error('HTTP ' + response.status);
      }
      const payload = await response.json();
      applyPayload(payload, 'API');
    } catch (error) {
      console.warn('[studio-directory]', error);
      const boot = window.CMP_STUDIO_BOOTSTRAP;
      if (boot && (boot.departments?.length || boot.workers?.length)) {
        applyPayload(boot, 'page');
      }
      setStatus(`Échec API (${error instanceof Error ? error.message : 'erreur'}). URL: ${url}`, true);
    }
  }

  function wireUi() {
    const refreshBtn = document.getElementById('refreshValidatedWorkersBtn');
    if (refreshBtn && !refreshBtn.dataset.studioBootBound) {
      refreshBtn.dataset.studioBootBound = '1';
      refreshBtn.addEventListener('click', function (event) {
        event.preventDefault();
        void refreshFromApi();
      });
    }

    const searchInput = document.getElementById('workerSearchInput');
    if (searchInput && !searchInput.dataset.studioBootBound) {
      searchInput.dataset.studioBootBound = '1';
      searchInput.addEventListener('input', function () {
        if (typeof window.Admin === 'object' && window.Admin) {
          window.Admin.searchQuery = searchInput.value || '';
        }
        if (typeof window.renderParticipantsList === 'function') {
          window.renderParticipantsList();
        }
      });
    }

    const list = document.getElementById('participantsList');
    if (list && !list.dataset.studioBootBound) {
      list.dataset.studioBootBound = '1';
      list.addEventListener('click', function (event) {
        const row = event.target.closest('.admin-person[data-studio-boot="1"]');
        if (!row) return;
        const id = row.dataset.id;
        const worker = (window.Admin?.validatedWorkers || window.CMP_STUDIO_BOOTSTRAP?.workers || [])
          .find((item) => String(item.id) === String(id));
        if (worker && typeof window.fillAdminForm === 'function') {
          window.fillAdminForm(worker);
          if (typeof window.renderParticipantsList === 'function') {
            window.renderParticipantsList();
          }
        }
      });
    }
  }

  function boot() {
    wireUi();
    const initial = window.CMP_STUDIO_BOOTSTRAP;
    if (initial && typeof initial === 'object') {
      applyPayload(initial, 'page');
    }
    void refreshFromApi();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  window.CMP_STUDIO_REFRESH_DIRECTORY = refreshFromApi;
})();

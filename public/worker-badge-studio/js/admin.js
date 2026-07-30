/* ═══════════════════════════════════════════
   ADMIN BADGE STUDIO
═══════════════════════════════════════════ */
'use strict';

const Admin = {
  participants: [],
  validatedWorkers: [],
  departments: [],
  departmentFilter: '',
  sourceFilter: 'all',
  searchQuery: '',
  selectedId: null,
  photo: null,
};

const ADMIN_MAX_PHOTO_BYTES = 3 * 1024 * 1024;
const ADMIN_MAX_PHOTO_DIMENSION = 1200;
const ADMIN_CORE_CATEGORIES = ['participant', 'encadrants'];
const ADMIN_DEMO_PARTICIPANT_ID = 'DEMO-PARTICIPANT';

function getAdminEl(id) {
  return document.getElementById(id);
}

function isAdminEncadrant(categoryKey = getAdminEl('adminCategory')?.value) {
  return categoryKey === 'encadrants';
}

function getAdminFormParticipant() {
  const categoryKey = getAdminEl('adminCategory').value || 'participant';
  const encadrant = isAdminEncadrant(categoryKey);
  const showPhoto = getAdminEl('adminShowPhoto') ? getAdminEl('adminShowPhoto').checked : true;
  const showWorkshop = getAdminEl('adminShowWorkshop') ? getAdminEl('adminShowWorkshop').checked : true;
  const showRoom = getAdminEl('adminShowRoom') ? getAdminEl('adminShowRoom').checked : true;
  const existing = findParticipantById(getAdminEl('adminId').value);

  const participant = {
    id: getAdminEl('adminId').value || `ADMIN-${Date.now()}`,
    prenom: getAdminEl('adminPrenom').value.trim(),
    nom: getAdminEl('adminNom').value.trim(),
    postnom: getAdminEl('adminPostnom').value.trim(),
    sexe: getAdminEl('adminSexe').value,
    category: categoryKey,
    atelier: getAdminEl('adminAtelier').value.trim().slice(0, 2),
    chambre: encadrant ? '' : getAdminEl('adminChambre').value.trim().slice(0, 24),
    departmentRole: encadrant ? '' : getAdminEl('adminChambre').value.trim().slice(0, 24),
    badgeToken: getAdminEl('adminId')?.dataset?.badgeToken || '',
    photo: Admin.photo,
    showPhoto,
    showWorkshop,
    showRoom: encadrant ? false : showRoom,
    showAssignments: showWorkshop || (!encadrant && showRoom),
    createdAt: new Date().toISOString(),
    source: existing?.source || 'local',
    departmentId: existing?.departmentId || '',
    departmentName: existing?.departmentName || '',
    departmentColor: existing?.departmentColor || '',
    churchWorkerId: existing?.churchWorkerId || null,
    badgeGenerated: existing?.badgeGenerated || false,
  };

  if (typeof ensureBadgeToken === 'function') {
    ensureBadgeToken(participant);
    if (getAdminEl('adminId')) {
      getAdminEl('adminId').dataset.badgeToken = participant.badgeToken;
    }
  }

  return participant;
}

function updateAdminCategoryState(options = {}) {
  const chambre = getAdminEl('adminChambre');
  const showRoom = getAdminEl('adminShowRoom');
  if (!chambre) return;

  const encadrant = isAdminEncadrant();
  chambre.disabled = encadrant;
  chambre.classList.toggle('field-input-disabled', encadrant);
  chambre.placeholder = encadrant ? 'Réservé' : 'Ex. Chef d’équipe';
  if (showRoom) {
    showRoom.disabled = encadrant;
    showRoom.closest('.studio-chip-toggle')?.classList.toggle('is-disabled', encadrant);
    if (encadrant) showRoom.checked = false;
  }
  if (encadrant && options.clearChambre !== false) {
    chambre.value = '';
  }
}

function fillAdminForm(participant) {
  const categoryKey = BADGE_CATEGORIES[participant.category] ? participant.category : 'participant';
  refreshAdminCategorySelect(categoryKey);

  getAdminEl('adminId').value = participant.id || '';
  if (typeof ensureBadgeToken === 'function') {
    ensureBadgeToken(participant);
  }
  getAdminEl('adminId').dataset.badgeToken = participant.badgeToken || '';
  getAdminEl('adminPrenom').value = participant.prenom || '';
  getAdminEl('adminNom').value = participant.nom || '';
  getAdminEl('adminPostnom').value = participant.postnom || '';
  getAdminEl('adminSexe').value = participant.sexe || 'M';
  getAdminEl('adminCategory').value = categoryKey;
  getAdminEl('adminAtelier').value = participant.atelier || '';
  getAdminEl('adminChambre').value = categoryKey === 'encadrants'
    ? ''
    : (participant.departmentRole || participant.chambre || '');
  getAdminEl('adminPhoto').value = '';
  if (getAdminEl('adminShowPhoto')) getAdminEl('adminShowPhoto').checked = participant.showPhoto !== false;
  if (getAdminEl('adminShowWorkshop')) {
    getAdminEl('adminShowWorkshop').checked = participant.showWorkshop ?? participant.showAssignments ?? true;
  }
  if (getAdminEl('adminShowRoom')) {
    getAdminEl('adminShowRoom').checked = categoryKey !== 'encadrants' && (participant.showRoom ?? participant.showAssignments ?? true);
  }
  Admin.photo = participant.photo || null;
  Admin.selectedId = participant.id || null;
  updateAdminCategoryState({ clearChambre: false });
  updateAdminPhotoState();
  updateAdminPreview();
}

function updateAdminPreview() {
  const participant = getAdminFormParticipant();
  renderRetreatBadge(getAdminEl('adminBadgePreview'), participant, {
    showPhoto: participant.showPhoto,
    showWorkshop: participant.showWorkshop,
    showRoom: participant.showRoom,
  });
}

function getParticipantMeta(participant) {
  const parts = [];
  if (participant.source === 'validated' || String(participant.id || '').startsWith('WORKER-')) {
    parts.push(participant.departmentName || getParticipantBadgeCategoryLabel(participant));
    parts.push('Validé');
  } else {
    parts.push(getParticipantBadgeCategoryLabel(participant));
  }
  const showWorkshop = participant.showWorkshop ?? participant.showAssignments ?? true;
  const showRoom = participant.showRoom ?? participant.showAssignments ?? true;
  if (!showWorkshop && !showRoom) {
    if (participant.source !== 'validated') {
      parts.push('Affectation masquée');
    }
    return parts.join(' · ');
  }
  if (showWorkshop && participant.atelier) {
    parts.push(`Atelier ${participant.atelier}`);
  }
  if (showRoom && (participant.category || '') !== 'encadrants') {
    const role = participant.departmentRole || participant.chambre || '';
    if (role) {
      parts.push(`Rôle ${role}`);
    }
  }
  return parts.join(' · ');
}

/**
 * Liste affichée selon filtres département / source / recherche.
 */
function getVisibleParticipants() {
  const dept = String(Admin.departmentFilter || '');
  const source = String(Admin.sourceFilter || 'all');
  const query = String(Admin.searchQuery || '').trim().toLowerCase();

  const local = (Admin.participants || []).map((item) => ({ ...item, source: item.source || 'local' }));
  const validated = (Admin.validatedWorkers || []).map((item) => ({ ...item, source: 'validated' }));

  let rows = [];
  if (source === 'validated') {
    rows = validated;
  } else if (source === 'local') {
    rows = local;
  } else {
    const validatedIds = new Set(validated.map((item) => item.id));
    rows = [...validated, ...local.filter((item) => !validatedIds.has(item.id))];
  }

  if (dept !== '') {
    rows = rows.filter((item) => String(item.departmentId || '') === dept
      || String(item.category || '') === dept
      || String(item.departmentSlug || '') === dept);
  }

  if (query !== '') {
    rows = rows.filter((item) => {
      const full = getParticipantFullName(item).toLowerCase();
      const parts = [item.prenom, item.nom, item.postnom, item.departmentName, item.departmentRole, item.chambre]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
      return full.includes(query) || parts.includes(query);
    });
  }

  return rows;
}

/**
 * Initialise le prénom/nom pour l’avatar (2 lettres max).
 */
function getParticipantInitials(participant) {
  const first = String(participant?.prenom || '').trim();
  const last = String(participant?.nom || '').trim();
  const a = first ? first.charAt(0) : '';
  const b = last ? last.charAt(0) : (first.length > 1 ? first.charAt(1) : '');
  const initials = `${a}${b}`.toUpperCase();
  return initials || '?';
}

/**
 * HTML de l’avatar (photo ou initiales).
 */
function renderParticipantAvatar(participant, color) {
  const photo = String(participant?.photo || '').trim();
  const name = getParticipantFullName(participant) || 'Ouvrier';
  if (photo !== '') {
    return `<span class="admin-person-avatar" style="--avatar-color:${badgeEscapeHtml(color)}"><img src="${badgeEscapeHtml(photo)}" alt="${badgeEscapeHtml(name)}"></span>`;
  }
  return `<span class="admin-person-avatar admin-person-avatar--initials" style="--avatar-color:${badgeEscapeHtml(color)}" aria-hidden="true">${badgeEscapeHtml(getParticipantInitials(participant))}</span>`;
}

function renderParticipantsList() {
  const list = getAdminEl('participantsList');
  if (!list) return;
  const rows = getVisibleParticipants();
  if (!rows.length) {
    list.innerHTML = `
      <div class="admin-empty">
        <i class="bi bi-inbox"></i>
        <span>Aucun ouvrier pour ce filtre. Modifiez la recherche, le département ou actualisez les validés.</span>
      </div>
    `;
    updateStudioListSummary();
    updateStudioFiltersSummary();
    return;
  }

  list.innerHTML = rows.map((participant) => {
    const category = getBadgeCategory(participant.category);
    const color = participant.departmentColor || category.color;
    const canDelete = participant.source !== 'validated' && !String(participant.id || '').startsWith('WORKER-');
    return `
      <div class="admin-person ${participant.id === Admin.selectedId ? 'active' : ''}" data-id="${participant.id}">
        <span class="admin-person-color" style="background:${color}"></span>
        ${renderParticipantAvatar(participant, color)}
        <button type="button" class="admin-person-main" data-action="select">
          <strong>${badgeEscapeHtml(getParticipantFullName(participant) || 'Sans nom')}</strong>
          <small>${badgeEscapeHtml(getParticipantMeta(participant))}</small>
        </button>
        ${canDelete ? `
          <button type="button" class="admin-person-delete" data-action="delete" aria-label="Supprimer ${badgeEscapeHtml(getParticipantFullName(participant) || 'ce badge')}">
            <i class="bi bi-x-lg"></i>
          </button>
        ` : `
          <span class="admin-person-delete" style="opacity:.35;pointer-events:none;" title="Ouvrier validé">
            <i class="bi bi-shield-check"></i>
          </span>
        `}
      </div>
    `;
  }).join('');
  updateStudioListSummary();
  updateStudioFiltersSummary();
}

function getBlankAdminParticipant() {
  return {
    id: '',
    prenom: '',
    nom: '',
    postnom: '',
    sexe: 'M',
    category: 'participant',
    atelier: '',
    chambre: '',
    photo: null,
    showPhoto: true,
    showWorkshop: true,
    showRoom: true,
    showAssignments: true,
  };
}

function findParticipantById(id) {
  if (!id) return null;
  return Admin.validatedWorkers.find((item) => item.id === id)
    || Admin.participants.find((item) => item.id === id)
    || null;
}

function deleteAdminParticipant(id) {
  if (String(id || '').startsWith('WORKER-')) {
    return;
  }
  Admin.participants = Admin.participants.filter((participant) => participant.id !== id);
  writeParticipants(Admin.participants);
  if (Admin.selectedId === id) {
    fillAdminForm(getVisibleParticipants()[0] || getBlankAdminParticipant());
  }
  renderParticipantsList();
}

function saveAdminParticipant(participant) {
  if (participant.source === 'validated' || String(participant.id || '').startsWith('WORKER-')) {
    const index = Admin.validatedWorkers.findIndex((item) => item.id === participant.id);
    if (index >= 0) {
      Admin.validatedWorkers[index] = { ...Admin.validatedWorkers[index], ...participant, source: 'validated' };
    }
    Admin.selectedId = participant.id;
    renderParticipantsList();
    return;
  }

  const index = Admin.participants.findIndex((item) => item.id === participant.id);
  if (index >= 0) {
    Admin.participants[index] = { ...Admin.participants[index], ...participant, source: 'local' };
  } else {
    Admin.participants.unshift({ ...participant, source: 'local' });
  }
  writeParticipants(Admin.participants);
  Admin.selectedId = participant.id;
  renderParticipantsList();
}

/**
 * Enregistre les départements BDD comme catégories de badge (couleur).
 */
function syncDepartmentsAsCategories(departments) {
  (departments || []).forEach((department) => {
    const key = String(department.slug || '').trim();
    if (!key) return;
    BADGE_CATEGORIES[key] = {
      label: department.name || key,
      color: department.color || '#7b1d3e',
    };
  });
  if (typeof saveStoredBadgeCategories === 'function') {
    saveStoredBadgeCategories();
  }
  refreshAdminCategorySelect(getAdminEl('adminCategory')?.value || 'participant');
  renderCategoryList();
}

/**
 * Remplit le filtre département.
 */
function renderDepartmentFilter() {
  const select = getAdminEl('departmentFilter');
  if (!select) return;
  const current = Admin.departmentFilter || '';
  const options = (Admin.departments || [])
    .slice()
    .sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'fr'));

  select.innerHTML = `<option value="">Tous les départements</option>${
    options.map((department) => `
      <option value="${badgeEscapeHtml(String(department.id))}" ${String(department.id) === String(current) ? 'selected' : ''}>
        ${badgeEscapeHtml(department.name || `Département #${department.id}`)}
      </option>
    `).join('')
  }`;
}

/**
 * Affiche un statut sous les filtres.
 */
function setStudioDirectoryStatus(message, isError = false) {
  const el = getAdminEl('studioDirectoryStatus');
  if (!el) return;
  el.textContent = message || '';
  el.style.color = isError ? '#fca5a5' : '';
}

/**
 * Applique un payload départements + ouvriers validés.
 */
function applyStudioDirectoryPayload(payload, sourceLabel = '') {
  Admin.departments = Array.isArray(payload?.departments) ? payload.departments : [];
  Admin.validatedWorkers = (Array.isArray(payload?.workers) ? payload.workers : []).map((worker) => ({
    ...worker,
    source: 'validated',
    departmentSlug: worker.category,
  }));
  syncDepartmentsAsCategories(Admin.departments);
  renderDepartmentFilter();
  renderParticipantsList();

  const deptCount = Admin.departments.length;
  const workerCount = Admin.validatedWorkers.length;
  setStudioDirectoryStatus(
    `${deptCount} département${deptCount > 1 ? 's' : ''} · ${workerCount} ouvrier${workerCount > 1 ? 's' : ''} validé${workerCount > 1 ? 's' : ''}${sourceLabel ? ` (${sourceLabel})` : ''}`,
  );
}

// Expose pour studio-directory-boot.js (anti-cache / secours).
window.applyStudioDirectoryPayload = applyStudioDirectoryPayload;
window.fillAdminForm = fillAdminForm;
window.renderParticipantsList = renderParticipantsList;
window.syncDepartmentsAsCategories = syncDepartmentsAsCategories;
window.refreshAdminCategorySelect = refreshAdminCategorySelect;
window.Admin = Admin;

/**
 * Résout l’URL API studio (évite le <base href> assets).
 */
function resolveStudioWorkersApiUrl() {
  if (typeof window.CMP_STUDIO_WORKERS_API_PATH === 'string' && window.CMP_STUDIO_WORKERS_API_PATH.startsWith('/')) {
    return window.CMP_STUDIO_WORKERS_API_PATH;
  }
  if (typeof window.CMP_STUDIO_WORKERS_API === 'string' && window.CMP_STUDIO_WORKERS_API !== '') {
    return window.CMP_STUDIO_WORKERS_API;
  }
  const prefix = window.location.pathname.startsWith('/public/') ? '/public' : '';
  return `${prefix}/admin/worker-badge-studio/workers`;
}

/**
 * Charge les ouvriers validés depuis l’API admin du studio.
 */
async function loadValidatedWorkers() {
  const apiUrl = resolveStudioWorkersApiUrl();
  setStudioDirectoryStatus('Chargement des départements…');

  try {
    const response = await fetch(apiUrl, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      cache: 'no-store',
    });
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    const payload = await response.json();
    applyStudioDirectoryPayload(payload, 'API');
  } catch (error) {
    console.warn('Impossible de charger les ouvriers validés', error);
    if (Admin.departments.length === 0 && window.CMP_STUDIO_BOOTSTRAP) {
      applyStudioDirectoryPayload(window.CMP_STUDIO_BOOTSTRAP, 'cache page');
    }
    setStudioDirectoryStatus(
      `API indisponible (${error instanceof Error ? error.message : 'erreur'}). Départements: ${Admin.departments.length}.`,
      true,
    );
  }
}

/**
 * Initialise la liste depuis le bootstrap serveur (immédiat, sans attendre l’API).
 */
function applyStudioBootstrap() {
  if (!window.CMP_STUDIO_BOOTSTRAP || typeof window.CMP_STUDIO_BOOTSTRAP !== 'object') {
    return;
  }
  applyStudioDirectoryPayload(window.CMP_STUDIO_BOOTSTRAP, 'page');
}

function isDemoParticipant(participant) {
  if (!participant) return false;
  return participant.id === ADMIN_DEMO_PARTICIPANT_ID
    || String(participant.id || '').startsWith('DEMO-')
    || (
      participant.prenom === 'Matthieu'
      && participant.nom === 'Makelela'
      && participant.category === 'participant'
      && participant.atelier === '00'
      && (participant.chambre === 'Équipe A' || participant.chambre === 'AA')
    );
}

async function downloadAdminBadge() {
  const badgeEl = getAdminEl('adminBadgePreview');
  const btn = getAdminEl('downloadAdminBadgeBtn');
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Génération...';
  btn.disabled = true;

  try {
    const participant = getAdminFormParticipant();
    const name = getBadgeExportName(getParticipantFullName(participant));
    await downloadRetreatBadge(badgeEl, `badge_retraite_${name}.jpg`);
  } catch (err) {
    alert('Erreur lors de la génération du badge.');
  } finally {
    btn.innerHTML = originalText;
    btn.disabled = false;
  }
}

function seedDemoParticipant() {
  const existingDemo = Admin.participants.find(isDemoParticipant);
  if (existingDemo) {
    fillAdminForm(existingDemo);
    renderParticipantsList();
    return;
  }

  const demo = {
    id: ADMIN_DEMO_PARTICIPANT_ID,
    prenom: 'Matthieu',
    nom: 'Makelela',
    postnom: '',
    sexe: 'M',
    category: 'participant',
    atelier: '00',
    chambre: 'Équipe A',
    departmentRole: 'Équipe A',
    badgeToken: (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function')
      ? crypto.randomUUID()
      : `demo-${Date.now()}`,
    role: 'Participant',
    photo: null,
    showPhoto: true,
    showWorkshop: true,
    showRoom: true,
    showAssignments: true,
    createdAt: new Date().toISOString(),
  };
  saveAdminParticipant(demo);
  fillAdminForm(demo);
}

function updateAdminPhotoState() {
  const fileName = getAdminEl('adminPhotoFileName');
  const removeBtn = getAdminEl('adminPhotoRemoveBtn');
  if (fileName) fileName.textContent = Admin.photo ? 'Photo recadrée et optimisée' : 'Aucune photo sélectionnée';
  if (removeBtn) removeBtn.classList.toggle('hidden', !Admin.photo);
}

function compressAdminImage(file, maxBytes, maxDim) {
  return new Promise((resolve) => {
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
      URL.revokeObjectURL(url);
      let { width, height } = img;
      if (width > maxDim || height > maxDim) {
        const ratio = Math.min(maxDim / width, maxDim / height);
        width = Math.round(width * ratio);
        height = Math.round(height * ratio);
      }
      const canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, width, height);
      let quality = 0.9;
      const tryCompress = () => {
        canvas.toBlob((blob) => {
          if (!blob) {
            resolve(null);
            return;
          }
          if (blob.size <= maxBytes || quality <= 0.1) {
            const reader = new FileReader();
            reader.onload = (event) => resolve(event.target.result);
            reader.readAsDataURL(blob);
          } else {
            quality -= 0.1;
            tryCompress();
          }
        }, 'image/jpeg', quality);
      };
      tryCompress();
    };
    img.onerror = () => {
      URL.revokeObjectURL(url);
      resolve(null);
    };
    img.src = url;
  });
}

function openAdminCropper(file) {
  const modal = getAdminEl('adminPhotoCropModal');
  const backdrop = getAdminEl('adminPhotoCropBackdrop');
  const close = getAdminEl('adminPhotoCropClose');
  const cancel = getAdminEl('adminPhotoCropCancel');
  const apply = getAdminEl('adminPhotoCropApply');
  const image = getAdminEl('adminPhotoCropImage');
  const zoom = getAdminEl('adminPhotoCropZoom');

  if (!modal || !backdrop || !close || !cancel || !apply || !image || !zoom || typeof Cropper === 'undefined') {
    return Promise.resolve(file);
  }

  return new Promise((resolve) => {
    let cropper = null;
    let objectUrl = URL.createObjectURL(file);
    let lastZoomValue = 0;

    const cleanup = () => {
      if (cropper) {
        cropper.destroy();
        cropper = null;
      }
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
      }
      zoom.removeEventListener('input', onZoomInput);
      backdrop.removeEventListener('click', cancelCrop);
      close.removeEventListener('click', cancelCrop);
      cancel.removeEventListener('click', cancelCrop);
      apply.removeEventListener('click', applyCrop);
      image.removeEventListener('load', initCropper);
      modal.classList.add('hidden');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('crop-modal-open');
    };

    const onZoomInput = () => {
      if (!cropper) return;
      const nextValue = Number(zoom.value);
      cropper.zoom((nextValue - lastZoomValue) / 100);
      lastZoomValue = nextValue;
    };

    const cancelCrop = () => {
      cleanup();
      resolve(null);
    };

    const applyCrop = () => {
      if (!cropper) {
        cleanup();
        resolve(file);
        return;
      }
      const canvas = cropper.getCroppedCanvas({
        width: 900,
        height: 900,
        imageSmoothingQuality: 'high',
        fillColor: '#ffffff',
      });
      canvas.toBlob((blob) => {
        cleanup();
        if (!blob) {
          resolve(file);
          return;
        }
        resolve(new File([blob], `admin-photo-${Date.now()}.jpg`, { type: 'image/jpeg' }));
      }, 'image/jpeg', 0.92);
    };

    const initCropper = () => {
      if (cropper) cropper.destroy();
      cropper = new Cropper(image, {
        aspectRatio: 1,
        viewMode: 1,
        dragMode: 'move',
        autoCropArea: 1,
        guides: false,
        center: false,
        highlight: false,
        cropBoxMovable: true,
        cropBoxResizable: false,
        toggleDragModeOnDblclick: false,
        responsive: true,
      });
    };

    image.addEventListener('load', initCropper);
    zoom.addEventListener('input', onZoomInput);
    backdrop.addEventListener('click', cancelCrop);
    close.addEventListener('click', cancelCrop);
    cancel.addEventListener('click', cancelCrop);
    apply.addEventListener('click', applyCrop);

    zoom.value = '0';
    lastZoomValue = 0;
    image.src = objectUrl;
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('crop-modal-open');
  });
}

async function handleAdminPhoto(file) {
  if (!file || !file.type.startsWith('image/')) return;
  const croppedFile = await openAdminCropper(file);
  if (!croppedFile) return;
  const dataURL = await compressAdminImage(croppedFile, ADMIN_MAX_PHOTO_BYTES, ADMIN_MAX_PHOTO_DIMENSION);
  if (!dataURL) {
    alert('Impossible de traiter cette image. Essayez un autre fichier.');
    return;
  }
  Admin.photo = dataURL;
  updateAdminPhotoState();
  updateAdminPreview();
}

function refreshAdminCategorySelect(selectedKey) {
  const select = getAdminEl('adminCategory');
  const nextSelected = BADGE_CATEGORIES[selectedKey] ? selectedKey : (BADGE_CATEGORIES[select.value] ? select.value : 'participant');
  select.innerHTML = Object.entries(BADGE_CATEGORIES).map(([key, category]) => (
    `<option value="${key}">${badgeEscapeHtml(category.label)}</option>`
  )).join('');
  select.value = nextSelected;
}

function resetCategoryEditor() {
  getAdminEl('adminCategoryKey').value = '';
  getAdminEl('adminCategoryLabel').value = '';
  getAdminEl('adminCategoryColor').value = '#4B5563';
  getAdminEl('adminCategorySaveBtn').innerHTML = '<i class="bi bi-check2-circle"></i><span>Enregistrer</span>';
  updateCategoryResetState();
}

function updateCategoryResetState(key = getAdminEl('adminCategoryKey')?.value) {
  const resetBtn = getAdminEl('adminCategoryResetBtn');
  if (!resetBtn) return;
  resetBtn.disabled = !key || !getDefaultBadgeCategory(key);
}

function renderCategoryList() {
  const list = getAdminEl('adminCategoryList');
  if (!list) return;

  list.innerHTML = Object.entries(BADGE_CATEGORIES).map(([key, category]) => {
    const isCore = ADMIN_CORE_CATEGORIES.includes(key);
    return `
      <div class="studio-category-row" data-key="${key}">
        <span class="studio-category-swatch" style="background:${category.color}"></span>
        <button type="button" class="studio-category-main" data-action="edit">
          <strong>${badgeEscapeHtml(category.label)}</strong>
          <small>${badgeEscapeHtml(category.color)}</small>
        </button>
        <button type="button" class="studio-icon-btn" data-action="delete" ${isCore ? 'disabled' : ''} aria-label="Supprimer ${badgeEscapeHtml(category.label)}">
          <i class="bi bi-trash3"></i>
        </button>
      </div>
    `;
  }).join('');
}

function beginCategoryEdit(key) {
  const category = BADGE_CATEGORIES[key];
  if (!category) return;
  getAdminEl('adminCategoryKey').value = key;
  getAdminEl('adminCategoryLabel').value = category.label;
  getAdminEl('adminCategoryColor').value = category.color;
  getAdminEl('adminCategorySaveBtn').innerHTML = '<i class="bi bi-check2-circle"></i><span>Mettre à jour</span>';
  updateCategoryResetState(key);
}

function getUniqueCategoryKey(label, currentKey) {
  if (currentKey) return currentKey;
  const baseKey = slugifyBadgeCategory(label);
  let key = baseKey;
  let suffix = 2;
  while (BADGE_CATEGORIES[key]) {
    key = `${baseKey}-${suffix}`;
    suffix += 1;
  }
  return key;
}

function saveCategoryFromStudio(event) {
  event.preventDefault();
  const label = getAdminEl('adminCategoryLabel').value.trim();
  const color = getAdminEl('adminCategoryColor').value || '#4B5563';
  if (!label) return;

  const previousKey = getAdminEl('adminCategoryKey').value;
  const key = getUniqueCategoryKey(label, previousKey);
  BADGE_CATEGORIES[key] = { label, color };
  saveStoredBadgeCategories();
  refreshAdminCategorySelect(key);
  renderCategoryList();
  renderParticipantsList();
  updateAdminCategoryState({ clearChambre: false });
  updateAdminPreview();
  resetCategoryEditor();
}

function deleteBadgeCategory(key) {
  if (!BADGE_CATEGORIES[key] || ADMIN_CORE_CATEGORIES.includes(key)) return;
  delete BADGE_CATEGORIES[key];
  Admin.participants = Admin.participants.map((participant) => (
    participant.category === key ? { ...participant, category: 'participant' } : participant
  ));
  writeParticipants(Admin.participants);
  saveStoredBadgeCategories();
  refreshAdminCategorySelect('participant');
  renderCategoryList();
  renderParticipantsList();
  updateAdminCategoryState({ clearChambre: false });
  updateAdminPreview();
}

function resetSelectedCategoryColor() {
  const key = getAdminEl('adminCategoryKey').value;
  const defaultCategory = getDefaultBadgeCategory(key);
  if (!key || !defaultCategory || !BADGE_CATEGORIES[key]) return;

  BADGE_CATEGORIES[key] = {
    ...BADGE_CATEGORIES[key],
    color: defaultCategory.color,
  };
  getAdminEl('adminCategoryColor').value = defaultCategory.color;
  saveStoredBadgeCategories();
  refreshAdminCategorySelect(key);
  renderCategoryList();
  renderParticipantsList();
  updateAdminPreview();
}

function initStudioTabs() {
  const tabs = Array.from(document.querySelectorAll('[data-studio-tab]'));
  const panels = Array.from(document.querySelectorAll('[data-studio-panel]'));
  const inspector = document.querySelector('.studio-inspector');
  if (!tabs.length || !panels.length) return;

  const setInspectorContext = (target) => {
    if (!inspector) return;
    inspector.classList.toggle('studio-tab-categories-active', target === 'categories');
  };

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.studioTab;
      setInspectorContext(target);
      tabs.forEach((item) => {
        const active = item === tab;
        item.classList.toggle('active', active);
        item.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      panels.forEach((panel) => {
        const active = panel.dataset.studioPanel === target;
        panel.classList.toggle('active', active);
        panel.hidden = !active;
      });
    });
  });
  setInspectorContext(tabs.find((tab) => tab.classList.contains('active'))?.dataset.studioTab || 'identity');
}

/**
 * Met à jour le résumé compact des filtres (bandeau replié).
 */
function updateStudioFiltersSummary() {
  const summary = getAdminEl('studioFiltersSummary');
  if (!summary) return;
  const deptSelect = getAdminEl('departmentFilter');
  const sourceSelect = getAdminEl('workersSourceFilter');
  const search = String(Admin.searchQuery || '').trim();
  const deptLabel = deptSelect?.selectedOptions?.[0]?.textContent?.trim() || 'Tous';
  const sourceLabel = sourceSelect?.selectedOptions?.[0]?.textContent?.trim() || 'Tous';
  const parts = [
    deptLabel === 'Tous les départements' ? 'Tous dép.' : deptLabel,
    sourceLabel,
  ];
  if (search !== '') {
    parts.push(`« ${search} »`);
  }
  summary.textContent = parts.join(' · ');
}

/**
 * Met à jour le compteur de la section Liste.
 */
function updateStudioListSummary() {
  const summary = getAdminEl('studioListSummary');
  if (!summary) return;
  const count = getVisibleParticipants().length;
  summary.textContent = `${count} ouvrier${count > 1 ? 's' : ''}`;
}

/**
 * Initialise les panneaux pliables (filtres / liste).
 */
function initStudioFolds() {
  const folds = Array.from(document.querySelectorAll('[data-studio-fold]'));
  if (!folds.length) return;

  const storageKey = 'cmp_studio_folds_v1';
  let saved = {};
  try {
    saved = JSON.parse(sessionStorage.getItem(storageKey) || '{}') || {};
  } catch (e) {
    saved = {};
  }

  const applyFold = (fold, expanded) => {
    const toggle = fold.querySelector('[data-studio-fold-toggle]');
    const body = fold.querySelector('[data-studio-fold-body]');
    fold.classList.toggle('is-expanded', expanded);
    fold.classList.toggle('is-collapsed', !expanded);
    if (toggle) {
      toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }
    if (body) {
      body.hidden = !expanded;
    }
    const key = fold.dataset.studioFold;
    if (key) {
      saved[key] = expanded ? 'open' : 'closed';
      try {
        sessionStorage.setItem(storageKey, JSON.stringify(saved));
      } catch (e) { /* ignore */ }
    }
  };

  folds.forEach((fold) => {
    const key = fold.dataset.studioFold;
    const preferred = key && saved[key]
      ? saved[key] === 'open'
      : key !== 'filters';
    applyFold(fold, preferred);

    const toggle = fold.querySelector('[data-studio-fold-toggle]');
    if (!toggle || toggle.dataset.foldBound === '1') return;
    toggle.dataset.foldBound = '1';
    toggle.addEventListener('click', () => {
      const willExpand = fold.classList.contains('is-collapsed');
      applyFold(fold, willExpand);
    });
  });

  updateStudioFiltersSummary();
  updateStudioListSummary();
}

function initAdmin() {
  initStudioTabs();
  initStudioFolds();
  refreshAdminCategorySelect('participant');
  renderCategoryList();

  Admin.participants = readParticipants().map((item) => ({ ...item, source: item.source || 'local' }));
  applyStudioBootstrap();
  renderParticipantsList();
  fillAdminForm(getVisibleParticipants()[0] || Admin.participants[0] || getBlankAdminParticipant());
  void loadValidatedWorkers().then(() => {
    const visible = getVisibleParticipants();
    if (visible.length && !findParticipantById(Admin.selectedId)) {
      fillAdminForm(visible[0]);
      renderParticipantsList();
    }
  });

  getAdminEl('participantsList').addEventListener('click', (event) => {
    const row = event.target.closest('.admin-person');
    if (!row) return;
    if (event.target.closest('[data-action="delete"]')) {
      deleteAdminParticipant(row.dataset.id);
      return;
    }
    const participant = findParticipantById(row.dataset.id);
    if (participant) fillAdminForm(participant);
    renderParticipantsList();
  });

  const departmentFilter = getAdminEl('departmentFilter');
  if (departmentFilter) {
    departmentFilter.addEventListener('change', () => {
      Admin.departmentFilter = departmentFilter.value || '';
      renderParticipantsList();
      updateStudioFiltersSummary();
    });
  }

  const sourceFilter = getAdminEl('workersSourceFilter');
  if (sourceFilter) {
    sourceFilter.addEventListener('change', () => {
      Admin.sourceFilter = sourceFilter.value || 'all';
      renderParticipantsList();
      updateStudioFiltersSummary();
    });
  }

  const searchInput = getAdminEl('workerSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      Admin.searchQuery = searchInput.value || '';
      renderParticipantsList();
      updateStudioFiltersSummary();
    });
  }

  const refreshBtn = getAdminEl('refreshValidatedWorkersBtn');
  if (refreshBtn) {
    refreshBtn.addEventListener('click', () => {
      void loadValidatedWorkers();
    });
  }

  getAdminEl('adminCategoryList').addEventListener('click', (event) => {
    const row = event.target.closest('.studio-category-row');
    if (!row) return;
    if (event.target.closest('[data-action="delete"]')) {
      deleteBadgeCategory(row.dataset.key);
      return;
    }
    beginCategoryEdit(row.dataset.key);
  });

  getAdminEl('adminAtelier').addEventListener('input', (event) => {
    event.target.value = event.target.value.trim().slice(0, 2);
  });
  getAdminEl('adminChambre').addEventListener('input', (event) => {
    event.target.value = event.target.value.trim().slice(0, 24);
  });

  getAdminEl('adminCategory').addEventListener('change', () => {
    updateAdminCategoryState({ clearChambre: true });
    updateAdminPreview();
  });

  ['adminShowPhoto', 'adminShowWorkshop', 'adminShowRoom'].forEach((id) => {
    getAdminEl(id).addEventListener('change', updateAdminPreview);
  });

  getAdminEl('adminForm').addEventListener('input', updateAdminPreview);
  getAdminEl('adminForm').addEventListener('change', updateAdminPreview);
  getAdminEl('adminForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const participant = getAdminFormParticipant();
    saveAdminParticipant(participant);
    fillAdminForm(participant);
  });

  getAdminEl('adminCategoryForm').addEventListener('submit', saveCategoryFromStudio);
  getAdminEl('adminCategoryResetBtn').addEventListener('click', resetSelectedCategoryColor);

  getAdminEl('adminPhotoPickBtn').addEventListener('click', () => {
    getAdminEl('adminPhoto').click();
  });

  getAdminEl('adminPhotoRemoveBtn').addEventListener('click', () => {
    Admin.photo = null;
    getAdminEl('adminPhoto').value = '';
    updateAdminPhotoState();
    updateAdminPreview();
  });

  getAdminEl('adminPhoto').addEventListener('change', async (event) => {
    const file = event.target.files[0];
    await handleAdminPhoto(file);
    event.target.value = '';
  });

  getAdminEl('newBadgeBtn').addEventListener('click', () => {
    fillAdminForm(getBlankAdminParticipant());
    renderParticipantsList();
  });

  getAdminEl('seedDemoBtn').addEventListener('click', seedDemoParticipant);
  getAdminEl('downloadAdminBadgeBtn').addEventListener('click', downloadAdminBadge);
}

document.addEventListener('DOMContentLoaded', initAdmin);

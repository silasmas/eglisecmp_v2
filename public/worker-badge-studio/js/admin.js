/* ═══════════════════════════════════════════
   ADMIN BADGE STUDIO
═══════════════════════════════════════════ */
'use strict';

const Admin = {
  participants: [],
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

  return {
    id: getAdminEl('adminId').value || `ADMIN-${Date.now()}`,
    prenom: getAdminEl('adminPrenom').value.trim(),
    nom: getAdminEl('adminNom').value.trim(),
    postnom: getAdminEl('adminPostnom').value.trim(),
    sexe: getAdminEl('adminSexe').value,
    category: categoryKey,
    atelier: getAdminEl('adminAtelier').value.trim().slice(0, 2),
    // « chambre » conservé en clé technique = rôle / sous-branche du département
    chambre: encadrant ? '' : getAdminEl('adminChambre').value.trim().slice(0, 24),
    departmentRole: encadrant ? '' : getAdminEl('adminChambre').value.trim().slice(0, 24),
    photo: Admin.photo,
    showPhoto,
    showWorkshop,
    showRoom: encadrant ? false : showRoom,
    showAssignments: showWorkshop || (!encadrant && showRoom),
    createdAt: new Date().toISOString(),
  };
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

  getAdminEl('adminId').value = participant.id || '';
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
  const parts = [getParticipantBadgeCategoryLabel(participant)];
  const showWorkshop = participant.showWorkshop ?? participant.showAssignments ?? true;
  const showRoom = participant.showRoom ?? participant.showAssignments ?? true;
  if (!showWorkshop && !showRoom) {
    parts.push('Affectation masquée');
    return parts.join(' · ');
  }
  if (showWorkshop) {
    parts.push(`Atelier ${participant.atelier || '—'}`);
  }
  if (showRoom && (participant.category || '') !== 'encadrants') {
    parts.push(`Rôle ${participant.departmentRole || participant.chambre || '—'}`);
  }
  return parts.join(' · ');
}

function renderParticipantsList() {
  const list = getAdminEl('participantsList');
  if (!Admin.participants.length) {
    list.innerHTML = `
      <div class="admin-empty">
        <i class="bi bi-inbox"></i>
        <span>Aucun inscrit pour le moment. Validez une inscription ou créez un exemple.</span>
      </div>
    `;
    return;
  }

  list.innerHTML = Admin.participants.map((participant) => {
    const category = getBadgeCategory(participant.category);
    return `
      <div class="admin-person ${participant.id === Admin.selectedId ? 'active' : ''}" data-id="${participant.id}">
        <span class="admin-person-color" style="background:${category.color}"></span>
        <button type="button" class="admin-person-main" data-action="select">
          <strong>${badgeEscapeHtml(getParticipantFullName(participant) || 'Sans nom')}</strong>
          <small>${badgeEscapeHtml(getParticipantMeta(participant))}</small>
        </button>
        <button type="button" class="admin-person-delete" data-action="delete" aria-label="Supprimer ${badgeEscapeHtml(getParticipantFullName(participant) || 'ce badge')}">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
    `;
  }).join('');
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

function deleteAdminParticipant(id) {
  Admin.participants = Admin.participants.filter((participant) => participant.id !== id);
  writeParticipants(Admin.participants);
  if (Admin.selectedId === id) {
    fillAdminForm(Admin.participants[0] || getBlankAdminParticipant());
  }
  renderParticipantsList();
}

function saveAdminParticipant(participant) {
  const index = Admin.participants.findIndex((item) => item.id === participant.id);
  if (index >= 0) {
    Admin.participants[index] = { ...Admin.participants[index], ...participant };
  } else {
    Admin.participants.unshift(participant);
  }
  writeParticipants(Admin.participants);
  Admin.selectedId = participant.id;
  renderParticipantsList();
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

function initAdmin() {
  initStudioTabs();
  refreshAdminCategorySelect('participant');
  renderCategoryList();

  Admin.participants = readParticipants();
  renderParticipantsList();
  fillAdminForm(Admin.participants[0] || getBlankAdminParticipant());

  getAdminEl('participantsList').addEventListener('click', (event) => {
    const row = event.target.closest('.admin-person');
    if (!row) return;
    if (event.target.closest('[data-action="delete"]')) {
      deleteAdminParticipant(row.dataset.id);
      return;
    }
    const participant = Admin.participants.find((item) => item.id === row.dataset.id);
    if (participant) fillAdminForm(participant);
    renderParticipantsList();
  });

  getAdminEl('adminCategoryList').addEventListener('click', (event) => {
    const row = event.target.closest('.studio-category-row');
    if (!row) return;
    if (event.target.closest('[data-action="delete"]')) {
      deleteBadgeCategory(row.dataset.key);
      return;
    }
    beginCategoryEdit(row.dataset.key);
  });

  ['adminAtelier', 'adminChambre'].forEach((id) => {
    getAdminEl(id).addEventListener('input', (event) => {
      event.target.value = event.target.value.trim().slice(0, 2);
    });
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

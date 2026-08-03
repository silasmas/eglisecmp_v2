/* ═══════════════════════════════════════════
   BADGE RENDERING & SUBMIT
═══════════════════════════════════════════ */
'use strict';

const BADGE_CATEGORIES = {
  participant: {
    label: 'Participant(e)',
    color: '#4B5563',
  },
  accueil: {
    label: 'Accueil',
    color: '#2563EB',
  },
  intercession: {
    label: 'Intercession',
    color: '#7C3AED',
  },
  securite: {
    label: 'Sécurité',
    color: '#DC2626',
  },
  restauration: {
    label: 'Restauration',
    color: '#F97316',
  },
  technique: {
    label: 'Technique',
    color: '#0F766E',
  },
  hygiene: {
    label: 'Hygiène',
    color: '#06B6D4',
  },
  crea: {
    label: 'CREA',
    color: '#DB2777',
  },
  sante: {
    label: 'Santé',
    color: '#16A34A',
  },
  event: {
    label: 'Event',
    color: '#EAB308',
  },
  protocole: {
    label: 'Protocole',
    color: '#7b1d3e',
  },
  chorale: {
    label: 'Chorale',
    color: '#CA8A04',
  },
  jeunesse: {
    label: 'Jeunesse',
    color: '#EAB308',
  },
  medias: {
    label: 'Médias',
    color: '#334155',
  },
  encadrants: {
    label: 'Encadrant(e)',
    color: '#991B1B',
  },
};

const BADGE_CATEGORY_DEFAULTS = Object.freeze(Object.fromEntries(
  Object.entries(BADGE_CATEGORIES).map(([key, category]) => [key, { ...category }]),
));

const BADGE_ZOOM = {};

function loadStoredBadgeCategories() {
  try {
    const stored = JSON.parse(localStorage.getItem('retraite_badge_categories') || '{}');
    Object.entries(stored).forEach(([key, category]) => {
      if (!category || !category.label || !category.color) return;
      BADGE_CATEGORIES[key] = {
        label: category.label,
        color: category.color,
      };
    });
  } catch (e) { /* ignore */ }
}

function saveStoredBadgeCategories() {
  localStorage.setItem('retraite_badge_categories', JSON.stringify(BADGE_CATEGORIES));
}

function slugifyBadgeCategory(label) {
  return String(label || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '') || `categorie-${Date.now()}`;
}

loadStoredBadgeCategories();

function getBadgeCategory(key) {
  return BADGE_CATEGORIES[key] || BADGE_CATEGORIES.participant;
}

/**
 * Garantit un jeton badge (UUID) pour le QR public.
 *
 * @param {object} participant
 * @returns {string}
 */
function ensureBadgeToken(participant) {
  if (!participant || typeof participant !== 'object') {
    return '';
  }
  if (participant.badgeToken && String(participant.badgeToken).trim() !== '') {
    return String(participant.badgeToken).trim();
  }
  const token = (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function')
    ? crypto.randomUUID()
    : `tok-${Date.now()}-${Math.random().toString(16).slice(2, 10)}`;
  participant.badgeToken = token;
  return token;
}

/**
 * URL publique scannable du badge ouvrier.
 *
 * @param {string} token
 * @returns {string}
 */
function getBadgePublicUrl(token) {
  const base = String(window.CMP_BADGE_PUBLIC_BASE || `${window.location.origin}/ouvriers/badge`).replace(/\/$/, '');
  return `${base}/${token}`;
}

/**
 * QR data URL avec logo CMP au centre.
 *
 * @param {string} content
 * @returns {Promise<string>}
 */
async function buildStudioQrDataUrl(content) {
  if (typeof QRCode === 'undefined') {
    return '';
  }

  const size = 512;
  const canvas = document.createElement('canvas');
  await QRCode.toCanvas(canvas, content, {
    errorCorrectionLevel: 'H',
    margin: 1,
    width: size,
    color: { dark: '#18181b', light: '#ffffff' },
  });

  const ctx = canvas.getContext('2d');
  if (!ctx) {
    return canvas.toDataURL('image/png');
  }

  try {
    const logoUrl = window.CMP_BADGE_LOGO_URL || 'assets/logo-cmp.png';
    const logo = await loadBadgeImage(logoUrl);
    const logoSize = size * 0.22;
    const x = (size - logoSize) / 2;
    const y = (size - logoSize) / 2;
    const pad = logoSize * 0.12;
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(x - pad, y - pad, logoSize + pad * 2, logoSize + pad * 2);
    ctx.drawImage(logo, x, y, logoSize, logoSize);
  } catch (e) { /* logo optionnel */ }

  return canvas.toDataURL('image/png');
}

/**
 * Remplit le QR en bas à droite du badge DOM.
 *
 * @param {HTMLElement} root
 * @param {object} participant
 */
/**
 * Remplit le QR en bas à droite du badge DOM (anti-course sur re-render).
 *
 * @param {HTMLElement} root
 * @param {object} participant
 * @param {number} [renderGeneration]
 */
async function fillBadgeQr(root, participant, renderGeneration) {
  if (!root || !participant) {
    return;
  }
  const token = ensureBadgeToken(participant);
  const url = getBadgePublicUrl(token);
  try {
    const dataUrl = await buildStudioQrDataUrl(url);
    if (!dataUrl) {
      return;
    }
    // Abandonne si un nouveau rendu a remplacé le HTML entre-temps.
    if (typeof renderGeneration === 'number' && root.__badgeRenderGeneration !== renderGeneration) {
      return;
    }
    const img = root.querySelector('[data-badge-qr]');
    if (!img || !root.contains(img)) {
      return;
    }
    img.src = dataUrl;
    img.alt = 'QR code badge ouvrier';
    img.classList.add('is-ready');
    const wrap = img.closest('.retreat-badge-qr');
    if (wrap) {
      wrap.classList.add('is-ready');
      wrap.setAttribute('title', 'QR prêt — scanner pour ouvrir le badge');
    }
  } catch (e) {
    console.warn('QR badge indisponible', e);
  }
}

function getDefaultBadgeCategory(key) {
  return BADGE_CATEGORY_DEFAULTS[key] || null;
}

function normalizeCategoryKey(value) {
  const raw = String(value || '').toLowerCase();
  if (raw.includes('accueil')) return 'accueil';
  if (raw.includes('intercession')) return 'intercession';
  if (raw.includes('sécurité') || raw.includes('securite')) return 'securite';
  if (raw.includes('restauration')) return 'restauration';
  if (raw.includes('technique')) return 'technique';
  if (raw.includes('hygiène') || raw.includes('hygiene')) return 'hygiene';
  if (raw.includes('crea') || raw.includes('créa')) return 'crea';
  if (raw.includes('santé') || raw.includes('sante')) return 'sante';
  if (raw.includes('event') || raw.includes('événement')) return 'event';
  if (raw.includes('encadr')) return 'encadrants';
  return 'participant';
}

function buildParticipantFromForm() {
  const roleValue = val('role') === 'Autre' ? val('roleAutre') : val('role');
  return {
    id: `RET-${Date.now()}`,
    nom: val('nom'),
    postnom: val('postnom'),
    prenom: val('prenom'),
    sexe: val('sexe'),
    dateNaissance: val('dateNaissance'),
    role: roleValue || 'Participant',
    telephone: `${val('indicatif')} ${val('telephone')}`.trim(),
    email: val('email'),
    ville: val('ville'),
    eglise: val('eglise'),
    departement: val('departement'),
    hebergement: getHebergementValue(),
    observations: val('observations'),
    photo: App.photoDataURL,
    category: normalizeCategoryKey(roleValue),
    atelier: '',
    chambre: '',
    createdAt: new Date().toISOString(),
  };
}

function getParticipantFullName(participant) {
  return [participant.prenom, participant.nom, participant.postnom].filter(Boolean).join(' ').trim();
}

function getParticipantBadgeName(participant) {
  return [participant.prenom, participant.nom].filter(Boolean).join(' ').trim();
}

function getParticipantBadgeCategoryLabel(participant) {
  const categoryKey = participant.category || 'participant';
  if (categoryKey === 'participant') {
    return participant.sexe === 'F' ? 'Participante' : 'Participant';
  }
  if (categoryKey === 'encadrants') {
    return participant.sexe === 'F' ? 'Encadrante' : 'Encadrant';
  }
  return getBadgeCategory(categoryKey).label;
}

function badgeEscapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function readParticipants() {
  try {
    return JSON.parse(localStorage.getItem('retraite_participants') || '[]');
  } catch (e) {
    return [];
  }
}

function writeParticipants(participants) {
  localStorage.setItem('retraite_participants', JSON.stringify(participants));
}

function saveParticipant(participant) {
  const participants = readParticipants();
  const existingIndex = participants.findIndex((item) => item.email && item.email === participant.email);
  if (existingIndex >= 0) {
    participants[existingIndex] = { ...participants[existingIndex], ...participant };
  } else {
    participants.unshift(participant);
  }
  writeParticipants(participants);
  sessionStorage.setItem('retraite_current_participant_id', participant.id);
  return participant;
}

function getCurrentBadgeParticipant() {
  const currentId = sessionStorage.getItem('retraite_current_participant_id');
  const savedParticipant = readParticipants().find((item) => item.id === currentId);
  if (savedParticipant) return savedParticipant;

  const formParticipant = buildParticipantFromForm();
  if (getParticipantBadgeName(formParticipant)) return formParticipant;

  return {
    prenom: 'Nom',
    nom: 'du participant',
    postnom: '',
    category: 'participant',
    atelier: '',
    chambre: '',
    photo: null,
  };
}

function renderCurrentBadgePreview() {
  const target = document.getElementById('badgeCard');
  if (!target) return;
  renderRetreatBadge(target, getCurrentBadgeParticipant(), { showAssignments: false });
}

function fitBadgeName(root) {
  const target = typeof root === 'string' ? document.getElementById(root) : root;
  const nameEl = target ? target.querySelector('.retreat-badge-name-banner span') : null;
  if (!nameEl) return;

  nameEl.style.removeProperty('--badge-name-fit-size');
  let size = parseFloat(getComputedStyle(nameEl).fontSize);
  while (nameEl.scrollWidth > nameEl.clientWidth && size > 9) {
    size -= 0.5;
    nameEl.style.setProperty('--badge-name-fit-size', `${size}px`);
  }
}

function setBadgeZoom(targetId, nextZoom, valueId) {
  const target = document.getElementById(targetId);
  if (!target) return;
  const zoom = Math.min(1.4, Math.max(0.7, nextZoom));
  BADGE_ZOOM[targetId] = zoom;
  target.style.width = `${360 * zoom}px`;
  requestAnimationFrame(() => fitBadgeName(target));
  const value = valueId ? document.getElementById(valueId) : null;
  if (value) value.textContent = `${Math.round(zoom * 100)}%`;
}

function initBadgeZoom(targetId, controls) {
  const target = document.getElementById(targetId);
  if (!target || !controls) return;
  const zoomIn = document.getElementById(controls.in);
  const zoomOut = document.getElementById(controls.out);
  if (!zoomIn || !zoomOut) return;
  setBadgeZoom(targetId, BADGE_ZOOM[targetId] || 1, controls.value);
  zoomIn.addEventListener('click', () => {
    setBadgeZoom(targetId, (BADGE_ZOOM[targetId] || 1) + 0.1, controls.value);
  });
  zoomOut.addEventListener('click', () => {
    setBadgeZoom(targetId, (BADGE_ZOOM[targetId] || 1) - 0.1, controls.value);
  });
}

function getBadgeExportName(baseName) {
  const cleaned = String(baseName || 'badge').trim().replace(/\s+/g, '_');
  return cleaned || 'badge';
}

async function waitForBadgeAssets(badgeEl) {
  if (document.fonts && document.fonts.ready) {
    try {
      await document.fonts.ready;
    } catch (e) { /* ignore */ }
  }

  const images = Array.from(badgeEl.querySelectorAll('img'));
  await Promise.all(images.map((img) => {
    if (img.complete && img.naturalWidth > 0) return Promise.resolve();
    if (typeof img.decode === 'function') {
      return img.decode().catch(() => {});
    }
    return new Promise((resolve) => {
      img.addEventListener('load', resolve, { once: true });
      img.addEventListener('error', resolve, { once: true });
    });
  }));
}

function loadBadgeImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = reject;
    img.src = src;
  });
}

function drawCoverImage(ctx, img, x, y, width, height) {
  const scale = Math.max(width / img.naturalWidth, height / img.naturalHeight);
  const drawWidth = img.naturalWidth * scale;
  const drawHeight = img.naturalHeight * scale;
  const drawX = x + (width - drawWidth) / 2;
  const drawY = y + (height - drawHeight) / 2;
  ctx.drawImage(img, drawX, drawY, drawWidth, drawHeight);
}

function drawRoundRect(ctx, x, y, width, height, radius) {
  const r = Math.min(radius, width / 2, height / 2);
  ctx.beginPath();
  ctx.moveTo(x + r, y);
  ctx.lineTo(x + width - r, y);
  ctx.quadraticCurveTo(x + width, y, x + width, y + r);
  ctx.lineTo(x + width, y + height - r);
  ctx.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
  ctx.lineTo(x + r, y + height);
  ctx.quadraticCurveTo(x, y + height, x, y + height - r);
  ctx.lineTo(x, y + r);
  ctx.quadraticCurveTo(x, y, x + r, y);
  ctx.closePath();
}

function drawBottomPill(ctx, x, y, width, height, radius) {
  const r = Math.min(radius, width / 2, height);
  ctx.beginPath();
  ctx.moveTo(x, y);
  ctx.lineTo(x + width, y);
  ctx.lineTo(x + width, y + height - r);
  ctx.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
  ctx.lineTo(x + r, y + height);
  ctx.quadraticCurveTo(x, y + height, x, y + height - r);
  ctx.closePath();
}

function getCanvasFont(size, weight = 800) {
  return `${weight} ${size}px Poppins, Arial, sans-serif`;
}

function drawFittedText(ctx, text, x, y, width, height, options = {}) {
  const value = String(text || '');
  let fontSize = options.maxSize || 96;
  const minSize = options.minSize || 34;
  const weight = options.weight || 800;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillStyle = options.color || '#ffffff';

  while (fontSize > minSize) {
    ctx.font = getCanvasFont(fontSize, weight);
    if (ctx.measureText(value).width <= width) break;
    fontSize -= 2;
  }

  ctx.font = getCanvasFont(fontSize, weight);
  ctx.fillText(value, x + width / 2, y + height / 2 + (options.yOffset || 0));
}

function drawAvatarPlaceholder(ctx, x, y, diameter) {
  const radius = diameter / 2;
  ctx.save();
  ctx.beginPath();
  ctx.arc(x + radius, y + radius, radius, 0, Math.PI * 2);
  ctx.clip();
  ctx.fillStyle = '#252525';
  ctx.fillRect(x, y, diameter, diameter);
  ctx.fillStyle = '#8f8f8f';
  ctx.beginPath();
  ctx.arc(x + radius, y + diameter * 0.36, diameter * 0.16, 0, Math.PI * 2);
  ctx.fill();
  ctx.beginPath();
  ctx.ellipse(x + radius, y + diameter * 0.74, diameter * 0.28, diameter * 0.17, 0, 0, Math.PI * 2);
  ctx.fill();
  ctx.restore();
}

async function renderBadgeToCanvas(participant, options = {}) {
  if (document.fonts && document.fonts.ready) {
    try {
      await document.fonts.ready;
    } catch (e) { /* ignore */ }
  }

  const category = getBadgeCategory(participant.category);
  const showPhoto = options.showPhoto !== false;
  const isEncadrant = (participant.category || '') === 'encadrants';
  const showAssignmentsFallback = (options.showAssignments ?? participant.showAssignments ?? true) !== false;
  const showWorkshop = (options.showWorkshop ?? participant.showWorkshop ?? options.showAssignments ?? participant.showAssignments ?? true) !== false;
  const showChambre = !isEncadrant && ((options.showRoom ?? participant.showRoom ?? options.showAssignments ?? participant.showAssignments ?? true) !== false);
  const showAssignments = showAssignmentsFallback && (showWorkshop || showChambre);
  const singleAssignment = showAssignments && (showWorkshop !== showChambre);
  const fullName = getParticipantBadgeName(participant) || 'Nom du participant';
  const categoryLabel = getParticipantBadgeCategoryLabel(participant);
  const atelier = showWorkshop && participant.atelier ? participant.atelier : '';
  const roleValue = showChambre
    ? String(participant.departmentRole || participant.chambre || '').trim()
    : '';
  const chambre = roleValue;

  const badgeWidth = 2480;
  const badgeHeight = 3508;
  const padding = 150;
  const canvas = document.createElement('canvas');
  canvas.width = badgeWidth + padding * 2;
  canvas.height = badgeHeight + padding * 2;
  const ctx = canvas.getContext('2d');
  const x0 = padding;
  const y0 = padding;

  const [background, nameBanner, atelierBanner, chambreBanner, photo] = await Promise.all([
    loadBadgeImage('assets/bagde-composants/fond-badge.png'),
    loadBadgeImage('assets/bagde-composants/nom-badge.png'),
    loadBadgeImage('assets/bagde-composants/Atelier.png'),
    loadBadgeImage('assets/bagde-composants/Chambre.png'),
    showPhoto && participant.photo ? loadBadgeImage(participant.photo).catch(() => null) : Promise.resolve(null),
  ]);

  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.drawImage(background, x0, y0, badgeWidth, badgeHeight);

  ctx.save();
  ctx.globalAlpha = 0.08;
  ctx.globalCompositeOperation = 'multiply';
  ctx.fillStyle = category.color;
  ctx.fillRect(x0, y0, badgeWidth, badgeHeight);
  ctx.restore();

  const borderX = x0 + badgeWidth * 0.0365;
  const borderY = y0 + badgeHeight * 0.0285;
  const borderW = badgeWidth * (1 - 0.0365 * 2);
  const borderH = badgeHeight * (1 - 0.0285 * 2);
  const borderOffset = 68;
  ctx.strokeStyle = category.color;
  ctx.lineWidth = 124;
  ctx.strokeRect(borderX - borderOffset, borderY - borderOffset, borderW + borderOffset * 2, borderH + borderOffset * 2);

  if (showPhoto) {
    const photoD = badgeWidth * 0.34;
    const photoX = x0 + badgeWidth * 0.5 - photoD / 2;
    const photoY = y0 + badgeHeight * 0.3265;
    const photoBorder = 38;
    ctx.save();
    ctx.beginPath();
    ctx.arc(photoX + photoD / 2, photoY + photoD / 2, photoD / 2, 0, Math.PI * 2);
    ctx.clip();
    ctx.fillStyle = '#252525';
    ctx.fillRect(photoX, photoY, photoD, photoD);
    if (photo) {
      drawCoverImage(ctx, photo, photoX + photoBorder, photoY + photoBorder, photoD - photoBorder * 2, photoD - photoBorder * 2);
    } else {
      drawAvatarPlaceholder(ctx, photoX + photoBorder, photoY + photoBorder, photoD - photoBorder * 2);
    }
    ctx.restore();
    ctx.strokeStyle = category.color;
    ctx.lineWidth = photoBorder;
    ctx.beginPath();
    ctx.arc(photoX + photoD / 2, photoY + photoD / 2, photoD / 2 - photoBorder / 2, 0, Math.PI * 2);
    ctx.stroke();
  }

  let nameTop = showPhoto ? 0.582 : 0.47;
  let categoryTop = showPhoto ? 0.6595 : 0.548;
  if (!showAssignments) {
    nameTop = showPhoto ? 0.56 : 0.51;
    categoryTop = showPhoto ? 0.638 : 0.588;
  }
  const nameX = x0 + badgeWidth * 0.149;
  const nameY = y0 + badgeHeight * nameTop;
  const nameW = badgeWidth * (1 - 0.149 * 2);
  const nameH = badgeHeight * 0.081;
  ctx.drawImage(nameBanner, nameX, nameY, nameW, nameH);
  drawFittedText(ctx, fullName, nameX + nameW * 0.085, nameY + nameH * 0.18, nameW * 0.83, nameH * 0.6, {
    maxSize: 108,
    minSize: 46,
    weight: 800,
    color: '#ffffff',
  });

  const catX = x0 + badgeWidth * 0.302;
  const catY = y0 + badgeHeight * categoryTop;
  const catW = badgeWidth * (1 - 0.302 * 2);
  const catH = badgeHeight * 0.038;
  ctx.fillStyle = category.color;
  drawBottomPill(ctx, catX, catY, catW, catH, catH / 2);
  ctx.fill();
  drawFittedText(ctx, categoryLabel, catX + catW * 0.08, catY, catW * 0.84, catH, {
    maxSize: 72,
    minSize: 38,
    weight: 800,
    color: '#ffffff',
  });

  if (showAssignments) {
    const assignmentW = badgeWidth * 0.173;
    const assignmentH = assignmentW * (491 / 426);
    const assignmentY = y0 + badgeHeight * (showPhoto ? 0.7275 : 0.62);
    if (showWorkshop) {
      const atelierCenter = singleAssignment ? 0.5 : 0.3875;
      const atelierX = x0 + badgeWidth * atelierCenter - assignmentW / 2;
      ctx.drawImage(atelierBanner, atelierX, assignmentY, assignmentW, assignmentH);
      drawFittedText(ctx, atelier, atelierX + assignmentW * 0.17, assignmentY + assignmentH * 0.384, assignmentW * 0.66, assignmentH * 0.3, {
        maxSize: 126,
        minSize: 54,
        weight: 900,
        color: '#373737',
      });
    }

    if (showChambre) {
      const chambreCenter = singleAssignment ? 0.5 : 0.6125;
      const chambreX = x0 + badgeWidth * chambreCenter - assignmentW / 2;
      ctx.drawImage(chambreBanner, chambreX, assignmentY, assignmentW, assignmentH);
      // Remplace le libellé « Chambre » du PNG par « Rôle »
      const captionY = assignmentY + assignmentH * 0.07;
      const captionH = assignmentH * 0.16;
      ctx.fillStyle = '#f4efe6';
      ctx.fillRect(chambreX + assignmentW * 0.14, captionY, assignmentW * 0.72, captionH);
      drawFittedText(ctx, 'Rôle', chambreX + assignmentW * 0.18, captionY, assignmentW * 0.64, captionH, {
        maxSize: 52,
        minSize: 28,
        weight: 800,
        color: '#44403c',
      });
      drawFittedText(ctx, chambre, chambreX + assignmentW * 0.17, assignmentY + assignmentH * 0.384, assignmentW * 0.66, assignmentH * 0.3, {
        maxSize: 96,
        minSize: 36,
        weight: 900,
        color: '#373737',
      });
    }
  }

  // QR code bas droite (scannable → page badge ouvrier)
  try {
    const token = ensureBadgeToken(participant);
    const qrUrl = getBadgePublicUrl(token);
    const qrDataUrl = await buildStudioQrDataUrl(qrUrl);
    if (qrDataUrl) {
      const qrImg = await loadBadgeImage(qrDataUrl);
      const qrSize = badgeWidth * 0.16;
      const qrX = x0 + badgeWidth * 0.90 - qrSize;
      const qrY = y0 + badgeHeight * 0.86 - qrSize * 0.15;
      const pad = qrSize * 0.08;
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(qrX - pad, qrY - pad, qrSize + pad * 2, qrSize + pad * 2);
      ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize);
    }
  } catch (e) { /* QR optionnel à l’export */ }

  return canvas;
}

async function downloadRetreatBadge(target, fileName) {
  const badgeEl = typeof target === 'string' ? document.getElementById(target) : target;
  if (!badgeEl) return;

  const participant = badgeEl.__badgeParticipant;
  const options = badgeEl.__badgeOptions || {};
  let canvas;
  if (participant) {
    canvas = await renderBadgeToCanvas(participant, options);
  } else {
    if (typeof html2canvas === 'undefined') {
      alert('Le module de téléchargement n\'est pas encore disponible. Veuillez réessayer.');
      return;
    }
    canvas = await html2canvas(badgeEl, {
      scale: 3,
      backgroundColor: '#ffffff',
      useCORS: true,
      allowTaint: true,
      logging: false,
    });
  }
  await new Promise((resolve) => {
    canvas.toBlob((blob) => {
      if (!blob) {
        resolve();
        return;
      }
      const link = document.createElement('a');
      link.download = fileName || 'badge_retraite.jpg';
      link.href = URL.createObjectURL(blob);
      link.click();
      setTimeout(() => URL.revokeObjectURL(link.href), 500);
      resolve();
    }, 'image/jpeg', 0.95);
  });
}

function renderRetreatBadge(target, participant, options = {}) {
  const root = typeof target === 'string' ? document.querySelector(target) : target;
  if (!root || !participant) return;

  const category = getBadgeCategory(participant.category);
  const showPhoto = options.showPhoto !== false;
  const isEncadrant = (participant.category || '') === 'encadrants';
  const showAssignmentsFallback = (options.showAssignments ?? participant.showAssignments ?? true) !== false;
  const showWorkshop = (options.showWorkshop ?? participant.showWorkshop ?? options.showAssignments ?? participant.showAssignments ?? true) !== false;
  const showChambre = !isEncadrant && ((options.showRoom ?? participant.showRoom ?? options.showAssignments ?? participant.showAssignments ?? true) !== false);
  const showAssignments = showAssignmentsFallback && (showWorkshop || showChambre);
  const singleAssignment = showAssignments && (showWorkshop !== showChambre);
  const fullName = getParticipantBadgeName(participant) || 'Nom du participant';
  const categoryLabel = getParticipantBadgeCategoryLabel(participant);
  const atelier = showWorkshop && participant.atelier ? participant.atelier : '';
  const roleValue = showChambre
    ? String(participant.departmentRole || participant.chambre || '').trim()
    : '';
  const chambre = roleValue;

  root.style.setProperty('--badge-category-color', category.color);
  ensureBadgeToken(participant);
  root.__badgeParticipant = { ...participant };
  root.__badgeOptions = { ...options };
  root.innerHTML = `
    <div class="retreat-badge ${showPhoto ? '' : 'retreat-badge-no-photo'} ${showAssignments ? '' : 'retreat-badge-no-assignments'} ${singleAssignment ? 'retreat-badge-single-assignment' : ''}" data-category="${participant.category || 'participant'}">
      <img class="retreat-badge-bg" src="assets/bagde-composants/fond-badge.png" alt="">
      <div class="retreat-badge-filter"></div>
      <div class="retreat-badge-border"></div>
      ${showPhoto ? `
        <div class="retreat-badge-photo" aria-label="Photo du badge">
          ${participant.photo ? `<img src="${participant.photo}" alt="Photo de ${badgeEscapeHtml(fullName)}">` : '<i class="bi bi-person-fill"></i>'}
        </div>
      ` : ''}
      <div class="retreat-badge-name-banner">
        <img src="assets/bagde-composants/nom-badge.png" alt="">
        <span>${badgeEscapeHtml(fullName)}</span>
      </div>
      <div class="retreat-badge-category-banner">${badgeEscapeHtml(categoryLabel)}</div>
      ${showAssignments ? `
        ${showWorkshop ? `
          <div class="retreat-badge-assignment retreat-badge-atelier">
            <img src="assets/bagde-composants/Atelier.png" alt="">
            <strong>${badgeEscapeHtml(atelier)}</strong>
          </div>
        ` : ''}
        ${showChambre ? `
          <div class="retreat-badge-assignment retreat-badge-chambre" data-assignment-label="Rôle">
            <img src="assets/bagde-composants/Chambre.png" alt="">
            <span class="retreat-badge-assignment-caption" aria-hidden="true">Rôle</span>
            <strong>${badgeEscapeHtml(chambre)}</strong>
          </div>
        ` : ''}
      ` : ''}
      <div class="retreat-badge-qr is-loading" title="Génération du QR…">
        <img data-badge-qr alt="QR code badge ouvrier" width="128" height="128">
        <span class="retreat-badge-qr-label" aria-hidden="true">QR</span>
      </div>
    </div>
  `;
  const badge = root.querySelector('.retreat-badge');
  if (badge) badge.style.setProperty('--badge-category-color', category.color);
  ensureBadgeToken(participant);
  root.__badgeParticipant = { ...participant };
  root.__badgeOptions = { ...options };
  root.__badgeRenderGeneration = (root.__badgeRenderGeneration || 0) + 1;
  const generation = root.__badgeRenderGeneration;
  requestAnimationFrame(() => fitBadgeName(root));
  void fillBadgeQr(root, participant, generation);
  updateStudioBadgeSizeLabel();
}

/** Dimensions print du badge (A4 @ ~300 dpi) pour le designer. */
const BADGE_PRINT_WIDTH_PX = 2480;
const BADGE_PRINT_HEIGHT_PX = 3508;
const BADGE_PRINT_WIDTH_MM = 210;
const BADGE_PRINT_HEIGHT_MM = 297;

/**
 * Affiche la taille print du badge dans le studio (aide designer).
 */
function updateStudioBadgeSizeLabel() {
  const el = document.getElementById('studioBadgeSizeLabel');
  if (!el) {
    return;
  }
  el.textContent = `${BADGE_PRINT_WIDTH_PX} × ${BADGE_PRINT_HEIGHT_PX} px · A4 (${BADGE_PRINT_WIDTH_MM} × ${BADGE_PRINT_HEIGHT_MM} mm) · ~300 dpi`;
}

function submitForm() {
  const confirmCheck = document.getElementById('confirmCheck');
  if (!confirmCheck.checked) return;

  const participant = buildParticipantFromForm();
  try {
    saveParticipant(participant);
  } catch (e) {
    sessionStorage.setItem('retraite_current_participant_id', participant.id);
  }
  renderRetreatBadge(document.getElementById('badgeCard'), participant, { showAssignments: false });
  goToStep(5);
}

async function downloadBadge() {
  const badgeEl = document.getElementById('badgeCard');
  const btn = document.getElementById('downloadBadgeBtn');
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Génération...';
  btn.disabled = true;

  try {
    const name = getBadgeExportName(`${val('prenom')}_${val('nom')}`);
    await downloadRetreatBadge(badgeEl, `badge_retraite_${name}.jpg`);
  } catch (err) {
    alert('Erreur lors de la génération. Essayez de faire une capture d\'écran du badge.');
  } finally {
    btn.innerHTML = originalText;
    btn.disabled = false;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  initBadgeZoom('badgeCard', {
    in: 'formBadgeZoomIn',
    out: 'formBadgeZoomOut',
    value: 'formBadgeZoomValue',
  });
});

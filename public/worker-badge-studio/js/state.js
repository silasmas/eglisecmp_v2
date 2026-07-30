/* ═══════════════════════════════════════════
   SHARED STATE & HELPERS
═══════════════════════════════════════════ */
'use strict';

window.App = {
  currentStep: 0,
  totalSteps: 6,
  stepLabels: [
    'Identité du participant',
    'Vos coordonnées',
    'Informations de participation',
    'Paiement & justificatif',
    'Récapitulatif',
    'Votre badge'
  ],
  photoDataURL: null,
  proofFile: null,
  proofDataURL: null,
};

/* ─── HELPER: Get field value ─── */
function val(id) {
  const el = document.getElementById(id);
  return el ? el.value.trim() : '';
}

/* ─── HELPER: Get selected text for select ─── */
function selectText(id) {
  const el = document.getElementById(id);
  if (!el || el.selectedIndex < 0) return '';
  return el.options[el.selectedIndex].text;
}

/* ─── HELPER: Get hébergement radio value ─── */
function getHebergementValue() {
  const select = document.getElementById('hebergement');
  if (select) return select.value;
  const checked = document.querySelector('input[name="hebergement"]:checked');
  return checked ? checked.value : '';
}

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>Studio badges — Ouvriers CMP</title>
  <meta name="description" content="Studio de création de badges ouvriers CMP (accès admin sécurisé).">
  <base href="{{ $assetBase }}/">

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
  <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" defer></script>

  <link rel="stylesheet" href="css/tokens.css">
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/form.css">
  <link rel="stylesheet" href="css/uploads.css">
  <link rel="stylesheet" href="css/badge.css">
  <link rel="stylesheet" href="css/buttons.css">
  <link rel="stylesheet" href="css/utilities.css">
  <link rel="stylesheet" href="css/pages.css">
</head>
<body class="studio-page">
  <header class="studio-topbar">
    <div class="studio-brand">
      <span class="studio-brand-mark"><i class="bi bi-person-badge"></i></span>
      <div>
        <strong>Studio badges</strong>
        <small>Ouvriers CMP · {{ $userName }}</small>
      </div>
    </div>

    <nav class="studio-nav" aria-label="Navigation studio">
      <a href="{{ $workersAdminUrl }}"><i class="bi bi-arrow-left"></i> Ouvriers</a>
      <a href="{{ $departmentsAdminUrl }}"><i class="bi bi-building"></i> Départements</a>
      <a href="{{ $qrLinksAdminUrl }}"><i class="bi bi-qr-code"></i> QR pages</a>
    </nav>

    <div class="studio-top-actions">
      <button type="button" class="studio-action-btn ghost" id="seedDemoBtn" title="Créer un exemple">
        <i class="bi bi-stars"></i>
        <span>Exemple</span>
      </button>
      <button type="button" class="studio-action-btn ghost" id="newBadgeBtn" title="Nouveau badge">
        <i class="bi bi-plus-circle"></i>
        <span>Nouveau</span>
      </button>
      <button type="submit" class="studio-action-btn success" form="adminForm" title="Enregistrer">
        <i class="bi bi-save"></i>
        <span>Enregistrer</span>
      </button>
      <button type="button" class="studio-action-btn primary" id="downloadAdminBadgeBtn" title="Télécharger">
        <i class="bi bi-download"></i>
        <span>Exporter</span>
      </button>
    </div>
  </header>

  <main class="studio-workspace">
    <aside class="studio-panel studio-people-panel" aria-label="Participants enregistrés">
      <div class="studio-panel-head">
        <div>
          <h2>Participants</h2>
          <p>Badges enregistrés</p>
        </div>
        <i class="bi bi-people"></i>
      </div>
      <div class="admin-list studio-scroll" id="participantsList"></div>
    </aside>

    <section class="studio-stage" aria-label="Aperçu du badge">
      <div class="studio-stage-toolbar" aria-label="Contrôles rapides">
        <label class="studio-chip-toggle" for="adminShowPhoto">
          <input type="checkbox" id="adminShowPhoto" checked>
          <i class="bi bi-camera"></i>
          <span>Photo</span>
        </label>
        <label class="studio-chip-toggle" for="adminShowWorkshop">
          <input type="checkbox" id="adminShowWorkshop" checked>
          <i class="bi bi-grid-1x2"></i>
          <span>Atelier</span>
        </label>
        <label class="studio-chip-toggle" for="adminShowRoom">
          <input type="checkbox" id="adminShowRoom" checked>
          <i class="bi bi-door-open"></i>
          <span>Chambre</span>
        </label>
      </div>

      <div class="studio-canvas">
        <div class="badge-scene">
          <div class="retreat-badge-shell" id="adminBadgePreview"></div>
        </div>
      </div>
    </section>

    <aside class="studio-panel studio-inspector" aria-label="Réglages du badge">
      <div class="studio-inspector-tabs" role="tablist" aria-label="Sections de réglages">
        <button type="button" class="studio-tab active" data-studio-tab="identity" role="tab" aria-selected="true">
          <i class="bi bi-person-lines-fill"></i>
          Infos
        </button>
        <button type="button" class="studio-tab" data-studio-tab="layout" role="tab" aria-selected="false">
          <i class="bi bi-sliders"></i>
          Badge
        </button>
        <button type="button" class="studio-tab" data-studio-tab="categories" role="tab" aria-selected="false">
          <i class="bi bi-palette"></i>
          Couleurs
        </button>
      </div>

      <form class="admin-form studio-inspector-form" id="adminForm">
        <input type="hidden" id="adminId">

        <section class="studio-tab-panel active" data-studio-panel="identity" role="tabpanel">
          <div class="studio-section-head">
            <div>
              <h3>Identité</h3>
              <p>Nom affiché et dénomination du badge.</p>
            </div>
          </div>
          <div class="fields-grid compact">
            <div class="field">
              <label class="field-label" for="adminPrenom">Prénom</label>
              <input type="text" id="adminPrenom" class="field-input" placeholder="Matthieu">
            </div>
            <div class="field">
              <label class="field-label" for="adminNom">Nom</label>
              <input type="text" id="adminNom" class="field-input" placeholder="Makelela">
            </div>
            <div class="field">
              <label class="field-label" for="adminPostnom">Post-nom</label>
              <input type="text" id="adminPostnom" class="field-input" placeholder="">
            </div>
            <div class="field">
              <label class="field-label" for="adminSexe">Sexe</label>
              <select id="adminSexe" class="field-input">
                <option value="M">Masculin</option>
                <option value="F">Féminin</option>
              </select>
            </div>
          </div>
        </section>

        <section class="studio-tab-panel" data-studio-panel="layout" role="tabpanel" hidden>
          <div class="studio-section-head">
            <div>
              <h3>Composition</h3>
              <p>Catégorie, affectation et image.</p>
            </div>
          </div>
          <div class="fields-grid compact">
            <div class="field full">
              <label class="field-label" for="adminCategory">Catégorie</label>
              <select id="adminCategory" class="field-input"></select>
            </div>
            <div class="field">
              <label class="field-label" for="adminAtelier">Atelier</label>
              <input type="text" id="adminAtelier" class="field-input" placeholder="00" maxlength="2">
            </div>
            <div class="field">
              <label class="field-label" for="adminChambre">Chambre</label>
              <input type="text" id="adminChambre" class="field-input" placeholder="AA" maxlength="2">
            </div>
            <div class="field full">
              <label class="field-label" for="adminPhoto">Photo</label>
              <div class="studio-photo-strip">
                <button type="button" class="studio-action-btn ghost" id="adminPhotoPickBtn">
                  <i class="bi bi-camera"></i>
                  <span>Choisir</span>
                </button>
                <button type="button" class="studio-action-btn ghost hidden" id="adminPhotoRemoveBtn">
                  <i class="bi bi-x-circle"></i>
                  <span>Retirer</span>
                </button>
                <span class="admin-photo-file-name" id="adminPhotoFileName">Aucune photo sélectionnée</span>
              </div>
              <input type="file" id="adminPhoto" class="admin-photo-input" accept="image/jpeg,image/png,image/webp">
            </div>
          </div>
        </section>
      </form>

      <section class="studio-tab-panel" data-studio-panel="categories" role="tabpanel" hidden>
        <div class="studio-section-head">
          <div>
            <h3>Catégories</h3>
            <p>Nuancier utilisé par les badges.</p>
          </div>
        </div>
        <div class="studio-category-list studio-scroll" id="adminCategoryList"></div>
        <form class="studio-category-editor" id="adminCategoryForm">
          <input type="hidden" id="adminCategoryKey">
          <div class="field">
            <label class="field-label" for="adminCategoryLabel">Nom</label>
            <input type="text" id="adminCategoryLabel" class="field-input" placeholder="Nouvelle catégorie">
          </div>
          <div class="field studio-color-field">
            <label class="field-label" for="adminCategoryColor">Couleur</label>
            <input type="color" id="adminCategoryColor" class="field-input" value="#4B5563">
          </div>
          <button type="submit" class="studio-action-btn ghost" id="adminCategorySaveBtn">
            <i class="bi bi-check2-circle"></i>
            <span>Enregistrer</span>
          </button>
          <button type="button" class="studio-action-btn ghost" id="adminCategoryResetBtn" disabled>
            <i class="bi bi-arrow-counterclockwise"></i>
            <span>Réinitialiser couleur</span>
          </button>
        </form>
      </section>
    </aside>
  </main>

  <div class="photo-crop-modal hidden" id="adminPhotoCropModal" aria-hidden="true">
    <div class="photo-crop-backdrop" id="adminPhotoCropBackdrop"></div>
    <div class="photo-crop-dialog" role="dialog" aria-modal="true" aria-labelledby="adminPhotoCropTitle">
      <div class="photo-crop-header">
        <h3 id="adminPhotoCropTitle">Recadrer la photo</h3>
        <button type="button" class="photo-crop-close" id="adminPhotoCropClose" aria-label="Fermer">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="photo-crop-body">
        <img id="adminPhotoCropImage" alt="Image à recadrer">
      </div>
      <div class="photo-crop-controls">
        <label for="adminPhotoCropZoom">Zoom</label>
        <input type="range" id="adminPhotoCropZoom" min="0" max="100" value="0">
      </div>
      <div class="photo-crop-actions">
        <button type="button" class="btn btn-outline" id="adminPhotoCropCancel">Annuler</button>
        <button type="button" class="btn btn-next" id="adminPhotoCropApply">
          Appliquer le recadrage <i class="bi bi-check2"></i>
        </button>
      </div>
    </div>
  </div>

  <script>
    // Prefixe localStorage dédié CMP — doit s’exécuter avant state/badge/admin.
    (function () {
      const map = {
        retraite_participants: 'cmp_worker_badge_participants',
        retraite_badge_categories: 'cmp_worker_badge_categories',
        retraite_current_participant_id: 'cmp_worker_badge_current_id',
      };
      const rawGet = localStorage.getItem.bind(localStorage);
      const rawSet = localStorage.setItem.bind(localStorage);
      localStorage.getItem = function (key) {
        return rawGet(map[key] || key);
      };
      localStorage.setItem = function (key, value) {
        return rawSet(map[key] || key, value);
      };
      const rawSessionGet = sessionStorage.getItem.bind(sessionStorage);
      const rawSessionSet = sessionStorage.setItem.bind(sessionStorage);
      sessionStorage.getItem = function (key) {
        return rawSessionGet(map[key] || key);
      };
      sessionStorage.setItem = function (key, value) {
        return rawSessionSet(map[key] || key, value);
      };
    })();
  </script>
  <script src="js/state.js"></script>
  <script src="js/badge.js"></script>
  <script src="js/admin.js"></script>
</body>
</html>

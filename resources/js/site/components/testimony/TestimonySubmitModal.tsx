import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { X } from 'lucide-react';
import type { TestimonyWallSettings, WallConfig } from '../../data/types';
import { submitTestimony } from '../../lib/siteApi';
import { cn } from '../../lib/utils';
import FileDropZone from '../ui/FileDropZone';

const INPUT_CLASS =
  'w-full rounded-lg border border-surface-200 bg-white px-3 py-2.5 text-sm text-surface-900 focus:border-[#950000] focus:outline-none focus:ring-1 focus:ring-[#950000] dark:border-surface-700 dark:bg-surface-900 dark:text-white';

const DEFAULT_SETTINGS: TestimonyWallSettings = {
  allowPhotoUpload: true,
  maxPhotosPerTestimony: 5,
  allowYoutubeLink: true,
  allowVideoUpload: true,
  maxVideoUploadMb: 5,
  allowAnonymous: true,
  requireFirstName: true,
  requireLastName: false,
};

type TestimonySubmitModalProps = {
  open: boolean;
  wall: WallConfig | null;
  wallSettings: TestimonyWallSettings | null;
  onClose: () => void;
  onSuccess: () => void;
};

/**
 * Modale de soumission avec aperçu post-it, palette couleurs/polices, anonymat et médias.
 */
export default function TestimonySubmitModal({
  open,
  wall,
  wallSettings,
  onClose,
  onSuccess,
}: TestimonySubmitModalProps) {
  const settings = wallSettings ?? DEFAULT_SETTINGS;

  const [kind, setKind] = useState<'text' | 'video'>('text');
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [title, setTitle] = useState('');
  const [text, setText] = useState('');
  const [video, setVideo] = useState('');
  const [videoSource, setVideoSource] = useState<'link' | 'upload'>('link');
  const [videoFile, setVideoFile] = useState<File | null>(null);
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [category, setCategory] = useState('');
  const [isAnonymous, setIsAnonymous] = useState(false);
  const [postitColor, setPostitColor] = useState('#FFF6D9');
  const [fontFamily, setFontFamily] = useState('Inter, sans-serif');
  const [images, setImages] = useState<File[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [doneMessage, setDoneMessage] = useState<string | null>(null);
  const [uploadProgress, setUploadProgress] = useState(0);

  const maxTitle = wall?.maxTitleLength ?? 50;
  const maxLen = wall?.maxTextLength ?? 500;
  const maxVideoMb = settings.maxVideoUploadMb ?? wall?.maxVideoUploadMb ?? 5;
  const maxPhotos = settings.maxPhotosPerTestimony;
  const categories = (wall?.categories ?? []).filter((c) => c !== 'Tous' && c !== 'Vidéos');
  const colors = wall?.postItColors ?? [{ name: 'Jaune', value: '#FFF6D9', border: '#F5D693' }];
  const fonts = wall?.fontStyles ?? [{ name: 'Sans-serif', value: 'Inter, sans-serif' }];

  const videoModesAvailable = settings.allowYoutubeLink || settings.allowVideoUpload;

  useEffect(() => {
    if (!settings.allowYoutubeLink && settings.allowVideoUpload) {
      setVideoSource('upload');
    } else if (settings.allowYoutubeLink) {
      setVideoSource('link');
    }
  }, [settings.allowYoutubeLink, settings.allowVideoUpload]);

  useEffect(() => {
    if (!settings.allowAnonymous && isAnonymous) {
      setIsAnonymous(false);
    }
  }, [settings.allowAnonymous, isAnonymous]);

  const previewAuthor = useMemo(() => {
    if (isAnonymous) {
      return 'Anonyme';
    }
    return [firstName, lastName].filter(Boolean).join(' ').trim() || 'Votre nom';
  }, [isAnonymous, firstName, lastName]);

  if (!open) {
    return null;
  }

  const resetAndClose = () => {
    setDoneMessage(null);
    setError(null);
    onClose();
  };

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    setBusy(true);
    setError(null);
    setUploadProgress(0);

    try {
      const result = await submitTestimony(
        {
          kind,
          first_name: isAnonymous ? 'Anonyme' : firstName.trim(),
          last_name: isAnonymous ? undefined : lastName.trim(),
          title: title.trim(),
          text: text.trim(),
          video: video.trim(),
          video_source: videoSource,
          video_file: videoFile ?? undefined,
          email: email.trim(),
          phone: phone.trim(),
          category: category.trim(),
          postit_color: postitColor,
          font_family: fontFamily,
          is_anonymous: isAnonymous,
          verification_type: 'email',
          images: images.length > 0 ? images : undefined,
        },
        (percent) => setUploadProgress(percent),
      );
      setDoneMessage(result.message);
      onSuccess();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Envoi impossible.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[180] flex items-center justify-center p-4" role="dialog" aria-modal="true">
      <button type="button" className="absolute inset-0 bg-black/60" aria-label="Fermer" onClick={resetAndClose} />
      <div className="relative z-10 max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-surface-900">
        <div className="mb-4 flex items-start justify-between gap-4">
          <div>
            <h2 className="text-xl font-bold text-surface-900 dark:text-white">Partager un témoignage</h2>
            <p className="mt-1 text-sm text-surface-600 dark:text-surface-400">
              Publication après validation. Vous recevrez un e-mail lorsque votre témoignage sera en ligne.
            </p>
          </div>
          <button type="button" onClick={resetAndClose} className="rounded-lg p-1 hover:bg-surface-100" aria-label="Fermer">
            <X className="h-5 w-5" />
          </button>
        </div>

        {doneMessage !== null ? (
          <div className="rounded-lg bg-green-50 p-4 text-sm text-green-900">
            {doneMessage}
            <button type="button" className="tw-cta-primary mt-4 w-full" onClick={resetAndClose}>
              Fermer
            </button>
          </div>
        ) : (
          <form onSubmit={(e) => void handleSubmit(e)} className="grid gap-6 lg:grid-cols-2">
            <div className="space-y-4">
              <div className="flex gap-2">
                {(['text', 'video'] as const).map((mode) => (
                  <button
                    key={mode}
                    type="button"
                    disabled={mode === 'video' && !videoModesAvailable}
                    onClick={() => setKind(mode)}
                    className={cn(
                      'flex-1 rounded-lg border py-2 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-40',
                      kind === mode ? 'border-[#950000] bg-[#950000]/10 text-[#950000]' : 'border-surface-200',
                    )}
                  >
                    {mode === 'text' ? 'Texte' : 'Vidéo'}
                  </button>
                ))}
              </div>

              {settings.allowAnonymous ? (
                <label className="flex items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={isAnonymous}
                    onChange={(e) => setIsAnonymous(e.target.checked)}
                    className="rounded border-surface-300"
                  />
                  Publier en anonyme (votre nom ne sera pas affiché)
                </label>
              ) : null}

              {!isAnonymous ? (
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="mb-1 block text-xs font-medium">
                      Prénom{settings.requireFirstName ? ' *' : ''}
                    </label>
                    <input
                      required={settings.requireFirstName}
                      className={INPUT_CLASS}
                      value={firstName}
                      onChange={(e) => setFirstName(e.target.value)}
                    />
                  </div>
                  <div>
                    <label className="mb-1 block text-xs font-medium">
                      Nom{settings.requireLastName ? ' *' : ''}
                    </label>
                    <input
                      required={settings.requireLastName}
                      className={INPUT_CLASS}
                      value={lastName}
                      onChange={(e) => setLastName(e.target.value)}
                    />
                  </div>
                </div>
              ) : null}

              <div>
                <label className="mb-1 block text-xs font-medium">Titre * (max {maxTitle})</label>
                <input
                  required
                  maxLength={maxTitle}
                  className={INPUT_CLASS}
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                />
              </div>

              {kind === 'text' ? (
                <div>
                  <label className="mb-1 block text-xs font-medium">Témoignage *</label>
                  <textarea
                    required
                    maxLength={maxLen}
                    rows={5}
                    className={INPUT_CLASS}
                    value={text}
                    onChange={(e) => setText(e.target.value)}
                  />
                  <p className="mt-1 text-right text-xs text-surface-500">
                    {text.length}/{maxLen}
                  </p>
                </div>
              ) : videoModesAvailable ? (
                <div className="space-y-3">
                  {settings.allowYoutubeLink && settings.allowVideoUpload ? (
                    <div className="flex gap-2">
                      <button
                        type="button"
                        onClick={() => setVideoSource('link')}
                        className={cn(
                          'flex-1 rounded-lg border py-2 text-xs font-medium',
                          videoSource === 'link' ? 'border-[#950000] text-[#950000]' : 'border-surface-200',
                        )}
                      >
                        Lien YouTube
                      </button>
                      <button
                        type="button"
                        onClick={() => setVideoSource('upload')}
                        className={cn(
                          'flex-1 rounded-lg border py-2 text-xs font-medium',
                          videoSource === 'upload' ? 'border-[#950000] text-[#950000]' : 'border-surface-200',
                        )}
                      >
                        Fichier (max {maxVideoMb} Mo)
                      </button>
                    </div>
                  ) : null}
                  {videoSource === 'link' && settings.allowYoutubeLink ? (
                    <input
                      required
                      type="url"
                      className={INPUT_CLASS}
                      placeholder="https://www.youtube.com/watch?v=..."
                      value={video}
                      onChange={(e) => setVideo(e.target.value)}
                    />
                  ) : settings.allowVideoUpload ? (
                    <FileDropZone
                      label="Vidéo"
                      hint={`MP4, WebM — max ${maxVideoMb} Mo`}
                      accept="video/mp4,video/webm,video/quicktime"
                      multiple={false}
                      files={videoFile !== null ? [videoFile] : []}
                      onFilesChange={(list) => {
                        setVideoFile(list[0] ?? null);
                      }}
                      maxFiles={1}
                    />
                  ) : null}
                </div>
              ) : (
                <p className="text-sm text-amber-800">La soumission vidéo est désactivée pour le moment.</p>
              )}

              <div>
                <label className="mb-1 block text-xs font-medium">E-mail * (pour la notification de publication)</label>
                <input required type="email" className={INPUT_CLASS} value={email} onChange={(e) => setEmail(e.target.value)} />
              </div>

              {settings.allowPhotoUpload ? (
                <FileDropZone
                  label={`Photos (max ${maxPhotos}, optionnel)`}
                  hint="Glissez vos images ici"
                  accept="image/*"
                  multiple
                  files={images}
                  onFilesChange={setImages}
                  maxFiles={maxPhotos}
                />
              ) : null}
            </div>

            <div className="space-y-4">
              <p className="text-xs font-semibold uppercase tracking-wide text-surface-500">Aperçu post-it</p>
              <div
                className="tw-preview-postit min-h-[160px] shadow-md"
                style={{ backgroundColor: postitColor, fontFamily }}
              >
                {category !== '' ? (
                  <span className="mb-2 inline-block rounded-full bg-black/5 px-2 py-0.5 text-[10px] font-bold uppercase">
                    {category}
                  </span>
                ) : null}
                <h3 className="font-bold text-surface-900">{title || 'Titre de votre témoignage'}</h3>
                <p className="mt-2 text-sm text-surface-800 whitespace-pre-wrap">
                  {text || (kind === 'video' ? 'Votre vidéo apparaîtra ici après validation.' : 'Votre texte…')}
                </p>
                <p className="mt-3 text-sm font-semibold">— {previewAuthor}</p>
              </div>

              <div>
                <label className="mb-2 block text-xs font-medium">Couleur</label>
                <div className="flex flex-wrap gap-2">
                  {colors.map((c) => (
                    <button
                      key={c.value}
                      type="button"
                      title={c.name}
                      className={cn('tw-color-swatch', postitColor === c.value && 'tw-color-swatch--active')}
                      style={{ backgroundColor: c.value, borderColor: c.border }}
                      onClick={() => setPostitColor(c.value)}
                    />
                  ))}
                </div>
              </div>

              <div>
                <label className="mb-1 block text-xs font-medium">Style d&apos;écriture</label>
                <select className={INPUT_CLASS} value={fontFamily} onChange={(e) => setFontFamily(e.target.value)}>
                  {fonts.map((f) => (
                    <option key={f.value} value={f.value}>
                      {f.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="mb-1 block text-xs font-medium">Catégorie</label>
                <select className={INPUT_CLASS} value={category} onChange={(e) => setCategory(e.target.value)}>
                  <option value="">— Choisir —</option>
                  {categories.map((cat) => (
                    <option key={cat} value={cat}>
                      {cat}
                    </option>
                  ))}
                </select>
              </div>

              {error !== null ? <p className="text-sm text-red-600">{error}</p> : null}

              {busy ? (
                <div className="mb-3">
                  <div className="mb-1 flex justify-between text-xs text-surface-600">
                    <span>Envoi en cours…</span>
                    <span>{uploadProgress}%</span>
                  </div>
                  <div className="h-2 overflow-hidden rounded-full bg-surface-200">
                    <div
                      className="h-full bg-[#950000] transition-all duration-300"
                      style={{ width: `${uploadProgress}%` }}
                    />
                  </div>
                </div>
              ) : null}

              <button type="submit" disabled={busy} className="tw-cta-primary w-full disabled:opacity-60">
                {busy ? 'Envoi…' : 'Envoyer pour validation'}
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
}

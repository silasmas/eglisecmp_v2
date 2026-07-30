import { useCallback, useRef, useState } from 'react';
import Cropper, { type Area } from 'react-easy-crop';
import { Camera, ImagePlus, Trash2 } from 'lucide-react';

type PhotoCropFieldProps = {
  value: Blob | null;
  previewUrl: string;
  onChange: (blob: Blob | null, previewUrl: string) => void;
};

/**
 * Capture / galerie photo avec rognage circulaire pour le badge.
 */
export default function PhotoCropField({ value, previewUrl, onChange }: PhotoCropFieldProps) {
  const fileRef = useRef<HTMLInputElement | null>(null);
  const cameraRef = useRef<HTMLInputElement | null>(null);
  const [rawUrl, setRawUrl] = useState<string | null>(null);
  const [crop, setCrop] = useState({ x: 0, y: 0 });
  const [zoom, setZoom] = useState(1);
  const [croppedArea, setCroppedArea] = useState<Area | null>(null);

  /**
   * Ouvre un fichier image sélectionné (galerie ou caméra).
   */
  const openFile = (file: File | undefined) => {
    if (file === undefined) {
      return;
    }
    const url = URL.createObjectURL(file);
    setRawUrl(url);
    setZoom(1);
    setCrop({ x: 0, y: 0 });
  };

  /**
   * Produit un blob JPEG carré à partir de la zone rognée.
   */
  const applyCrop = useCallback(async () => {
    if (rawUrl === null || croppedArea === null) {
      return;
    }

    const image = await loadImage(rawUrl);
    const canvas = document.createElement('canvas');
    const size = 640;
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    if (ctx === null) {
      return;
    }

    ctx.drawImage(
      image,
      croppedArea.x,
      croppedArea.y,
      croppedArea.width,
      croppedArea.height,
      0,
      0,
      size,
      size,
    );

    const blob = await new Promise<Blob | null>((resolve) => {
      canvas.toBlob((result) => resolve(result), 'image/jpeg', 0.92);
    });

    if (blob === null) {
      return;
    }

    const nextPreview = URL.createObjectURL(blob);
    onChange(blob, nextPreview);
    URL.revokeObjectURL(rawUrl);
    setRawUrl(null);
  }, [croppedArea, onChange, rawUrl]);

  return (
    <div className="space-y-4">
      {previewUrl !== '' && rawUrl === null ? (
        <div className="flex flex-col items-center gap-3">
          <img
            src={previewUrl}
            alt="Aperçu photo"
            className="h-40 w-40 rounded-full object-cover ring-4 ring-burgundy-200"
          />
          <button
            type="button"
            onClick={() => onChange(null, '')}
            className="inline-flex items-center gap-2 text-sm font-semibold text-red-700"
          >
            <Trash2 className="h-4 w-4" />
            Supprimer
          </button>
        </div>
      ) : null}

      {rawUrl !== null ? (
        <div className="space-y-3">
          <div className="relative h-72 overflow-hidden rounded-2xl bg-surface-900">
            <Cropper
              image={rawUrl}
              crop={crop}
              zoom={zoom}
              aspect={1}
              cropShape="round"
              showGrid={false}
              onCropChange={setCrop}
              onZoomChange={setZoom}
              onCropComplete={(_, area) => setCroppedArea(area)}
            />
          </div>
          <input
            type="range"
            min={1}
            max={3}
            step={0.05}
            value={zoom}
            onChange={(e) => setZoom(Number(e.target.value))}
            className="w-full"
            aria-label="Zoom"
          />
          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => {
                URL.revokeObjectURL(rawUrl);
                setRawUrl(null);
              }}
              className="flex-1 rounded-xl border border-surface-200 px-4 py-2.5 text-sm font-semibold"
            >
              Annuler
            </button>
            <button
              type="button"
              onClick={() => void applyCrop()}
              className="flex-1 rounded-xl bg-burgundy-900 px-4 py-2.5 text-sm font-semibold text-white"
            >
              Rogner et utiliser
            </button>
          </div>
        </div>
      ) : (
        <div className="grid gap-2 sm:grid-cols-2">
          <button
            type="button"
            onClick={() => cameraRef.current?.click()}
            className="inline-flex items-center justify-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-3 text-sm font-semibold hover:bg-surface-50"
          >
            <Camera className="h-4 w-4" />
            Capturer
          </button>
          <button
            type="button"
            onClick={() => fileRef.current?.click()}
            className="inline-flex items-center justify-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-3 text-sm font-semibold hover:bg-surface-50"
          >
            <ImagePlus className="h-4 w-4" />
            Galerie
          </button>
        </div>
      )}

      <input
        ref={fileRef}
        type="file"
        accept="image/*"
        className="hidden"
        onChange={(e) => openFile(e.target.files?.[0])}
      />
      <input
        ref={cameraRef}
        type="file"
        accept="image/*"
        capture="user"
        className="hidden"
        onChange={(e) => openFile(e.target.files?.[0])}
      />

      {value === null && previewUrl === '' && rawUrl === null ? (
        <p className="text-xs text-surface-500">Photo obligatoire pour le badge (visage bien visible).</p>
      ) : null}
    </div>
  );
}

/**
 * Charge une image depuis une URL objet.
 */
function loadImage(src: string): Promise<HTMLImageElement> {
  return new Promise((resolve, reject) => {
    const image = new Image();
    image.addEventListener('load', () => resolve(image));
    image.addEventListener('error', reject);
    image.src = src;
  });
}

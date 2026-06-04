import { useCallback, useState, type DragEvent } from 'react';
import { Upload } from 'lucide-react';
import { cn } from '../../lib/utils';

type FileDropZoneProps = {
  label: string;
  hint?: string;
  accept?: string;
  multiple?: boolean;
  files: File[];
  onFilesChange: (files: File[]) => void;
  maxFiles?: number;
};

/**
 * Zone glisser-déposer pour fichiers (photos ou vidéo).
 */
export default function FileDropZone({
  label,
  hint,
  accept,
  multiple = false,
  files,
  onFilesChange,
  maxFiles = 5,
}: FileDropZoneProps) {
  const [dragOver, setDragOver] = useState(false);

  const addFiles = useCallback(
    (incoming: FileList | null) => {
      if (incoming === null) {
        return;
      }
      const list = multiple ? [...files, ...Array.from(incoming)].slice(0, maxFiles) : [incoming[0]].filter(Boolean);
      onFilesChange(list as File[]);
    },
    [files, maxFiles, multiple, onFilesChange],
  );

  return (
    <div>
      <p className="mb-1 block text-xs font-medium">{label}</p>
      <div
        onDragOver={(e: DragEvent) => {
          e.preventDefault();
          setDragOver(true);
        }}
        onDragLeave={() => setDragOver(false)}
        onDrop={(e: DragEvent) => {
          e.preventDefault();
          setDragOver(false);
          addFiles(e.dataTransfer.files);
        }}
        className={cn(
          'flex flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-8 text-center transition-colors',
          dragOver ? 'border-[#950000] bg-[#950000]/5' : 'border-surface-300 bg-surface-50 dark:border-surface-600 dark:bg-surface-800',
        )}
      >
        <Upload className="mb-2 h-8 w-8 text-surface-400" aria-hidden />
        <p className="text-sm text-surface-600 dark:text-surface-300">
          Glissez-déposez ou{' '}
          <label className="cursor-pointer font-semibold text-[#950000] underline">
            parcourir
            <input
              type="file"
              className="sr-only"
              accept={accept}
              multiple={multiple}
              onChange={(e) => addFiles(e.target.files)}
            />
          </label>
        </p>
        {hint !== undefined ? <p className="mt-1 text-xs text-surface-500">{hint}</p> : null}
      </div>
      {files.length > 0 ? (
        <ul className="mt-2 space-y-1 text-xs text-surface-600">
          {files.map((f, i) => (
            <li key={`${f.name}-${i}`} className="flex justify-between gap-2">
              <span className="truncate">{f.name}</span>
              <button
                type="button"
                className="shrink-0 text-red-600"
                onClick={() => onFilesChange(files.filter((_, idx) => idx !== i))}
              >
                Retirer
              </button>
            </li>
          ))}
        </ul>
      ) : null}
    </div>
  );
}

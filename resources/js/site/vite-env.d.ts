/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_YOUTUBE_API_KEY?: string;
  /** Base des routes JSON site public (défaut : `/api/site`). */
  readonly VITE_SITE_API_BASE?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}

import { isRouteErrorResponse, Link, useRouteError } from 'react-router-dom';
import { Compass, Home, RotateCcw } from 'lucide-react';
import PageHero from '../components/ui/PageHero';

type SiteErrorPageProps = {
  /** Force le code affiché (ex. 404) sans lire l’erreur routeur. */
  statusCode?: number;
  /** Titre personnalisé. */
  title?: string;
  /** Message personnalisé. */
  message?: string;
};

/**
 * Page d’erreur publique (404, navigation invalide ou erreur inattendue).
 */
export default function SiteErrorPage({ statusCode, title, message }: SiteErrorPageProps) {
  const routeError = useRouteError();

  let code = statusCode ?? 404;
  let heading = title ?? 'Page introuvable';
  let body =
    message ??
    'Le lien que vous avez suivi n’existe pas ou a été déplacé. Revenez à l’accueil pour continuer votre visite.';

  if (routeError !== undefined && statusCode === undefined && title === undefined) {
    if (isRouteErrorResponse(routeError)) {
      code = routeError.status;
      heading = code === 404 ? 'Page introuvable' : `Erreur ${code}`;
      body =
        typeof routeError.data === 'string' && routeError.data.trim() !== ''
          ? routeError.data
          : routeError.statusText || body;
    } else if (routeError instanceof Error && routeError.message.trim() !== '') {
      code = 500;
      heading = 'Une erreur est survenue';
      body = 'Nous n’avons pas pu afficher cette page. Réessayez ou retournez à l’accueil.';
    }
  }

  return (
    <>
      <PageHero
        badge="Navigation"
        title={heading}
        description="Centre Missionnaire Philadelphie"
        compact
        backgroundImage="https://images.unsplash.com/photo-1438232992991-995b7058bbb3?w=1600&h=700&fit=crop"
      />

      <section className="mx-auto max-w-3xl px-4 pb-24 pt-10 sm:px-6 lg:px-8">
        <div className="overflow-hidden rounded-3xl border border-surface-200 bg-white shadow-xl dark:border-surface-700 dark:bg-surface-950">
          <div className="bg-gradient-to-br from-burgundy-900 via-burgundy-800 to-burgundy-950 px-8 py-10 text-center text-white">
            <p className="text-6xl font-black tracking-tight opacity-95">{code}</p>
            <p className="mt-3 font-heading text-xl font-semibold">{heading}</p>
          </div>

          <div className="space-y-6 px-6 py-8 text-center sm:px-10">
            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-burgundy-50 text-burgundy-800 dark:bg-burgundy-950/40 dark:text-burgundy-200">
              <Compass className="h-7 w-7" aria-hidden />
            </div>
            <p className="text-sm leading-relaxed text-surface-600 dark:text-surface-300">{body}</p>

            <div className="flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center">
              <Link
                to="/"
                className="inline-flex items-center justify-center gap-2 rounded-xl bg-burgundy-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-burgundy-800"
              >
                <Home className="h-4 w-4 shrink-0" aria-hidden />
                Retour à l&apos;accueil
              </Link>
              <button
                type="button"
                onClick={() => window.history.back()}
                className="inline-flex items-center justify-center gap-2 rounded-xl border border-surface-300 bg-white px-6 py-3 text-sm font-semibold text-surface-800 transition hover:bg-surface-50 dark:border-surface-600 dark:bg-surface-900 dark:text-surface-100"
              >
                <RotateCcw className="h-4 w-4 shrink-0" aria-hidden />
                Page précédente
              </button>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}

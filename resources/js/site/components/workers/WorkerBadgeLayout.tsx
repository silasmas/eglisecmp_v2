import { useEffect, type ReactNode } from 'react';
import { Outlet } from 'react-router-dom';

/**
 * Layout module badge (modèle retraite) — aucun chrome du site public.
 *
 * @param props.children Contenu optionnel (entrée Blade dédiée) ; sinon Outlet React Router.
 */
export default function WorkerBadgeLayout({ children }: { children?: ReactNode }) {
  useEffect(() => {
    document.documentElement.classList.add('worker-badge-module-root');
    document.body.classList.add('print-a6-page', 'worker-badge-module');

    return () => {
      document.documentElement.classList.remove('worker-badge-module-root');
      document.body.classList.remove('print-a6-page', 'worker-badge-module');
    };
  }, []);

  return <div className="worker-badge-module-shell">{children ?? <Outlet />}</div>;
}

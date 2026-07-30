import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import WorkerBadgeLayout from './components/workers/WorkerBadgeLayout';
import { WorkerBadgeView } from './pages/WorkerBadgePage';
import './styles/worker-badge.css';

/**
 * Point d’entrée dédié — page badge hors SPA du site public.
 */
const rootElement = document.getElementById('worker-badge-root');

if (rootElement === null) {
  throw new Error('Élément #worker-badge-root introuvable.');
}

const token = rootElement.dataset.token?.trim() ?? '';

createRoot(rootElement).render(
  <StrictMode>
    <WorkerBadgeLayout>
      <WorkerBadgeView token={token} />
    </WorkerBadgeLayout>
  </StrictMode>,
);

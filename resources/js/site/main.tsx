import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { RouterProvider } from 'react-router-dom';
import SpaErrorBoundary from './components/ui/SpaErrorBoundary';
import { router } from './routes';
import './styles/index.css';
import './styles/worker-badge.css';

const rootElement = document.getElementById('root');

if (rootElement == null) {
  throw new Error('Élément #root introuvable — la SPA ne peut pas démarrer.');
}

createRoot(rootElement).render(
  <StrictMode>
    <SpaErrorBoundary>
      <RouterProvider router={router} />
    </SpaErrorBoundary>
  </StrictMode>,
);

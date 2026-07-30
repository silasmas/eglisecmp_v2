import { Outlet } from 'react-router-dom';
import '../styles/worker-badge.css';

/**
 * Layout minimal pour pages badge (hors chrome site : pas de navbar / footer / FAB).
 */
export default function WorkerBadgeLayout() {
  return (
    <div className="worker-badge-page min-h-screen bg-[#f4f0ea] text-zinc-900">
      <Outlet />
    </div>
  );
}

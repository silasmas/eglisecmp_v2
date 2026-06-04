import { PenLine, Share2, Sparkles } from 'lucide-react';
import type { WallStats } from '../../data/types';
import { TestimonyCtaSkeleton } from '../ui/Skeleton';

type TestimonyCtaFooterProps = {
  stats: WallStats | null;
  statsLoading: boolean;
  onWriteClick: () => void;
};

/**
 * Section « Prêt à témoigner ? » avec statistiques et cartes explicatives.
 */
export default function TestimonyCtaFooter({ stats, statsLoading, onWriteClick }: TestimonyCtaFooterProps) {
  if (statsLoading) {
    return <TestimonyCtaSkeleton />;
  }

  return (
    <section className="tw-footer-cta">
      <div className="tw-footer-glow" aria-hidden />
      <div className="relative mx-auto max-w-4xl px-4 text-center">
        <div className="tw-stats-row">
          <div className="tw-stat-badge">
            <span aria-hidden>📝</span>
            <span className="tw-stat-value">{stats?.testimonies ?? 0}</span>
            <span>témoignages partagés</span>
          </div>
          <div className="tw-stat-badge">
            <span aria-hidden>🙌</span>
            <span className="tw-stat-value">{stats?.reactions ?? 0}</span>
            <span>réactions reçues</span>
          </div>
          <div className="tw-stat-badge">
            <span aria-hidden>🧡</span>
            <span className="tw-stat-value">{stats?.shares ?? 0}</span>
            <span>partages</span>
          </div>
        </div>

        <h2 className="mb-3 text-3xl font-bold text-surface-900 md:text-4xl">Prêt à témoigner ?</h2>
        <p className="mx-auto mb-10 max-w-xl text-surface-600">
          Votre histoire peut encourager, inspirer et fortifier la foi de milliers de personnes.
          <br />
          Partagez ce que Dieu a fait dans votre vie.
        </p>

        <div className="tw-steps">
          <div className="tw-step-card">
            <div className="tw-step-icon">
              <PenLine className="h-6 w-6" aria-hidden />
            </div>
            <h3 className="mb-2 font-bold text-surface-900">Écris ton témoignage</h3>
            <p className="text-sm text-surface-600">
              Titre court (max 50 caractères) + description. Option photo (modérée).
            </p>
          </div>
          <div className="tw-step-card">
            <div className="tw-step-icon">
              <Sparkles className="h-6 w-6" aria-hidden />
            </div>
            <h3 className="mb-2 font-bold text-surface-900">Épingle et célèbre</h3>
            <p className="text-sm text-surface-600">
              Ton post-it apparaît sur le mur avec ta couleur. Animation douce à l&apos;ajout.
            </p>
          </div>
          <div className="tw-step-card">
            <div className="tw-step-icon">
              <Share2 className="h-6 w-6" aria-hidden />
            </div>
            <h3 className="mb-2 font-bold text-surface-900">Partage &amp; modère</h3>
            <p className="text-sm text-surface-600">
              Liens de partage, signalement, et validation par les modérateurs CMP.
            </p>
          </div>
        </div>

        <button type="button" onClick={onWriteClick} className="tw-cta-primary text-lg shadow-lg shadow-[#950000]/30">
          Écrire mon témoignage
        </button>
      </div>
    </section>
  );
}

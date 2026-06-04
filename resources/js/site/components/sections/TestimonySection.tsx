import { useCallback, useEffect, useState } from 'react';
import { fetchWallConfig } from '../../lib/siteApi';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { ArrowRight } from 'lucide-react';
import SectionHeader from '../ui/SectionHeader';
import TestimonyHomeCarousel from '../testimony/TestimonyHomeCarousel';
import TestimonyDetailModal from '../testimony/TestimonyDetailModal';
import type { WallTestimony } from '../../data/types';
import { staggerContainer, staggerItem } from '../../lib/animations';

/**
 * Section d’accueil : carrousel horizontal de témoignages + lien vers le mur.
 */
export default function TestimonySection() {
  const [modalOpen, setModalOpen] = useState(false);
  const [modalIndex, setModalIndex] = useState(0);
  const [modalList, setModalList] = useState<WallTestimony[]>([]);
  const [reactionKeys, setReactionKeys] = useState<Record<string, string>>({});

  useEffect(() => {
    void fetchWallConfig()
      .then((cfg) => setReactionKeys(cfg.reactionKeys))
      .catch(() => {
        /* réactions optionnelles sur l’aperçu accueil */
      });
  }, []);

  const openDetail = useCallback((testimony: WallTestimony, index: number, list: WallTestimony[]) => {
    const idx = list.findIndex((t) => t.id === testimony.id);
    setModalList(list);
    setModalIndex(idx >= 0 ? idx : index);
    setModalOpen(true);
  }, []);

  return (
    <section className="py-24 relative overflow-hidden bg-surface-50">
      <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <motion.div variants={staggerContainer} initial="hidden" whileInView="show" viewport={{ once: true, margin: '-50px' }}>
          <motion.div variants={staggerItem}>
            <SectionHeader
              badge="Témoignages"
              title="Nos fidèles parlent"
              description="Ce que Dieu fait à CMP à travers la vie de nos membres."
            />
          </motion.div>

          <motion.div variants={staggerItem} className="mb-8 flex flex-wrap items-center justify-center gap-4">
            <Link
              to="/temoignages"
              className="inline-flex items-center gap-2 rounded-full bg-[#950000] px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-[#750000]"
            >
              Voir le mur de témoignages
              <ArrowRight className="h-4 w-4" aria-hidden />
            </Link>
            <Link
              to="/temoignages"
              className="text-sm font-semibold text-burgundy-700 underline hover:text-burgundy-900 dark:text-burgundy-400"
            >
              Partager mon témoignage
            </Link>
          </motion.div>

          <motion.div variants={staggerItem}>
            <TestimonyHomeCarousel onSelect={openDetail} />
          </motion.div>
        </motion.div>
      </div>

      <TestimonyDetailModal
        open={modalOpen}
        items={modalList}
        index={modalIndex}
        reactionLabels={reactionKeys}
        onClose={() => setModalOpen(false)}
        onIndexChange={setModalIndex}
      />
    </section>
  );
}

import { motion } from 'framer-motion';
import PageHero from '../components/ui/PageHero';
import MediaCard from '../components/cards/MediaCard';
import { gallery as fallbackGallery } from '../data/content';
import { useSiteGallery } from '../hooks/useSiteGallery';

export default function MediaPage() {
  const { items: gallery } = useSiteGallery(fallbackGallery, 60);
  return (
    <>
      <PageHero
        badge="Médias"
        title="Notre galerie"
        description="Revivez les moments forts de notre communauté à travers photos et vidéos."
        backgroundImage="https://images.unsplash.com/photo-1529070538774-1843cb3265df?w=1400&h=600&fit=crop"
      />

      <section className="py-24">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5 }}
            className="grid grid-cols-2 md:grid-cols-3 gap-4"
          >
            {gallery.map((item) => (
              <MediaCard key={item.id} item={item} />
            ))}
          </motion.div>
        </div>
      </section>
    </>
  );
}

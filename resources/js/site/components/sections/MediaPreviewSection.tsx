import { motion } from 'framer-motion';
import { ArrowRight } from 'lucide-react';
import CTAButton from '../ui/CTAButton';
import { gallery as fallbackGallery } from '../../data/content';
import { useSiteGallery } from '../../hooks/useSiteGallery';

export default function MediaPreviewSection() {
  const { items: gallery } = useSiteGallery(fallbackGallery, 6);
  const items = gallery.slice(0, 6);

  if (items.length === 0) {
    return null;
  }

  return (
    <section className="py-24 bg-surface-900 relative overflow-hidden">
      {/* Subtle glow */}
      <div className="absolute bottom-0 right-0 w-[600px] h-[400px] bg-surface-800/30 rounded-full blur-[100px]" />

      <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6 mb-12">
          <div>
            <span className="inline-block text-[11px] font-semibold uppercase tracking-[0.15em] mb-5 px-4 py-1.5 rounded-full bg-white/10 text-gold-300 border border-white/10">
              Galerie
            </span>
            <h2 className="font-heading font-extrabold text-3xl sm:text-4xl lg:text-[3.25rem] text-white leading-[1.1] tracking-tight">
              Notre galerie
            </h2>
            <p className="mt-4 text-lg text-surface-400 leading-relaxed max-w-xl">
              Revivez les moments forts de notre communauté en images.
            </p>
          </div>
          <CTAButton to="/media" variant="ghost" className="text-white border border-white/15 hover:bg-white/10 hover:text-white shrink-0">
            Voir tout <ArrowRight className="w-4 h-4" />
          </CTAButton>
        </div>

        <motion.div
          initial={{ opacity: 0, y: 40 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: '-80px' }}
          transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
          className="grid grid-cols-2 md:grid-cols-4 md:grid-rows-2 gap-3"
        >
          {/* Large feature image */}
          <div className="col-span-2 row-span-2 group relative rounded-2xl overflow-hidden cursor-pointer">
            <div className="aspect-square md:h-full">
              <img
                src={items[0].src}
                alt={items[0].alt}
                className="w-full h-full object-cover img-hover"
              />
            </div>
            <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
            <div className="absolute bottom-0 left-0 right-0 p-5 translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
              <span className="text-[11px] font-semibold text-gold-300 uppercase tracking-[0.12em]">
                {items[0].category}
              </span>
              <p className="text-white text-lg font-heading font-bold mt-1">{items[0].alt}</p>
            </div>
          </div>
          {/* Remaining 4 items in a 2x2 grid */}
          {items.slice(1, 5).map((item) => (
            <div
              key={item.id}
              className="group relative rounded-2xl overflow-hidden cursor-pointer"
            >
              <div className="aspect-square">
                <img
                  src={item.src}
                  alt={item.alt}
                  className="w-full h-full object-cover img-hover"
                />
              </div>
              <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
              <div className="absolute bottom-0 left-0 right-0 p-3 translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                <span className="text-[10px] font-semibold text-gold-300 uppercase tracking-[0.12em]">
                  {item.category}
                </span>
                <p className="text-white text-sm font-medium mt-0.5">{item.alt}</p>
              </div>
            </div>
          ))}
        </motion.div>
      </div>
    </section>
  );
}

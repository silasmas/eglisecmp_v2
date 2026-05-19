import { motion } from 'framer-motion';
import QuoteBlock from '../cards/QuoteBlock';
import SectionHeader from '../ui/SectionHeader';
import { testimonies } from '../../data/content';
import { staggerContainer, staggerItem } from '../../lib/animations';

export default function TestimonySection() {
  return (
    <section className="py-24 relative bg-surface-50">
      <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <SectionHeader
          badge="Témoignages"
          title="Nos fidèles parlent"
          description="Ce que Dieu fait à CMP à travers la vie de nos membres."
        />

        <motion.div
          variants={staggerContainer}
          initial="hidden"
          whileInView="show"
          viewport={{ once: true, margin: '-50px' }}
          className="grid grid-cols-1 md:grid-cols-3 gap-6"
        >
          {testimonies.map((testimony) => (
            <motion.div key={testimony.id} variants={staggerItem}>
              <QuoteBlock testimony={testimony} />
            </motion.div>
          ))}
        </motion.div>
      </div>
    </section>
  );
}

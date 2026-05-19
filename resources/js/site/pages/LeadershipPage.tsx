import { motion } from 'framer-motion';
import PageHero from '../components/ui/PageHero';
import { leaders } from '../data/content';

export default function LeadershipPage() {
  return (
    <>
      <PageHero
        badge="Leadership"
        title="Nos pasteurs"
        description="Rencontrez les leaders qui servent et guident notre communauté avec amour et dévouement."
      />

      <section className="py-24">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          {/* Featured leaders */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            {leaders.map((leader, i) => (
              <motion.div
                key={leader.id}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: i * 0.15 }}
                className="rounded-2xl bg-white border border-surface-200 shadow-sm overflow-hidden group"
              >
                <div className="aspect-[4/5] overflow-hidden">
                  <img
                    src={leader.image}
                    alt={leader.name}
                    className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                  />
                </div>
                <div className="p-6">
                  <span className="text-xs font-semibold uppercase tracking-widest text-burgundy-600">
                    {leader.role}
                  </span>
                  <h3 className="font-heading font-semibold text-surface-900 text-2xl mt-2 mb-3">
                    {leader.name}
                  </h3>
                  <p className="text-surface-500 text-sm leading-relaxed">
                    {leader.bio}
                  </p>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}

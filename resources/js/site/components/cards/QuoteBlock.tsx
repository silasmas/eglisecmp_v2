import { motion } from 'framer-motion';
import { Quote } from 'lucide-react';
import type { Testimony } from '../../data/types';

interface QuoteBlockProps {
  testimony: Testimony;
}

export default function QuoteBlock({ testimony }: QuoteBlockProps) {
  return (
    <motion.div
      whileHover={{ y: -5, transition: { type: 'spring', stiffness: 400, damping: 25 } }}
      className="rounded-3xl bg-white border border-surface-200 shadow-sm hover:shadow-xl p-8 flex flex-col transition-shadow duration-300"
    >
      <div className="w-10 h-10 rounded-full bg-burgundy-50 border border-burgundy-100 flex items-center justify-center mb-5">
        <Quote className="w-5 h-5 text-burgundy-600" />
      </div>
      <blockquote className="font-heading text-lg sm:text-xl text-surface-800 leading-relaxed flex-1 italic">
        « {testimony.quote} »
      </blockquote>
      <div className="mt-6 pt-6 border-t border-surface-100">
        <p className="font-bold text-surface-900 text-sm">{testimony.name}</p>
        {testimony.role && (
          <p className="text-surface-500 text-[12px] mt-1">{testimony.role}</p>
        )}
      </div>
    </motion.div>
  );
}

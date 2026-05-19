import { motion } from 'framer-motion';
import {
  BookOpen,
  HeartHandshake,
  Users,
  Church,
  CalendarDays,
  Radio,
  Play,
  MapPin,
  Sparkles,
  Sunrise,
  MessageSquareMore,
  Orbit,
} from 'lucide-react';
import SectionHeader from '../ui/SectionHeader';
import ReactionBar from '../ui/ReactionBar';
import { programs as fallbackPrograms } from '../../data/content';
import { useSitePrograms } from '../../hooks/useSitePrograms';
import ImageWithSkeleton from '../ui/ImageWithSkeleton';
import { ProgramsGridSkeleton } from '../ui/Skeleton';

const iconMap: Record<string, typeof BookOpen> = {
  'book-open': BookOpen,
  'heart-handshake': HeartHandshake,
  users: Users,
  church: Church,
  'calendar-days': CalendarDays,
  radio: Radio,
  play: Play,
  'map-pin': MapPin,
  sparkles: Sparkles,
  sunrise: Sunrise,
};

const palettes = [
  'bg-[#132A7A] text-white',
  'bg-[#6A9C41] text-white',
  'bg-[#53B7D6] text-white',
  'bg-[#0B0B0E] text-white',
  'bg-[#DD6A3C] text-white',
];

const illustrationMap = [MessageSquareMore, Orbit, Sunrise, Church, HeartHandshake];

const revealContainer = {
  hidden: {},
  show: {
    transition: {
      staggerChildren: 0.09,
      delayChildren: 0.04,
    },
  },
};

const revealItem = {
  hidden: { opacity: 0, y: 24 },
  show: {
    opacity: 1,
    y: 0,
    transition: {
      duration: 0.5,
      ease: [0.22, 1, 0.36, 1] as const,
    },
  },
};

export default function ProgramsSection() {
  const { programs, loading } = useSitePrograms(fallbackPrograms);

  return (
    <section className="bg-white py-24">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <SectionHeader
          badge="Nos programmes"
          title="Nos rendez-vous"
          description="Des temps forts pensés pour grandir dans la foi, créer du lien et marcher ensemble tout au long de la semaine."
        />

        {loading ? (
          <ProgramsGridSkeleton />
        ) : (
        <motion.div
          variants={revealContainer}
          initial="hidden"
          whileInView="show"
          viewport={{ once: true, margin: '-50px' }}
          className="grid grid-cols-1 gap-5 md:grid-cols-6"
        >
          {programs.map((program, i) => {
            const Icon = iconMap[program.icon] ?? BookOpen;
            const Illustration = illustrationMap[i % illustrationMap.length];
            const spanClass = program.gridWide ? 'md:col-span-3' : 'md:col-span-2';

            return (
              <motion.div
                key={program.id}
                variants={revealItem}
                className={`group relative overflow-hidden rounded-[1.9rem] border border-white/10 p-7 shadow-[0_16px_38px_rgba(15,23,42,0.14)] transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_22px_55px_rgba(15,23,42,0.18)] ${palettes[i % palettes.length]} ${spanClass}`}
              >
                <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0.06),transparent_60%)]" />
                <div className="absolute -right-6 -bottom-10 opacity-[0.12]">
                  <Illustration className="h-44 w-44 stroke-[1.1]" />
                </div>

                <div className="relative flex h-full min-h-[240px] flex-col">
                  {program.thumbnail ? (
                    <span className="absolute right-4 top-4 z-[2] block h-16 w-24 overflow-hidden rounded-xl ring-1 ring-white/20">
                      <ImageWithSkeleton
                        src={program.thumbnail}
                        alt=""
                        className="h-full w-full object-cover opacity-90"
                      />
                    </span>
                  ) : null}
                  <div className="inline-flex w-fit items-center gap-2 rounded-full border border-white/12 bg-black/10 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-white/72">
                    <Icon className="h-3.5 w-3.5" />
                    {program.day}
                  </div>

                  <h3 className="mt-5 max-w-[20rem] font-heading text-[1.95rem] font-bold leading-[1.05] tracking-tight text-white">
                    {program.name}
                  </h3>

                  <p className="mt-4 max-w-md text-sm leading-relaxed text-white/72">
                    {program.description}
                  </p>

                  <div className="mt-auto space-y-3 pt-8">
                    <div className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-black/12 px-3 py-1.5 text-[11px] font-medium text-white/88">
                      <span className="h-1.5 w-1.5 rounded-full bg-gold-300" />
                      {program.time}
                    </div>
                    {program.reactableKey ? (
                      <ReactionBar reactableKey={program.reactableKey} compact className="relative z-10" />
                    ) : null}
                  </div>
                </div>
              </motion.div>
            );
          })}
        </motion.div>
        )}
      </div>
    </section>
  );
}

import { motion } from 'framer-motion';
import { Link } from 'react-router-dom';
import { Play, Clock } from 'lucide-react';
import Tag from '../ui/Tag';
import ReactionBar from '../ui/ReactionBar';
import type { Sermon } from '../../data/types';
import ImageWithSkeleton from '../ui/ImageWithSkeleton';

interface SermonCardProps {
  sermon: Sermon;
  featured?: boolean;
}

export default function SermonCard({ sermon, featured }: SermonCardProps) {
  return (
    <motion.div
      whileHover={{ y: -4, transition: { type: 'spring', stiffness: 400, damping: 25 } }}
      className={`group ${featured ? 'sm:col-span-2' : ''}`}
    >
      <div className="overflow-hidden rounded-3xl border border-surface-200 bg-white shadow-sm transition-shadow duration-300 hover:border-surface-300 hover:shadow-lg">
        <div className={`relative overflow-hidden ${featured ? 'aspect-[16/9]' : 'aspect-video'}`}>
          <Link to={`/teachings/message/${sermon.id}`} className="absolute inset-0 z-0 block">
            <ImageWithSkeleton
              src={sermon.thumbnail}
              alt={sermon.title}
              className="h-full w-full object-cover img-hover transition-transform duration-300 group-hover:scale-[1.02]"
            />
          </Link>
          <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
          {sermon.youtubeEmbedUrl ? (
            <Link
              to={`/teachings/message/${sermon.id}?autoplay=1`}
              className="absolute bottom-4 left-4 z-20 flex h-10 w-10 items-center justify-center rounded-full bg-burgundy-700/90 ring-1 ring-white/20 backdrop-blur-md transition hover:bg-burgundy-600/95"
              aria-label={`Lire la vidéo : ${sermon.title}`}
            >
              <Play className="ml-0.5 h-4 w-4 text-white" />
            </Link>
          ) : (
            <div className="pointer-events-none absolute bottom-4 left-4 flex h-10 w-10 items-center justify-center rounded-full bg-burgundy-700/90 ring-1 ring-white/20 backdrop-blur-md">
              <Play className="ml-0.5 h-4 w-4 text-white" />
            </div>
          )}
          <span className="pointer-events-none absolute bottom-4 right-4 flex items-center gap-1 text-[11px] font-medium text-white/90">
            <Clock className="h-3 w-3" />
            {sermon.duration}
          </span>
        </div>
        <div className="p-5">
          <div className="mb-3 flex items-center gap-2">
            <Tag>{sermon.category}</Tag>
          </div>
          <Link to={`/teachings/message/${sermon.id}`} className="group block">
            <h3 className="font-heading text-lg font-bold leading-snug text-surface-900 transition-colors group-hover:text-burgundy-700 line-clamp-2">
              {sermon.title}
            </h3>
          </Link>
          <p className="mt-2 text-[13px] text-surface-500">
            {sermon.speaker} ·{' '}
            {new Date(sermon.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
          </p>
        </div>
        {sermon.reactableKey ? (
          <div className="border-t border-surface-100 bg-surface-50/80 px-5 py-3">
            <ReactionBar reactableKey={sermon.reactableKey} compact />
          </div>
        ) : null}
      </div>
    </motion.div>
  );
}

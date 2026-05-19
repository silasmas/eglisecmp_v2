import type { GalleryItem } from '../../data/types';

interface MediaCardProps {
  item: GalleryItem;
}

export default function MediaCard({ item }: MediaCardProps) {
  return (
    <div className="group relative rounded-3xl overflow-hidden bg-white border border-surface-200 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer">
      <div className="aspect-[4/3] overflow-hidden">
        <img
          src={item.src}
          alt={item.alt}
          className="w-full h-full object-cover img-hover"
        />
      </div>
      <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
      <div className="absolute bottom-0 left-0 right-0 p-4 translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
        <span className="text-[11px] font-semibold text-burgundy-200 uppercase tracking-[0.12em]">
          {item.category}
        </span>
        <p className="text-white text-sm font-medium mt-1">{item.alt}</p>
      </div>
    </div>
  );
}

import { cn } from '../../lib/utils';

interface FramedImageProps {
  src: string;
  alt: string;
  caption?: string;
  aspect?: string;
  className?: string;
}

export default function FramedImage({
  src,
  alt,
  caption,
  aspect = 'aspect-[4/3]',
  className,
}: FramedImageProps) {
  return (
    <div
      className={cn(
        'bg-white p-2.5 sm:p-3 rounded-xl shadow-lg shadow-black/8 hover:shadow-xl transition-all duration-500 group',
        className
      )}
    >
      <div className={cn('rounded-lg overflow-hidden', aspect)}>
        <img
          src={src}
          alt={alt}
          className="w-full h-full object-cover img-hover"
        />
      </div>
      {caption && (
        <p className="text-surface-500 text-[11px] text-center pt-2 pb-0.5 font-medium">
          {caption}
        </p>
      )}
    </div>
  );
}

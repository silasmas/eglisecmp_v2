import { cn } from '../../lib/utils';

interface TagProps {
  children: string;
  variant?: 'default' | 'accent';
  className?: string;
}

export default function Tag({ children, variant = 'default', className }: TagProps) {
  return (
    <span
      className={cn(
        'inline-block text-[11px] font-medium px-3 py-1 rounded-full',
        variant === 'default'
          ? 'bg-surface-100 text-surface-600'
          : 'bg-burgundy-50 text-burgundy-700',
        className
      )}
    >
      {children}
    </span>
  );
}

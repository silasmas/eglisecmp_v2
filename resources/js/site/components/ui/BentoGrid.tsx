import type { ReactNode } from 'react';
import { cn } from '../../lib/utils';

interface BentoGridProps {
  children: ReactNode;
  className?: string;
}

export function BentoGrid({ children, className }: BentoGridProps) {
  return (
    <div
      className={cn(
        'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4',
        className
      )}
    >
      {children}
    </div>
  );
}

interface BentoCardProps {
  children: ReactNode;
  className?: string;
  colSpan?: 1 | 2 | 3 | 4;
  rowSpan?: 1 | 2;
  variant?: 'default' | 'glass' | 'accent' | 'image';
  onClick?: () => void;
}

const colSpanMap = {
  1: '',
  2: 'sm:col-span-2',
  3: 'sm:col-span-2 lg:col-span-3',
  4: 'sm:col-span-2 lg:col-span-4',
};

const rowSpanMap = {
  1: '',
  2: 'row-span-2',
};

export function BentoCard({
  children,
  className,
  colSpan = 1,
  rowSpan = 1,
  variant = 'default',
  onClick,
}: BentoCardProps) {
  const variantStyles = {
    default: 'bg-white border border-surface-200 shadow-sm',
    glass: 'bg-surface-50 border border-surface-200',
    accent: 'bg-surface-50 border border-surface-200',
    image: 'overflow-hidden',
  };

  const Component = onClick ? 'button' : 'div';

  return (
    <Component
      onClick={onClick}
      className={cn(
        'rounded-3xl p-6 transition-all duration-300',
        'hover:shadow-md hover:border-surface-300',
        variantStyles[variant],
        colSpanMap[colSpan],
        rowSpanMap[rowSpan],
        onClick && 'cursor-pointer hover:scale-[1.01]',
        className
      )}
    >
      {children}
    </Component>
  );
}

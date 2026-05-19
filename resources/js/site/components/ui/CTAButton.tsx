import { motion } from 'framer-motion';
import { Link } from 'react-router-dom';
import { cn } from '../../lib/utils';
import type { ReactNode } from 'react';

const MotionLink = motion.create(Link);

const tapSpring = { type: 'spring', stiffness: 500, damping: 30 } as const;
const hoverSpring = { type: 'spring', stiffness: 400, damping: 20 } as const;

interface CTAButtonProps {
  children: ReactNode;
  to?: string;
  href?: string;
  variant?: 'primary' | 'secondary' | 'ghost' | 'white';
  size?: 'sm' | 'md' | 'lg';
  className?: string;
  onClick?: () => void;
}

export default function CTAButton({
  children,
  to,
  href,
  variant = 'primary',
  size = 'md',
  className,
  onClick,
}: CTAButtonProps) {
  const variants = {
    primary:
      'bg-burgundy-800 text-white hover:bg-burgundy-700 shadow-md shadow-burgundy-900/20 hover:shadow-lg hover:shadow-burgundy-900/25',
    secondary:
      'bg-white text-surface-900 hover:bg-surface-50 border border-surface-200 shadow-sm hover:shadow-md hover:border-surface-300',
    ghost:
      'text-surface-600 hover:text-surface-900 hover:bg-surface-100',
    white:
      'bg-white text-surface-900 border border-surface-200 shadow-sm hover:bg-burgundy-700 hover:text-white hover:border-burgundy-700 hover:shadow-md',
  };

  const sizes = {
    sm: 'px-5 py-2 text-sm',
    md: 'px-6 py-3 text-sm',
    lg: 'px-8 py-3.5 text-[15px]',
  };

  const styles = cn(
    'inline-flex items-center justify-center gap-2 font-semibold rounded-full transition-all duration-300',
    variants[variant],
    sizes[size],
    className
  );

  const motionProps = {
    whileHover: { scale: 1.025, y: -1 },
    whileTap: { scale: 0.975, y: 0 },
    transition: hoverSpring,
  };

  if (to) {
    return (
      <MotionLink to={to} className={styles} {...motionProps} transition={tapSpring}>
        {children}
      </MotionLink>
    );
  }

  if (href) {
    return (
      <motion.a
        href={href}
        target="_blank"
        rel="noopener noreferrer"
        className={styles}
        {...motionProps}
      >
        {children}
      </motion.a>
    );
  }

  return (
    <motion.button onClick={onClick} className={styles} {...motionProps}>
      {children}
    </motion.button>
  );
}

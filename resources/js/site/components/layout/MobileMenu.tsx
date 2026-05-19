import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ChevronDown, Heart } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { navigation } from '../../data/navigation';
import { cn } from '../../lib/utils';

interface MobileMenuProps {
  open: boolean;
  onClose: () => void;
}

export default function MobileMenu({ open, onClose }: MobileMenuProps) {
  const [expandedItem, setExpandedItem] = useState<string | null>(null);

  return (
    <AnimatePresence>
      {open && (
        <>
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-40 bg-black/20 backdrop-blur-sm lg:hidden"
            onClick={onClose}
          />

          {/* Panel */}
          <motion.div
            initial={{ x: '100%' }}
            animate={{ x: 0 }}
            exit={{ x: '100%' }}
            transition={{ type: 'spring', damping: 30, stiffness: 300 }}
            className="fixed top-0 right-0 bottom-0 z-50 w-full max-w-sm bg-white border-l border-surface-200 lg:hidden overflow-y-auto shadow-2xl"
          >
            <div className="p-6 pt-24">
              <nav className="space-y-1">
                {navigation.map((item) => (
                  <div key={item.label}>
                    {item.children ? (
                      <>
                        <button
                          onClick={() =>
                            setExpandedItem(
                              expandedItem === item.label ? null : item.label
                            )
                          }
                          className="flex items-center justify-between w-full px-4 py-3 text-base font-medium text-surface-700 hover:text-burgundy-800 hover:bg-burgundy-50/60 rounded-xl transition-colors"
                        >
                          {item.label}
                          <ChevronDown
                            className={cn(
                              'w-4 h-4 transition-transform',
                              expandedItem === item.label && 'rotate-180'
                            )}
                          />
                        </button>
                        <AnimatePresence>
                          {expandedItem === item.label && (
                            <motion.div
                              initial={{ height: 0, opacity: 0 }}
                              animate={{ height: 'auto', opacity: 1 }}
                              exit={{ height: 0, opacity: 0 }}
                              transition={{ duration: 0.2 }}
                              className="overflow-hidden"
                            >
                              <div className="pl-4 space-y-1 pb-2">
                                {item.children.map((child) => (
                                  <Link
                                    key={child.label}
                                    to={child.href}
                                    onClick={onClose}
                                    className="block px-4 py-2.5 text-sm text-surface-500 hover:text-burgundy-800 hover:bg-burgundy-50/60 rounded-lg transition-colors"
                                  >
                                    {child.label}
                                  </Link>
                                ))}
                              </div>
                            </motion.div>
                          )}
                        </AnimatePresence>
                      </>
                    ) : (
                      <Link
                        to={item.href}
                        onClick={onClose}
                        className="block px-4 py-3 text-base font-medium text-surface-700 hover:text-burgundy-800 hover:bg-burgundy-50/60 rounded-xl transition-colors"
                      >
                        {item.label}
                      </Link>
                    )}
                  </div>
                ))}
              </nav>

              <div className="mt-8 px-4 space-y-3">
                <Link
                  to="/offrandes"
                  onClick={onClose}
                  className="flex items-center justify-center gap-2 w-full px-6 py-3.5 text-base font-semibold rounded-full border-2 border-burgundy-200 text-burgundy-800 hover:bg-burgundy-50 transition-colors"
                >
                  <Heart className="w-4 h-4" />
                  Offrande
                </Link>
                <Link
                  to="/join"
                  onClick={onClose}
                  className="flex items-center justify-center w-full px-6 py-3.5 text-base font-semibold rounded-full bg-burgundy-800 text-white hover:bg-burgundy-700 transition-colors"
                >
                  Nous rejoindre
                </Link>
              </div>
            </div>
          </motion.div>
        </>
      )}
    </AnimatePresence>
  );
}

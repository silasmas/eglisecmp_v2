import {
  Baby,
  Calendar,
  ClipboardList,
  Heart,
  HandHeart,
  IdCard,
  MessageCircleHeart,
  QrCode,
  type LucideIcon,
} from 'lucide-react';

export type QuickActionItem = {
  to: string;
  label: string;
  description: string;
  Icon: LucideIcon;
  fabClassName: string;
  landingClassName: string;
};

/**
 * Actions du bouton flottant (sans inscription ouvrier — QR / lien uniquement).
 */
export const QUICK_ACTION_ITEMS: QuickActionItem[] = [
  {
    to: '/offrandes',
    label: 'Offrande',
    description: 'Soutenir l’œuvre de Dieu en ligne',
    Icon: Heart,
    fabClassName: 'bg-emerald-600 hover:bg-emerald-500',
    landingClassName: 'from-emerald-700 to-emerald-600',
  },
  {
    to: '/temoignages',
    label: 'Mur de témoignages',
    description: 'Lire et partager ce que Dieu fait',
    Icon: MessageCircleHeart,
    fabClassName: 'bg-amber-600 hover:bg-amber-500',
    landingClassName: 'from-amber-600 to-amber-500',
  },
  {
    to: '/requete-de-priere',
    label: 'Requête de prière',
    description: 'Confier un sujet à l’équipe pastorale',
    Icon: HandHeart,
    fabClassName: 'bg-burgundy-700 hover:bg-burgundy-600',
    landingClassName: 'from-burgundy-800 to-burgundy-700',
  },
  {
    to: '/rendez-vous',
    label: 'Prendre rendez-vous',
    description: 'Réserver un créneau pastoral',
    Icon: Calendar,
    fabClassName:
      'bg-surface-900 hover:bg-surface-800 dark:bg-white dark:text-surface-900 dark:hover:bg-surface-100',
    landingClassName: 'from-surface-900 to-surface-800',
  },
  {
    to: '/presentation-enfants',
    label: 'Présentation des enfants',
    description: 'Inscrire votre enfant pour le 2e ou 4e dimanche',
    Icon: Baby,
    fabClassName: 'bg-sky-700 hover:bg-sky-600',
    landingClassName: 'from-sky-800 to-sky-700',
  },
];

/**
 * Pages destinées au scan QR / lien direct (génération QR admin).
 */
export type QrAccessPage = {
  path: string;
  label: string;
  description: string;
};

export const QR_ACCESS_PAGES: QrAccessPage[] = [
  {
    path: '/raccourcis',
    label: 'Raccourcis (landing QR)',
    description: 'Page d’accueil des raccourcis scannables',
  },
  {
    path: '/presentation-enfants',
    label: 'Présentation des enfants',
    description: 'Formulaire parents — 2e et 4e dimanche',
  },
  {
    path: '/protocole/stats-culte',
    label: 'Stats culte (protocole)',
    description: 'Saisie des statistiques de participation',
  },
  {
    path: '/ouvriers/inscription',
    label: 'Inscription ouvrier',
    description: 'Dossier ouvrier + badge (QR / lien uniquement)',
  },
];

/**
 * Liens utiles sur la landing QR (FAB + pages QR dédiées).
 */
export const QR_LANDING_ITEMS: QuickActionItem[] = [
  ...QUICK_ACTION_ITEMS,
  {
    to: '/protocole/stats-culte',
    label: 'Rapport de culte',
    description: 'Équipe protocole — stats de participation',
    Icon: ClipboardList,
    fabClassName: 'bg-teal-700 hover:bg-teal-600',
    landingClassName: 'from-teal-800 to-teal-700',
  },
  {
    to: '/ouvriers/inscription',
    label: 'Inscription ouvrier',
    description: 'Créer mon dossier et obtenir mon badge',
    Icon: IdCard,
    fabClassName: 'bg-indigo-700 hover:bg-indigo-600',
    landingClassName: 'from-indigo-800 to-indigo-700',
  },
];

/** Icône exportée pour usage éventuel. */
export const QrLandingIcon = QrCode;

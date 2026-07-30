import type { NavItem } from './types';

export const navigation: NavItem[] = [
  {
    label: 'Accueil',
    href: '/',
  },
  {
    label: 'Découvrir CMP',
    href: '/discover',
    children: [
      { label: 'À propos', href: '/discover/about' },
      { label: 'Vision & Mission', href: '/discover/vision' },
      { label: 'Leadership', href: '/discover/leadership' },
      { label: 'Nos cellules', href: '/discover/cellules' },
      { label: 'Nos extensions', href: '/discover/extensions' },
    ],
  },
  {
    label: 'Enseignements',
    href: '/teachings',
    children: [
      { label: 'Méditations', href: '/teachings?tab=meditations' },
      { label: 'Messages', href: '/teachings?tab=sermons' },
      { label: 'Playlists', href: '/teachings?tab=playlists' },
    ],
  },
  {
    label: 'Événements',
    href: '/events',
    children: [
      { label: 'Jeudi dédicace', href: '/events?theme=jeudi-dedicace' },
      { label: "Mois de l'ouvrier", href: '/events?theme=mois-ouvrier' },
      { label: 'Séminaires', href: '/events?theme=seminaires' },
      { label: 'Mois évangélique', href: '/events?theme=mois-evangelique' },
      { label: 'Bunda 21', href: '/events/bunda' },
      { label: 'Aksanti Mungu', href: '/events?theme=aksanti-mungu' },
      { label: 'Culte de nativité', href: '/events?theme=nativite' },
      { label: 'Réveillon', href: '/events?theme=reveillon' },
    ],
  },
  {
    label: 'Médias',
    href: '/media',
  },
];

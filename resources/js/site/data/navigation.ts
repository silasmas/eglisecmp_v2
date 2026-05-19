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
      { label: 'Bunda', href: '/events/bunda' },
      { label: 'Événements à venir', href: '/events' },
    ],
  },
  {
    label: 'Médias',
    href: '/media',
  },
  {
    label: 'Contact',
    href: '/contact',
  },
];

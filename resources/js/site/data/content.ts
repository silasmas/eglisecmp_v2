import type { Sermon, Event, Leader, Program, Testimony, GalleryItem } from './types';

export const sermons: Sermon[] = [
  {
    id: '1',
    title: 'Le zèle de ta maison me dévore',
    speaker: 'Pasteur Ken Luamba',
    date: '2026-03-29',
    category: 'Culte dominical',
    thumbnail: 'https://images.unsplash.com/photo-1507692049790-de58290a4334?w=600&h=400&fit=crop',
    duration: '1h 15min',
    description: 'Un message puissant sur la passion pour la maison de Dieu et notre engagement envers Son œuvre.',
  },
  {
    id: '2',
    title: 'La marche de Dieu, Son Alliance avec nous',
    speaker: 'Pasteur Ken Luamba',
    date: '2026-03-22',
    category: 'Enseignement',
    thumbnail: 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?w=600&h=400&fit=crop',
    duration: '58min',
    description: "Comprendre l'alliance que Dieu a établie avec son peuple et comment y marcher fidèlement.",
  },
  {
    id: '3',
    title: 'Le service et la fidélité',
    speaker: 'Pasteur Nathalie Luamba',
    date: '2026-03-15',
    category: 'Méditation',
    thumbnail: 'https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=600&h=400&fit=crop',
    duration: '45min',
    description: 'La fidélité dans le service est le fondement de toute élévation spirituelle.',
  },
  {
    id: '4',
    title: 'Derrière la différence',
    speaker: 'Pasteur Ken Luamba',
    date: '2026-03-08',
    category: 'Culte dominical',
    thumbnail: 'https://images.unsplash.com/photo-1519834785169-98be25ec3f84?w=600&h=400&fit=crop',
    duration: '1h 05min',
    description: "Découvrir ce qui fait la différence dans la vie d'un chrétien engagé.",
  },
  {
    id: '5',
    title: "L'identité spirituelle qui crée la différence",
    speaker: 'Pasteur Ken Luamba',
    date: '2026-03-01',
    category: 'Enseignement',
    thumbnail: 'https://images.unsplash.com/photo-1544027993-37dbfe43562a?w=600&h=400&fit=crop',
    duration: '52min',
    description: 'Comprendre notre identité en Christ pour vivre une vie transformée.',
  },
  {
    id: '6',
    title: 'Marcher dans la foi au quotidien',
    speaker: 'Pasteur Nathalie Luamba',
    date: '2026-02-22',
    category: 'Enseignement',
    thumbnail: 'https://images.unsplash.com/photo-1507692049790-de58290a4334?w=600&h=400&fit=crop',
    duration: '49min',
    description: 'Un enseignement pratique pour vivre une foi solide, persévérante et joyeuse au quotidien.',
  },
];

export const events: Event[] = [
  {
    id: '1',
    title: 'Bunda — Conférence annuelle',
    date: '2026-07-15',
    time: '09:00 - 18:00',
    location: 'Centre Missionnaire Philadelphie',
    description: "Notre conférence phare rassemblant des milliers de fidèles autour de la Parole de Dieu. Un moment unique de communion, d'enseignement et de célébration.",
    image: 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&h=500&fit=crop',
    featured: true,
  },
  {
    id: '2',
    title: 'Nuit de prière',
    date: '2026-04-10',
    time: '21:00 - 05:00',
    location: 'Centre Missionnaire Philadelphie',
    description: 'Une nuit consacrée à la prière et à la louange pour rechercher la face de Dieu.',
    image: 'https://images.unsplash.com/photo-1508963493744-76fce69379c0?w=600&h=400&fit=crop',
  },
  {
    id: '3',
    title: 'Séminaire des jeunes',
    date: '2026-04-25',
    time: '14:00 - 18:00',
    location: 'Centre Missionnaire Philadelphie',
    description: 'Un séminaire interactif dédié à la jeunesse sur le thème de la foi et du leadership.',
    image: 'https://images.unsplash.com/photo-1529070538774-1843cb3265df?w=600&h=400&fit=crop',
  },
  {
    id: '4',
    title: "Journée d'évangélisation",
    date: '2026-05-03',
    time: '08:00 - 16:00',
    location: 'En extérieur',
    description: "Rejoignez-nous pour une journée d'évangélisation et de témoignage dans la communauté.",
    image: 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=600&h=400&fit=crop',
  },
];

export const leaders: Leader[] = [
  {
    id: '1',
    name: 'Pasteur Ken Luamba',
    role: 'Pasteur principal',
    image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop',
    bio: "Fondateur et pasteur principal du Centre Missionnaire Philadelphie, le Pasteur Ken Luamba est animé par la vision de former des disciples et d'impacter les nations.",
  },
  {
    id: '2',
    name: 'Pasteur Nathalie Luamba',
    role: 'Co-pasteur',
    image: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&h=400&fit=crop',
    bio: "Co-pasteur et épouse du Pasteur Ken, elle porte un ministère puissant d'enseignement et d'accompagnement pastoral.",
  },
];

export const programs: Program[] = [
  {
    id: '1',
    name: 'Culte dominical',
    day: 'Dimanche',
    time: '10h00 - 13h00',
    description: "Le grand rassemblement de la semaine pour adorer, écouter la Parole et grandir ensemble dans la foi.",
    icon: 'church',
  },
  {
    id: '2',
    name: 'Intercession',
    day: 'Jeudi',
    time: '17h30 - 19h30',
    description: "Une soirée consacrée à la prière pour l'église, les familles et les nations.",
    icon: 'heart-handshake',
  },
  {
    id: '3',
    name: 'Enseignement',
    day: 'Mercredi',
    time: '17h30 - 19h30',
    description: "Un temps d'enseignement biblique pour affermir la foi et mieux comprendre la pensée de Dieu.",
    icon: 'book-open',
  },
  {
    id: '4',
    name: 'Matinées de Gloire',
    day: 'Lundi à Vendredi',
    time: '5h45 - 6h45',
    description: "Un temps de prière à l'aube pour remettre la journée entre les mains de Dieu.",
    icon: 'church',
  },
  {
    id: '5',
    name: 'Réunion des cellules',
    day: 'Lundi',
    time: '17h30 - 19h30',
    description: 'Des rencontres de proximité pour partager, prier ensemble et prendre soin les uns des autres.',
    icon: 'users',
  },
];

export const testimonies: Testimony[] = [
  {
    id: '1',
    name: 'Marie K.',
    quote: "Le Centre Missionnaire Philadelphie a transformé ma vie. J'ai trouvé une famille spirituelle et un enseignement qui m'a rapprochée de Dieu comme jamais auparavant.",
    role: 'Membre depuis 2018',
  },
  {
    id: '2',
    name: 'Patrick M.',
    quote: "Les enseignements du Pasteur Ken m'ont ouvert les yeux sur ma véritable identité en Christ. Aujourd'hui, je vis ma foi avec une conviction renouvelée.",
    role: 'Membre depuis 2020',
  },
  {
    id: '3',
    name: 'Grâce L.',
    quote: "Bunda a été un tournant dans ma marche chrétienne. Cette conférence m'a donné une nouvelle vision pour ma vie et mon ministère.",
    role: 'Membre depuis 2015',
  },
];

export const gallery: GalleryItem[] = [
  { id: '1', src: 'https://images.unsplash.com/photo-1507692049790-de58290a4334?w=600&h=400&fit=crop', alt: 'Culte dominical', category: 'Culte' },
  { id: '2', src: 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&h=400&fit=crop', alt: 'Conférence Bunda', category: 'Bunda' },
  { id: '3', src: 'https://images.unsplash.com/photo-1529070538774-1843cb3265df?w=600&h=400&fit=crop', alt: 'Séminaire jeunesse', category: 'Jeunesse' },
  { id: '4', src: 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=600&h=400&fit=crop', alt: 'Communion fraternelle', category: 'Communion' },
  { id: '5', src: 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?w=600&h=400&fit=crop', alt: 'Moment de louange', category: 'Louange' },
  { id: '6', src: 'https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=600&h=400&fit=crop', alt: 'Étude biblique', category: 'Enseignement' },
];

export const churchInfo = {
  name: 'Centre Missionnaire Philadelphie',
  shortName: 'CMP',
  brandLineOne: 'Centre Missionnaire',
  brandLineTwo: 'Philadelphie',
  tagline: "L'amour fraternel au service des nations",
  address: '4524, Avenue des Forces armees (ex Haut-Commandement), Kinshasa / Gombe',
  shortAddress: '4524, avenue des Forces Armees...',
  postalBox: 'B.P. 14 Kinshasa 2',
  phone: '(+243) 81 046 68 83 - 081 783 64 11',
  email: 'info@cm-philadelphie.org',
  website: 'https://www.eglisecmp.com',
  socialHandle: '@eglisecmp',
  serviceTimes: {
    sunday: '08h00 - 10h00',
    wednesday: '17h30 - 19h30',
    thursday: '17h30 - 19h30',
    morningGlory: '05h45 - 06h45',
  },
  stats: {
    members: 3360,
    extensions: 10,
    cells: 7,
    pastors: 4,
  },
  social: {
    youtube: 'https://youtube.com/@eglisecmp',
    facebook: 'https://facebook.com/eglisecmp',
    instagram: 'https://instagram.com/eglisecmp',
    x: 'https://x.com/eglisecmp',
    tiktok: 'https://www.tiktok.com/@eglisecmp',
  },
};

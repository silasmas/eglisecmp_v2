export type ChurchGeo = {
  lat: number;
  lng: number;
  mapLabel: string;
  mapsQuery: string;
};

export type CellGroup = {
  id: string;
  name: string;
  commune: string;
  day: string;
  time: string;
  host: string;
  description: string;
};

export type ChurchExtension = {
  id: string;
  name: string;
  city: string;
  country: string;
  address: string;
  lat: number;
  lng: number;
  description: string;
};

/**
 * Coordonnées précises du siège CMP (Gombe, Kinshasa).
 * Avenue des Forces Armées (ex Haut-Commandement).
 */
export const churchGeo: ChurchGeo = {
  lat: -4.30545,
  lng: 15.28672,
  mapLabel: 'Centre Missionnaire Philadelphie',
  mapsQuery: '4524 Avenue des Forces Armees, Gombe, Kinshasa',
};

/**
 * Cellules de maison CMP (Kinshasa).
 */
export const cellGroups: CellGroup[] = [
  {
    id: 'cell-gombe',
    name: 'Cellule Gombe',
    commune: 'Gombe',
    day: 'Mardi',
    time: '18h00',
    host: 'Famille Kabongo',
    description: 'Communion, prière et étude biblique au cœur de Gombe.',
  },
  {
    id: 'cell-lingwala',
    name: 'Cellule Lingwala',
    commune: 'Lingwala',
    day: 'Mercredi',
    time: '18h30',
    host: 'Famille Mbayo',
    description: 'Temps fraternel pour grandir ensemble dans la Parole.',
  },
  {
    id: 'cell-kintambo',
    name: 'Cellule Kintambo',
    commune: 'Kintambo',
    day: 'Jeudi',
    time: '18h00',
    host: 'Famille Ilunga',
    description: 'Partage, intercession et encouragement mutuel.',
  },
  {
    id: 'cell-limete',
    name: 'Cellule Limete',
    commune: 'Limete',
    day: 'Vendredi',
    time: '18h30',
    host: 'Famille Tshilombo',
    description: 'Cellule familiale ouverte à tous les voisins du quartier.',
  },
  {
    id: 'cell-ngaliema',
    name: 'Cellule Ngaliema',
    commune: 'Ngaliema',
    day: 'Mardi',
    time: '18h30',
    host: 'Famille Kalonji',
    description: 'Un foyer pour prier et marcher ensemble dans la foi.',
  },
  {
    id: 'cell-masina',
    name: 'Cellule Masina',
    commune: 'Masina',
    day: 'Mercredi',
    time: '17h30',
    host: 'Famille Mwamba',
    description: 'Rencontre de cellule pour les familles de Masina.',
  },
  {
    id: 'cell-lemba',
    name: 'Cellule Lemba',
    commune: 'Lemba',
    day: 'Jeudi',
    time: '18h00',
    host: 'Famille Ngoie',
    description: 'Étude biblique et communion fraternelle à Lemba.',
  },
];

/**
 * Extensions CMP dans le monde (siège + diaspora).
 */
export const churchExtensions: ChurchExtension[] = [
  {
    id: 'ext-kinshasa-siege',
    name: 'CMP Siège',
    city: 'Kinshasa',
    country: 'RD Congo',
    address: '4524, Avenue des Forces Armées, Gombe',
    lat: -4.30545,
    lng: 15.28672,
    description: 'Maison mère du Centre Missionnaire Philadelphie.',
  },
  {
    id: 'ext-lubumbashi',
    name: 'CMP Lubumbashi',
    city: 'Lubumbashi',
    country: 'RD Congo',
    address: 'Lubumbashi, Haut-Katanga',
    lat: -11.6876,
    lng: 27.5026,
    description: 'Extension missionnaire au Katanga.',
  },
  {
    id: 'ext-matadi',
    name: 'CMP Matadi',
    city: 'Matadi',
    country: 'RD Congo',
    address: 'Matadi, Kongo-Central',
    lat: -5.816,
    lng: 13.45,
    description: 'Présence CMP dans le Kongo-Central.',
  },
  {
    id: 'ext-bruxelles',
    name: 'CMP Bruxelles',
    city: 'Bruxelles',
    country: 'Belgique',
    address: 'Bruxelles, Belgique',
    lat: 50.8503,
    lng: 4.3517,
    description: 'Communauté CMP en diaspora européenne.',
  },
  {
    id: 'ext-paris',
    name: 'CMP Paris',
    city: 'Paris',
    country: 'France',
    address: 'Paris, France',
    lat: 48.8566,
    lng: 2.3522,
    description: 'Assemblée CMP en France.',
  },
  {
    id: 'ext-johannesburg',
    name: 'CMP Johannesburg',
    city: 'Johannesburg',
    country: 'Afrique du Sud',
    address: 'Johannesburg, Afrique du Sud',
    lat: -26.2041,
    lng: 28.0473,
    description: 'Extension CMP en Afrique australe.',
  },
  {
    id: 'ext-montreal',
    name: 'CMP Montréal',
    city: 'Montréal',
    country: 'Canada',
    address: 'Montréal, Québec',
    lat: 45.5017,
    lng: -73.5673,
    description: 'Communauté CMP en Amérique du Nord.',
  },
  {
    id: 'ext-washington',
    name: 'CMP Washington DC',
    city: 'Washington',
    country: 'États-Unis',
    address: 'Washington DC, USA',
    lat: 38.9072,
    lng: -77.0369,
    description: 'Présence CMP aux États-Unis.',
  },
  {
    id: 'ext-london',
    name: 'CMP Londres',
    city: 'Londres',
    country: 'Royaume-Uni',
    address: 'Londres, Royaume-Uni',
    lat: 51.5074,
    lng: -0.1278,
    description: 'Assemblée CMP au Royaume-Uni.',
  },
  {
    id: 'ext-dubai',
    name: 'CMP Dubaï',
    city: 'Dubaï',
    country: 'Émirats arabes unis',
    address: 'Dubaï, EAU',
    lat: 25.2048,
    lng: 55.2708,
    description: 'Cellule / extension CMP au Moyen-Orient.',
  },
];

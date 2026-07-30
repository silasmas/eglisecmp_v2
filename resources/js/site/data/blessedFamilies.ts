/**
 * Galerie locale des familles et parents bénis (présentation d’enfants).
 */
export type BlessedFamilyPhoto = {
  id: string;
  src: string;
  caption: string;
  kind: 'family' | 'parents';
};

export const BLESSED_FAMILY_PHOTOS: BlessedFamilyPhoto[] = [
  {
    id: 'family-1',
    src: 'https://images.unsplash.com/photo-1511895426328-dc8714191300?w=800&h=600&fit=crop',
    caption: 'Famille présentée au culte',
    kind: 'family',
  },
  {
    id: 'parents-1',
    src: 'https://images.unsplash.com/photo-1609220136736-443140cffec6?w=800&h=600&fit=crop',
    caption: 'Parents bénis',
    kind: 'parents',
  },
  {
    id: 'family-2',
    src: 'https://images.unsplash.com/photo-1476703993599-0035a21b17a9?w=800&h=600&fit=crop',
    caption: 'Moment de présentation',
    kind: 'family',
  },
  {
    id: 'parents-2',
    src: 'https://images.unsplash.com/photo-1542037104857-ffbb0b9155fb?w=800&h=600&fit=crop',
    caption: 'Parents et enfants',
    kind: 'parents',
  },
  {
    id: 'family-3',
    src: 'https://images.unsplash.com/photo-1609220136736-443140cffec6?w=800&h=700&fit=crop&crop=faces',
    caption: 'Célébration familiale',
    kind: 'family',
  },
  {
    id: 'parents-3',
    src: 'https://images.unsplash.com/photo-1511895426328-dc8714191300?w=800&h=700&fit=crop&crop=entropy',
    caption: 'Action de grâce des parents',
    kind: 'parents',
  },
];

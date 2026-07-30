/**
 * Calcule l’âge en années et mois à partir d’une date de naissance.
 *
 * @param birthDateIso Date YYYY-MM-DD.
 * @param now Référence (défaut : aujourd’hui).
 * @returns Âge ou null si date invalide / future.
 */
export function ageFromBirthDate(
  birthDateIso: string,
  now: Date = new Date(),
): { years: number; months: number } | null {
  const trimmed = birthDateIso.trim();
  if (trimmed === '') {
    return null;
  }

  const birth = new Date(`${trimmed}T00:00:00`);
  if (Number.isNaN(birth.getTime())) {
    return null;
  }

  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  if (birth > today) {
    return null;
  }

  let years = today.getFullYear() - birth.getFullYear();
  let months = today.getMonth() - birth.getMonth();

  if (today.getDate() < birth.getDate()) {
    months -= 1;
  }

  if (months < 0) {
    years -= 1;
    months += 12;
  }

  if (years < 0) {
    return null;
  }

  return { years, months };
}

/**
 * Libellé français de l’âge calculé.
 *
 * @param years Années.
 * @param months Mois.
 */
export function formatAgeLabel(years: number, months: number): string {
  const yearPart = `${years} an${years > 1 ? 's' : ''}`;
  if (months <= 0) {
    return yearPart;
  }

  return `${yearPart} et ${months} mois`;
}

/**
 * Date max pour le date picker (aujourd’hui en YYYY-MM-DD).
 */
export function todayIsoDate(): string {
  const now = new Date();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');

  return `${now.getFullYear()}-${month}-${day}`;
}

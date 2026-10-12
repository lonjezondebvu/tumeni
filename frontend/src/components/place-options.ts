export type PlaceOption = {value: string; label: string; description: string; aliases: string[]; verified: boolean};
const normalize = (text: string) => text.normalize('NFD').replace(/\p{M}/gu, '').toLocaleLowerCase().trim();
/** Search each word across place names, local aliases and meeting points. */
export function filterPlaces(options: PlaceOption[], query: string): PlaceOption[] {
  const words = normalize(query).split(/\s+/).filter(Boolean);
  return options.filter(option => {
    const searchable = normalize([option.label, ...option.aliases, option.description].join(' '));
    return words.every(word => searchable.includes(word));
  });
}
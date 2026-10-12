import {definitions} from './catalogs.generated.ts';
export type Language = keyof typeof definitions;
export const DEFAULT_LANGUAGE: Language = 'ny';
export const LANGUAGE_STORAGE_KEY = 'tumeni.language';
export const catalogs = Object.fromEntries(Object.entries(definitions).map(([code, locale]) => [code, locale.messages])) as unknown as Record<Language, Readonly<Record<string,string>>>;
export const availableLanguages = Object.entries(definitions).map(([code, locale]) => ({code: code as Language, name: locale.name}));
export function resolveLanguage(value: unknown): Language { return typeof value === 'string' && Object.hasOwn(definitions,value) ? value as Language : DEFAULT_LANGUAGE; }
export function otherLanguage(language: Language): Language {
 const index = availableLanguages.findIndex(locale=>locale.code===language);
 return availableLanguages[(index+1)%availableLanguages.length].code;
}
export function languageButtonLabel(language: Language): string { return definitions[otherLanguage(language)].name; }
export function documentTitle(language: Language): string { return definitions[language].documentTitle; }
function interpolate(message: string, values: readonly (string | number | undefined)[]): string {
 return message.replace(/\{(\d+)\}/g, (placeholder,index) => values[Number(index)] === undefined ? placeholder : String(values[Number(index)]));
}
const patterns = Object.keys(catalogs.en).filter(key=>/\{\d+\}/.test(key)).map(key=>{
 const indices: number[]=[];
 const pieces=key.split(/(\{\d+\})/).map(piece=>{
  if(/^\{\d+\}$/.test(piece)){indices.push(Number(piece.slice(1,-1)));return '(.*?)';}
  return piece.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
 });
 return {key,indices,regex:new RegExp('^'+pieces.join('')+'$')};
});
export function translate(language: Language, message: string, values: readonly (string | number | undefined)[] = []): string {
 const key=message.trim(); const catalog=catalogs[language];
 const leading=message.match(/^\s*/)?.[0]||''; const trailing=message.match(/\s*$/)?.[0]||'';
 const text=catalog[key]?.trim()?catalog[key]:catalogs.en[key];
 if(text)return leading+interpolate(text,values)+trailing;
 // Stored notifications stay in source English; interpolate them when rendered in the current locale.
 if(language!=='en')for(const pattern of patterns){const match=pattern.regex.exec(key);if(match){const args: string[]=[];pattern.indices.forEach((index,i)=>args[index]=match[i+1]);return leading+interpolate(catalog[pattern.key]?.trim()?catalog[pattern.key]:catalogs.en[pattern.key],args)+trailing;}}
 return message;
}
export interface PreferenceStorage { getItem(key: string): string | null; setItem(key: string,value: string): void; }
export function readPreference(storage?: PreferenceStorage): Language {
 try{return resolveLanguage(storage?.getItem(LANGUAGE_STORAGE_KEY));}catch{return DEFAULT_LANGUAGE;}
}
export function savePreference(language: Language,storage?: PreferenceStorage): void {
 try{storage?.setItem(LANGUAGE_STORAGE_KEY,language);}catch{/* The in-memory choice still works when storage is blocked. */}
}
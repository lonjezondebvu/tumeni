# Editable translation files

Translations are plain UTF-8 JSON text files in `frontend/src/i18n/locales/`:

- `en.json`: English source messages and fallback text.
- `ny.json`: Chichewa translations, the default language.
- `_template.json`: copy this to begin a new language; disabled until ready.

Edit these files, not `catalogs.generated.ts`. English text on the left is the stable message key. Translate only the text on the right. For example, ny.json contains `"Sign in": "Lowani"`. Configured place names, user notes and API enum values are separate content.

## Add a language

From the repository root in PowerShell, copy the template with a language code as its filename:

```powershell
Copy-Item -LiteralPath frontend/src/i18n/locales/_template.json -Destination frontend/src/i18n/locales/fr.json
```

Edit the new text file. Set `code` to match its filename, `name` to the language's native name, `documentTitle` to a translated page title, and `enabled` to true. Translate the message values. This abbreviated example illustrates the format:

```json
{
  "code": "fr",
  "name": "Français",
  "enabled": true,
  "documentTitle": "Tumeni · Voyager simplement",
  "messages": {
    "Sign in": "Se connecter",
    "Hello, {0}.": "Bonjour, {0}.",
    "Your pickup": ""
  }
}
```

Blank or omitted message values use English automatically. Leave `enabled` false to keep a translation out of the app. Codes support two/three lowercase letters and optional region, such as `fr` or `pt-MZ`. English and Chichewa must stay enabled. Keep `{0}`, `{1}` and any other numbered placeholders exactly as in the source, including their count. JSON needs double quotes; escape quotes within a value as `\"`. Save as UTF-8.

Run from the repository root:

```powershell
Set-Location frontend
npm.cmd run languages:generate
npm.cmd run test:language
npm.cmd run test:language-files
npm.cmd run build
```

Generation also runs automatically before `npm.cmd run dev`, `npm.cmd run build` and `npm.cmd run test:language`. If editing files while the dev server is running, run `npm.cmd run languages:generate` again in another terminal. The generated module then updates through Vite. Production changes require rebuilding/redeploying the app; this is not a server-side translation editor.

No screen or TypeScript import needs editing to add a language. With English and Chichewa enabled, the header keeps the opposite-language button. With more languages enabled, it becomes a chooser listing their configured names. Chichewa remains the default; the chosen code is saved locally and restored on later visits. Removing/disabling a saved language returns the preference to Chichewa. Changing languages preserves mounted booking/signup forms.

## Add new interface messages

Add the English source text as both key and value in en.json, call `tr('English source text')` in the UI, and add translated values to other JSON files where available. Missing translations fall back to English. Add the same key with a blank value to `_template.json` for future translators. Unknown source keys, invalid JSON, non-text translations and mismatched placeholders stop generation before the previous generated module is replaced.

`008-language-files.ps1` validates enabled files and atomically generates `frontend/src/i18n/catalogs.generated.ts`. The older `005-language-catalog.ps1` now delegates to this generator. `scripts/changes/language-messages.tsv` is retained solely as historical migration input; editing it no longer changes the app. The old en.ts/ny.ts catalogs have been retired.

## Runtime and verification

`core.ts` resolves configured languages, selects messages, interpolates values and handles storage safely. `index.ts` connects React to preferences and updates the document title/lang. New standalone component trees should subscribe with useLanguage. Authentication remains cookie-based; language preference is not an authentication token. No API/database/dependency change.

The initial 438 English/Chichewa messages still need native-speaker review before public release. The default/toggle/storage/status/Unicode tests run with `test:language`; registry discovery, third-language persistence, interpolation and partial-file fallback run with `test:language-files`, which also verifies four invalid-input cases preserve prior output. Fixtures are isolated and removed via PowerShell. Browser layout, keyboard and touch QA remain pending because there is no connected browser surface. No complete French translation is shipped; French appears only in isolated test fixtures and the example above.

Driver verification and PayChangu top-ups add paired interface translations to the same editable JSON files. Current source catalog: 438 messages. Names, ID/licence numbers, addresses and uploaded document contents remain user data and are not translated. Native-speaker review remains pending.

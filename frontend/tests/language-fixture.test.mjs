import test from 'node:test';
import assert from 'node:assert/strict';
import {join} from 'node:path';
import {pathToFileURL} from 'node:url';
const core = await import(pathToFileURL(join(process.env.TUMENI_LANGUAGE_FIXTURE, 'core.ts')).href);

test('a third JSON language is registered without editing the runtime', () => {
  assert.equal(core.resolveLanguage('fr'), 'fr');
  assert.equal(core.availableLanguages.find(locale=>locale.code==='fr').name, 'Français');
  assert.equal(core.documentTitle('fr'), 'Tumeni · Voyager simplement');
  assert.equal(core.resolveLanguage('xx'), 'ny');
  assert.equal(core.resolveLanguage('__proto__'), 'ny');
  assert.equal(core.DEFAULT_LANGUAGE, 'ny');
});
test('partial language files use English for blank or missing translations', () => {
  assert.equal(core.translate('fr', 'Sign in'), 'Se connecter');
  assert.equal(core.translate('fr', 'Your pickup'), 'Your pickup');
  assert.equal(core.translate('fr', 'See your fare'), 'See your fare');
  assert.equal(core.translate('fr', 'Hello, {0}.', ['Chikondi']), 'Hello, Chikondi.');
});
test('new languages translate interpolated and previously stored messages', () => {
  assert.equal(core.translate('fr', '{0} is on the way.', ['Peter $&']), 'Peter $& arrive.');
  assert.equal(core.translate('fr', 'Peter is on the way.'), 'Peter arrive.');
});
test('new language preferences persist and resolve safely', () => {
  let saved;
  const storage = {getItem:()=>saved, setItem:(_key,value)=>{saved=value;}};
  core.savePreference('fr', storage);
  assert.equal(core.readPreference(storage), 'fr');
  saved='unknown'; assert.equal(core.readPreference(storage), 'ny');
});
import test from 'node:test';
import assert from 'node:assert/strict';
import {catalogs,availableLanguages,DEFAULT_LANGUAGE,LANGUAGE_STORAGE_KEY,resolveLanguage,otherLanguage,languageButtonLabel,translate,readPreference,savePreference} from '../src/i18n/core.ts';
const values=new Map([['booking:example','unchanged-booking-id'],['pickup-note','Meet at Gate 4']]);
const handlers=new Map();
const storage={getItem:key=>values.get(key)??null,setItem:(key,value)=>values.set(key,value)};
globalThis.window={localStorage:storage,addEventListener:(name,listener)=>handlers.set(name,listener)};
globalThis.document={documentElement:{lang:''},title:''};
const runtime=await import('../src/i18n/index.ts');

test('first visit and invalid preferences use Chichewa regardless of browser language',()=>{
 assert.equal(DEFAULT_LANGUAGE,'ny');
 for(const value of [null,undefined,'','not-a-locale','EN','ny'])assert.equal(resolveLanguage(value),'ny');
 assert.equal(readPreference(),'ny');assert.equal(runtime.getLanguage(),'ny');assert.equal(document.documentElement.lang,'ny');
 assert.equal(document.title,'Tumeni · Njira yosavuta yoyendera');
 assert.equal(runtime.tr('Where are'),'Mukupita');assert.equal(runtime.tr('you going'),'kuti');
});
test('the button names the next configured language and preserves the bilingual toggle',()=>{
 for(const locale of availableLanguages){
  const next=otherLanguage(locale.code);
  assert.notEqual(next,locale.code);
  assert.equal(languageButtonLabel(locale.code),availableLanguages.find(item=>item.code===next).name);
 }
 if(availableLanguages.length===2){
  assert.equal(languageButtonLabel('ny'),'English');assert.equal(otherLanguage('ny'),'en');
  assert.equal(languageButtonLabel('en'),'Chichewa');assert.equal(otherLanguage('en'),'ny');
 }
});
test('switching to English persists and updates the document without clearing booking input',()=>{
 runtime.setLanguage('en');
 assert.equal(runtime.getLanguage(),'en');assert.equal(document.documentElement.lang,'en');assert.equal(document.title,'Tumeni · A simpler way to ride');
 assert.equal(runtime.tr('Your pickup'),'Your pickup');assert.equal(storage.getItem(LANGUAGE_STORAGE_KEY),'en');assert.equal(readPreference(storage),'en');
 assert.equal(storage.getItem('booking:example'),'unchanged-booking-id');assert.equal(storage.getItem('pickup-note'),'Meet at Gate 4');
 runtime.setLanguage('ny');assert.equal(runtime.tr('Your pickup'),'Pokutengerani');assert.equal(readPreference(storage),'ny');
});
test('blocked storage does not prevent an in-memory language switch',()=>{
 const blocked={getItem(){throw new Error('denied')},setItem(){throw new Error('denied')}};
 assert.equal(readPreference(blocked),'ny');assert.doesNotThrow(()=>savePreference('en',blocked));
 window.localStorage=blocked;assert.doesNotThrow(()=>runtime.setLanguage('en'));assert.equal(runtime.tr('Sign in'),'Sign in');
 window.localStorage=storage;runtime.setLanguage('ny');
});
test('saved preference changes and removal synchronize across tabs',()=>{
 storage.setItem(LANGUAGE_STORAGE_KEY,'en');handlers.get('storage')({key:LANGUAGE_STORAGE_KEY});assert.equal(runtime.getLanguage(),'en');
 values.delete(LANGUAGE_STORAGE_KEY);handlers.get('storage')({key:LANGUAGE_STORAGE_KEY});assert.equal(runtime.getLanguage(),'ny');
 storage.setItem(LANGUAGE_STORAGE_KEY,'en');handlers.get('storage')({key:'unrelated'});assert.equal(runtime.getLanguage(),'ny');
 values.clear();handlers.get('storage')({key:null});assert.equal(runtime.getLanguage(),'ny');
});
test('dynamic messages preserve driver names, references and literal dollar signs',()=>{
 assert.equal(translate('ny','{0} is on the way.',['Peter $&']),'Peter $& ali panjira.');
 assert.equal(translate('en','Hello, {0}.',['Chikondi']),'Hello, Chikondi.');
 assert.equal(translate('ny','Support request #482 recorded. Review due within 48 hours.'),'Pempho la thandizo #482 lasungidwa. Liyenera kuunikidwa pasanathe maola 48.');
 assert.equal(translate('ny',' Sign in '),' Lowani ');
 assert.equal(translate('ny','Mzuzu University'),'Mzuzu University');
 assert.equal(translate('ny','Meet beside Gate 4'),'Meet beside Gate 4');
});
test('statuses and errors are translated for display while API values stay unchanged',()=>{
 const response={state:'driver_arrived',payment_method:'cash'};
 assert.equal(runtime.statusLabel(response.state),'Dalaivala wafika');assert.equal(response.state,'driver_arrived');assert.equal(response.payment_method,'cash');
 assert.equal(runtime.translateError('Pickup PIN is incorrect.'),'PIN ya ulendo si yolondola.');
 assert.equal(runtime.translateError('Unmapped validation error'),'Zachitika zolakwika. Onaninso zomwe mwalemba kapena yesaninso.');
 runtime.setLanguage('en');assert.equal(runtime.translateError('Unmapped validation error'),'Unmapped validation error');runtime.setLanguage('ny');
});
test('configured catalogs use known messages with matching placeholders and correct Unicode',()=>{
 for(const catalog of Object.values(catalogs))for(const [key,value] of Object.entries(catalog)){
  assert.ok(Object.hasOwn(catalogs.en,key),'Unknown message: '+key);
  if(value.trim())assert.deepEqual((key.match(/\{\d+\}/g)||[]).sort(),(value.match(/\{\d+\}/g)||[]).sort(),key);
  assert.ok(!/[\uFFFD]|Ã|Â|â€™/.test(key+value),'Broken encoding: '+key);
 }
 assert.equal(catalogs.ny['Please wait…'],"Dikirani pang'ono…");
});

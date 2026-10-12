import {test} from 'node:test';
import assert from 'node:assert/strict';
import {filterPlaces} from '../src/components/place-options.ts';

const places = [
  {value:'1',label:'Mzuzu University',description:'Main gate',aliases:['Mzuni'],verified:true},
  {value:'2',label:'Mzuzu Market',description:'North entrance',aliases:['Msika'],verified:true},
  {value:'3',label:'Cafe',description:'Meeting point',aliases:['Caf\u00e9'],verified:false},
];

test('blank search shows every configured place in its original order', () => {
  assert.deepEqual(filterPlaces(places, '  '), places);
});
test('local aliases work regardless of case, accents or surrounding whitespace', () => {
  assert.deepEqual(filterPlaces(places, '  MZUNI  ').map(p=>p.value), ['1']);
  assert.deepEqual(filterPlaces(places, 'msika').map(p=>p.value), ['2']);
  assert.deepEqual(filterPlaces(places, 'CAF\u00c9').map(p=>p.value), ['3']);
});
test('search words can match names and meeting points in either order', () => {
  assert.deepEqual(filterPlaces(places, 'gate  mzuzu').map(p=>p.value), ['1']);
  assert.deepEqual(filterPlaces(places, 'north mzuzu').map(p=>p.value), ['2']);
});
test('unmatched and excluded places never become selectable search results', () => {
  assert.deepEqual(filterPlaces(places, 'not-a-landmark'), []);
  assert.deepEqual(filterPlaces(places.filter(p=>p.value!=='1'), 'Mzuni'), []);
});
test('search preserves server identifiers and does not change configured content', () => {
  const original = structuredClone(places);
  const result = filterPlaces(places, 'Mzuni');
  assert.equal(result[0], places[0]);
  assert.deepEqual(places, original);
});
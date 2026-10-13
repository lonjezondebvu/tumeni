import test, {before, after} from 'node:test';
import assert from 'node:assert/strict';
import {fileURLToPath} from 'node:url';
import {createServer} from 'vite';
import react from '@vitejs/plugin-react';
import {createElement} from 'react';
import {renderToStaticMarkup} from 'react-dom/server';

// Rendered HTML checks only: no browser, network requests or financial actions.
let server, rider, consoles, admin, ui, language, Map;
before(async () => {
  server = await createServer({root:fileURLToPath(new URL('../',import.meta.url)),configFile:false,plugins:[react()],server:{middlewareMode:true,hmr:false},appType:'custom'});
  rider = await server.ssrLoadModule('/src/Rider.tsx');
  consoles = await server.ssrLoadModule('/src/Consoles.tsx');
  admin = await server.ssrLoadModule('/src/Admin.tsx');
  ui = await server.ssrLoadModule('/src/components/ui.tsx');
  language = await server.ssrLoadModule('/src/i18n/index.ts');
  ({Map} = await server.ssrLoadModule('/src/Map.tsx'));
});
after(async () => { await server?.close(); });
const render=(Component,props={})=>renderToStaticMarkup(createElement(Component,props));
const noop=async()=>{};
const point={display_name:'Fixture pickup',latitude:0,longitude:0,meeting_point:'Fixture gate'};
const trip={id:'fixture-trip',state:'assigned',pickup:point,destination:{...point,display_name:'Fixture destination'},pickup_note:'Meet at the gate',pricing:{version:1,currency:'MWK',fare_minor:500000,fee_minor:50000,driver_minor:450000},payment_method:'cash',payment_status:'cash_due_to_driver',fee_state:'reserved',pickup_pin:'4821',route:{distance_m:6000,duration_s:720,label:'Demo route'},driver:{first_name:'Fixture driver',vehicle:'Fixture vehicle',plate:'FIXTURE'},created_at:'2026-10-13T08:00:00Z'};
const driver={driver:{id:1,status:'approved',online:true,vehicle:'Fixture vehicle',plate:'FIXTURE',vehicle_approved:true,trial_credit_minor:450000},available_credit_minor:400000,today_earnings_minor:450000,completed_trips:1,offers:[],ledger:[],review_cases:[]};

test('booking retains labelled searchable fields and optional note in both languages',()=>{
  for(const code of ['en','ny']){
    language.setLanguage(code);
    const html=render(rider.Booking,{landmarks:[{...point,id:1,name:'Fixture pickup',aliases:['Gate'],verified:true}],busy:false,onQuote:noop});
    assert.equal((html.match(/<h1>/g)||[]).length,1);
    assert.match(html,/id="pickup" role="combobox"/);
    assert.match(html,/id="destination" role="combobox"/);
    assert.match(html,/<details class="pickup-note">/);
    assert.match(html,/id="note"/);
    assert.ok(html.includes(language.tr('See your fare')));
    assert.ok(html.includes(language.tr('Where are you going?')));
  }
});
test('busy action is disabled and exposes its progress accessibly',()=>{
  const html=render(ui.Button,{loading:true,children:'Request ride'});
  assert.match(html,/disabled=""/);assert.match(html,/aria-busy="true"/);
  assert.match(html,/aria-hidden="true"/);assert.match(html,/Request ride/);
});
test('illustrative map retains real zoom/reset buttons and honest accessible label',()=>{
  language.setLanguage('en');const html=render(Map);
  for(const label of ['Zoom in','Zoom out','Reset map view'])assert.ok(html.includes(`aria-label="${label}"`));
  assert.ok(html.includes('Illustrative Mzuzu map, not live navigation'));
});
test('rider still sees pickup PIN before start and never after start',()=>{
  language.setLanguage('en');const props={trip,busy:false,cancel:noop,help:noop};
  assert.ok(render(rider.TripPanel,props).includes('4821'));
  const started=render(rider.TripPanel,{...props,trip:{...trip,state:'in_progress'}});
  assert.ok(!started.includes('4821'));assert.ok(!started.includes('Cancel ride'));assert.ok(started.includes('Get help'));
});
test('active driver task precedes credit tools in document order and retains fare/action',()=>{
  language.setLanguage('en');const html=render(consoles.DriverConsole,{data:driver,trip,busy:false,run:noop,reload:noop});
  assert.ok(html.indexOf('class="driver-task"')<html.indexOf('class="driver-sidebar"'));
  assert.ok(html.includes('MWK 5,000'));assert.ok(html.includes('Mark arrival'));assert.ok(!html.includes('4821'));
});
test('pending driver sees verification as the task and cannot go online',()=>{
  language.setLanguage('en');const html=render(consoles.DriverConsole,{data:{...driver,driver:{...driver.driver,status:'pending',vehicle_approved:false,online:false}},busy:false,run:noop,reload:noop});
  assert.ok(html.includes('Driver verification application'));assert.ok(!html.includes('No open requests.'));
  assert.match(html,/<button[^>]*disabled=""[^>]*>Go online<\/button>/);
});
test('operations uses full-width review sections and retains audit reason and navigation',()=>{
  language.setLanguage('en');const html=render(admin.AdminConsole,{data:{drivers:[{id:1,first_name:'Fixture driver',vehicle:'Fixture vehicle',plate:'FIXTURE',status:'pending'}],settings:{name:'Fixture area',opens:'06:00',closes:'20:00',enabled:true,bounds:{}},pricing:[],ledger:[],events:[],incidents:[],audit:[]},busy:false,run:noop,reload:noop});
  assert.match(html,/<article class="driver-review-item"/);assert.ok(!html.includes('<table'));
  assert.ok(html.includes('Reason for your next action'));assert.ok(html.includes('Review application and documents'));assert.ok(html.includes('aria-current="page"'));
});
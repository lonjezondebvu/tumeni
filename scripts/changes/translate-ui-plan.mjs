// Read-only TypeScript AST analysis. PowerShell applies the guarded source edits.
import ts from '../../frontend/node_modules/typescript/lib/typescript.js';
import fs from 'node:fs';
import crypto from 'node:crypto';
const catalogText=fs.readFileSync('frontend/src/i18n/locales/en.json','utf8');
const catalog=JSON.parse(catalogText).messages;
const files=['main.tsx','Rider.tsx','Consoles.tsx','Admin.tsx','Map.tsx','components/ui.tsx'];
const plan=[];
for(const file of files){
 const path='frontend/src/'+file;const text=fs.readFileSync(path,'utf8');
 const source=ts.createSourceFile(file,text,ts.ScriptTarget.Latest,true,ts.ScriptKind.TSX);const edits=[];
 const known=value=>Object.hasOwn(catalog,value.trim());
 const call=value=>'tr('+JSON.stringify(value)+')';
 function replace(node,value){edits.push({start:node.getStart(source),end:node.end,expected:text.slice(node.getStart(source),node.end),value});}
 function storedMessage(node){for(let p=node.parent;p;p=p.parent){if(ts.isCallExpression(p)&&['setNotice','setGeoMessage','setError'].includes(p.expression.getText(source)))return true;}return false;}
 function visit(node){
  if(ts.isCallExpression(node) && node.expression.getText(source)==='tr')return;
  if(ts.isJsxText(node)){
   const key=node.text.trim().replace(/\s+/g,' ');
   if(known(key)){const leading=/^[ \t]/.test(node.text)?' ':'';const trailing=/[ \t]$/.test(node.text)?' ':'';replace(node,'{'+call(leading+key+trailing)+'}');}
  }else if(ts.isStringLiteral(node)&&known(node.text)&&!storedMessage(node)){
   const replacement=call(node.text);
   if(ts.isJsxAttribute(node.parent))replace(node,'{'+replacement+'}');
   else if(!ts.isPropertyAssignment(node.parent)||node.parent.name!==node)replace(node,replacement);
  }else if(ts.isTemplateExpression(node)&&!storedMessage(node)){
   const key=node.head.text+node.templateSpans.map((s,i)=>`{${i}}`+s.literal.text).join('');
   if(known(key)){replace(node,'tr('+JSON.stringify(key)+',['+node.templateSpans.map(s=>s.expression.getText(source)).join(',')+'])');return;}
  }
  ts.forEachChild(node,visit);
 }
 visit(source);
 const importPath=file==='components/ui.tsx'?'../i18n':'./i18n';
 if(!text.includes('// translated-ui-v1'))edits.push({start:0,end:0,expected:'',value:`// translated-ui-v1\nimport {tr,statusLabel} from '${importPath}';\n`});
 plan.push({path,sha256:crypto.createHash('sha256').update(text).digest('hex'),edits});
}
console.log(JSON.stringify(plan));
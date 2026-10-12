import ts from '../../frontend/node_modules/typescript/lib/typescript.js';
import fs from 'node:fs';
const files=['main.tsx','Rider.tsx','Consoles.tsx','Admin.tsx','Map.tsx','components/ui.tsx'];
const messages=new Set();
for(const file of files){const text=fs.readFileSync('frontend/src/'+file,'utf8');const source=ts.createSourceFile(file,text,ts.ScriptTarget.Latest,true,ts.ScriptKind.TSX);function visit(node){if(ts.isJsxText(node)){const v=node.text.trim().replace(/\s+/g,' ');if(/[a-zA-Z]/.test(v))messages.add(v);}if(ts.isStringLiteral(node) && /[A-Z]|\s/.test(node.text) && !node.text.includes('/') && node.text.length>3)messages.add(node.text);if(ts.isTemplateExpression(node) && /\s/.test(node.head.text)){messages.add(node.head.text+node.templateSpans.map((s,i)=>`{${i}}`+s.literal.text).join(''));}ts.forEachChild(node,visit);}visit(source);}
console.log(JSON.stringify([...messages].sort(),null,2));
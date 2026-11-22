(() => {
 'use strict';
 let previous = {}, sequence = 0, latest = null;
 const locale = () => document.documentElement.lang || 'fa';
 const skip = (node) => node.parentElement?.closest('script,style,pre,code,textarea,select,[data-no-translate]');
 function apply(payload) {
  const base=payload.resources?.fa?.translation||{}, selected=payload.resources?.[payload.locale]?.translation||{};
  const current=Object.fromEntries(Object.keys(base).map((key)=>[key,selected[key]||base[key]||key]));
  const lookup=new Map();
  for(const [key,value] of Object.entries(current)) {lookup.set(key,value);lookup.set(base[key],value);if(previous[key])lookup.set(previous[key],value);}
  const translate=(value)=>{const trimmed=value.trim();const replacement=lookup.get(trimmed);return replacement===undefined?value:value.replace(trimmed,replacement);};
  const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);
  const nodes=[];while(walker.nextNode())nodes.push(walker.currentNode);
  for(const node of nodes)if(!skip(node))node.nodeValue=translate(node.nodeValue);
  for(const node of document.querySelectorAll('[title],[placeholder],[aria-label],[alt]')) {
   if(node.closest('[data-no-translate]'))continue;
   for(const attribute of ['title','placeholder','aria-label','alt'])if(node.hasAttribute(attribute))node.setAttribute(attribute,translate(node.getAttribute(attribute)));
  }
  const picker=document.getElementById('site-language');
  if(picker){picker.replaceChildren(...payload.languages.map((language)=>{const option=document.createElement('option');option.value=language.code;option.textContent=language.native_name+' — '+language.code.toUpperCase();option.selected=language.code===payload.locale;return option;}));}
  document.title=translate(document.title);
  for(const node of document.querySelectorAll('meta[name="description"],meta[property="og:title"],meta[property="og:description"],meta[name="twitter:title"],meta[name="twitter:description"]'))if(node.content)node.content=translate(node.content);
  document.documentElement.lang=payload.locale;
  document.documentElement.dir=payload.languages.find((language)=>language.code===payload.locale)?.direction||'rtl';
  document.body.dir=document.documentElement.dir;
  const rtlStyles=document.getElementById('site-rtl-styles');if(rtlStyles)rtlStyles.media=document.documentElement.dir==='rtl'?'all':'not all';
  previous=current;
 }
 async function load() {
  const request=++sequence;
  const response=await fetch('/api/localization/bootstrap?app=website&locale='+encodeURIComponent(locale()),{headers:{Accept:'application/json'}});
  if(!response.ok)throw new Error('Localization unavailable');
  const payload=await response.json();
  if(request!==sequence)return payload;
  if(!latest || payload.revision!==latest.revision || payload.locale!==latest.locale)apply(payload);
  latest=payload;return payload;
 }
 async function start() {
  try{await load();}catch{}
  if(!window.LoopRealtime)return;
  const client=window.LoopRealtime.createRealtimeClient({config:async()=>{const payload=await load();return payload.realtime;},onEvent:(event)=>{if(event.reconnect || event.topics?.includes('localization'))load().catch(()=>{});}});
  client.start();
  document.addEventListener('visibilitychange',()=>{if(document.hidden)client.stop();else client.start();});
  window.addEventListener('pagehide',()=>client.stop());
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();

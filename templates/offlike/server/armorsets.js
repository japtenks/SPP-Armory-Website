// ---------- Armor Sets Tooltip Scripts ----------


(function(){
  // Create tooltip div once
  const tip = document.createElement('div');
  tip.className = 'talent-tt';
  tip.style.display = 'none';
  document.body.appendChild(tip);

  let anchor = null;

  // Position tooltip above element
  function place(el){
    const pad = 8;
    const r = el.getBoundingClientRect();
    tip.style.visibility = 'hidden';
    tip.style.display = 'block';
    const t = tip.getBoundingClientRect();
    let left = Math.max(6, Math.min(r.left + (r.width - t.width)/2, innerWidth - t.width - 6));
    let top  = Math.max(6, r.top - t.height - pad);
    tip.style.left = left+'px';
    tip.style.top  = top+'px';
    tip.style.visibility = 'visible';
  }

  // Show tooltip (decode HTML safely)
  function show(el){
    anchor = el;
    const raw = el.getAttribute('data-tip-html') || '';
    const ta  = document.createElement('textarea');
    ta.innerHTML = raw;
    tip.innerHTML = ta.value;
    place(el);
  }

  // Hide tooltip
  function hide(){ tip.style.display = 'none'; anchor=null; }

  // Re-position on scroll/resize
  function nudge(){ if(anchor && tip.style.display!=='none') place(anchor); }

  // Mouse events for both set tips + item tips
  document.addEventListener('mouseover', e=>{
    const el = e.target.closest('.js-set-tip, .js-item-tip');
    if(el) show(el);
  });
  document.addEventListener('mouseout', e=>{
    const el = e.target.closest('.js-set-tip, .js-item-tip');
    if(el && !(e.relatedTarget && el.contains(e.relatedTarget))) hide();
  });

  // Keep tooltip stuck to screen when user scrolls or resizes
  addEventListener('scroll', nudge, {passive:true});
  addEventListener('resize', nudge);
})();


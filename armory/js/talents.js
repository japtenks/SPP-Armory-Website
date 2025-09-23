
(function(){
  const tt = document.createElement('div');
  tt.className = 'talent-tt';
  tt.style.display = 'none';
  document.body.appendChild(tt);

  let showTimer = null;
  let anchorEl = null;

  function render(el){
    const title = el.getAttribute('data-tt-title') || '';
    const desc  = el.getAttribute('data-tt-desc')  || '';
    tt.innerHTML = '<h5>'+title+'</h5><p>'+desc+'</p>';
  }

  function placeToTopRight(el){
    const pad = 8;
    const vw = innerWidth;

    const rEl = el.getBoundingClientRect();
    const rTT = tt.getBoundingClientRect();

    let left = rEl.right + pad;
    let top  = rEl.top - rTT.height - pad;

    if (left + rTT.width > vw - 6) left = vw - rTT.width - 6;
    if (left < 6) left = 6;
    if (top < 6) top = rEl.bottom + pad;

    tt.style.left = left + 'px';
    tt.style.top  = top + 'px';
  }

  function show(el){
    anchorEl = el;
    render(el);
    tt.style.display = 'block';
    placeToTopRight(el);
  }

  function hide(){
    clearTimeout(showTimer);
    tt.style.display = 'none';
    anchorEl = null;
  }

  document.addEventListener('mouseover', function(e){
    const el = e.target.closest('.talent-cell[data-tt-title]');
    if (!el) return;
    clearTimeout(showTimer);
    showTimer = setTimeout(function(){ show(el); }, 60);
  });

  document.addEventListener('mouseout', function(e){
    const el = e.target.closest('.talent-cell[data-tt-title]');
    if (!el) return;
    if (!e.relatedTarget || !el.contains(e.relatedTarget)) hide();
  });

  document.addEventListener('scroll', function(){
    if (tt.style.display !== 'none' && anchorEl) placeToTopRight(anchorEl);
  }, {passive:true});

  window.addEventListener('resize', function(){
    if (tt.style.display !== 'none' && anchorEl) placeToTopRight(anchorEl);
  });
})();
// talents.js — calculator glue

(function () {
  const treeEls   = Array.from(document.querySelectorAll('.talent-tree'));
  const cellEls   = Array.from(document.querySelectorAll('.talent-cell'));
  const leftEl    = document.getElementById('tcLeft');
  const reqEl     = document.getElementById('tcReqLvl');
  const splitsEl  = document.getElementById('tcSplits');

  // Points pool (TBC)
  const MAX_POINTS = 61;
  let spent = 0;

  // Track per-tree splits
  const splitByTree = new Map(); // treeEl -> points
  treeEls.forEach(t => splitByTree.set(t, 0));

  function fmtReqLevel(spentPoints) {
    // first point at level 10 => required level = 9 + spent
    // with 0 spent: 10
    return Math.max(10, 9 + spentPoints);
  }

  function updateHud() {
    leftEl.textContent = String(MAX_POINTS - spent);
    reqEl.textContent  = String(fmtReqLevel(spent));
    // splits order = DOM order of trees
    const arr = treeEls.map(t => splitByTree.get(t) || 0);
    splitsEl.textContent = arr.join(' / ');
  }

  function setCellRank(cell, rank) {
    const max  = parseInt(cell.dataset.max || '0', 10);
    const cl   = cell.classList;
    const badge= cell.querySelector('.talent-rank');
    const cur  = Math.max(0, Math.min(rank, max));
    cell.dataset.current = String(cur);
    badge.textContent = `${cur}/${max}`;
    cl.toggle('learned', cur > 0 && cur < max);
    cl.toggle('maxed',   cur >= max && max > 0);
    cl.toggle('empty',   cur === 0);
  }

function addPoint(cell) {
  if (spent >= MAX_POINTS) return;
  const cur = +cell.dataset.current || 0;
  const max = +cell.dataset.max || 0;
  if (cur >= max) return;
  setCellRank(cell, cur + 1);
  recomputeFromDOM();
}

function removePoint(cell) {
  const cur = +cell.dataset.current || 0;
  if (cur <= 0) return;
  setCellRank(cell, cur - 1);
  recomputeFromDOM();
}


  // Mouse input: left add, right remove
  cellEls.forEach(cell => {
    cell.addEventListener('click', e => {
      e.preventDefault();
      addPoint(cell);
    });
    cell.addEventListener('contextmenu', e => {
      e.preventDefault();
      removePoint(cell);
    });
  });

  // Initialize badge classes correctly (all zeros)
  cellEls.forEach(c => setCellRank(c, parseInt(c.dataset.current || '0', 10)));
  updateHud();
})();

function recomputeFromDOM() {
  let total = 0;

  treeEls.forEach(tree => {
    const spentHere = Array.from(tree.querySelectorAll('.talent-cell'))
      .reduce((acc, c) => acc + (+c.dataset.current || 0), 0);
    splitByTree.set(tree, spentHere);
    total += spentHere;

    const numEl = tree.querySelector('.talent-head .num');
    if (numEl) numEl.textContent = String(spentHere);
  });

  spent = total;
  updateHud();
}

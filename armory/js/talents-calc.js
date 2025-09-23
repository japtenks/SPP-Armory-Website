/* talents-calc.js — tooltip + calculator + robust boot
   -------------------------------------------------------------- */

/* ================== TOOLTIP ================== */
(function () {
  const tt = document.createElement('div');
  tt.className = 'talent-tt';
  tt.style.display = 'none';
  document.body.appendChild(tt);

  let showTimer = null;
  let anchorEl  = null;

  const rankDesc = (el, r) =>
    el.getAttribute('data-tt-desc' + r) ||
    el.getAttribute('data-tt-desc') || '';

  function buildTooltipHTML(el) {
    const title = el.getAttribute('data-tt-title') || '';
    const max   = parseInt(el.dataset.max || '0', 10);
    const cur   = parseInt(el.dataset.current || '0', 10);
    const showRank = cur > 0 ? Math.min(cur, max) : 1;

    const acts = (window.__talentActions
      ? window.__talentActions(el)
      : { canLearn:false, canUnlearn:false });

    let html = `<h5>${title}</h5>`;
    html += `<div class="tt-subtle">Talent</div>`;
    if (cur > 0) html += `<div class="tt-subtle">Rank ${cur}/${max}</div>`;
    html += `<p>${rankDesc(el, showRank)}</p>`;

    if (cur > 0 && cur < max) {
      html += `<div class="tt-nexthead">Next rank:</div>`;
      html += `<p>${rankDesc(el, cur + 1)}</p>`;
    }

    if (acts.canLearn)   html += `<div class="tt-action tt-learn">Click to learn</div>`;
    if (acts.canUnlearn) html += `<div class="tt-action tt-unlearn">Right-click to unlearn</div>`;
    return html;
  }

  function place(el){
    const pad = 8, vw = innerWidth, vh = innerHeight;
    const rEl = el.getBoundingClientRect();
    tt.style.visibility = 'hidden';
    tt.style.display = 'block';
    const rTT = tt.getBoundingClientRect();

    let left = rEl.right + pad;
    let top  = rEl.top - rTT.height - pad;
    if (left + rTT.width > vw - 6) left = vw - rTT.width - 6;
    if (left < 6) left = 6;
    if (top < 6) top = rEl.bottom + pad;
    if (top + rTT.height > vh - 6) top = Math.max(6, vh - rTT.height - 6);

    tt.style.left = left + 'px';
    tt.style.top  = top  + 'px';
    tt.style.visibility = 'visible';
  }

  const show  = (el) => { anchorEl = el; tt.innerHTML = buildTooltipHTML(el); tt.style.display='block'; place(el); };
  const hide  = ()   => { clearTimeout(showTimer); tt.style.display='none'; anchorEl=null; };
  const nudge = ()   => { if (tt.style.display!=='none' && anchorEl) place(anchorEl); };

  document.addEventListener('mouseover', e => {
    const el = e.target.closest('.talent-cell[data-tt-title]');
    if (!el) return;
    clearTimeout(showTimer);
    showTimer = setTimeout(()=>show(el), 70);
  });
  document.addEventListener('mouseout', e => {
    const el = e.target.closest('.talent-cell[data-tt-title]');
    if (!el) return;
    if (e.relatedTarget && el.contains(e.relatedTarget)) return;
    hide();
  });
  document.addEventListener('scroll', nudge, { passive:true });
  addEventListener('resize', nudge);

  window.__talentTooltipRefresh = function(el){
    if (anchorEl === el && tt.style.display !== 'none') {
      tt.innerHTML = buildTooltipHTML(el);
      place(el);
    }
  };
})();

/* ================== CALCULATOR ================== */
(() => {
  const treeEls  = Array.from(document.querySelectorAll('.talent-tree'));
  const cellEls  = Array.from(document.querySelectorAll('.talent-cell[data-talent-id]'));
  const leftEl   = document.getElementById('tcLeft');
  const reqEl    = document.getElementById('tcReqLvl');
  const splitsEl = document.getElementById('tcSplits');

  const MAX_POINTS = 61;
  let spent = 0;

  const splitByTree = new Map(); // treeEl -> points
  treeEls.forEach(t => splitByTree.set(t, 0));

  // index + edges
  const treeIndex = new Map(); // treeEl -> { byId, edges }
  treeEls.forEach(tree => {
    const cells = Array.from(tree.querySelectorAll('.talent-cell[data-talent-id]'));
    const byId  = new Map();
    const edges = new Map();
    cells.forEach(c => byId.set(+c.dataset.talentId, c));
    cells.forEach(c => {
      const pid = +c.dataset.prereqId || 0;
      if (pid > 0 && byId.has(pid)) {
        if (!edges.has(pid)) edges.set(pid, new Set());
        edges.get(pid).add(c);
      }
    });
    treeIndex.set(tree, { byId, edges });
  });

  // ---------- helpers ----------
  const fmtReqLevel     = (p) => Math.max(10, 9 + p);
  const current         = (cell) => parseInt(cell.dataset.current || '0', 10);
  const maxRank         = (cell) => parseInt(cell.dataset.max     || '0', 10);
  const rowIndex        = (cell) => parseInt(cell.dataset.row    || '0', 10);
  const rowRequirement  = (cell) => rowIndex(cell) * 5;
  const requiredParentRank = (cell) => {
    const raw = parseInt(cell.dataset.prereqRank || '0', 10);
    theHas = parseInt(cell.dataset.prereqId || '0', 10) > 0;
    return theHas ? (raw + 1) : 0;
  };
  const pointsInTree    = (tree) => splitByTree.get(tree) || 0;

  // Expose minimal info for tooltips
  window.__tc_info = (cell) => {
    const tree = cell.closest('.talent-tree');
    const head = tree.querySelector('.talent-head-title');
    const treeName = head ? head.textContent.trim() : 'this tree';

    const unmet = [];
    const total = (splitByTree.get(tree) || 0);

    const needPts = rowRequirement(cell);
    if (total < needPts) unmet.push(`${needPts} points in ${treeName}`);

    const reqId = parseInt(cell.dataset.prereqId || '0', 10);
    const need  = requiredParentRank(cell);
    if (reqId && need) {
      const { byId } = treeIndex.get(tree);
      const parent = byId.get(reqId);
      const have   = parent ? current(parent) : 0;
      if (have < need) {
        const pName = parent?.getAttribute('data-tt-title') || 'the prerequisite talent';
        unmet.push(`${pName} (${have}/${need})`);
      }
    }

    const eligible = !cell.classList.contains('locked') &&
                     current(cell) < parseInt(cell.dataset.max || '0', 10) &&
                     (MAX_POINTS - spent) > 0;

    return { treeName, unmet, eligible };
  };

  // ----- reset helpers -----
  function resetTree(tree){
    const cells = tree.querySelectorAll('.talent-cell[data-talent-id]');
    cells.forEach(c => setCellRank(c, 0));
    // recompute totals, relock, redraw lines
    recalcFromDOM();
    refreshShareUI();
  }



  function resetAll(){
    // simplest + safest: reuse the decoder to zero everything
    decodeWowheadLike('0-0-0');
    refreshShareUI();
  }

  // header reset-all icon
document.getElementById('tcResetAllBtn')?.addEventListener('click', (e) => {
  e.preventDefault();
  resetAll();               // uses the resetAll() you already defined above
});
  // header button
  document.getElementById('tcResetAll')?.addEventListener('click', () => {
    resetAll();
  });

  // per-tree button (event delegation handles all three)
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.tree-reset');
    if (!btn) return;
    const tree = btn.closest('.talent-tree');
    if (tree) resetTree(tree);
  });

  function updateHud(){
    if (leftEl) leftEl.textContent = String(MAX_POINTS - spent);
    if (reqEl)  reqEl.textContent  = String(fmtReqLevel(spent));
    if (splitsEl){
      const arr = treeEls.map(t => splitByTree.get(t) || 0);
      splitsEl.textContent = arr.join(' / ');
    }
  }

  function updateTreeHeader(tree){
    const numEl = tree.querySelector('.talent-head-pts .num');
    if (numEl) numEl.textContent = String(pointsInTree(tree));
  }
  function updateAllTreeHeaders(){ treeEls.forEach(updateTreeHeader); }

  function setCellRank(cell, rank){
    const max   = parseInt(cell.dataset.max || '0', 10);
    const badge = cell.querySelector('.talent-rank');
    const cur   = Math.max(0, Math.min(rank, max));
    cell.dataset.current = String(cur);
    if (badge) badge.textContent = `${cur}/${max}`;
    const cl = cell.classList;
    cl.toggle('learned', cur > 0 && cur < max);
    cl.toggle('maxed',   cur >= max && max > 0);
    cl.toggle('empty',   cur === 0);
  }

  // strict row-gate: count only points ABOVE the target row
  function pointsAboveRow(tree, row, clicked = null, clickedNextRank = null) {
    let sum = 0;
    const cells = tree.querySelectorAll('.talent-cell[data-talent-id]');
    cells.forEach(c => {
      const r = parseInt(c.dataset.row || '0', 10);
      if (r >= row) return;
      if (clicked && c === clicked && clickedNextRank != null) {
        sum += Math.max(0, clickedNextRank);
      } else {
        sum += current(c);
      }
    });
    return sum;
  }
  function meetsRowGateStrict(cell) {
    const tree = cell.closest('.talent-tree');
    return pointsAboveRow(tree, rowIndex(cell)) >= rowRequirement(cell);
  }
  function meetsPrereq(cell){
    const reqId = parseInt(cell.dataset.prereqId || '0', 10);
    const need  = requiredParentRank(cell);
    if (!reqId || !need) return true;
    const { byId } = treeIndex.get(cell.closest('.talent-tree'));
    const parent = byId.get(reqId);
    return parent ? current(parent) >= need : true;
  }
  function isUnlocked(cell){ return meetsRowGateStrict(cell) && meetsPrereq(cell); }
  function lockState(cell){ const locked = !isUnlocked(cell); cell.classList.toggle('locked', locked); return locked; }

  function markEligibility(tree){
    const left = MAX_POINTS - spent;
    tree.querySelectorAll('.talent-cell[data-talent-id]').forEach(cell => {
      const canInvest = !lockState(cell) && current(cell) < maxRank(cell) && left > 0;
      cell.classList.toggle('eligible', canInvest);
    });
  }

  function enforceValidityForTree(tree){
    const { byId, edges } = treeIndex.get(tree);
    const queue = [];
    byId.forEach(parent => {
      const kids = edges.get(+parent.dataset.talentId);
      if (kids && kids.size) queue.push(...kids);
    });
    while (queue.length){
      const cell = queue.shift();
      if (!isUnlocked(cell) && current(cell) > 0){
        const drop = current(cell);
        setCellRank(cell, 0);
        spent = Math.max(0, spent - drop);
        splitByTree.set(tree, Math.max(0, (splitByTree.get(tree) || 0) - drop));
        const kids = edges.get(+cell.dataset.talentId);
        if (kids) queue.push(...kids);
      }
    }
    updateTreeHeader(tree);
  }

  function refreshTreeState(tree){
    tree.querySelectorAll('.talent-cell[data-talent-id]').forEach(lockState);
    drawReqArrows(tree);
    markEligibility(tree);
    updateTreeHeader(tree);
  }

  function flashDeny(el){ el.classList.add('deny'); setTimeout(()=>el.classList.remove('deny'), 220); }

  function wouldViolateAnyRuleOnRemove(cell) {
    const tree = cell.closest('.talent-tree');
    const { byId } = treeIndex.get(tree);
    const clickedNext = current(cell) - 1;

    const nodes = tree.querySelectorAll('.talent-cell[data-talent-id]');
    for (const n of nodes) {
      const cur = current(n);
      if (cur <= 0) continue;

      const need = rowRequirement(n);
      const have = pointsAboveRow(tree, rowIndex(n), cell, clickedNext);
      if (have < need) return true;

      const reqId = parseInt(n.dataset.prereqId || '0', 10);
      const reqRank = requiredParentRank(n);
      if (reqId && reqRank) {
        const parent = byId.get(reqId);
        if (parent) {
          const parentRankSim = (parent === cell) ? clickedNext : current(parent);
          if (parentRankSim < reqRank) return true;
        }
      }
    }
    return false;
  }

  function removePoint(cell) {
    const cur = current(cell);
    if (cur <= 0) return;

    if (wouldViolateAnyRuleOnRemove(cell)) {
      flashDeny(cell);
      return;
    }

    setCellRank(cell, cur - 1);
    spent = Math.max(0, spent - 1);

    const tree = cell.closest('.talent-tree');
    splitByTree.set(tree, Math.max(0, (splitByTree.get(tree) || 0) - 1));

    enforceValidityForTree(tree);
    refreshTreeState(tree);
    updateHud();
    window.__talentTooltipRefresh?.(cell);
  }

  function canLearn(cell) {
    if (spent >= MAX_POINTS) return false;
    if (current(cell) >= maxRank(cell)) return false;
    return isUnlocked(cell);
  }

  function canUnlearn(cell) {
    if (current(cell) <= 0) return false;
    return !wouldViolateAnyRuleOnRemove(cell);
  }

  window.__talentActions = (el) => ({
    canLearn:   canLearn(el),
    canUnlearn: canUnlearn(el)
  });

  function addPoint(cell){
    if (spent >= MAX_POINTS) return;
    if (lockState(cell)) return;
    const cur = current(cell), max = maxRank(cell);
    if (cur >= max) return;

    setCellRank(cell, cur + 1);
    spent += 1;

    const tree = cell.closest('.talent-tree');
    splitByTree.set(tree, (splitByTree.get(tree) || 0) + 1);

    enforceValidityForTree(tree);
    refreshTreeState(tree);
    updateHud();
    window.__talentTooltipRefresh?.(cell);
  }

  // input
  cellEls.forEach(cell => {
    cell.addEventListener('click',       e => { e.preventDefault(); addPoint(cell); });
    cell.addEventListener('contextmenu', e => { e.preventDefault(); removePoint(cell); });
  });

  // ----- prerequisite connectors -----
  function clearReqLayer(tree){ const old = tree.querySelector('.req-layer'); if (old) old.remove(); }

  function drawReqArrows(tree){
    clearReqLayer(tree);
    const layer = document.createElement('div');
    layer.className = 'req-layer';
    tree.appendChild(layer);

    const { byId } = treeIndex.get(tree);
    const children = Array.from(tree.querySelectorAll('.talent-cell[data-prereq-id]'));
    const treeRect = tree.getBoundingClientRect();

    children.forEach(child => {
      const reqId = parseInt(child.dataset.prereqId || '0', 10);
      if (!reqId) return;
      const parent = byId.get(reqId);
      if (!parent) return;

      const need = requiredParentRank(child);
      const met  = current(parent) >= need;

      const pr = parent.getBoundingClientRect();
      const cr = child.getBoundingClientRect();

      const x1 = pr.left + pr.width/2 - treeRect.left;
      const y1 = pr.top  + pr.height/2 - treeRect.top;
      const x2 = cr.left + cr.width/2 - treeRect.left;
      const y2 = cr.top  + cr.height/2 - treeRect.top;

      const v = document.createElement('div');
      v.className = 'req-line' + (met ? ' met' : '');
      v.style.left = Math.round(x1) + 'px';
      v.style.top  = Math.round(Math.min(y1, y2)) + 'px';
      v.style.width  = '3px';
      v.style.height = Math.round(Math.abs(y2 - y1)) + 'px';
      layer.appendChild(v);

      const h = document.createElement('div');
      h.className = 'req-line' + (met ? ' met' : '');
      h.style.left   = Math.round(Math.min(x1, x2)) + 'px';
      h.style.top    = Math.round(y2) + 'px';
      h.style.width  = Math.round(Math.abs(x2 - x1)) + 'px';
      h.style.height = '3px';
      layer.appendChild(h);
    });
  }

  // ----- recalc / init -----
  function recalcFromDOM(){
    spent = 0;
    treeEls.forEach(t => splitByTree.set(t, 0));
    treeEls.forEach(tree => {
      tree.querySelectorAll('.talent-cell[data-talent-id]').forEach(c => {
        const n = current(c);
        spent += n;
        splitByTree.set(tree, (splitByTree.get(tree) || 0) + n);
      });
      enforceValidityForTree(tree);
      refreshTreeState(tree);
    });
    updateHud();
    updateAllTreeHeaders();
  }

  window.tcRecalc = recalcFromDOM;

  // first paint
  cellEls.forEach(c => setCellRank(c, current(c)));
  recalcFromDOM();

  addEventListener('resize', () => treeEls.forEach(drawReqArrows), { passive:true });
})();

/* ================== SHARE / ENCODE-DECODE ================== */
function __sortCells(cells){
  return cells.slice().sort((a,b)=>{
    const ar=+a.dataset.row||0, ac=+a.dataset.col||0;
    const br=+b.dataset.row||0, bc=+b.dataset.col||0;
    return ar-br || ac-bc;
  });
}

// Optional: set once somewhere (or read from PHP)
window.tcBotName = window.tcBotName || 'Botname';

// Encode to trees-only: "<t1>-<t2>-<t3>"
function encodeWowheadLike() {
  const trees = Array.from(document.querySelectorAll('.talent-tree'));
  const sortCells = (cells) =>
    cells.slice().sort((a,b)=>
      (+a.dataset.row - +b.dataset.row) || (+a.dataset.col - +b.dataset.col)
    );
  const encodeTree = (treeEl) => {
    const cells = sortCells(Array.from(treeEl.querySelectorAll('.talent-cell[data-talent-id]')));
    let s = '';
    for (const c of cells){
      const cur = Math.max(0, Math.min(5, +(c.dataset.current||0)));
      s += cur;
    }
    s = s.replace(/0+$/, '');
    return s.length ? s : '0';
  };
  return trees.map(encodeTree).join('-');
}

// Accepts "t1-t2-t3" OR "class-t1-t2-t3"
function decodeWowheadLike(token){
  const parts = String(token||'').trim().split('-').filter(Boolean);
  if (parts.length < 3) return false;
  const treesOnly = (parts.length === 3) ? parts : parts.slice(1,4);

  const trees = Array.from(document.querySelectorAll('.talent-tree'));
  trees.forEach((tree, i)=>{
    const seq = treesOnly[i] || '0';
    const cells = __sortCells(Array.from(tree.querySelectorAll('.talent-cell[data-talent-id]')));
    for (let j=0; j<cells.length; j++){
      const d   = (j < seq.length) ? (seq.charCodeAt(j) - 48) : 0; // '0'..'5'
      const max = +(cells[j].dataset.max || 5);
      const val = Math.max(0, Math.min(max, isNaN(d) ? 0 : d));
      cells[j].dataset.current = String(val);
      const badge = cells[j].querySelector('.talent-rank');
      if (badge) badge.textContent = `${val}/${max}`;
      cells[j].classList.toggle('learned', val>0 && val<max);
      cells[j].classList.toggle('maxed',   val>=max && max>0);
      cells[j].classList.toggle('empty',   val===0);
    }
  });

  if (window.tcRecalc) window.tcRecalc();
  return true;
}

// Pull a token from raw text or a URL (3 or 4 dash-separated blocks)
function extractBuildToken(input){
  const s = String(input||'').trim();
  const PATN = /(\d+(?:-\d+){2,3})/;
  try {
    const u = new URL(s);
    const hash = u.hash.replace(/^#/, '');
    const hm = hash.match(PATN); if (hm) return hm[1];
    const pm = u.pathname.match(PATN); if (pm) return pm[1];
    const qb = u.searchParams.get('build');
    if (qb && PATN.test(qb)) return qb.match(PATN)[1];
  } catch {}
  const m = s.match(PATN);
  return m ? m[1] : '';
}

/* ============== UI SYNC (token box, whisper, hash) ============== */
function refreshShareUI(){
  const token = encodeWowheadLike();

  // Address bar (no history spam)
  const url = new URL(location.href);
  url.search = '';
  url.hash   = token;
  history.replaceState(null, '', url);

  // Token box
  const box = document.getElementById('tcTokenBox');
  if (box) box.value = token;

  // Whisper line (supports either id)
// use the id you're rendering in PHP
const whisper = document.getElementById('tcWhisperText');  // not 'tcWhisper'
if (whisper) whisper.textContent = `/w ${window.tcBotName || 'Botname'} talents ${token}`;


  return token; // <-- needed by copy handler
}

/* ================== ROBUST BOOT ==================
   - If hash or ?build exists, apply it (3- or 4-part).
   - Otherwise set default "#0-0-0".
   - Normalize to trees-only in URL and token box.
   - Works on first load, BFCache, and manual hash edits.
*/
(function bootOnce(){
  let booted = false;

  function doBoot(){
    if (booted) return;

    // wait until the grid exists
    if (!document.querySelector('.talent-tree .talent-cell')) {
      requestAnimationFrame(doBoot);
      return;
    }

    const qs   = new URLSearchParams(location.search);
    const raw  = qs.get('build') || location.hash.replace(/^#/,'') || '';
    const tok  = extractBuildToken(raw); // "" if none

    if (!tok) {
      const def = '0-0-0';
      const u = new URL(location.href);
      u.search = '';
      u.hash = def;
      history.replaceState(null,'',u);
      refreshShareUI(); // also updates whisper
      booted = true;
      return;
    }

    // If 4-part token class doesn't match current page class, redirect to that class
    const parts = tok.split('-');
    if (parts.length === 4) {
      const wantedClass  = +parts[0] || 0;
      const currentClass = (window.tcClassId|0) || 0;
      if (wantedClass && currentClass && wantedClass !== currentClass) {
        const u = new URL(location.href);
        u.searchParams.set('class', String(wantedClass));
        u.hash = tok;
        location.replace(u.toString());
        return; // next load will apply
      }
    }

    decodeWowheadLike(tok);
    refreshShareUI();
    booted = true;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', doBoot, { once:true });
  } else {
    doBoot();
  }

  // handle BFCache restores
  window.addEventListener('pageshow', (e)=>{ if (e.persisted) doBoot(); });

  // If the user manually edits the hash + presses Enter, apply it
  window.addEventListener('hashchange', () => {
    const tok = extractBuildToken(location.hash.replace(/^#/, ''));
    if (!tok) return;
    decodeWowheadLike(tok);
    refreshShareUI();
  });
})();

/** Live updates: keep code box + hash + whisper synced after any rank change */
document.addEventListener('click', (e)=>{
  if (e.target.closest('.talent-cell[data-talent-id]')) {
    setTimeout(refreshShareUI, 0);
  }
});

/** Copy (trees-only code) */
document.getElementById('tcCopyToken')?.addEventListener('click', async ()=>{
  const code = refreshShareUI(); // ensures latest + returns token
  try { await navigator.clipboard.writeText(code); }
  catch { prompt('Copy this build code:', code); }
});

/** Optional: “Open build” helper */
document.getElementById('tcOpenBuild')?.addEventListener('click', (e)=>{
  e.preventDefault();
  const given = prompt('Paste a build code or URL (t1-t2-t3 or class-t1-t2-t3):','');
  if (!given) return;
  const token = extractBuildToken(given);
  if (!token || !decodeWowheadLike(token)) { alert('Invalid build token.'); return; }
  refreshShareUI();
});

/* NOTE:
   Do NOT preserve the current hash when switching classes.
   Class links should be plain URLs without a hash; the destination loads
   and this boot script initializes to #0-0-0 unless a build token is provided.
*/

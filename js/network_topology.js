/* ------------------------------------------------------------------------
   MagicShoot - netwerk topologie editor (page_network.php)
   Vanilla JS + SVG, geen externe libraries.
   Model:
     state.nodes = [ {id, type, label, ip, note, w, x, y} ]   // w = breedte, standaard NODE_W
     state.edges = [ {id, from, to, kind, label, note} ]
   Selectie:
     selection   = wat het eigenschappen-paneel toont (1 element of 1 verbinding)
     selectedIds = alle geselecteerde elementen; meerdere via het sleepkader
   ------------------------------------------------------------------------ */
var MSNet = (function () {

  var CANVAS_W = 1500, CANVAS_H = 860;
  var NODE_W   = 168,  NODE_H   = 66;   // NODE_W = standaardbreedte van een nieuw element
  var NODE_W_MIN = 110, NODE_W_MAX = 460;
  var GRID     = 10;

  /* ---------------- element types ---------------- */
  var NODE_TYPES = {
    router4g4p: { label: '4G router 4-poorts',    group: 'Modem / router' },
    router4g2p: { label: '4G router 2-poorts',    group: 'Modem / router' },
    router4p:   { label: 'Router 4-poorts',       group: 'Modem / router' },
    modem4g:    { label: 'Los 4G modem',          group: 'Modem / router' },
    wifimodule: { label: 'WiFi module (client)',  group: 'Modem / router' },
    switch:     { label: 'Switch',                group: 'Modem / router' },
    ap:         { label: 'Access point',          group: 'Modem / router' },
    internet:   { label: 'Internet / 4G',         group: 'Uplink' },
    venuelan:   { label: 'Netwerk locatie (UTP)', group: 'Uplink' },
    venuewifi:  { label: 'WiFi locatie',          group: 'Uplink' },
    pc:         { label: 'Fotokast PC',           group: 'Apparaten' },
    printer:    { label: 'Printer',               group: 'Apparaten' },
    payter:     { label: 'Betaalterminal',        group: 'Apparaten' },
    camera:     { label: 'Camera',                group: 'Apparaten' },
    screen:     { label: 'Scherm / tablet',       group: 'Apparaten' },
    hdmisplit:  { label: 'HDMI splitter',         group: 'Apparaten' },
    ledlight:   { label: 'LED verlichting',       group: 'Apparaten' },
    outlet:     { label: 'Stopcontact',           group: 'Apparaten' },
    relay:      { label: 'Relais (zwaar)',        group: 'Apparaten' },
    shelly:     { label: 'Shelly relais',         group: 'Shelly' },
    shellydim:  { label: 'Shelly dimmer',         group: 'Shelly' },
    shellydim2: { label: 'Shelly dimmer duo',     group: 'Shelly' },
    shellyh:    { label: 'Shelly H&T sensor',     group: 'Shelly' },
    other:      { label: 'Overig',                group: 'Overig' }
  };
  var TYPE_ORDER = ['router4g4p', 'router4g2p', 'router4p', 'modem4g', 'wifimodule', 'switch', 'ap',
                    'internet', 'venuelan', 'venuewifi',
                    'pc', 'printer', 'payter', 'camera', 'screen', 'hdmisplit', 'ledlight', 'outlet', 'relay',
                    'shelly', 'shellydim', 'shellydim2', 'shellyh', 'other'];

  /* 24x24 lijn-iconen, stroke = currentColor */
  var ICONS = {
    router4g4p: '<path d="M2 13h20v7H2z"/><path d="M6 16.5h.01M9 16.5h.01M12 16.5h.01M15 16.5h.01"/><path d="M19 16.5h1"/><path d="M7 13V9M17 13V9"/><path d="M5 7a4 4 0 0 1 4-4M19 7a4 4 0 0 0-4-4"/>',
    router4g2p: '<path d="M2 13h20v7H2z"/><path d="M8 16.5h.01M12 16.5h.01"/><path d="M19 16.5h1"/><path d="M7 13V9M17 13V9"/><path d="M5 7a4 4 0 0 1 4-4M19 7a4 4 0 0 0-4-4"/>',
    router4p:   '<path d="M2 13h20v7H2z"/><path d="M6 16.5h.01M9 16.5h.01M12 16.5h.01M15 16.5h.01"/><path d="M19 16.5h1"/><path d="M12 13V9"/>',
    modem4g:    '<path d="M6 8h12v13H6z"/><path d="M9 12h6M9 15h6"/><path d="M12 8V3"/><path d="M9.5 4.5 12 2l2.5 2.5"/>',
    wifimodule: '<path d="M4 14h16v7H4z"/><path d="M8 17.5h.01M12 17.5h.01"/><path d="M6 8.5a8 8 0 0 1 12 0"/><path d="M9 11a4 4 0 0 1 6 0"/>',
    switch:     '<path d="M2 8h20v9H2z"/><path d="M5 14h2M9 14h2M13 14h2M17 14h2"/><path d="M5 11h14"/>',
    ap:         '<path d="M4 16h16v5H4z"/><path d="M12 16v-3"/><path d="M7 9a7 7 0 0 1 10 0"/><path d="M9.5 11.5a3.5 3.5 0 0 1 5 0"/>',
    internet:   '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.5 2.7 3.8 5.7 3.8 9S14.5 18.3 12 21c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z"/>',
    venuelan:   '<path d="M4 21V6l7-3v18"/><path d="M11 10h9v11"/><path d="M7 8h.01M7 12h.01M7 16h.01M15 14h.01M15 18h.01"/>',
    venuewifi:  '<path d="M4 21V9l7-3v15"/><path d="M14 21h6"/><path d="M14 12.5a5 5 0 0 1 7 0"/><path d="M16 15.5a2.5 2.5 0 0 1 3 0"/>',
    pc:         '<path d="M3 4h18v12H3z"/><path d="M9 20h6"/><path d="M12 16v4"/>',
    printer:    '<path d="M7 8V3h10v5"/><path d="M4 8h16v8h-3"/><path d="M7 16H4V8"/><path d="M7 13h10v8H7z"/>',
    payter:     '<path d="M6 2h12v20H6z"/><path d="M8 5h8v5H8z"/><path d="M9 14h.01M12 14h.01M15 14h.01M9 18h.01M12 18h.01M15 18h.01"/>',
    camera:     '<path d="M2 7h5l2-3h6l2 3h5v13H2z"/><circle cx="12" cy="13" r="4"/>',
    screen:     '<rect x="1.5" y="5" width="21" height="14" rx="2"/><path d="M4 8h13.5v8H4z"/><path d="M20 12h.01"/>',
    hdmisplit:  '<path d="M6 9h12v6H6z"/><path d="M2 12h4"/><path d="M18 12h2"/><path d="M20 12V6h2"/><path d="M20 12v6h2"/><path d="M9 12h.01M12 12h.01"/>',
    ledlight:   '<path d="M9 18h6"/><path d="M10 21h4"/><path d="M12 2a6 6 0 0 0-3.5 10.9c.6.4 1 1.1 1 1.9v1.2h5v-1.2c0-.8.4-1.5 1-1.9A6 6 0 0 0 12 2z"/>',
    outlet:     '<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9.2" cy="12" r="1.2"/><circle cx="14.8" cy="12" r="1.2"/><path d="M12 6.5v1.4M12 16.1v1.4"/>',
    relay:      '<rect x="2.5" y="4.5" width="19" height="15" rx="1.5"/><path d="M5.5 9h4v6h-4z"/><path d="M7.5 9V7M7.5 17v-2"/><path d="M13 15h.01M19 15h.01"/><path d="M13.3 14.7 18.7 11"/>',
    shelly:     '<path d="M4 6h16v12H4z"/><path d="M8 12h8"/><path d="M13 9l-3 6"/>',
    shellydim:  '<path d="M4 6h16v12H4z"/><path d="M7 12h10"/><circle cx="10" cy="12" r="2"/>',
    shellydim2: '<path d="M3 5h18v14H3z"/><path d="M6 10h12M6 15h12"/><circle cx="9" cy="10" r="1.8"/><circle cx="15" cy="15" r="1.8"/>',
    shellyh:    '<path d="M12 3s5 6 5 10a5 5 0 0 1-10 0c0-4 5-10 5-10z"/><path d="M10 14a2 2 0 0 0 4 0"/>',
    other:      '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3 3.5V15"/><path d="M12 18h.01"/>'
  };

  /* ---------------- soorten verbindingen ---------------- */
  var EDGE_KINDS = {
    utp:    { label: 'UTP kabel', color: '#2f4f7a', dash: '',         width: 2.6 },
    wifi:   { label: 'WiFi',      color: '#e0a800', dash: '7 5',      width: 2.6 },
    mobile: { label: '4G / 5G',   color: '#d9534f', dash: '2 5',      width: 3.0 },
    usb:    { label: 'USB',       color: '#6f42c1', dash: '10 4 2 4', width: 2.4 },
    power:  { label: '230V',      color: '#8a5a00', dash: '1 6',      width: 3.4 },
    hdmi:   { label: 'HDMI',      color: '#20a8d8', dash: '6 3 1 3', width: 2.6 },
    dp:     { label: 'DisplayPort', color: '#4dbd74', dash: '4 3',   width: 2.6 }
  };
  var KIND_ORDER = ['utp', 'wifi', 'mobile', 'usb', 'power', 'hdmi', 'dp'];

  /* ---------------- state ---------------- */
  var state = { nodes: [], edges: [], nextId: 1 };
  var editMode = false, connectFrom = null, zoom = 1, dirty = false;
  // selection = wat het eigenschappen-paneel toont (1 element of 1 verbinding)
  // selectedIds = alle geselecteerde elementen (meervoudig, via het sleepkader)
  var selection = null, selectedIds = [];
  var svg, edgeLayer, nodeLayer, overlayLayer, els = {};
  var drag = null;

  function uid(p) { return p + (state.nextId++); }
  function byId(id) {
    for (var i = 0; i < state.nodes.length; i++) { if (state.nodes[i].id === id) { return state.nodes[i]; } }
    return null;
  }
  function edgeById(id) {
    for (var i = 0; i < state.edges.length; i++) { if (state.edges[i].id === id) { return state.edges[i]; } }
    return null;
  }
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function svgEl(n) { return document.createElementNS('http://www.w3.org/2000/svg', n); }
  function clip(s, max) { s = String(s || ''); return s.length > max ? s.slice(0, max - 1) + '…' : s; }
  function typeLabel(t) { return (NODE_TYPES[t] || NODE_TYPES.other).label; }

  function markDirty() {
    dirty = true;
    if (els.saveBtn) { els.saveBtn.disabled = false; }
    if (els.dirtyFlag) { els.dirtyFlag.style.display = ''; }
  }

  /* ---------------- geometrie ---------------- */
  // breedte per element; ontbreekt 'w' (oudere opgeslagen tekeningen) dan de standaardbreedte
  function nodeW(n) {
    // let op: isFinite(null) is true, dus expliciet op een bruikbaar getal controleren
    var raw = n ? Number(n.w) : NaN;
    var w = (isFinite(raw) && raw > 0) ? raw : NODE_W;
    return Math.max(NODE_W_MIN, Math.min(NODE_W_MAX, w));
  }
  function centerOf(n) { return { x: n.x + nodeW(n) / 2, y: n.y + NODE_H / 2 }; }

  // Aanknopingspunt op de rand van de node-box, in de richting van (tx,ty).
  // 'side' = welke zijde de lijn verlaat (l/r/t/b) en bepaalt de richting van de bocht.
  // De lijn hecht altijd aan het MIDDEN van die zijde: links/rechts halverwege boven
  // en onder, boven/onder halverwege links en rechts.
  function borderPoint(n, tx, ty) {
    var c = centerOf(n), dx = tx - c.x, dy = ty - c.y;
    var hw = nodeW(n) / 2 + 4, hh = NODE_H / 2 + 4;
    var side = 'r';
    if (dx !== 0 || dy !== 0) {
      var sx = dx === 0 ? Infinity : hw / Math.abs(dx);
      var sy = dy === 0 ? Infinity : hh / Math.abs(dy);
      side = sx <= sy ? (dx > 0 ? 'r' : 'l') : (dy > 0 ? 'b' : 't');
    }
    if (side === 'r') { return { x: c.x + hw, y: c.y, side: side }; }
    if (side === 'l') { return { x: c.x - hw, y: c.y, side: side }; }
    if (side === 't') { return { x: c.x, y: c.y - hh, side: side }; }
    return { x: c.x, y: c.y + hh, side: side };
  }

  var SIDE_NORMAL = { r: [1, 0], l: [-1, 0], t: [0, -1], b: [0, 1] };

  // Bezier tussen twee randpunten. De lijn verlaat elk vak loodrecht op de zijde
  // waar hij uitkomt, zodat er nooit een knik bij de rand ontstaat.
  function edgePath(p1, p2) {
    var dx = p2.x - p1.x, dy = p2.y - p1.y;
    var dist = Math.sqrt(dx * dx + dy * dy);
    var k = Math.max(28, Math.min(140, dist * 0.42));
    var n1 = SIDE_NORMAL[p1.side] || SIDE_NORMAL.r;
    var n2 = SIDE_NORMAL[p2.side] || SIDE_NORMAL.l;
    var c1 = { x: p1.x + n1[0] * k, y: p1.y + n1[1] * k };
    var c2 = { x: p2.x + n2[0] * k, y: p2.y + n2[1] * k };
    function r(v) { return Math.round(v * 10) / 10; }
    return {
      d: 'M' + r(p1.x) + ',' + r(p1.y) +
         ' C' + r(c1.x) + ',' + r(c1.y) + ' ' + r(c2.x) + ',' + r(c2.y) + ' ' + r(p2.x) + ',' + r(p2.y),
      // punt op t=0.5 van de kromme, voor het label
      mid: { x: (p1.x + 3 * c1.x + 3 * c2.x + p2.x) / 8,
             y: (p1.y + 3 * c1.y + 3 * c2.y + p2.y) / 8 }
    };
  }

  /* ---------------- tekenen ---------------- */
  function render() { renderNodes(); renderEdges(); renderLists(); }

  // Kort de tekst pas af als hij echt niet past (opgemeten, niet op tekenaantal geschat),
  // zodat een breder getrokken vak automatisch meer laat zien.
  function fitText(el, maxWidth, full) {
    full = String(full == null ? '' : full);
    el.textContent = full;
    if (full === '' || maxWidth <= 0) { return; }
    var len;
    try { len = el.getComputedTextLength(); } catch (err) { return; }
    if (!len || len <= maxWidth) { return; }   // 0 = niet meetbaar (canvas verborgen)
    var lo = 0, hi = full.length;
    while (lo < hi) {                          // binair zoeken naar het langste stuk dat past
      var mid = Math.ceil((lo + hi) / 2);
      el.textContent = full.slice(0, mid) + '…';
      if (el.getComputedTextLength() <= maxWidth) { lo = mid; } else { hi = mid - 1; }
    }
    el.textContent = lo > 0 ? full.slice(0, lo) + '…' : '…';
  }

  function renderNodes() {
    while (nodeLayer.firstChild) { nodeLayer.removeChild(nodeLayer.firstChild); }
    state.nodes.forEach(function (n) {
      var w = nodeW(n);
      var g = svgEl('g');
      var cls = 'ms-node';
      if (isSelected(n.id)) { cls += ' is-selected'; }
      if (connectFrom === n.id) { cls += ' is-connect-src'; }
      g.setAttribute('class', cls);
      g.setAttribute('transform', 'translate(' + n.x + ',' + n.y + ')');
      g.setAttribute('data-id', n.id);
      nodeLayer.appendChild(g);   // eerst in de DOM hangen: nodig om tekst te kunnen opmeten

      var r = svgEl('rect');
      r.setAttribute('class', 'ms-node-box');
      r.setAttribute('width', w);
      r.setAttribute('height', NODE_H);
      r.setAttribute('rx', 8);
      g.appendChild(r);

      var ico = svgEl('svg');
      ico.setAttribute('x', 10);
      ico.setAttribute('y', (NODE_H - 30) / 2);
      ico.setAttribute('width', 30);
      ico.setAttribute('height', 30);
      ico.setAttribute('viewBox', '0 0 24 24');
      ico.setAttribute('class', 'ms-node-icon');
      ico.innerHTML = ICONS[n.type] || ICONS.other;
      g.appendChild(ico);

      var textW = w - 48 - 12 - (n.note ? 14 : 0);   // ruimte tussen icoon en rechterrand

      var label = svgEl('text');
      label.setAttribute('class', 'ms-node-label');
      label.setAttribute('x', 48);
      label.setAttribute('y', n.ip ? 27 : 32);
      g.appendChild(label);
      fitText(label, textW, n.label || typeLabel(n.type));

      if (n.ip) {
        var ip = svgEl('text');
        ip.setAttribute('class', 'ms-node-ip');
        ip.setAttribute('x', 48);
        ip.setAttribute('y', 45);
        g.appendChild(ip);
        fitText(ip, textW, n.ip);
      }

      if (n.note) {
        var m = svgEl('circle');
        m.setAttribute('class', 'ms-node-notemark');
        m.setAttribute('cx', w - 13);
        m.setAttribute('cy', 13);
        m.setAttribute('r', 7);
        g.appendChild(m);
        var mt = svgEl('text');
        mt.setAttribute('class', 'ms-node-notemark-txt');
        mt.setAttribute('x', w - 13);
        mt.setAttribute('y', 16.5);
        mt.textContent = 'i';
        g.appendChild(mt);
      }

      // greepje om het vak breder/smaller te slepen (alleen zichtbaar in bewerkmodus)
      var h = svgEl('circle');
      h.setAttribute('class', 'ms-node-resize');
      h.setAttribute('cx', w);
      h.setAttribute('cy', NODE_H / 2);
      h.setAttribute('r', 5);
      var htip = svgEl('title');
      htip.textContent = 'Sleep om het vak breder of smaller te maken (dubbelklik = passend maken)';
      h.appendChild(htip);
      g.appendChild(h);

      var tip = svgEl('title');
      tip.textContent = (n.label || typeLabel(n.type)) + ' (' + typeLabel(n.type) + ')' +
                        (n.ip ? '\n' + n.ip : '') + (n.note ? '\n\n' + n.note : '');
      g.appendChild(tip);
    });
  }

  // breedte die nodig is om label en IP volledig te tonen
  function autoWidth(n) {
    var g = nodeLayer.querySelector('.ms-node[data-id="' + n.id + '"]');
    if (!g) { return nodeW(n); }
    var need = 0;
    ['.ms-node-label', '.ms-node-ip'].forEach(function (sel) {
      var el = g.querySelector(sel);
      if (!el) { return; }
      var keep = el.textContent;
      el.textContent = sel === '.ms-node-ip' ? (n.ip || '') : (n.label || typeLabel(n.type));
      try { need = Math.max(need, el.getComputedTextLength()); } catch (err) { /* niet meetbaar */ }
      el.textContent = keep;
    });
    if (!need) { return nodeW(n); }
    return Math.max(NODE_W_MIN, Math.min(NODE_W_MAX, Math.ceil(need) + 48 + 12 + (n.note ? 14 : 0)));
  }

  function renderEdges() {
    while (edgeLayer.firstChild) { edgeLayer.removeChild(edgeLayer.firstChild); }
    state.edges.forEach(function (e) {
      var a = byId(e.from), b = byId(e.to);
      if (!a || !b) { return; }
      var ca = centerOf(a), cb = centerOf(b);
      var p1 = borderPoint(a, cb.x, cb.y), p2 = borderPoint(b, ca.x, ca.y);
      var curve = edgePath(p1, p2);
      var k = EDGE_KINDS[e.kind] || EDGE_KINDS.utp;
      var sel = selection && selection.kind === 'edge' && selection.id === e.id;

      var g = svgEl('g');
      g.setAttribute('class', 'ms-edge' + (sel ? ' is-selected' : ''));
      g.setAttribute('data-id', e.id);

      var hit = svgEl('path'); // dikke onzichtbare lijn = makkelijker aan te klikken
      hit.setAttribute('class', 'ms-edge-hit');
      hit.setAttribute('d', curve.d);
      hit.setAttribute('fill', 'none');
      g.appendChild(hit);

      var ln = svgEl('path');
      ln.setAttribute('d', curve.d);
      ln.setAttribute('fill', 'none');
      ln.setAttribute('stroke', sel ? '#321fdb' : k.color);
      ln.setAttribute('stroke-width', sel ? k.width + 1.6 : k.width);
      ln.setAttribute('stroke-linecap', 'round');
      if (k.dash) { ln.setAttribute('stroke-dasharray', k.dash); }
      g.appendChild(ln);

      var tip = svgEl('title');
      tip.textContent = k.label + (e.label ? ' — ' + e.label : '') + (e.note ? '\n\n' + e.note : '');
      g.appendChild(tip);

      if (e.label || e.note) {
        var mx = curve.mid.x, my = curve.mid.y;
        var lbl = svgEl('text');
        lbl.setAttribute('class', 'ms-edge-label');
        lbl.setAttribute('x', mx);
        lbl.setAttribute('y', my + 4);
        lbl.textContent = clip(e.label || 'notitie', 24) + (e.note ? ' ⓘ' : '');
        g.appendChild(lbl);
        edgeLayer.appendChild(g);           // eerst plaatsen, dan pas opmeten
        var bb = lbl.getBBox();
        var bg = svgEl('rect');
        bg.setAttribute('class', 'ms-edge-label-bg');
        bg.setAttribute('x', bb.x - 5);
        bg.setAttribute('y', bb.y - 3);
        bg.setAttribute('width', bb.width + 10);
        bg.setAttribute('height', bb.height + 6);
        bg.setAttribute('rx', 4);
        g.insertBefore(bg, lbl);
        return;
      }
      edgeLayer.appendChild(g);
    });
  }

  /* --------- lees-tabellen onder de tekening --------- */
  function iconHtml(type, size) {
    return '<svg width="' + size + '" height="' + size + '" viewBox="0 0 24 24" class="ms-inline-icon">' +
           (ICONS[type] || ICONS.other) + '</svg>';
  }

  function renderLists() {
    if (els.nodeTable) {
      var rows = '';
      state.nodes.forEach(function (n) {
        rows += '<tr><td>' + iconHtml(n.type, 18) + ' ' + esc(n.label || typeLabel(n.type)) + '</td>' +
                '<td>' + esc(typeLabel(n.type)) + '</td>' +
                '<td>' + (n.ip ? '<code>' + esc(n.ip) + '</code>' : '') + '</td>' +
                '<td>' + esc(n.note || '') + '</td></tr>';
      });
      els.nodeTable.innerHTML = rows || '<tr><td colspan="4" class="text-muted">Nog geen elementen geplaatst.</td></tr>';
    }
    if (els.edgeTable) {
      var er = '';
      state.edges.forEach(function (e) {
        var a = byId(e.from), b = byId(e.to);
        if (!a || !b) { return; }
        var k = EDGE_KINDS[e.kind] || EDGE_KINDS.utp;
        er += '<tr><td>' + esc(a.label || typeLabel(a.type)) + '</td>' +
              '<td>' + esc(b.label || typeLabel(b.type)) + '</td>' +
              '<td><span class="ms-kind-chip" style="background:' + k.color + '"></span> ' + esc(k.label) + '</td>' +
              '<td>' + esc(e.label || '') + '</td>' +
              '<td>' + esc(e.note || '') + '</td></tr>';
      });
      els.edgeTable.innerHTML = er || '<tr><td colspan="5" class="text-muted">Nog geen verbindingen.</td></tr>';
    }
  }

  /* ---------------- coordinaten ---------------- */
  function toModel(evt) {
    var pt = svg.createSVGPoint();
    pt.x = evt.clientX;
    pt.y = evt.clientY;
    var m = svg.getScreenCTM();
    if (!m) { return { x: 0, y: 0 }; }
    var p = pt.matrixTransform(m.inverse());
    return { x: p.x, y: p.y };
  }
  function snap(v) { return (els.snapChk && els.snapChk.checked) ? Math.round(v / GRID) * GRID : Math.round(v); }
  function clampX(x, w) { return Math.max(0, Math.min(CANVAS_W - (w || NODE_W), x)); }
  function clampY(y) { return Math.max(0, Math.min(CANVAS_H - NODE_H, y)); }

  /* ---------------- wijzigingen ---------------- */
  function addNode(type, x, y) {
    var t = NODE_TYPES[type] ? type : 'other';
    var n = {
      id: uid('n'), type: t, label: NODE_TYPES[t].label, ip: '', note: '', w: NODE_W,
      x: clampX(snap(x - NODE_W / 2), NODE_W), y: clampY(snap(y - NODE_H / 2))
    };
    state.nodes.push(n);
    markDirty();
    select('node', n.id);
    render();
    return n;
  }

  function addEdge(from, to) {
    if (from === to) { return; }
    var dup = state.edges.some(function (e) {
      return (e.from === from && e.to === to) || (e.from === to && e.to === from);
    });
    if (dup) { flash('Deze twee elementen zijn al met elkaar verbonden.'); return; }
    var e = { id: uid('e'), from: from, to: to, kind: 'utp', label: '', note: '' };
    state.edges.push(e);
    markDirty();
    select('edge', e.id);
    render();
  }

  function removeSelected() {
    if (selection && selection.kind === 'edge') {
      var eid = selection.id;
      state.edges = state.edges.filter(function (e) { return e.id !== eid; });
    } else if (selectedIds.length) {
      var ids = selectedIds;
      state.nodes = state.nodes.filter(function (n) { return ids.indexOf(n.id) < 0; });
      state.edges = state.edges.filter(function (e) {
        return ids.indexOf(e.from) < 0 && ids.indexOf(e.to) < 0;
      });
    } else {
      return;
    }
    clearSelection();
    markDirty();
    render();
  }

  function isSelected(id) { return selectedIds.indexOf(id) >= 0; }

  function selectNodes(ids) {
    selectedIds = ids.slice();
    // het eigenschappen-paneel toont alleen details bij precies één element
    selection = selectedIds.length === 1 ? { kind: 'node', id: selectedIds[0] } : null;
    renderProps();
  }

  function selectEdge(id) {
    selectedIds = [];
    selection = { kind: 'edge', id: id };
    renderProps();
  }

  function clearSelection() {
    selectedIds = [];
    selection = null;
    renderProps();
  }

  function select(kind, id) {
    if (kind === 'node' && id) { selectNodes([id]); }
    else if (kind === 'edge' && id) { selectEdge(id); }
    else { clearSelection(); }
  }

  // omhullende rechthoek van de huidige meervoudige selectie
  function selectionBox() {
    if (!selectedIds.length) { return null; }
    var minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
    selectedIds.forEach(function (id) {
      var n = byId(id);
      if (!n) { return; }
      minX = Math.min(minX, n.x);
      minY = Math.min(minY, n.y);
      maxX = Math.max(maxX, n.x + nodeW(n));
      maxY = Math.max(maxY, n.y + NODE_H);
    });
    return isFinite(minX) ? { minX: minX, minY: minY, maxX: maxX, maxY: maxY } : null;
  }

  function insideSelection(p) {
    var b = selectionBox();
    return !!b && p.x >= b.minX && p.x <= b.maxX && p.y >= b.minY && p.y <= b.maxY;
  }

  // elementen die het sleepkader raken (overlap, niet volledig omsloten)
  function nodesInRect(x1, y1, x2, y2) {
    var ax = Math.min(x1, x2), ay = Math.min(y1, y2);
    var bx = Math.max(x1, x2), by = Math.max(y1, y2);
    return state.nodes.filter(function (n) {
      return n.x < bx && n.x + nodeW(n) > ax && n.y < by && n.y + NODE_H > ay;
    }).map(function (n) { return n.id; });
  }

  /* ---------------- eigenschappen-paneel ---------------- */
  // Elk invoerveld draagt zelf bij welk element het hoort (data-owner). Zonder dat zou een
  // change-event dat pas na het aanklikken van een ander element afgaat -- de browser vuurt dat
  // pas bij het wegvallen van de focus, dus na onCanvasMouseDown -- de tekst in het inmiddels
  // nieuw geselecteerde element schrijven.
  function ownerAttr() {
    return selection ? ' data-owner="' + esc(selection.kind + ':' + selection.id) + '"' : '';
  }
  function fld(lab, prop, val, ph) {
    return '<div class="form-group mb-2"><label class="small mb-1">' + esc(lab) + '</label>' +
           '<input type="text" class="form-control form-control-sm" data-prop="' + prop + '"' + ownerAttr() +
           ' value="' + esc(val) + '" placeholder="' + esc(ph || '') + '"></div>';
  }
  function area(lab, prop, val) {
    return '<div class="form-group mb-2"><label class="small mb-1">' + esc(lab) + '</label>' +
           '<textarea class="form-control form-control-sm" rows="4" data-prop="' + prop + '"' + ownerAttr() +
           '>' + esc(val) + '</textarea></div>';
  }

  function renderProps() {
    var box = els.props;
    if (!box) { return; }
    if (!editMode) { box.innerHTML = ''; return; }

    if (selectedIds.length > 1) {
      box.innerHTML = '<p class="small mb-2"><strong>' + selectedIds.length + ' elementen</strong> geselecteerd.</p>' +
                      '<p class="text-muted small mb-2">Sleep de selectie in één keer naar een andere plek. ' +
                      'Klik naast de selectie om hem op te heffen, of shift-klik om er elementen bij te doen ' +
                      'of uit te halen.</p>' +
                      '<button type="button" class="btn btn-sm btn-outline-danger btn-block" data-act="del">' +
                      'Alle ' + selectedIds.length + ' verwijderen</button>';
      renderNodes(); renderEdges();
      return;
    }

    if (!selection) {
      box.innerHTML = '<p class="text-muted small mb-0">Klik op een element of op een verbindingslijn om de ' +
                      'eigenschappen, het IP adres en de notitie te bewerken. Sleep op een lege plek om ' +
                      'meerdere elementen tegelijk te selecteren.</p>';
      renderNodes(); renderEdges();
      return;
    }

    var html = '';
    if (selection.kind === 'node') {
      var n = byId(selection.id);
      if (!n) { selectedIds = []; selection = null; renderProps(); return; }
      html += '<div class="form-group mb-2"><label class="small mb-1">Type</label>' +
              '<select class="form-control form-control-sm" data-prop="type"' + ownerAttr() + '>';
      TYPE_ORDER.forEach(function (t) {
        html += '<option value="' + t + '"' + (t === n.type ? ' selected' : '') + '>' + esc(NODE_TYPES[t].label) + '</option>';
      });
      html += '</select></div>';
      html += fld('Naam / omschrijving', 'label', n.label);
      html += fld('IP adres', 'ip', n.ip, 'bv. 192.168.1.1');
      html += area('Notitie bij dit element', 'note', n.note);
    } else {
      var e = edgeById(selection.id);
      if (!e) { selectedIds = []; selection = null; renderProps(); return; }
      var a = byId(e.from), b = byId(e.to);
      html += '<p class="small text-muted mb-2"><strong>' + esc(a ? (a.label || typeLabel(a.type)) : '?') +
              '</strong> &rarr; <strong>' + esc(b ? (b.label || typeLabel(b.type)) : '?') + '</strong></p>';
      html += '<div class="form-group mb-2"><label class="small mb-1">Soort verbinding</label>' +
              '<select class="form-control form-control-sm" data-prop="kind"' + ownerAttr() + '>';
      KIND_ORDER.forEach(function (k) {
        html += '<option value="' + k + '"' + (k === e.kind ? ' selected' : '') + '>' + esc(EDGE_KINDS[k].label) + '</option>';
      });
      html += '</select></div>';
      html += fld('Label op de lijn', 'label', e.label, 'bv. WAN poort');
      html += area('Notitie bij deze verbinding', 'note', e.note);
      html += '<button type="button" class="btn btn-sm btn-outline-secondary btn-block mb-2" data-act="swap">Richting omdraaien</button>';
    }
    html += '<button type="button" class="btn btn-sm btn-outline-danger btn-block" data-act="del">Verwijderen</button>';
    box.innerHTML = html;
    renderNodes();
    renderEdges();
  }

  function onPropInput(ev) {
    var prop  = ev.target.getAttribute('data-prop');
    var owner = ev.target.getAttribute('data-owner');
    if (!prop || !owner) { return; }
    // een <select> vuurt zowel input als change: één keer afhandelen
    if (ev.target.tagName === 'SELECT' && ev.type === 'input') { return; }
    // altijd naar het element schrijven waar dit veld bij hoort, ook als de selectie
    // inmiddels versprongen is
    var sep  = owner.indexOf(':');
    var kind = owner.slice(0, sep), id = owner.slice(sep + 1);
    var obj  = kind === 'node' ? byId(id) : edgeById(id);
    if (!obj) { return; }

    if (prop === 'type' && kind === 'node') {
      // standaardnaam mee laten veranderen zolang die niet handmatig is aangepast
      var wasDefault = TYPE_ORDER.some(function (t) { return NODE_TYPES[t].label === obj.label; });
      obj.type = ev.target.value;
      if (wasDefault) { obj.label = typeLabel(obj.type); }
      markDirty();
      render();
      renderProps();
      return;
    }
    obj[prop] = ev.target.value;
    markDirty();
    render();
  }

  /* ---------------- sleepkader (marquee) ---------------- */
  function drawMarquee(x1, y1, x2, y2) {
    if (!overlayLayer) { return; }
    var r = overlayLayer.querySelector('.ms-marquee');
    if (!r) {
      r = svgEl('rect');
      r.setAttribute('class', 'ms-marquee');
      overlayLayer.appendChild(r);
    }
    r.setAttribute('x', Math.min(x1, x2));
    r.setAttribute('y', Math.min(y1, y2));
    r.setAttribute('width', Math.abs(x2 - x1));
    r.setAttribute('height', Math.abs(y2 - y1));
  }

  function hideMarquee() {
    if (!overlayLayer) { return; }
    var r = overlayLayer.querySelector('.ms-marquee');
    if (r) { overlayLayer.removeChild(r); }
  }

  /* ---------------- interactie op het canvas ---------------- */
  function closestSel(target, sel) {
    var el = target;
    while (el && el !== svg) {
      if (el.classList && el.classList.contains(sel)) { return el; }
      el = el.parentNode;
    }
    return null;
  }

  // begin met het verslepen van alle geselecteerde elementen
  function startGroupDrag(p) {
    var orig = [];
    var minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
    selectedIds.forEach(function (id) {
      var n = byId(id);
      if (!n) { return; }
      orig.push({ id: id, x: n.x, y: n.y });
      minX = Math.min(minX, n.x);
      minY = Math.min(minY, n.y);
      maxX = Math.max(maxX, n.x + nodeW(n));
      maxY = Math.max(maxY, n.y + NODE_H);
    });
    if (!orig.length) { return false; }
    drag = { mode: 'movegroup', startX: p.x, startY: p.y, orig: orig,
             minX: minX, minY: minY, maxX: maxX, maxY: maxY, moved: false };
    return true;
  }

  function onCanvasMouseDown(ev) {
    if (!editMode) { return; }
    var g  = closestSel(ev.target, 'ms-node');
    var eg = closestSel(ev.target, 'ms-edge');
    var p  = toModel(ev);
    var addToSel = ev.shiftKey || ev.ctrlKey || ev.metaKey;

    if (g) {
      var id = g.getAttribute('data-id');
      if (connectFrom) {
        if (connectFrom !== id) { addEdge(connectFrom, id); }
        connectFrom = null;
        setConnectUi(false);
        render();
        return;
      }
      var n = byId(id);
      if (ev.target.classList && ev.target.classList.contains('ms-node-resize')) {
        selectNodes([id]);
        drag = { id: id, mode: 'resize', startX: p.x, startW: nodeW(n), moved: false };
        ev.preventDefault();
        return;
      }
      if (addToSel) {
        // shift/ctrl-klik: element aan de selectie toevoegen of eruit halen
        selectNodes(isSelected(id)
          ? selectedIds.filter(function (x) { return x !== id; })
          : selectedIds.concat([id]));
        ev.preventDefault();
        return;
      }
      // een al geselecteerd element aanklikken houdt de hele selectie vast,
      // zodat je er meerdere tegelijk mee verplaatst
      if (!isSelected(id)) { selectNodes([id]); }
      startGroupDrag(p);
      ev.preventDefault();
      return;
    }

    if (eg) { selectEdge(eg.getAttribute('data-id')); return; }

    // ---- lege plek op het canvas ----
    if (connectFrom) { connectFrom = null; setConnectUi(false); render(); return; }

    // binnen de bestaande selectie: die als geheel verslepen
    if (!addToSel && insideSelection(p) && startGroupDrag(p)) {
      ev.preventDefault();
      return;
    }

    // daarbuiten: selectie opheffen en een sleepkader beginnen
    if (!addToSel) { clearSelection(); }
    drag = { mode: 'marquee', x0: p.x, y0: p.y, lastX: p.x, lastY: p.y,
             base: addToSel ? selectedIds.slice() : [], moved: false };
    ev.preventDefault();
  }

  function onCanvasMouseMove(ev) {
    if (!drag) { return; }
    var p = toModel(ev);

    if (drag.mode === 'marquee') {
      drag.moved = true;
      drag.lastX = p.x;
      drag.lastY = p.y;
      drawMarquee(drag.x0, drag.y0, p.x, p.y);
      return;
    }

    if (drag.mode === 'movegroup') {
      // de verplaatsing zelf uitlijnen, zodat de onderlinge afstanden blijven kloppen
      var dx = snap(p.x - drag.startX), dy = snap(p.y - drag.startY);
      dx = Math.max(-drag.minX, Math.min(CANVAS_W - drag.maxX, dx));
      dy = Math.max(-drag.minY, Math.min(CANVAS_H - drag.maxY, dy));
      if (dx !== 0 || dy !== 0) { drag.moved = true; }
      drag.orig.forEach(function (o) {
        var nn = byId(o.id);
        if (!nn) { return; }
        nn.x = o.x + dx;
        nn.y = o.y + dy;
        var gg = nodeLayer.querySelector('.ms-node[data-id="' + o.id + '"]');
        if (gg) { gg.setAttribute('transform', 'translate(' + nn.x + ',' + nn.y + ')'); }
      });
      renderEdges();
      return;
    }

    var n = byId(drag.id);
    if (!n) { return; }
    drag.moved = true;

    if (drag.mode === 'resize') {
      var w = snap(drag.startW + (p.x - drag.startX));
      w = Math.max(NODE_W_MIN, Math.min(NODE_W_MAX, w));
      n.w = Math.min(w, CANVAS_W - n.x);        // niet buiten het canvas laten lopen
      renderNodes();                            // breedte + afgekapte tekst opnieuw opbouwen
      renderEdges();
    }
  }

  function onCanvasMouseUp() {
    if (!drag) { return; }

    if (drag.mode === 'marquee') {
      hideMarquee();
      if (drag.moved) {
        var found = nodesInRect(drag.x0, drag.y0, drag.lastX, drag.lastY);
        drag.base.forEach(function (id) {        // shift: bij de bestaande selectie optellen
          if (found.indexOf(id) < 0) { found.push(id); }
        });
        selectNodes(found);
      }
      drag = null;
      return;
    }

    if (drag.moved) { markDirty(); renderLists(); }
    drag = null;
  }

  function onCanvasDblClick(ev) {
    if (!editMode) { return; }
    var g = closestSel(ev.target, 'ms-node');
    if (!g) { return; }
    var n = byId(g.getAttribute('data-id'));
    // dubbelklik op het greepje = vak precies passend maken om de tekst
    if (n && ev.target.classList && ev.target.classList.contains('ms-node-resize')) {
      n.w = autoWidth(n);
      markDirty();
      render();
      return;
    }
    startConnect(g.getAttribute('data-id'));
  }

  function startConnect(id) {
    connectFrom = id;
    setConnectUi(true);
    render();
  }

  function setConnectUi(on) {
    if (els.connectHint) { els.connectHint.style.display = on ? '' : 'none'; }
    if (els.connectBtn) { els.connectBtn.classList.toggle('active', !!on); }
    if (svg) { svg.classList.toggle('is-connecting', !!on); }
  }

  /* ---------------- palet + legenda opbouwen ---------------- */
  function buildPalette() {
    var box = document.getElementById('msPalette');
    if (!box) { return; }
    var groups = [], seen = {};
    TYPE_ORDER.forEach(function (t) {
      var g = NODE_TYPES[t].group;
      if (!seen[g]) { seen[g] = []; groups.push(g); }
      seen[g].push(t);
    });
    var html = '';
    groups.forEach(function (g) {
      html += '<div class="ms-pal-group">' + esc(g) + '</div>';
      seen[g].forEach(function (t) {
        html += '<div class="ms-pal-item" draggable="true" data-type="' + t + '" title="Sleep naar de tekening (of klik)">' +
                iconHtml(t, 22) + '<span>' + esc(NODE_TYPES[t].label) + '</span></div>';
      });
    });
    box.innerHTML = html;
  }

  function buildLegend() {
    var box = document.getElementById('msLegend');
    if (!box) { return; }
    var html = '';
    KIND_ORDER.forEach(function (k) {
      var d = EDGE_KINDS[k];
      html += '<span class="ms-legend-item">' +
              '<svg width="34" height="10" viewBox="0 0 34 10"><line x1="1" y1="5" x2="33" y2="5" stroke="' + d.color +
              '" stroke-width="' + d.width + '" stroke-linecap="round"' +
              (d.dash ? ' stroke-dasharray="' + d.dash + '"' : '') + '/></svg> ' + esc(d.label) + '</span>';
    });
    box.innerHTML = html;
  }

  /* ---------------- slepen vanuit het palet ---------------- */
  function bindPalette() {
    buildPalette();
    var items = document.querySelectorAll('#msPalette [data-type]');
    Array.prototype.forEach.call(items, function (it) {
      it.addEventListener('dragstart', function (ev) {
        ev.dataTransfer.setData('text/plain', it.getAttribute('data-type'));
        ev.dataTransfer.effectAllowed = 'copy';
      });
      // klikken werkt ook: plaatst het element midden in beeld
      it.addEventListener('click', function () {
        if (!editMode) { return; }
        var wrap = els.canvasWrap;
        var cx = (wrap.scrollLeft + wrap.clientWidth / 2) / zoom;
        var cy = (wrap.scrollTop + wrap.clientHeight / 2) / zoom;
        addNode(it.getAttribute('data-type'), cx, cy);
      });
    });
    svg.addEventListener('dragover', function (ev) {
      if (!editMode) { return; }
      ev.preventDefault();
      ev.dataTransfer.dropEffect = 'copy';
    });
    svg.addEventListener('drop', function (ev) {
      if (!editMode) { return; }
      ev.preventDefault();
      var type = ev.dataTransfer.getData('text/plain');
      if (!type) { return; }
      var p = toModel(ev);
      addNode(type, p.x, p.y);
    });
  }

  /* ---------------- zoom ---------------- */
  function setZoom(z) {
    zoom = Math.max(0.4, Math.min(1.6, z));
    svg.style.width = (CANVAS_W * zoom) + 'px';
    svg.style.height = (CANVAS_H * zoom) + 'px';
    if (els.zoomLabel) { els.zoomLabel.textContent = Math.round(zoom * 100) + '%'; }
  }

  /* ---------------- voorbeeld-opstellingen ---------------- */
  function template(key) {
    var N = [], E = [], seq = 1;
    function n(type, label, x, y, ip) {
      var o = { id: 'n' + (seq++), type: type, label: label, ip: ip || '', note: '', x: x, y: y };
      N.push(o);
      return o;
    }
    function e(a, b, kind, label) {
      E.push({ id: 'e' + (seq++), from: a.id, to: b.id, kind: kind, label: label || '', note: '' });
    }

    var net = null, sw = null;
    if (key === 't1') {
      var i1 = n('internet', 'Internet (4G)', 60, 60);
      net = n('router4g4p', '4G router 4-poorts', 60, 220, '192.168.1.1');
      e(i1, net, 'mobile', 'SIM');
    } else if (key === 't2') {
      var i2 = n('internet', 'Internet (4G)', 60, 50);
      net = n('router4g4p', '4G router 4-poorts', 60, 230, '192.168.1.1');
      var v2 = n('venuelan', 'Netwerk locatie (UTP)', 60, 420);
      e(i2, net, 'mobile', 'SIM');
      e(v2, net, 'utp', 'WAN poort');
    } else if (key === 't3') {
      var i3 = n('internet', 'Internet (4G)', 60, 50);
      var m3 = n('modem4g', 'Los 4G modem', 60, 230, '192.168.8.1');
      net = n('router4p', 'Router 4-poorts', 60, 420, '192.168.1.1');
      e(i3, m3, 'mobile', 'SIM');
      e(m3, net, 'utp', 'WAN poort');
    } else if (key === 't4') {
      var i4 = n('internet', 'Internet (4G)', 60, 50);
      net = n('router4g2p', '4G router 2-poorts', 60, 230, '192.168.1.1');
      sw = n('switch', 'Switch in de kast', 60, 420);
      e(i4, net, 'mobile', 'SIM');
      e(net, sw, 'utp', 'LAN poort');
    } else { // t5
      var v5 = n('venuewifi', 'WiFi locatie', 60, 50);
      var w5 = n('wifimodule', 'WiFi module (client)', 60, 230);
      net = n('router4p', 'Router 4-poorts', 60, 420, '192.168.1.1');
      e(v5, w5, 'wifi', 'SSID locatie');
      e(w5, net, 'utp', 'WAN poort');
    }

    var hub = sw || net;
    var pc  = n('pc', 'Fotokast PC', 430, 90, '192.168.1.10');
    var pr  = n('printer', 'Printer', 720, 90);
    var pay = n('payter', 'Betaalterminal', 430, 250, '192.168.1.20');
    var sh  = n('shelly', 'Shelly relais', 430, 410, '192.168.1.30');
    var sd  = n('shellydim2', 'Shelly dimmer duo', 430, 570, '192.168.1.31');
    e(hub, pc, 'utp', 'LAN 1');
    e(pc, pr, 'usb', '');
    e(hub, pay, 'utp', 'LAN 2');
    e(hub, sh, 'utp', 'LAN 3');
    e(hub, sd, 'utp', 'LAN 4');
    return { nodes: N, edges: E };
  }

  // zet een tekening op het canvas; id's worden hernummerd zodat ze nooit
  // botsen met wat er al stond
  function installTopology(nodes, edges) {
    var map = {}, seq = 1;
    state.nodes = (nodes || []).map(function (n) {
      var id = 'n' + (seq++);
      map[n.id] = id;
      return { id: id, type: n.type, label: n.label || '', ip: n.ip || '', note: n.note || '',
               w: n.w, x: n.x, y: n.y };
    });
    state.edges = (edges || []).map(function (e) {
      return { id: 'e' + (seq++), from: map[e.from], to: map[e.to],
               kind: e.kind || 'utp', label: e.label || '', note: e.note || '' };
    }).filter(function (e) { return e.from && e.to; });
    state.nextId = seq + 1;
    selectedIds = [];
    selection = null;
    markDirty();
    render();
    renderProps();
  }

  function applyTemplate(key) {
    if (!confirmReplace()) { return; }
    var t = template(key);
    installTopology(t.nodes, t.edges);
  }

  function confirmReplace() {
    return !state.nodes.length ||
           confirm('De huidige tekening wordt vervangen door dit voorbeeld. Doorgaan?');
  }

  /* ---------------- eigen voorbeelden (uit de database) ---------------- */
  function applyDbTemplate(id) {
    if (!confirmReplace()) { return; }
    fetch('ajax_network_template.php?action=get&template_id=' + encodeURIComponent(id),
          { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.success) {
          flash('Voorbeeld laden mislukt: ' + ((res && res.error) || 'onbekende fout'), 'danger');
          return;
        }
        installTopology(res.nodes, res.edges);
        // de bijbehorende gegevens meenemen als startpunt; nog niet opgeslagen
        if (els.modemTypeInput && res.modem_type) { els.modemTypeInput.value = res.modem_type; }
        if (els.baseIpInput && res.base_ip)       { els.baseIpInput.value = res.base_ip; }
        if (els.noteInput && res.note)            { els.noteInput.value = res.note; }
        flash('Voorbeeld "' + res.name + '" geplaatst. Pas het aan voor deze fotokast en klik daarna op Opslaan.',
              'success');
      })
      .catch(function (err) { flash('Voorbeeld laden mislukt: ' + err, 'danger'); });
  }

  function saveAsTemplate() {
    if (!state.nodes.length) {
      flash('Er staat nog niets op de tekening om te bewaren.');
      return;
    }
    var suggestion = (els.eventName && els.eventName.value) ? els.eventName.value : '';
    var name = window.prompt('Naam voor dit voorbeeld:', suggestion);
    if (name === null) { return; }
    name = name.replace(/^\s+|\s+$/g, '');
    if (name === '') { flash('Geef het voorbeeld een naam.'); return; }
    postTemplate(name, false);
  }

  function postTemplate(name, overwrite) {
    var fd = new FormData();
    fd.append('action', 'save');
    fd.append('name', name);
    fd.append('overwrite', overwrite ? '1' : '0');
    fd.append('payload', JSON.stringify({
      event_id:   els.eventId.value,
      base_ip:    els.baseIpInput.value,
      modem_type: els.modemTypeInput.value,
      shelly_type: els.shellyTypeInput ? els.shellyTypeInput.value : '',
      note:       els.noteInput.value,
      nodes:      state.nodes,
      edges:      state.edges
    }));
    fetch('ajax_network_template.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.exists) {
          if (confirm('Er bestaat al een voorbeeld met de naam "' + name + '".\nWil je dat overschrijven?')) {
            postTemplate(name, true);
          }
          return;
        }
        if (!res || !res.success) {
          flash('Bewaren mislukt: ' + ((res && res.error) || 'onbekende fout'), 'danger');
          return;
        }
        addTemplateOption(res.template_id, res.name);
        flash(res.replaced
              ? 'Voorbeeld "' + res.name + '" is bijgewerkt.'
              : 'Bewaard als voorbeeld "' + res.name + '". Je vindt het in de lijst hierboven.', 'success');
      })
      .catch(function (err) { flash('Bewaren mislukt: ' + err, 'danger'); });
  }

  // voegt het nieuwe voorbeeld toe aan de keuzelijst zonder de pagina te herladen
  function addTemplateOption(id, name) {
    var grp = document.getElementById('msTplGroup');
    if (!grp) { return; }
    var val = 'db:' + id;
    var existing = grp.querySelector('option[value="' + val + '"]');
    if (existing) { existing.textContent = name; return; }
    var opt = document.createElement('option');
    opt.value = val;
    opt.textContent = name;
    var before = null;
    Array.prototype.forEach.call(grp.children, function (o) {
      if (!before && o.textContent.toLowerCase() > name.toLowerCase()) { before = o; }
    });
    grp.insertBefore(opt, before);
    addTemplateRow(id, name);
  }

  function addTemplateRow(id, name) {
    var list = document.getElementById('msTplList');
    if (!list) { return; }
    var empty = list.querySelector('td[colspan]');
    if (empty) { list.innerHTML = ''; }
    if (list.querySelector('tr[data-tpl-id="' + id + '"]')) { return; }
    var tr = document.createElement('tr');
    tr.setAttribute('data-tpl-id', id);
    tr.innerHTML = '<td></td><td>' + esc((els.eventName && els.eventName.value) || '—') + '</td>' +
                   '<td>zojuist</td><td class="text-right">' +
                   '<button type="button" class="btn btn-sm btn-outline-danger" data-tpl-del="' + id + '">Verwijderen</button></td>';
    tr.firstChild.textContent = name;   // naam als tekst, niet als HTML
    list.appendChild(tr);
  }

  function deleteTemplate(id, name) {
    if (!confirm('Voorbeeld "' + name + '" verwijderen?\nDe tekeningen van fotokasten blijven ongewijzigd.')) { return; }
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('template_id', id);
    fetch('ajax_network_template.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.success) {
          flash('Verwijderen mislukt: ' + ((res && res.error) || 'onbekende fout'), 'danger');
          return;
        }
        var grp = document.getElementById('msTplGroup');
        var opt = grp && grp.querySelector('option[value="db:' + id + '"]');
        if (opt) { opt.parentNode.removeChild(opt); }
        var row = document.querySelector('#msTplList tr[data-tpl-id="' + id + '"]');
        if (row) { row.parentNode.removeChild(row); }
        flash('Voorbeeld "' + name + '" is verwijderd.', 'success');
      })
      .catch(function (err) { flash('Verwijderen mislukt: ' + err, 'danger'); });
  }

  /* ---------------- bewerkmodus ---------------- */
  function setEditMode(on) {
    editMode = !!on;
    document.body.classList.toggle('ms-editing', editMode);
    if (els.editBtn)   { els.editBtn.style.display   = editMode ? 'none' : ''; }
    if (els.saveBtn)   { els.saveBtn.style.display   = editMode ? '' : 'none'; }
    if (els.cancelBtn) { els.cancelBtn.style.display = editMode ? '' : 'none'; }
    if (els.editTools) { els.editTools.style.display = editMode ? '' : 'none'; }
    if (els.palette)   { els.palette.style.display   = editMode ? '' : 'none'; }
    if (els.propsCol)  { els.propsCol.style.display  = editMode ? '' : 'none'; }
    Array.prototype.forEach.call(document.querySelectorAll('.ms-meta-view'), function (x) {
      x.style.display = editMode ? 'none' : '';
    });
    Array.prototype.forEach.call(document.querySelectorAll('.ms-meta-edit'), function (x) {
      x.style.display = editMode ? '' : 'none';
    });
    connectFrom = null;
    setConnectUi(false);
    selectedIds = [];
    selection = null;
    render();
    renderProps();
  }

  function flash(msg, type) {
    if (!els.msg) { alert(msg); return; }
    els.msg.className = 'alert alert-' + (type || 'warning') + ' py-2 px-3 mb-3';
    els.msg.textContent = msg;
    els.msg.style.display = '';
    clearTimeout(flash._t);
    flash._t = setTimeout(function () { els.msg.style.display = 'none'; }, 6000);
  }

  /* ---------------- opslaan ---------------- */
  function save() {
    var payload = {
      event_id:   els.eventId.value,
      base_ip:    els.baseIpInput.value,
      modem_type: els.modemTypeInput.value,
      shelly_type: els.shellyTypeInput ? els.shellyTypeInput.value : '',
      note:       els.noteInput.value,
      nodes:      state.nodes,
      edges:      state.edges
    };
    els.saveBtn.disabled = true;
    els.saveBtn.textContent = 'Opslaan…';

    var fd = new FormData();
    fd.append('payload', JSON.stringify(payload));

    fetch('ajax_network_save.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        els.saveBtn.textContent = 'Opslaan';
        if (res && res.success) {
          dirty = false;
          if (els.dirtyFlag) { els.dirtyFlag.style.display = 'none'; }
          els.baseIpView.textContent    = payload.base_ip || '—';
          els.modemTypeView.textContent = payload.modem_type || '—';
          if (els.shellyTypeView) { els.shellyTypeView.textContent = payload.shelly_type || '—'; }
          els.noteView.textContent      = payload.note || '—';
          if (els.updatedView && res.updated) { els.updatedView.textContent = res.updated; }
          setEditMode(false);
          flash('De netwerkconfiguratie is opgeslagen.', 'success');
        } else {
          els.saveBtn.disabled = false;
          flash('Opslaan mislukt: ' + ((res && res.error) || 'onbekende fout'), 'danger');
        }
      })
      .catch(function (err) {
        els.saveBtn.textContent = 'Opslaan';
        els.saveBtn.disabled = false;
        flash('Opslaan mislukt: ' + err, 'danger');
      });
  }

  /* ---------------- init ---------------- */
  function init(cfg) {
    svg          = document.getElementById('msCanvas');
    edgeLayer    = document.getElementById('msEdgeLayer');
    nodeLayer    = document.getElementById('msNodeLayer');
    overlayLayer = document.getElementById('msOverlayLayer');

    els.canvasWrap     = document.getElementById('msCanvasWrap');
    els.palette        = document.getElementById('msPaletteCol');
    els.propsCol       = document.getElementById('msPropsCol');
    els.props          = document.getElementById('msProps');
    els.editBtn        = document.getElementById('msEditBtn');
    els.saveBtn        = document.getElementById('msSaveBtn');
    els.cancelBtn      = document.getElementById('msCancelBtn');
    els.editTools      = document.getElementById('msEditTools');
    els.connectBtn     = document.getElementById('msConnectBtn');
    els.connectHint    = document.getElementById('msConnectHint');
    els.snapChk        = document.getElementById('msSnap');
    els.zoomLabel      = document.getElementById('msZoomLabel');
    els.msg            = document.getElementById('msMsg');
    els.dirtyFlag      = document.getElementById('msDirty');
    els.nodeTable      = document.getElementById('msNodeTable');
    els.edgeTable      = document.getElementById('msEdgeTable');
    els.eventId        = document.getElementById('msEventId');
    els.eventName      = document.getElementById('msEventName');
    els.baseIpInput    = document.getElementById('msBaseIp');
    els.modemTypeInput = document.getElementById('msModemType');
    els.shellyTypeInput= document.getElementById('msShellyType');
    els.noteInput      = document.getElementById('msNote');
    els.baseIpView     = document.getElementById('msBaseIpView');
    els.modemTypeView  = document.getElementById('msModemTypeView');
    els.shellyTypeView = document.getElementById('msShellyTypeView');
    els.noteView       = document.getElementById('msNoteView');
    els.updatedView    = document.getElementById('msUpdatedView');

    state.nodes = (cfg.nodes || []).slice();
    state.edges = (cfg.edges || []).slice();
    var maxN = 0;
    state.nodes.concat(state.edges).forEach(function (o) {
      var m = /(\d+)$/.exec(o.id || '');
      if (m) { maxN = Math.max(maxN, parseInt(m[1], 10)); }
    });
    state.nextId = maxN + 1;

    svg.setAttribute('viewBox', '0 0 ' + CANVAS_W + ' ' + CANVAS_H);
    setZoom(1);

    svg.addEventListener('mousedown', onCanvasMouseDown);
    window.addEventListener('mousemove', onCanvasMouseMove);
    window.addEventListener('mouseup', onCanvasMouseUp);
    svg.addEventListener('dblclick', onCanvasDblClick);

    if (els.props) {
      els.props.addEventListener('input', onPropInput);
      els.props.addEventListener('change', onPropInput);
      els.props.addEventListener('click', function (ev) {
        var act = ev.target.getAttribute('data-act');
        if (act === 'del') { removeSelected(); }
        if (act === 'swap' && selection && selection.kind === 'edge') {
          var e = edgeById(selection.id);
          if (e) { var t = e.from; e.from = e.to; e.to = t; markDirty(); render(); renderProps(); }
        }
      });
    }

    if (els.editBtn) { els.editBtn.addEventListener('click', function () { setEditMode(true); }); }
    if (els.cancelBtn) {
      els.cancelBtn.addEventListener('click', function () {
        if (dirty && !confirm('Niet-opgeslagen wijzigingen gaan verloren. Toch annuleren?')) { return; }
        dirty = false;
        window.location.reload();
      });
    }
    if (els.saveBtn) { els.saveBtn.addEventListener('click', save); }
    if (els.connectBtn) {
      els.connectBtn.addEventListener('click', function () {
        if (connectFrom) { connectFrom = null; setConnectUi(false); render(); return; }
        if (selection && selection.kind === 'node') { startConnect(selection.id); }
        else { flash('Selecteer eerst het element waar de verbinding moet beginnen.'); }
      });
    }

    var zi = document.getElementById('msZoomIn');
    var zo = document.getElementById('msZoomOut');
    var zr = document.getElementById('msZoomReset');
    if (zi) { zi.addEventListener('click', function () { setZoom(zoom + 0.15); }); }
    if (zo) { zo.addEventListener('click', function () { setZoom(zoom - 0.15); }); }
    if (zr) { zr.addEventListener('click', function () { setZoom(1); }); }

    var tpl = document.getElementById('msTemplate');
    if (tpl) {
      tpl.addEventListener('change', function () {
        var v = tpl.value;
        tpl.value = '';                       // altijd terug naar de kop van de lijst
        if (!v) { return; }
        if (v.indexOf('db:') === 0) { applyDbTemplate(v.slice(3)); }
        else { applyTemplate(v); }
      });
    }

    var tplSave = document.getElementById('msTplSaveBtn');
    if (tplSave) { tplSave.addEventListener('click', saveAsTemplate); }

    var tplManage = document.getElementById('msTplManageBtn');
    if (tplManage) {
      tplManage.addEventListener('click', function () {
        if (window.jQuery) { window.jQuery('#msTplModal').modal('show'); }
      });
    }

    var tplList = document.getElementById('msTplList');
    if (tplList) {
      tplList.addEventListener('click', function (ev) {
        var id = ev.target.getAttribute && ev.target.getAttribute('data-tpl-del');
        if (!id) { return; }
        var row = ev.target.closest ? ev.target.closest('tr') : null;
        var name = row ? (row.firstChild.textContent || '').replace(/^\s+|\s+$/g, '') : '';
        deleteTemplate(id, name);
      });
    }

    [els.baseIpInput, els.modemTypeInput, els.shellyTypeInput, els.noteInput].forEach(function (i) {
      if (i) { i.addEventListener('input', markDirty); }
    });

    document.addEventListener('keydown', function (ev) {
      if (!editMode) { return; }
      var tag = (ev.target.tagName || '').toLowerCase();
      if (tag === 'input' || tag === 'textarea' || tag === 'select') { return; }
      if (ev.key === 'Delete' || ev.key === 'Backspace') { ev.preventDefault(); removeSelected(); }
      if (ev.key === 'Escape') {
        connectFrom = null;
        setConnectUi(false);
        hideMarquee();          // een lopend sleepkader ook meteen weghalen
        drag = null;
        select(null);
        render();
      }
      if (ev.key === 'c' && selection && selection.kind === 'node') { startConnect(selection.id); }
    });

    window.addEventListener('beforeunload', function (ev) {
      if (dirty) { ev.preventDefault(); ev.returnValue = ''; }
    });

    bindPalette();
    buildLegend();
    setEditMode(false);
    if (els.saveBtn) { els.saveBtn.disabled = true; }
  }

  return {
    init: init,
    NODE_TYPES: NODE_TYPES,
    TYPE_ORDER: TYPE_ORDER,
    ICONS: ICONS,
    EDGE_KINDS: EDGE_KINDS,
    KIND_ORDER: KIND_ORDER
  };
})();

/* Rendu des pages à partir de data.js. Chaque page déclare <body data-page="...">. */

const ICON = {
  bell: '<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3v3"/><path d="M9 22c0-8 1-14 7-14s7 6 7 14"/><path d="M6 22h20l-1.5 3h-17z"/><circle cx="16" cy="28" r="1.6"/></svg>',
  search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
  play: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>',
  arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
  menu: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
};

const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);
const norm = (s) => s.normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase();
const fmt = (n) => n.toLocaleString("fr-FR");

const edifice = ([dept, commune, nom, slug]) => ({ dept, commune, nom, slug, fiche: FICHES[slug] });
const ALL = EDIFICES.map(edifice);
const bySlug = Object.fromEntries(ALL.map((e) => [e.slug, e]));
const ficheUrl = (slug) => `edifice.html?e=${encodeURIComponent(slug)}`;
const origineUrl = (slug) => `${SITE.ancienSite}/post/${encodeURIComponent(slug)}`;
const typeOf = (nom) => {
  const n = norm(nom);
  if (n.startsWith("chapelle") || n.startsWith("prieure")) return "Chapelle";
  if (n.startsWith("cathedrale")) return "Cathédrale";
  if (n.startsWith("abbaye") || n.includes("abbatiale") || n.startsWith("augustines")) return "Abbaye & couvent";
  return "Église";
};

/* ---------- Gabarit commun ---------- */
function layout() {
  const page = document.body.dataset.page;
  const links = [
    ["index.html", "Accueil", "accueil"],
    ["edifices.html", "Les édifices", "edifices"],
    ["carte.html", "Carte", "carte"],
    ["a-propos.html", "Qui suis-je ?", "apropos"],
  ];
  const brand = `<a class="brand" href="index.html">${ICON.bell}<span class="brand-name">Cloches de Bretagne<small>Inventaire campanaire</small></span></a>`;

  document.body.insertAdjacentHTML("afterbegin", `
    <a class="skip" href="#main">Aller au contenu</a>
    <header class="site-header"><div class="wrap">
      ${brand}
      <button class="nav-toggle" aria-label="Menu" aria-expanded="false">${ICON.menu}</button>
      <nav class="nav">${links.map(([h, t, k]) => `<a href="${h}"${k === page ? ' aria-current="page"' : ""}>${t}</a>`).join("")}</nav>
    </div></header>`);

  const deptLinks = Object.entries(DEPARTEMENTS)
    .map(([n, name]) => `<li><a href="edifices.html?d=${n}">${n} · ${name}</a></li>`).join("");
  document.body.insertAdjacentHTML("beforeend", `
    <footer class="site-footer"><div class="wrap">
      <div class="footer-grid">
        <div>${brand}<p>L'inventaire des cloches des églises et chapelles de Bretagne, relevées clocher par clocher : fondeurs, notes, poids, inscriptions et enregistrements.</p></div>
        <div><h4>Départements</h4><ul>${deptLinks}</ul></div>
        <div><h4>Suivre</h4><ul>
          <li><a href="${SITE.youtube}" target="_blank" rel="noopener">Chaîne YouTube</a></li>
          <li><a href="${SITE.facebook}" target="_blank" rel="noopener">Facebook</a></li>
          <li><a href="carte.html">Carte des clochers</a></li>
          <li><a href="a-propos.html">Qui suis-je ?</a></li>
        </ul></div>
      </div>
      <div class="footer-bottom"><span>© ${new Date().getFullYear()} ${SITE.auteur} · Cloches de Bretagne</span><span>Photos et relevés : tous droits réservés</span></div>
    </div></footer>`);

  const toggle = document.querySelector(".nav-toggle");
  const nav = document.querySelector(".nav");
  toggle.addEventListener("click", () => {
    const open = nav.classList.toggle("open");
    toggle.setAttribute("aria-expanded", open);
  });
}

/* ---------- Accueil ---------- */
function pageAccueil() {
  document.querySelector(".hero img").src = HERO_IMG;

  const counts = {};
  ALL.forEach((e) => (counts[e.dept] = (counts[e.dept] || 0) + 1));
  document.getElementById("stats").innerHTML = [
    [fmt(SITE.totalCloches), "cloches répertoriées"],
    [fmt(SITE.totalEdifices), "édifices visités"],
    ["5", "départements bretons"],
    ["2015", "première montée en clocher"],
  ].map(([b, s]) => `<div class="stat"><b>${b}</b><span>${s}</span></div>`).join("");

  document.getElementById("recents").innerHTML = RECENTS.map(([slug, img]) => {
    const e = bySlug[slug];
    return `<a class="card" href="${ficheUrl(slug)}">
      <div class="card-img" style="background-image:url('${img}')" role="img" aria-label="${esc(e.nom)}, ${esc(e.commune)}"></div>
      <div class="card-body"><span class="tag">${e.dept} · ${esc(DEPARTEMENTS[e.dept])}</span>
      <h3>${esc(e.commune)}</h3><div class="card-meta">${esc(e.nom)}</div></div></a>`;
  }).join("");

  document.getElementById("depts").innerHTML = Object.entries(DEPARTEMENTS).map(([n, name]) =>
    `<a class="dept" href="edifices.html?d=${n}"><div class="num">${n}</div><div class="name">${name}</div>
     <div class="count">${counts[n] || 0} édifice${(counts[n] || 0) > 1 ? "s" : ""}</div></a>`).join("");

  document.getElementById("upcoming").innerHTML = A_PARAITRE.map(([d, c]) =>
    `<li><span class="badge">${d}</span><span>${esc(c)}</span><span class="soon">Bientôt</span></li>`).join("");
}

/* ---------- Catalogue ---------- */
function pageEdifices() {
  const params = new URLSearchParams(location.search);
  const state = { q: params.get("q") || "", dept: params.get("d") || "", type: "" };
  const input = document.getElementById("q");
  input.value = state.q;

  const chip = (group, value, label) =>
    `<button class="chip" data-group="${group}" data-value="${value}" aria-pressed="false">${label}</button>`;
  document.getElementById("chips-dept").innerHTML = chip("dept", "", "Tous") +
    Object.entries(DEPARTEMENTS).map(([n, name]) => chip("dept", n, `${n} · ${name}`)).join("");
  document.getElementById("chips-type").innerHTML = chip("type", "", "Tous types") +
    ["Église", "Chapelle", "Cathédrale", "Abbaye & couvent"].map((t) => chip("type", t, t)).join("");

  function render() {
    document.querySelectorAll(".chip").forEach((c) =>
      c.setAttribute("aria-pressed", state[c.dataset.group] === c.dataset.value));
    const q = norm(state.q.trim());
    const hits = ALL.filter((e) =>
      (!state.dept || e.dept === state.dept) &&
      (!state.type || typeOf(e.nom) === state.type) &&
      (!q || norm(`${e.commune} ${e.nom}`).includes(q)));

    document.getElementById("count").textContent =
      `${hits.length} édifice${hits.length > 1 ? "s" : ""} sur ${ALL.length} publiés en ligne`;

    const out = document.getElementById("results");
    if (!hits.length) { out.innerHTML = `<p class="empty">Aucun édifice ne correspond à cette recherche.</p>`; return; }
    out.innerHTML = Object.keys(DEPARTEMENTS).map((d) => {
      const rows = hits.filter((e) => e.dept === d);
      if (!rows.length) return "";
      return `<section class="group"><h2>${d} · ${DEPARTEMENTS[d]} <span class="n">${rows.length}</span></h2><ul class="list">${
        rows.map((e) => `<li><a href="${ficheUrl(e.slug)}"><span><span class="commune">${esc(e.commune)}</span>
          <span class="edifice">${esc(e.nom)}</span></span>${e.fiche ? `<span class="pill">${e.fiche.cloches.length} cloches</span>` : ""}</a></li>`).join("")
      }</ul></section>`;
    }).join("");

    const p = new URLSearchParams();
    if (state.q) p.set("q", state.q);
    if (state.dept) p.set("d", state.dept);
    history.replaceState(null, "", p.toString() ? `?${p}` : location.pathname);
  }

  input.addEventListener("input", () => { state.q = input.value; render(); });
  document.addEventListener("click", (ev) => {
    const c = ev.target.closest(".chip");
    if (!c) return;
    state[c.dataset.group] = c.dataset.value;
    render();
  });
  render();
}

/* ---------- Fiche édifice ---------- */
function pageFiche() {
  const slug = new URLSearchParams(location.search).get("e");
  const e = bySlug[slug];
  const main = document.getElementById("main");
  if (!e) {
    main.innerHTML = `<div class="wrap section"><h1>Édifice introuvable</h1><p><a href="edifices.html">Retour à la liste des édifices</a></p></div>`;
    return;
  }
  document.title = `${e.commune} – ${e.nom} | Cloches de Bretagne`;
  const crumbs = `<div class="crumbs"><a href="edifices.html">Édifices</a> › <a href="edifices.html?d=${e.dept}">${DEPARTEMENTS[e.dept]}</a> › ${esc(e.commune)}</div>`;
  const f = e.fiche;

  if (!f) {
    main.innerHTML = `<div class="wrap section">${crumbs}
      <span class="eyebrow">${e.dept} · ${DEPARTEMENTS[e.dept]}</span><h1>${esc(e.commune)}</h1>
      <p class="lead" style="font-size:1.2rem;color:var(--ink-2)">${esc(e.nom)}</p>
      <div class="notice"><h3>Fiche en cours de migration</h3>
      <p>Le relevé complet de cet édifice est consultable sur l'ancien site en attendant sa mise en ligne ici.</p>
      <a class="btn btn-primary" href="${origineUrl(e.slug)}" target="_blank" rel="noopener">Voir l'article ${ICON.arrow}</a></div></div>`;
    return;
  }

  const total = f.cloches.reduce((s, c) => s + c.poids, 0);
  const maxP = Math.max(...f.cloches.map((c) => c.poids));
  const accord = f.cloches.map((c) => c.note).join(" · ");

  const bell = (c, i) => `<article class="bell">
    <div class="bell-top"><h3>${esc(c.nom)}</h3><span class="note">${esc(c.note)}</span></div>
    <div class="sub">Cloche ${i + 1} · ${esc(c.annee)}</div>
    <dl>
      <dt>Fondeur</dt><dd>${esc(c.fondeur)}</dd>
      <dt>Poids</dt><dd>${fmt(c.poids)} kg</dd>
      <dt>Diamètre</dt><dd>${fmt(c.diametre)} mm</dd>
      ${c.parrains ? `<dt>Parrainage</dt><dd>${esc(c.parrains)}</dd>` : ""}
      ${c.remarque ? `<dt>Note</dt><dd>${esc(c.remarque)}</dd>` : ""}
    </dl>
    ${c.inscription ? `<blockquote>${esc(c.inscription)}</blockquote>` : ""}
  </article>`;

  main.innerHTML = `
    <div class="wrap"><div class="fiche-hero">
      <div>${crumbs}<span class="eyebrow">${e.dept} · ${DEPARTEMENTS[e.dept]}</span>
        <h1>${esc(e.commune)}</h1><p class="lead">${esc(e.nom)}</p><p>${esc(f.resume)}</p>
        <div class="facts">
          <div><b>${f.cloches.length}</b><span>cloches</span></div>
          <div><b>${fmt(total)} kg</b><span>de bronze</span></div>
          <div><b>${esc(accord)}</b><span>accord</span></div>
        </div>
      </div>
      <img src="${f.image}" alt="${esc(e.nom)} de ${esc(e.commune)}">
    </div></div>

    ${f.video ? `<section class="section alt"><div class="wrap">
      <span class="eyebrow">Écouter</span><h2>La sonnerie en vidéo</h2>
      <div class="video"><iframe src="https://www.youtube-nocookie.com/embed/${f.video}" title="Sonnerie de ${esc(e.commune)}" loading="lazy" allow="accelerometer; encrypted-media; picture-in-picture" allowfullscreen></iframe></div>
    </div></section>` : ""}

    <section class="section"><div class="wrap">
      <span class="eyebrow">Histoire</span><h2>L'édifice</h2>
      <div class="prose">${f.histoire.map((p) => `<p>${esc(p)}</p>`).join("")}</div>
    </div></section>

    <section class="section alt"><div class="wrap">
      <span class="eyebrow">Relevé campanaire</span><h2>Les cloches</h2>
      <div class="table-scroll" style="margin:28px 0 32px"><table class="compare">
        <thead><tr><th>#</th><th>Nom</th><th>Note</th><th>Poids</th><th>Diamètre</th><th>Fondeur</th><th style="width:22%"></th></tr></thead>
        <tbody>${f.cloches.map((c, i) => `<tr><td>${i + 1}</td><td><b>${esc(c.nom)}</b></td><td>${esc(c.note)}</td>
          <td class="num">${fmt(c.poids)} kg</td><td class="num">${fmt(c.diametre)} mm</td><td>${esc(c.fondeur)}</td>
          <td><div class="bar"><i style="width:${(c.poids / maxP) * 100}%"></i></div></td></tr>`).join("")}</tbody>
      </table></div>
      <div class="bells">${f.cloches.map(bell).join("")}</div>
    </div></section>`;
}

layout();
({ accueil: pageAccueil, edifices: pageEdifices, fiche: pageFiche })[document.body.dataset.page]?.();

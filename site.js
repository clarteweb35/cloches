/* Menu mobile + filtres du catalogue des édifices. */

(function () {
  const toggle = document.querySelector(".nav-toggle");
  const nav = document.getElementById("nav");
  if (toggle && nav) {
    toggle.addEventListener("click", () => {
      toggle.setAttribute("aria-expanded", nav.classList.toggle("open"));
    });
  }

  const cat = document.querySelector(".catalogue");
  if (!cat) return;

  const norm = (s) => s.normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().trim();
  const input = document.getElementById("cdb-q");
  const count = document.getElementById("cdb-count");
  const vide = document.getElementById("cdb-vide");
  const groups = [...cat.querySelectorAll(".group")];
  const params = new URLSearchParams(location.search);
  const state = { q: params.get("q") || "", dept: cat.dataset.preselection || params.get("d") || "", type: "" };
  input.value = state.q;

  function render() {
    cat.querySelectorAll(".chip").forEach((c) =>
      c.setAttribute("aria-pressed", String(state[c.dataset.group] === c.dataset.value)));
    const q = norm(state.q);
    let total = 0;
    groups.forEach((g) => {
      let n = 0;
      const deptOk = !state.dept || g.dataset.dept === state.dept;
      g.querySelectorAll("li").forEach((li) => {
        const ok = deptOk && (!state.type || li.dataset.type === state.type) && (!q || li.dataset.q.includes(q));
        li.hidden = !ok;
        if (ok) n++;
      });
      g.hidden = n === 0;
      total += n;
    });
    count.textContent = `${total} édifice${total > 1 ? "s" : ""}`;
    vide.hidden = total > 0;
  }

  input.addEventListener("input", () => { state.q = input.value; render(); });
  cat.addEventListener("click", (ev) => {
    const c = ev.target.closest(".chip");
    if (!c) return;
    state[c.dataset.group] = c.dataset.value;
    render();
  });
  render();
})();

/* Formulaire des cloches : ajouter, supprimer, réordonner. */

(function () {
  const liste = document.getElementById("cdb-cloches");
  const modele = document.getElementById("cdb-modele");
  if (!liste || !modele) return;

  let compteur = Date.now();

  function numeroter() {
    liste.querySelectorAll(".cdb-cloche").forEach((f, i) => {
      const nom = f.querySelector('input[name$="[nom]"]').value.trim();
      f.querySelector(".cdb-num").textContent = `Cloche ${i + 1}${nom ? " — " + nom : ""}`;
    });
  }

  document.getElementById("cdb-ajouter").addEventListener("click", () => {
    const i = String(compteur++);
    const html = modele.innerHTML.replaceAll("__i__", i);
    liste.insertAdjacentHTML("beforeend", html);
    numeroter();
    liste.lastElementChild.querySelector("input").focus();
  });

  liste.addEventListener("click", (ev) => {
    const f = ev.target.closest(".cdb-cloche");
    if (!f) return;
    if (ev.target.closest(".cdb-supprimer")) {
      const nom = f.querySelector('input[name$="[nom]"]').value.trim() || "cette cloche";
      if (confirm(`Supprimer ${nom} ?`)) f.remove();
    } else if (ev.target.closest(".cdb-monter") && f.previousElementSibling) {
      liste.insertBefore(f, f.previousElementSibling);
    } else if (ev.target.closest(".cdb-descendre") && f.nextElementSibling) {
      liste.insertBefore(f.nextElementSibling, f);
    } else {
      return;
    }
    numeroter();
  });

  liste.addEventListener("input", (ev) => {
    if (ev.target.matches('input[name$="[nom]"]')) numeroter();
  });

  numeroter();
})();

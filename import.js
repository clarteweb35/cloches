/* Import initial : enchaîne les appels par petits lots, avec une barre de progression. */

(function () {
  const bouton = document.getElementById("cdb-import-go");
  if (!bouton) return;

  const barre = document.getElementById("cdb-import-barre");
  const etat = document.getElementById("cdb-import-etat");
  const journal = document.getElementById("cdb-import-journal");
  const total = Number(bouton.dataset.total);

  async function lot(debut) {
    const data = new FormData();
    data.append("action", "cdb_import_lot");
    data.append("_ajax_nonce", bouton.dataset.nonce);
    data.append("debut", debut);
    const reponse = await fetch(window.ajaxurl, { method: "POST", body: data, credentials: "same-origin" });
    const json = await reponse.json();
    if (!json.success) throw new Error(json.data || "erreur inconnue");
    return json.data;
  }

  function noter(texte) {
    const li = document.createElement("li");
    li.textContent = texte;
    journal.appendChild(li);
  }

  bouton.addEventListener("click", async () => {
    bouton.disabled = true;
    barre.hidden = false;
    journal.textContent = "";
    let debut = 0;
    let echecs = 0;

    for (;;) {
      try {
        const r = await lot(debut);
        r.journal.forEach(noter);
        debut = r.suivant;
        echecs = 0;
        barre.value = debut;
        etat.textContent = `${debut} édifice${debut > 1 ? "s" : ""} sur ${total}…`;
        if (r.termine) break;
      } catch (e) {
        if (++echecs >= 3) {
          etat.textContent = `L'import s'est interrompu (${e.message}). Cliquez de nouveau sur « Lancer l'import » : il reprend là où il s'est arrêté.`;
          bouton.disabled = false;
          return;
        }
      }
    }
    etat.textContent = `Import terminé : ${total} édifices traités.`;
    bouton.disabled = false;
  });
})();

/* IDEE ABBOZZATE HO MODIFICATO DATABASE.PHP  con getpersona E TUTTO QUELLO CHE TANGE A RUBRICA 

allora devo importare dal db le persone
devo salvare il nome il cognome, la mail e il livello di sicurezza
se il livello di sicurezza è uguale a 2 bisogna avvisare che si tratta di un rappresentante
degli stud, se il livello è uguale a 3 allora bisogna avvisare che si tratta i un professore
e se è uguale al livello 4 è uguale ad un membro della segreteria

vorrei dividere quindi le persone in queste 3 categorie

vorrei far visualizzare in ordine:
    1. rappresentanti degli stud
    2. professore
    3. segretari

ovviamente tutti in ordine alfabetico, vorrei che ogni persona abbia un link che apra diretttamente le mail


ora vorrei fare il css: vorrei che i titoli delle categorie fossero personalizzabili da me , 
anche i campi mail (solo le etichette intendo) e che la linea di 
separazione sia divisibile anche con la griglia intendo 
( vorrei poter scegliere il divisore, spessore, etc)  
vorrei anche che si vedessero per riga della griglia 3 
persone occupando tutta la pagina ovviamente mobile 
(se lo schermo è piccolo vorrei uno sotto l'altro) 
vorrei la possibilità diespandere o restringere le 
persone divise in gruppi (ovvero espanndere le segretarie, 
oppuure espandere i professori ovviamente mostrando il nome della 
categoria invariato e una freccia erso il basso con un pointer quando 
vci si passa sopra.   vorrei che se ci sono campi vuoti nella griglia 
rimangano vuoti e uniti.
*/

document.addEventListener("DOMContentLoaded", () => {
  fetch("../Api/api-rubrica.php")
    .then(r => r.json())
    .then(persone => {
      // Filtra livelli > 1 e suddivide in gruppi
      const gruppi = {2: [], 3: [], 4: []};
      persone.forEach(p => {
        if(p.livello > 1 && gruppi[p.livello]) {
          gruppi[p.livello].push(p);
        }
      });

      // Ordina alfabeticamente per cognome, poi nome
      Object.values(gruppi).forEach(lista => {
        lista.sort((a,b) => {
          if(a.cognome.toLowerCase() < b.cognome.toLowerCase()) return -1;
          if(a.cognome.toLowerCase() > b.cognome.toLowerCase()) return 1;
          if(a.nome.toLowerCase() < b.nome.toLowerCase()) return -1;
          if(a.nome.toLowerCase() > b.nome.toLowerCase()) return 1;
          return 0;
        });
      });

      const main = document.querySelector(".content main");
      const nomiGruppi = {2: "Rappresentanti", 3: "Professori", 4: "Segretari"};

      Object.keys(gruppi).forEach(livello => {
        const lista = gruppi[livello];
        if(lista.length === 0) return;

        // Sezione gruppo
        const section = document.createElement("section");
        section.classList.add("rubrica-group");
        section.id = `gruppo-livello-${livello}`;

        // Titolo con icona espandibile
        const titleDiv = document.createElement("div");
        titleDiv.classList.add("rubrica-title");
        titleDiv.innerHTML = `
          <span class="rubrica-category-name">${nomiGruppi[livello]}</span>
          <span class="rubrica-toggle-icon">▼</span>
        `;
        section.appendChild(titleDiv);

        // Lista persone in griglia
        const listaDiv = document.createElement("div");
        listaDiv.classList.add("rubrica-list");

        lista.forEach((p, index) => {
          const personaDiv = document.createElement("div");
          personaDiv.classList.add("rubrica-item");
          personaDiv.id = `persona-${livello}-${index}`;

          const emailUniClass = p.email_uni ? "" : "empty";
          const emailPersonaleClass = p.email ? "" : "empty";

          personaDiv.innerHTML = `
            <div class="rubrica-name">${p.cognome} ${p.nome}</div>
            <div class="rubrica-email"><span class="label">Email Uni:</span> ${p.email_uni || ""}</div>
            <div class="rubrica-email"><span class="label">Email Personale:</span> ${p.email || ""}</div>
          `;

          listaDiv.appendChild(personaDiv);
        });

        section.appendChild(listaDiv);

        // Funzione espandi/restringi
        const toggleIcon = titleDiv.querySelector(".rubrica-toggle-icon");
        toggleIcon.addEventListener("click", () => {
          section.classList.toggle("collapsed");
          listaDiv.style.display = section.classList.contains("collapsed") ? "none" : "grid";

          // Ruota la freccia usando CSS
          toggleIcon.classList.toggle("rotated");
        });

        main.appendChild(section);
      });
    })
    .catch(err => console.error(err));
});

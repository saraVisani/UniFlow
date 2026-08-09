let codes = 0;
let promotoriOriginali = [];
let promotoriDaRimuovere = [];
let promotoriDaAggiungere = [];
let promotoriModificare = [];
let componentiOriginali = [];
let componentiDaRimuovere = [];
let componentiDaAggiungere = [];
let componentiModificare = [];
let persone = [];

function reset(){
    promotoriOriginali = [];
    promotoriDaRimuovere = [];
    promotoriDaAggiungere = [];
    promotoriModificare = [];
    componentiOriginali = [];
    componentiDaRimuovere = [];
    componentiDaAggiungere = [];
    componentiModificare = [];
    persone = [];
    codes = 0;
}

function getCode(){
    if (codes === 0) {
        const lista = promotoriOriginali.concat(promotoriDaAggiungere);
        codes = lista.reduce((max, p) => Math.max(max, p.codice), 0) + 1;
    }
    return codes++;
}

function listPersona(persone) {
    return persone.map(p => `
            <option value="${p.codice}">
                ${p.nome} ${p.cognome} (${p.codice})
            </option>
        `).join("");
}

function renderMainEventi() {
    return `
    <header class="promotori-header">

        <h2 id="promotori-title">
            Azioni su Promotori:
        </h2>

        <div class="filters">
            <select id="selectAction">
            </select>
        </div>

    </header>


    <form id="azioni-form" method="post"></form>
    `;
}

function renderForum(json) {
    const action = json.action;
    const logged = json.logged;
    const level = logged ? json.level : 0;
    persone = json.persone;
    if (action === "nothing") {
        return `<p>Seleziona un'azione per visualizzare il forum.</p>`;
    }
    if(action === "add"){
        return `
            <label>Promotori</label>
            <ul id="lista-promotori">
                ${renderListPromotori("add")}
            </ul>
            <label>Nome Promotore</label>
            <input type="text" id="nome"/>
            <label>Email</label>
            <input type="email" id="email"/>
            <button type="button" onclick="addPromotore('${json.action}')">
                Aggiungi Promotore
            </button>
            <br>
            <button type="button" onclick="saveAllChanges()">
                Invia richiesta
            </button>
        `;
    }
    let html = "";
    if(level === 0){
        html = `
            <label>Persona</label>
            <select id="persona-select">
                <option value="">
                    -- Seleziona una persona --
                </option>
                ${listPersona(persone)}
            </select>
            <div id="promotori-content"></div>
        `;
    } else {
        promotoriOriginali = json.promotori;
        componentiOriginali = json.promotori.flatMap(p => p.componenti);
        html = `
            <label>Promotori</label>
            <ul id="lista-promotori">
                ${renderListPromotori(action)}
            </ul>
        `;
        if(action === "edit"){
            html += `
                <label>Nome Promotore</label>
                <input type="text" id="nome"/>
                <label>Email</label>
                <input type="email" id="email"/>
                <button type="button" onclick="addPromotore('${json.action}')">
                    Aggiungi Promotore
                </button>
            `;
        }
        html += `
            <br>
            <button type="button" onclick="saveAllChanges()">
                Invia richiesta
            </button>
        `;
    }
    return html;
}

function renderDetails(json){
    promotoriOriginali = json.promotori;
    componentiOriginali = json.promotori.flatMap(p => p.componenti);
    persone = json.persone;
    let html = `
        <label>Promotori</label>
        <ul id="lista-promotori">
            ${renderListPromotori(json.action)}
        </ul>
    `;
    if(json.action === "edit"){
        html += `
            <label>Nome Promotore</label>
            <input type="text" id="nome"/>
            <label>Email</label>
            <input type="email" id="email"/>
            <button type="button" onclick="addPromotore('${json.action}')">
                Aggiungi Promotore
            </button>
        `;
    }
    html += `
        <br>
        <button type="button" onclick="saveAllChanges()">
            Invia richiesta
        </button>
    `;

    return html;
}

async function loadDetail() {
    const persona = document.getElementById("persona-select").value;

    if (!persona)
        return;

    const azione = document.getElementById("selectAction").value;

    const res = await fetch(
        `./Api/api-azioniPromotori.php?azione=${azione}&user=${persona}`
    );

    const json = await res.json();

    document.getElementById("promotori-content").innerHTML =
        renderDetails(json);
}

async function loadAzioni() {

    reset();

    const azione = document.getElementById("selectAction").value;

    const url = azione === ""
    ? "./Api/api-azioniPromotori.php"
    : `./Api/api-azioniPromotori.php?azione=${azione}`;

    const res = await fetch(url);
    const json = await res.json();

    if(json.select){
        let select = document.getElementById("selectAction");

        select.innerHTML = "";
        Object.entries(json.select).forEach(([value, text]) => {
            select.innerHTML += `<option value="${value}">${text}</option>`;
        });
    } else {
        document.getElementById("azioni-form").innerHTML = renderForum(json);

        let select = document.getElementById("persona-select");

        if (select) {
            select.addEventListener("change", loadDetail);
        }
    }
}

function renderListPromotori(action) {
    let lista;

    switch (action) {

        case "add":
            lista = promotoriDaAggiungere;
            break;

        case "edit":
            lista = promotoriOriginali
                .map(o =>
                    promotoriModificare.find(m =>
                        m.codice === o.codice
                    ) ?? o)
                .concat(promotoriDaAggiungere);
            break;

        case "delete":
            lista = promotoriOriginali;
            break;
    }

    return lista.map(prom => {

        const eliminata = promotoriDaRimuovere.includes(prom.codice);

        let buttons = "";

        if (eliminata) {

            buttons = `
                <button onclick="restorePromotore('${action}', ${prom.codice})">
                    Annulla
                </button>
            `;

        } else {

            if (action !== "delete") {
                buttons += `
                    <button onclick="editPromotore('${action}', ${prom.codice})">
                        Modifica Promotore
                    </button>
                `;
            }

            buttons += `
                <button onclick="removePromotore('${action}', ${prom.codice})">
                    Elimina Promotore
                </button>
            `;
        }

        let controlli = "";

        if (action !== "delete") {
            controlli = `
                <label>Persona</label>

                <select id="persona-select-${prom.codice}">
                    <option value="">
                        -- Seleziona una persona --
                    </option>
                    ${listPersona(persone)}
                </select>

                <button
                    type="button"
                    id="add-componente-${prom.codice}"
                    onclick='addComponente("${action}", ${prom.codice})'>
                    Aggiungi componente
                </button>
            `;
        }

        return `
            <li id="promotore-${prom.codice}" class="${eliminata ? "pending-delete" : ""}">

                <p>Codice: ${prom.codice}</p>
                <p>Nome: ${prom.nome}</p>
                <p>Email: ${prom.email}</p>

                <p>Lista componenti correnti</p>

                <ul id="lista-componenti-${prom.codice}">
                    ${renderListaComponente(action, prom.codice)}
                </ul>

                ${controlli}

                ${buttons}

            </li>
        `;
    }).join("");
}

function renderListaComponente(action, promotoreCodice) {
    let lista;

    switch (action) {

        case "add":
            lista = componentiDaAggiungere;
            break;

        case "edit":
            lista = componentiOriginali
                .map(o =>
                    componentiModificare.find(m =>
                        m.codice === o.codice
                    ) ?? o)
                .concat(componentiDaAggiungere);
            break;

        case "delete":
            lista = componentiOriginali;
            break;
    }
    lista = lista.filter(c => c.codice === promotoreCodice);

    return lista.map(comp => {
        const eliminata = componentiDaRimuovere.some(r => r.codice === comp.codice && r.cf === comp.cf);
        let buttons = "";

        if (eliminata) {
            buttons = `
                <button onclick="restoreComponente('${action}', ${comp.codice}, ${comp.cf})">
                    Annulla
                </button>
            `;
        } else {
            if (action !== "delete") {
                buttons += `
                    <button onclick="editComponente('${action}', ${comp.codice}, ${comp.cf})">
                        Modifica Componente
                    </button>
                `;
            }
            buttons += `
                <button onclick="removeComponente('${action}', ${comp.codice}, ${comp.cf})">
                    Elimina Componente
                </button>
            `;
        }
        return `
            <li id="componente-${comp.codice}-${comp.cf}" class="${eliminata ? "pending-delete" : ""}">

                <p>CF: ${comp.cf}</p>
                <p>Nome: ${comp.nome}</p>
                <p>Cognome: ${comp.cognome}</p>
                <p>Email: ${comp.email}</p>

                ${buttons}

            </li>
        `;
    }).join("");
}

function addPromotore(action){
    const nome = document.getElementById("nome").value.trim();
    const email = document.getElementById("email").value.trim();

    let mancanti = [];
    if(!nome) mancanti.push("Nome");
    if(!email) mancanti.push("Email");

    if(mancanti.length > 0){
        alert("Compila i seguenti campi: " + mancanti.join(", "));
        return;
    }

    if (!email.includes("@")) {
        alert("Email non valida");
        return;
    }

    const nuovoPromotore = {
        codice: getCode(),
        nome: nome,
        email: email
    };

    promotoriDaAggiungere.push(nuovoPromotore);
    refresh("lista-promotori", renderListPromotori, action);
}

function restorePromotore(action, codice) {
    promotoriDaRimuovere = promotoriDaRimuovere.filter(p => p !== codice);
    refresh("lista-promotori", renderListPromotori, action);
}

function removePromotore(action, codice) {
    if(promotoriDaAggiungere.some(p => p.codice === codice)){
        promotoriDaAggiungere = promotoriDaAggiungere.filter(p => p.codice !== codice);
    } else if (promotoriModificare.some(p => p.codice === codice)){
        promotoriModificare = promotoriModificare.filter(p => p.codice !== codice);
    } else {
        promotoriDaRimuovere.push(codice);
    }
    refresh("lista-promotori", renderListPromotori, action);
}

function editPromotore(action, codice) {
    const html = document.getElementById(`promotore-${codice}`);
    let prom;

    if(promotoriDaAggiungere.some(p => p.codice === codice)){
        prom = promotoriDaAggiungere.find(p => p.codice === codice);
    } else if(promotoriModificare.some(p => p.codice === codice)){
        prom = promotoriModificare.find(p => p.codice === codice);
    } else {
        prom = promotoriOriginali.find(p => p.codice === codice);
    }

    html.innerHTML = `
            <p>Codice: ${prom.codice}</p>
            <label>Modifica nome</label>
            <input type="text" id="nome-${prom.codice}" value="${prom.nome}"/>
            <label>Modifica email</label>
            <input type="email" id="email-${prom.codice}" value="${prom.email}"/>

            <p>Lista componenti correnti</p>

            <ul id="lista-componenti-${prom.codice}">
                ${renderListaComponente(action, prom.codice)}
            </ul>

            <label>Persona</label>

            <select id="persona-select-${prom.codice}">
                <option value="">
                    -- Seleziona una persona --
                </option>
                ${listPersona(persone)}
            </select>

            <button
                type="button"
                id="add-componente-${prom.codice}"
                onclick='addComponente("${action}", ${prom.codice})'>
                Aggiungi componente
            </button>

            <button onclick="confirmEditPromotore('${action}', ${codice})">
                Conferma
            </button>
    `;
}

function confirmEditPromotore(action, codice){
    const prom = promotoriModificare.find(p => p.codice === codice)
                ?? promotoriOriginali.find(p => p.codice === codice)
                ?? promotoriDaAggiungere.find(p => p.codice === codice);

    if(!prom){
        alert("Promotore non trovato");
        return;
    }
    const nome = document.getElementById(`nome-${codice}`).value.trim();
    const email = document.getElementById(`email-${codice}`).value.trim();

    if((!nome && !email) || (nome === prom.nome && email === prom.email)){
        alert("Nessuna modifica effettuata");
        return;
    }

    if (!email.includes("@")) {
        alert("Email non valida");
        return;
    }

    const nuovoPromotore = {
        codice: prom.codice,
        nome: nome || prom.nome,
        email: email || prom.email
    };

    if(promotoriDaAggiungere.some(p => p.codice === codice)){
        promotoriDaAggiungere = promotoriDaAggiungere.filter(p => p.codice !== codice);
        promotoriDaAggiungere.push(nuovoPromotore);
    } else {
        promotoriModificare = promotoriModificare.filter(p => p.codice !== codice);
        promotoriModificare.push(nuovoPromotore);
    }

    refresh("lista-promotori", renderListPromotori, action);
}

function addComponente(action, promotoreCodice){
    const select = document.getElementById(`persona-select-${promotoreCodice}`);
    const cf = select.value;

    if(!cf){
        alert("Seleziona una persona");
        return;
    }

    const persona = persone.find(p => p.codice === cf);

    if(!persona){
        alert("Persona non trovata");
        return;
    }

    if (componentiOriginali.some(c => c.codice === promotoreCodice && c.cf === cf) ||
        componentiDaAggiungere.some(c => c.codice === promotoreCodice && c.cf === cf)
    ) {
        alert("Questa persona è già presente.");
        return;
    }

    const nuovoComponente = {
        codice: promotoreCodice,
        cf: persona.codice,
        nome: persona.nome,
        cognome: persona.cognome,
        email: persona.email
    };

    componentiDaAggiungere.push(nuovoComponente);
    refresh("lista-componenti-" + promotoreCodice, renderListaComponente, action, promotoreCodice);
}

function restoreComponente(action, codice, cf){
    componentiDaRimuovere = componentiDaRimuovere.filter(c => !(c.codice === codice && c.cf === cf));
    refresh("lista-componenti-" + codice, renderListaComponente, action, codice);
}

function removeComponente(action, codice, cf) {
    if(componentiDaAggiungere.some(c => c.codice === codice && c.cf === cf)){
        componentiDaAggiungere = componentiDaAggiungere.filter(c => !(c.codice === codice && c.cf === cf));
    } else if (componentiModificare.some(c => c.codice === codice && c.cf === cf)){
        componentiModificare = componentiModificare.filter(c => !(c.codice === codice && c.cf === cf));
    } else {
        componentiDaRimuovere.push({ codice: codice, cf: cf });
    }
    refresh("lista-componenti-" + codice, renderListaComponente, action, codice);
}

function editComponente(action, codice, cf){
    const html = document.getElementById(`componente-${codice}-${cf}`);
    let comp;

    if(componentiDaAggiungere.some(c => c.codice === codice && c.cf === cf)){
        comp = componentiDaAggiungere.find(c => c.codice === codice && c.cf === cf);
    } else if(componentiModificare.some(c => c.codice === codice && c.cf === cf)){
        comp = componentiModificare.find(c => c.codice === codice && c.cf === cf);
    } else {
        comp = componentiOriginali.find(c => c.codice === codice && c.cf === cf);
    }

    html.innerHTML = `
            <p>CF: ${comp.cf}</p>
            <label>Modifica nome</label>
            <input type="text" id="nome-${comp.codice}-${comp.cf}" value="${comp.nome}"/>
            <label>Modifica cognome</label>
            <input type="text" id="cognome-${comp.codice}-${comp.cf}" value="${comp.cognome}"/>
            <label>Modifica email</label>
            <input type="email" id="email-${comp.codice}-${comp.cf}" value="${comp.email}"/>

            <button onclick="confirmEditComponente('${action}', ${codice}, ${cf})">
                Conferma
            </button>
    `;
}

function confirmEditComponente(action, codice, cf){
    const comp = componentiModificare.find(c => c.codice === codice && c.cf === cf)
                ?? componentiOriginali.find(c => c.codice === codice && c.cf === cf)
                ?? componentiDaAggiungere.find(c => c.codice === codice && c.cf === cf);

    if(!comp){
        alert("Componente non trovato");
        return;
    }
    const nome = document.getElementById(`nome-${codice}-${cf}`).value.trim();
    const cognome = document.getElementById(`cognome-${codice}-${cf}`).value.trim();
    const email = document.getElementById(`email-${codice}-${cf}`).value.trim();

    if((!nome && !cognome && !email) || (nome === comp.nome && cognome === comp.cognome && email === comp.email)){
        alert("Nessuna modifica effettuata");
        return;
    }

    if (!email.includes("@")) {
        alert("Email non valida");
        return;
    }

    const nuovoComponente = {
        codice: comp.codice,
        cf: comp.cf,
        nome: nome || comp.nome,
        cognome: cognome || comp.cognome,
        email: email || comp.email
    };

    if(componentiDaAggiungere.some(c => c.codice === codice && c.cf === cf)){
        componentiDaAggiungere = componentiDaAggiungere.filter(c => !(c.codice === codice && c.cf === cf));
        componentiDaAggiungere.push(nuovoComponente);
    }
    else{
        componentiModificare = componentiModificare.filter(c => !(c.codice === codice && c.cf === cf));
        componentiModificare.push(nuovoComponente);
    }

    refresh("lista-componenti-" + codice, renderListaComponente, action, codice);
}

function refresh(id, fun, ...args) {
    document.getElementById(id).innerHTML = fun(...args);
}

async function saveAllChanges() {
    const action = document.getElementById("selectAction").value;
    const dati = {
        action
    };

    switch(action) {
        case "add":
            if (promotoriDaAggiungere.length === 0 && componentiDaAggiungere.length === 0) {
                alert("Aggiungi almeno un promotore o componente prima di inviare la richiesta.");
                return;
            }
            Object.assign(dati, {promotoriDaAggiungere, componentiDaAggiungere});
            break;
        case "edit":
            if (promotoriDaAggiungere.length === 0 && componentiDaAggiungere.length === 0
                && promotoriModificare.length === 0 && componentiModificare.length === 0
                && promotoriDaRimuovere.length === 0 && componentiDaRimuovere.length === 0
            ) {
                alert("Fai una qualche modifica prima di inviare la richiesta.");
                return;
            }
            Object.assign(dati, {promotoriDaAggiungere, componentiDaAggiungere, promotoriModificare, componentiModificare, promotoriDaRimuovere, componentiDaRimuovere});
            break;
        case "delete":
            if (promotoriDaRimuovere.length === 0 && componentiDaRimuovere.length === 0) {
                alert("Rimuovi almeno un promotore o componente prima di inviare la richiesta.");
                return;
            }
            if (!confirm("Sei sicuro di voler eliminare questo evento?"))
                return;
            Object.assign(dati, {promotoriDaRimuovere, componentiDaRimuovere});
            break;
    }

    try{
        const res = await fetch("./Api/api-savePromotore.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(dati)
        });

        const json = await res.json();

        alert(json.message);

        if (json.success) {
            await loadAzioni();
        }
    } catch (error) {
        console.error("Errore durante l'invio della richiesta:", error);
        alert("Si è verificato un errore durante l'invio della richiesta. Controlla la console per ulteriori dettagli.");
    }
}

// --- Avvio ---
const main = document.querySelector("main");

main.innerHTML = renderMainEventi();

document
    .getElementById("selectAction")
    .addEventListener("change", loadAzioni);

loadAzioni();
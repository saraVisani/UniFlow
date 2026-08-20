let first = true;
let originals = [];
let adds = [];
let deletes = [];
let edits = [];
let uffTot = [];
let profTot = [];
let inlineEdits = {};
let codStart = 0;

function reset(){
    first = true;
    originals = [];
    adds = [];
    deletes = [];
    edits = [];
    uffTot = [];
    profTot = [];
    inlineEdits = {};
    codStart = 0;
}

function getCode(){
    if (codStart === 0) {
        const lista = originals.concat(adds);
        codStart = lista.reduce((max, p) => Math.max(max, p.codice), 0) + 1;
    }
    return codStart++;
}

function listPerson(person) {
    return person ? `
        <option value="">-- Seleziona un persona --</option>
        ${person.map(p => `
            <option value="${p.matr}">
                ${p.nome} ${p.cognome} (${p.email})
            </option>
        `).join("")}` : "";
}

function listUff(list){
    return `
        <option value="">-- Seleziona un ufficio --</option>
        ${list.map(l => `
            <option value="${l.codice}">
                ${l.nome_sede} ${l.nome_stanza} c: ${l.capienza} (${l.indirizzo})
            </option>
        `).join("")}
    `;
}

function formatDateTime(date) {
    return date.toLocaleString("it-IT", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit"
    });
}

function aggiornaUffici(prof, online, uff, uffici) {
    if ((prof && prof.value === "") || online.checked) {
        uff.innerHTML = "";
        uff.disabled = true;
    } else {
        if(!prof){
            uff.innerHTML = listUff(uffici);
        } else {
            const selUff = uffici.filter(u => Number(u.matricola) === Number(prof.value));
            uff.innerHTML = listUff(selUff);
        }
        uff.disabled = false;
    }
}

function renderMainEventi() {
    return `
    <header class="ricevimento-header">

        <h2 id="ricevimento-title">
            Azioni su Ricevimento:
        </h2>

        <div class="filters">
            <select id="selectAction">
            </select>
        </div>

    </header>


    <form id="azioni-form" method="post"></form>
    `;
}

function renderForum(json){
    let action = json.action;
    if(action === "nothing"){
        return `<p>Seleziona un'azione per visualizzare il form.</p>`;
    }
    let html;
    if(action === "addRicevimento"){
        let option = "";
        if(json.level === 4){
            profTot = json.professori;
            const prof = listPerson(json.professori);
            option = `
            <div>
                <label for="prof">Professore titolare:</label>
                <select id="prof" name="prof">
                ${prof}
                </select>
            </div>
            `;
        }
        html = `
            <fieldset id="ricevimenti">
                <legend id="legend-ricevimenti"></legend>
                <div>
                    <h3 id="titolo-lista-ricevimenti"></h3>
                    <ul id="lista-ricevimenti">${prepareListReunion(action, json)}</ul>
                </div>
                <div>
                    <label for="online">Ricevimento Online:</label>
                    <input type="checkbox" id="online" name="online"/>
                </div>
                ${option}
                <div>
                    <label for="uff">Ufficio:</label>
                    <select id="uff" name="uff">
                    </select>
                </div>
                <div>
                    <label for="dateInizio">Data Inizio:</label>
                    <input type="datetime-local" id="dateInizio" name="dateInizio">
                </div>
                <div>
                    <label for="dateFine">Data Fine:</label>
                    <input type="datetime-local" id="dateFine" name="dateFine">
                </div>
                <div>
                    <label for="posti">Numero di Posti Disponibili:</label>
                    <input type="number" id="posti" name="posti" min=1/>
                </div>
                <div class="saveAdd">
                    <button type="button" onclick="addRicevimento('${action}')">
                        Aggiungi Ricevimento
                    </button>
                </div>
                <div class="save">
                    <button type="button" onclick="saveAllChanges(${json.level})">
                        Invia richiesta
                    </button>
                </div>
            </fieldset>`;
    } else {
        const prof = listPerson(json.professori);
        const stud = listPerson(json.studenti);
        let fielsets = "";
        let extra = ``;
        let timeSet = `<div>
                        <label for="date">Data:</label>
                        <input type="date" id="date" name="date"/>
                        </div>
                        <div>
                        <label for="range">Periodo:</label>
                        <select id="range" name="range">
                            <option value="giorno">
                                Giornata
                            </option>
                            <option value="settimana">
                                Settimana
                            </option>
                            <option value="mese">
                                Mese
                            </option>
                        </select>
                        </div>`;

        if(json.level <= 3) {
            if(action !== "delete"){
                extra = `
                    <div>
                    <label for="prof">Professore:</label>
                    <select id="prof" name="prof">
                    ${prof}
                    </select>
                    </div>`;
            }
        } else {
            switch(action){
                case "edit":
                case "add": {
                    extra = `
                        <div>
                        <label for="stud">Studente:</label>
                        <select id="stud" name="stud">
                        ${stud}
                        </select>
                        </div>
                        <div>
                        <label for="prof">Professore:</label>
                        <select id="prof" name="prof">
                        ${prof}
                        </select>
                        </div>`;
                    break;
                }
                case "delete":{
                    extra = `
                        <div>
                        <label for="stud">Studente:</label>
                        <select id="stud" name="stud">
                        ${stud}
                        </select>
                        </div>`;
                    break;
                }
                case "editRicevimento":
                case "deleteRicevimento":{
                    extra = `
                        <div>
                        <label for="prof">Professore:</label>
                        <select id="prof" name="prof">
                        ${prof}
                        </select>
                        </div>`;
                    break;
                }
            }
        }
        const selects = `
                <fieldset id="selections">
                    <legend>Dati di Ricerca</legend>
                    ${timeSet}
                    ${extra}
                </fieldset>
            `;
        const mostraInsert = action === "editRicevimento";
        if (["add", "edit", "delete", "editRicevimento", "deleteRicevimento"].includes(action)) {
            fielsets = `
                <fieldset id="ricevimenti">
                    <legend id="legend-ricevimenti"></legend>
                    <div>
                        <h3 id="titolo-lista-ricevimenti"></h3>
                        <ul id="lista-ricevimenti">${prepareListReunion(action, json)}</ul>
                    </div>
                    ${mostraInsert ? `${renderAdd(action, json)}` : ""}
                    <div class="save">
                        <button type="button" onclick="saveAllChanges(${json.level})">
                            Invia richiesta
                        </button>
                    </div>
                </fieldset>`;
        }

        html = `${selects}
                ${fielsets}`;
    }
    return html;
}

function renderAdd(action, json){
    let option = "";
    if(json.level === 4){
        profTot = json.professori;
        const prof = listPerson(json.professori);
        option = `
        <div>
            <label for="prof">Professore titolare:</label>
            <select id="prof" name="prof">
            ${prof}
            </select>
        </div>
        `;
    }
    return `
        <div>
            <label for="online">Ricevimento Online:</label>
            <input type="checkbox" id="online" name="online"/>
        </div>
        ${option}
        <div>
            <label for="uff">Ufficio:</label>
            <select id="uff" name="uff">
            </select>
        </div>
        <div>
            <label for="dateInizio">Data Inizio:</label>
            <input type="datetime-local" id="dateInizio" name="dateInizio">
        </div>
        <div>
            <label for="dateFine">Data Fine:</label>
            <input type="datetime-local" id="dateFine" name="dateFine">
        </div>
        <div>
            <label for="posti">Numero di Posti Disponibili:</label>
            <input type="number" id="posti" name="posti" min=1/>
        </div>
        <div class="saveAdd">
            <button type="button" onclick="addRicevimento('${action}')">
                Aggiungi Ricevimento
            </button>
        </div>`;
}

async function loadAzioni() {
    reset();
    const azione = document.getElementById("selectAction").value;

    let url = "./Api/api-azioniRicevimento.php";
    if (azione !== "") {
        url += `?azione=${encodeURIComponent(azione)}`;
    }

    const res = await fetch(url);
    const json = await res.json();

    if (!json.success && json.redirect) {
        window.location.href = json.redirect;
        return;
    }

    if (azione === "view") {
        window.location.href = "ricevimentiPersona.php";
        return;
    }

    if(json.select){
        let select = document.getElementById("selectAction");

        select.innerHTML = "";
        Object.entries(json.select).forEach(([value, text]) => {
            select.innerHTML += `<option value="${value}">${text}</option>`;
        });
    } else {
        document.getElementById("azioni-form").innerHTML = renderForum(json);
        const range = document.getElementById("range");
        const data = document.getElementById("date");
        const prof = document.getElementById("prof");
        const stud = document.getElementById("stud");
        const legend = document.getElementById("legend-ricevimenti");
        const title = document.getElementById("titolo-lista-ricevimenti");
        function changeTitle(mtr, type, extra = "", x = null){
            const p = type.find(
                p => p.matr == mtr.value
            );
            if (!p) return;
            (x ?? title).innerHTML = `${extra}${p.nome} ${p.cognome} ${p.email}`;
        }
        function attachListener(lx, lfun = [], largs = []) {
            let lastFun = null;
            let lastArgs = [];
            lx.forEach((element, i) => {
                if (lfun[i] !== undefined) {
                    lastFun = lfun[i];
                }
                if (largs[i] !== undefined) {
                    lastArgs = largs[i];
                }
                if (element && lastFun) {
                    element.addEventListener("change", () => {
                        lastFun(...lastArgs);
                    });
                }
            });
        }
        attachListener([range, data, prof, stud], [aggiornaLista], [[]]);
        switch(azione){
            case "addRicevimento":{
                const uff = document.getElementById("uff");
                const online = document.getElementById("online");
                attachListener([online, prof], [aggiornaUffici], [[prof, online, uff, json.uffici]]);
                aggiornaUffici(prof, online, uff, json.uffici);
                if(json.level == 4){
                    title.innerHTML = `Lista Da Salvare`;
                    legend.innerHTML = "Ricevimenti";
                } else {
                    title.innerHTML = `I Tuoi Ricevimenti`;
                    legend.innerHTML = "Lista Ricevimenti";
                }
                break;
            }
            default:{
                let studOK = !!stud && stud.value !== "";
                let profOK = !!prof && prof.value !== "";
                function disableAndFocus(id, response){
                    if(!first)alert(response);
                    first = false;
                    const fieldset = document.getElementById("ricevimenti");
                    fieldset.disabled = true;
                    document.getElementById(id).focus();
                }
                function checkSelects(l){
                    if(!!l && l.value !== ""){
                        const fieldset = document.getElementById("ricevimenti");
                        fieldset.disabled = false;
                    } else {
                        studOK = !!stud && stud.value !== "";
                        profOK = !!prof && prof.value !== "";
                        switch(azione){
                            case "add":
                            case "edit": {
                                if(json.level == 4){
                                    if(!studOK){
                                        disableAndFocus("stud", "Scegliere quale studente");
                                        first = true;
                                    } else if(!profOK){
                                        disableAndFocus("prof", "Scegliere quale professore");
                                    }
                                } else {
                                    if(!profOK){
                                        disableAndFocus("prof", "Scegliere quale professore");
                                    }
                                }
                                break;
                            }
                            case "delete": {
                                if(json.level == 4){
                                    if(!studOK){
                                        disableAndFocus("stud", "Scegliere quale studente");
                                    }
                                }
                                break;
                            }
                            case "deleteRicevimento":
                            case "editRicevimento":{
                                if(json.level == 4){
                                    if(!profOK){
                                        disableAndFocus("prof", "Scegliere quale professore");
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
                attachListener([stud, prof], [checkSelects], [[stud], [prof]]);

                if(azione === "editRicevimento"){
                    const uff = document.getElementById("uff");
                    const online = document.getElementById("online");
                    attachListener([online, prof], [aggiornaUffici], [[prof, online, uff, json.uffici]]);
                    aggiornaUffici(prof, online, uff, json.uffici);
                    break;
                }

                let contP = (azione.endsWith("Ricevimento") && json.level == 4) || ["add", "edit"].includes(azione);
                let contS = ["add", "edit", "delete"].includes(azione) && json.level == 4;
                if(contP){
                    let str = "";
                    if(["add", "edit"].includes(azione)){
                        str = "Ricevimenti di ";
                        if(profOk) changeTitle(prof, json.professori, str);
                    } else {
                        if(profOk) changeTitle(prof, json.professori);
                        title.innerHTML = `Lista Da Salvare`;
                    }
                    attachListener([prof], [changeTitle], [[prof, json.professori, str]]);
                } else if(azione.endsWith("Ricevimento")) legend.innerHTML = "Ricevimenti";
                if(contS){
                    let str = "";
                    let l = null;
                    if(azione === "delete"){
                        if(studOK) changeTitle(stud, json.studenti);
                        legend.innerHTML = "Lista Appuntamenti";
                    } else {
                        str = "Disponibilità per ";
                        l = legend;
                        if(studOK) changeTitle(stud, json.studenti, str, l);
                    }
                    attachListener([stud], [changeTitle], [[stud, json.studenti, str, l]]);
                } else {
                    if(azione === "delete"){
                        title.innerHTML = `Lista`;
                        legend.innerHTML = "I Tuoi Appuntamenti";
                    } else {
                        legend.innerHTML = "Lista Ricevimenti Disponibili";
                    }
                }
            }
        }
    }
}

async function aggiornaLista() {
    const azione = document.getElementById("selectAction").value;
    const range = document.getElementById("range");
    const data = document.getElementById("date");
    const prof = document.getElementById("prof");
    const stud = document.getElementById("stud");

    let url = "./Api/api-azioniRicevimento.php";
    url += `?azione=${encodeURIComponent(azione)}`;
    if (data) url += `&date=${encodeURIComponent(data.value)}`;
    if (range) url += `&range=${encodeURIComponent(range.value)}`;
    if (stud) url += `&stud=${encodeURIComponent(stud.value)}`;
    if (prof) url += `&prof=${encodeURIComponent(prof.value)}`;

    const res = await fetch(url);
    const json = await res.json();
    if (!json.success && json.redirect) {
        window.location.href = json.redirect;
        return;
    }
    document.getElementById("lista-ricevimenti").innerHTML =
        prepareListReunion(azione, json);
}

function prepareListReunion(action, json){
    if(!json.ok) return "";
    if(["add", "edit"].includes(action)){
        if(action === "edit"){
            originals = json.ricevimenti.map(r => ({
                    ...r,
                    appuntamento_user: json.appuntamenti_user.find(a => Number(a.codice_ric) === Number(r.codice)),
                    appuntamento: json.appuntamenti.filter(a => Number(a.codice_ric) === Number(r.codice))
                }));
        } else {
            originals = json.ricevimenti.filter(
                    r => !json.appuntamenti_user.some(
                        a => Number(a.codice_ric) === Number(r.codice)))
                .map(r => ({
                    ...r,
                    appuntamento: json.appuntamenti.filter(a => Number(a.codice_ric) === Number(r.codice))
            }));
        }
        return listSlots(action);
    } else {
        if(action === "delete"){
            originals = json.ricevimenti
                .filter(r => json.appuntamenti_user.some(a => Number(a.codice_ric) === Number(r.codice)))
                .map(r => ({
                    ...r,
                    appuntamento: json.appuntamenti_user.find(a => Number(a.codice_ric) === Number(r.codice))
                }));
        } else {
            if(action !== "addRicevimento"){
                originals = json.ricevimenti;
            }
            uffTot = json.uffici;
        }
        return listRicevimenti(action);
    }
}

function listRicevimenti(action){
    let list;
    switch(action){
        case "addRicevimento":
            list = adds;
            break;
        case "editRicevimento":
            list = originals
                .map(r => edits.find(e => Number(e.codice) === Number(r.codice)) ?? r)
                .concat(adds);
            break;
        case "delete":
        case "deleteRicevimento":
            list = originals;
            break;
    }

    return list.map(l => {
        let elim = deletes.includes(l.codice);
        let buttons = "";
        if (elim) {
            if(action === "delete"){
                buttons += `
                    <button onclick="restoreSlot('${action}', ${l.codice}, ${l.appuntamento.n_slot})">
                        Annulla
                    </button>
                `;
            } else {
                buttons = `
                    <button onclick="restoreRicevimento('${action}', ${l.codice})">
                        Annulla
                    </button>
                `;
            }

        } else {

            if (!action.startsWith("delete")) {
                buttons += `
                    <button onclick="editRicevimento('${action}', ${l.codice})">
                        Modifica Ricevimento
                    </button>
                `;
            }

            if(action === "delete"){
                buttons += `
                    <button onclick="removeSlot('${action}', ${l.codice}, ${l.appuntamento.n_slot})">
                        Elimina Appuntamento
                    </button>
                `;
            } else {
                buttons += `
                    <button onclick="removeRicevimento('${action}', ${l.codice})">
                        Elimina Ricevimento
                    </button>
                `;
            }
        }
        let title = "";
        let info = "";
        const inizio = new Date(l.data_inizio);
        const fine = new Date(l.data_fine);

        if(["delete", "addRicevimento"].includes(action) && document.getElementById("titolo-lista-ricevimenti").textContent !== "I Tuoi Ricevimenti"){
            title = `Professore ${l.nome} ${l.cognome} ${l.email}`;
            info = `<div>
                        <dt>Inizio</dt>
                        <dd>${formatDateTime(inizio)}</dd>
                    </div>

                    <div>
                        <dt>Fine</dt>
                        <dd>${formatDateTime(fine)}</dd>
                    </div>`
        } else {
            title = `${formatDateTime(inizio)} - ${formatDateTime(fine)}`;
        }

        const ufficio = Number(l.online) === 0
        ? uffTot.find(u =>
            Number(u.cod_uni) === Number(l.cod_uni) &&
            Number(u.cod_stanza) === Number(l.cod_stanza)
        )
        : null;

        let render =`
            <h4 id="title-${l.codice}">${title}</h4>

            <dl>
                ${info}
                <div>
                    <dt>Posti disponibili</dt>
                    <dd>${l.n_slot}</dd>
                </div>

                <div>
                    <dt>Modalità</dt>
                    <dd>${Number(l.online) === 1 ? "Online" : "In presenza"}</dd>
                </div>
            </dl>

            ${ufficio ? `
                <section>
                    <h5 id="ufficio-${l.codice}">Ufficio</h5>

                    <dl>
                        <div>
                            <dt>Stanza</dt>
                            <dd>${ufficio.nome_stanza}</dd>
                        </div>

                        <div>
                            <dt>Capienza</dt>
                            <dd>${ufficio.capienza}</dd>
                        </div>

                        <div>
                            <dt>Indirizzo</dt>
                            <dd>${ufficio.indirizzo}</dd>
                        </div>
                    </dl>
                </section>
            ` : ""}
            `;
        if(action === "delete"){
            const durata = (fine - inizio) / l.n_slot;
            const inizioAppuntamento = new Date(
                inizio.getTime() + durata * (l.appuntamento.slot - 1)
            );
            const fineAppuntamento = new Date(
                inizioAppuntamento.getTime() + durata
            );
            render += `
                <section>
                    <h5>Appuntamento</h5>

                    <dl>
                        <div>
                            <dt>Studente</dt>
                            <dd>${l.appuntamento.nome} ${l.appuntamento.cognome}</dd>
                        </div>

                        <div>
                            <dt>Email</dt>
                            <dd>${l.appuntamento.email}</dd>
                        </div>

                        <div>
                            <dt>Orario</dt>
                            <dd>
                                ${formatDateTime(inizioAppuntamento)}
                                -
                                ${formatDateTime(fineAppuntamento)}
                            </dd>
                        </div>
                    </dl>
                </section>
            `;
        }

        return `<li id="ricevimento-${l.codice}" class="${elim ? "pending-delete" : ""}">

                ${render}

                ${buttons}

            </li>`;
    }).join("");
}

function listSlots(action){
    let list;
    let level = 0;
    switch(action){
        case "add":
            list = originals.map(r => ({
                ...r,
                appuntamento_user: adds.find(
                    a => Number(a.codice_ric) === Number(r.codice)
                ),
                appuntamento: [
                    ...r.appuntamento,
                    ...adds.filter(
                        a => Number(a.codice_ric) === Number(r.codice)
                    )
                ]
            }));
            break;
        case "edit":
            list = originals.map(r => ({
                ...r,

                appuntamento_user:
                    r.appuntamento_user
                        ?? adds.find(
                            a => Number(a.codice_ric) === Number(r.codice)
                        ),

                appuntamento: [
                    ...r.appuntamento,
                    ...adds.filter(
                        a => Number(a.codice_ric) === Number(r.codice)
                    )
                ]
            }));
            break;
    }

    level = document.getElementById("stud") ? 4 : 0;

    return list.map(r => {

        const inizio = new Date(r.data_inizio);
        const fine = new Date(r.data_fine);
        const ufficio = uffTot?.find(u =>
            Number(u.cod_uni) === Number(r.cod_uni) &&
            Number(u.cod_stanza) === Number(r.cod_stanza)
        );

        return `
            <li
                id="ricevimento-${r.codice}"
                class="ricevimento"
            >

                <h4>
                    ${formatDateTime(inizio)}
                    -
                    ${formatDateTime(fine)}
                </h4>

                <div>
                    <dt>Modalità</dt>
                    <dd>${Number(r.online) === 1 ? "Online" : "In presenza"}</dd>
                </div>

                ${Number(r.online) === 0 ? `
                    <section>
                        <h5>Ufficio</h5>

                        <dl>
                            <div>
                                <dt>Stanza</dt>
                                <dd>${ufficio?.nome_stanza ?? ""}</dd>
                            </div>

                            <div>
                                <dt>Capienza</dt>
                                <dd>${ufficio?.capienza ?? ""}</dd>
                            </div>

                            <div>
                                <dt>Indirizzo</dt>
                                <dd>${ufficio?.indirizzo ?? ""}</dd>
                            </div>
                        </dl>
                    </section>
                ` : ""}

                ${renderSlots(r, action, level)}

            </li>
        `;

    }).join("");
}

function renderSlots(r, action, level) {
    const inizio = new Date(r.data_inizio);
    const fine = new Date(r.data_fine);
    const durataSlot =
        (fine.getTime() - inizio.getTime()) / Number(r.n_slot);
    const slots = [];
    const addNelRicevimento = adds.some(a =>
        Number(a.codice_ric) === Number(r.codice)
    );
    for (let i = 1; i <= Number(r.n_slot); i++) {
        const inizioSlot = new Date(
            inizio.getTime() + durataSlot * (i - 1)
        );
        const fineSlot = new Date(
            inizioSlot.getTime() + durataSlot
        );
        /*
         * Appuntamento originale nello slot
         */
        const appuntamento = r.appuntamento?.find(a =>
            Number(a.slot) === i
        );
        /*
         * Modifica dello slot
         */
        const editOriginale = edits.find(e =>
            Number(e.codice_ric) === Number(r.codice) &&
            Number(e.slot) === i
        );
        const editNuovo = edits.find(e =>
            Number(e.codice_ric) === Number(r.codice) &&
            Number(e.nuovo_slot) === i
        );
        /*
         * Eliminazione dello slot
         */
        const elimina = deletes.find(e =>
            Number(e.codice_ric) === Number(r.codice) &&
            Number(e.slot) === i
        );
        /*
         * Appuntamento aggiunto
         */
        const aggiunto = adds.find(a =>
            Number(a.codice_ric) === Number(r.codice) &&
            Number(a.slot) === i
        );

        let tipo;
        let testo;
        let cliccabile = false;
        let stato;

        if (elimina) {
            tipo = "elimina";
            testo = "E";
            cliccabile = !addNelRicevimento;
            stato = addNelRicevimento
                ? "Eliminazione pendente, non ripristinabile perché esiste un nuovo appuntamento"
                : "Eliminazione pendente";
        } else if (editOriginale) {
            // Vecchia posizione dell'appuntamento modificato
            tipo = "elimina";
            testo = "E";
            cliccabile = false;
            stato = "Posizione originale dell'appuntamento, eliminazione pendente";
        } else if (aggiunto) {
            tipo = "studente";
            testo = "S";
            cliccabile = true;
            stato = level === 4
                ? "Occupato da uno studente"
                : "Tuo appuntamento";
        } else if (editNuovo) {
            // Nuova posizione dell'appuntamento modificato
            tipo = "modificato";
            testo = "M";
            cliccabile = true;
            stato = "Appuntamento modificato";
        } else if (appuntamento) {
            tipo = "occupato";
            testo = "O";
            cliccabile = false;
            stato = "Occupato";
        } else {
            tipo = "libero";
            testo = "L";
            cliccabile = true;
            stato = "Libero";
        }

        const dalle = formatDateTime(inizioSlot);
        const alle = formatDateTime(fineSlot);
        const clic = cliccabile
            ? ", cliccabile"
            : "";

        const aria =
            `Slot ${i}, ${stato}, dalle ${dalle} alle ${alle}${clic}`;

        const contenutoSlot = `
            <span aria-hidden="true" class="slot-date">${dalle}</span>
            <span aria-hidden="true" class="slot-separator"> - </span>
            <span aria-hidden="true" class="slot-date">${alle}</span>
            <span aria-hidden="true" class="slot-short">${testo}</span>
            <span aria-hidden="true" class="slot-long">${tipo}</span>
        `;

        if (cliccabile) {

            slots.push(`
                <button
                    type="button"
                    class="slot ${tipo}"
                    aria-label="${aria}"
                    onclick="selezionaSlot(
                        ${r.codice},
                        ${i},
                        '${action}'
                    )"
                >
                    ${contenutoSlot}
                </button>
            `);

        } else {

            slots.push(`
                <span
                    class="slot ${tipo}"
                    role="img"
                    aria-label="${aria}"
                >
                    ${contenutoSlot}
                </span>
            `);
        }
    }

    return `
        <div
            class="slot-griglia"
            aria-label="Griglia degli Appuntamenti"
        >
            ${slots.join("")}
        </div>
    `;
}

function selezionaSlot(idRic, idSlot, action){
    const ricevimento = originals.find(
        r => Number(r.codice) === Number(idRic)
    );
    if (!ricevimento) return;
    idRic = Number(idRic);
    idSlot = Number(idSlot);
    /*
     * Stato dello slot cliccato
     */
    const addIndex = adds.findIndex(a =>
        Number(a.codice_ric) === idRic &&
        Number(a.slot) === idSlot
    );
    const editIndex = edits.findIndex(e =>
        Number(e.codice_ric) === idRic &&
        Number(e.nuovo_slot) === idSlot
    );
    const editOriginaleIndex = edits.findIndex(e =>
        Number(e.codice_ric) === idRic &&
        Number(e.slot) === idSlot
    );
    const deleteIndex = deletes.findIndex(e =>
        Number(e.codice_ric) === idRic &&
        Number(e.slot) === idSlot
    );
    /*
     * Appuntamento originale nello slot
     */
    const appuntamento = ricevimento.appuntamento?.find(a =>
        Number(a.slot) === idSlot
    );
    /*
     * Appuntamento dell'utente originale.
     * NON consideriamo quello aggiunto:
     * quello è già gestito da adds.
     */
    const appuntamentoUser = ricevimento.appuntamento_user;
    /*
     * 1. SLOT AGGIUNTO
     * =====================================================
     * È un appuntamento creato da noi.
     * Cliccandolo lo rimuoviamo.
     */
    if (addIndex !== -1) {
        adds.splice(addIndex, 1);
    }
    /*
     * 2. NUOVO SLOT DI UN EDIT
     * =====================================================
     * Cliccando nuovamente il nuovo slot annulliamo
     * completamente la modifica.
     */
    else if (editIndex !== -1) {
        edits.splice(editIndex, 1);
    }
    /*
     * 3. SLOT CANCELLATO
     * =====================================================
     * Vogliamo ripristinare l'appuntamento originale.
     * Prima controlliamo che non ci sia un add nello stesso
     * slot.
     */
    else if (deleteIndex !== -1) {
        const occupatoDaAdd = adds.some(a => Number(a.codice_ric) === idRic);
        if (occupatoDaAdd) {
            return;
        }
        deletes.splice(deleteIndex, 1);
    }
    /*
     * 4. VECCHIA POSIZIONE DI UN EDIT
     * =====================================================
     * Questa normalmente non dovrebbe essere cliccabile,
     * ma la lasciamo protetta anche qui.
     */
    else if (editOriginaleIndex !== -1) {
        return;
    }
    /*
     * 5. APPUNTAMENTO DELL'UTENTE
     * =====================================================
     * Se è il suo appuntamento originale, lo eliminiamo.
     */
    else if (appuntamentoUser && Number(appuntamentoUser.slot) === idSlot) {
        deletes.push({
            codice_ric: idRic,
            slot: idSlot
        });
    }
    /*
     * 6. SLOT OCCUPATO DA UN ALTRO STUDENTE
     * =====================================================
     * Questa normalmente non dovrebbe essere cliccabile,
     * ma la lasciamo protetta anche qui.
     */
    else if (appuntamento) {
        return;
    }
    /*
     * 7. SLOT LIBERO
     * =====================================================
     */
    else {
        const addUserIndex = adds.findIndex(a =>
            Number(a.codice_ric) === idRic
        );
        /*
         * -------------------------------------------------
         * CASO A
         * L'utente ha già un appuntamento in ADDS.
         * -------------------------------------------------
         */
        if (addUserIndex !== -1) {
            adds[addUserIndex].slot = idSlot;
        }
        else {
            const editUserIndex = edits.findIndex(e =>
                Number(e.codice_ric) === idRic
            );
            /*
            * -------------------------------------------------
            * CASO B
            * L'utente ha già un EDIT.
            * -------------------------------------------------
            */
            if (editUserIndex !== -1) {
                edits[editUserIndex].nuovo_slot = idSlot;
            }
            /*
             * ---------------------------------------------
             * CASO C
             * L'utente ha un appuntamento originale.
             * ---------------------------------------------
             */
            else if (appuntamentoUser) {
                edits.push({
                    codice_ric: idRic,
                    slot: Number(appuntamentoUser.slot),
                    nuovo_slot: idSlot
                });
            }
            /*
             * ---------------------------------------------
             * CASO D
             * Non esiste alcun appuntamento dell'utente.
             * ---------------------------------------------
             */
            else {
                adds.push({
                    codice_ric: idRic,
                    slot: idSlot
                });
            }
        }
    }
    refresh("lista-ricevimenti", listSlots, action);
}

function restoreSlot(action, idRic, idSlot){
    deletes = deletes.filter(d => {!(Number(d.codice_ric) === Number(idRic) && Number(d.n_slot) === Number(idSlot))});
    refresh("lista-ricevimenti", listRicevimenti, action);
}

function removeSlot(action, idRic, idSlot){
    deletes.push({
        codice_ric: Number(idRic),
        n_slot: Number(idSlot)
    });
    refresh("lista-ricevimenti", listRicevimenti, action);
}

function restoreRicevimento(action, idRic){
    deletes = deletes.filter(!Number(idRic));
    refresh("lista-ricevimenti", listRicevimenti, action);
}

function removeRicevimento(action, idRic){
    if(adds.includes(a => {a.codice === Number(idRic)})){
        adds = adds.filter(d => {Number(d.codice) === Number(idRic)});
    } else if (edits.includes(a => {a.codice === Number(idRic)})){
        edits = edits.filter(d => {Number(d.codice) === Number(idRic)});
    } else {
        deletes.push(Number(idRic));
    }
    refresh("lista-ricevimenti", listRicevimenti, action);
}

function addRicevimento(action, level) {
    let mancanti = [];
    let prof = level === 4 ? document.getElementById("prof").value : null;
    let dataI = document.getElementById("dateInizio").value;
    let dataF = document.getElementById("dateFine").value;
    let slots = document.getElementById("posti").value;
    let online = document.getElementById("online").checked;
    let uff = online ? null : document.getElementById("uff").value;

    if (level === 4 && !prof)
        mancanti.push("inserire professore");
    if (!dataI)
        mancanti.push("inserire data di inizio");
    if (!dataF)
        mancanti.push("inserire data di fine");
    if (!slots)
        mancanti.push("inserire quanti posti");
    if (!online && !uff)
        mancanti.push("inserire ufficio");
    if(mancanti.length > 0){
        alert("Compila i seguenti campi: " + mancanti.join(", "));
        return;
    }

    let inizio = new Date(dataI);
    let fine = new Date(dataF);
    if(fine - inizio <= 0){
        alert("La data di fine non può coincidere o essere prima della data di inizio");
        return;
    }

    uff = uff ? uffTot.find(u => Number(u.codice) === Number(uff)) : null;
    prof = prof ? profTot.find(p => Number(p.matr) === Number(prof)) : null;

    let nuovo = {
        codice: getCode(),
        online: online,
        data_inizio: dataI,
        data_fine: dataF,
        n_slot: Number(slots),
        matricola: prof ? Number(prof.matr) : null,
        nome: prof ? prof.nome : null,
        cognome: prof ? prof.cognome : null,
        email: prof ? prof.email : null,
        cod_uni: uff ? Number(uff.cod_uni) : null,
        cod_stanza: uff ? Number(uff.cod_stanza) : null,
        nome_stanza: uff ? uff.nome_stanza : null,
        capienza: uff ? Number(uff.capienza) : null
    };

    adds.push(nuovo);

    refresh("lista-ricevimenti", listRicevimenti, action);
}

function editRicevimento(action, idRic){
    const html = document.getElementById(`ricevimento-${idRic}`);
    const ric = edits.find(p => Number(p.codice) === Number(idRic))
                ?? originals.find(p => Number(p.codice) === Number(idRic))
                ?? adds.find(p => Number(p.codice) === Number(idRic));
    const oldTitle = document.getElementById(`title-${idRic}`).textContent;
    let option = "";
    if(document.getElementById("prof")){
        const prof = listPerson(profTot.filter(p => Number(p.matr) !== Number(ric.matricola)));
        const p = profTot.find(p => Number(p.matr) === Number(ric.matricola));
        //togli riga <option value="">-- Seleziona un persona --</option>
        option = `
        <div>
            <label for="prof-${idRic}">Professore titolare:</label>
            <select id="prof-${idRic}" name="prof-${idRic}">
                <option value=${ric.matricola}>${p.nome} ${p.cognome} (${p.email})</option>
                ${prof}
            </select>
        </div>
        `;
    }
    html.innerHTML = `
        <fieldset id="edit-ric-${idRic}">
            <legend>Edit: ${oldTitle}</legend>
                <div>
                    <label for="online-${idRic}">Ricevimento Online:</label>
                    <input type="checkbox" id="online-${idRic}" name="online-${idRic}" ${Number(ric.online) === 1 ? "checked" : ""}/>
                </div>
                ${option}
                <div>
                    <label for="uff-${idRic}">Ufficio:</label>
                    <select id="uff-${idRic}" name="uff-${idRic}">
                    </select>
                </div>
                <div>
                    <label for="dateInizio-${idRic}">Data Inizio:</label>
                    <input type="datetime-local" id="dateInizio-${idRic}" name="dateInizio-${idRic}" value="${ric.data_inizio}">
                </div>
                <div>
                    <label for="dateFine-${idRic}">Data Fine:</label>
                    <input type="datetime-local" id="dateFine-${idRic}" name="dateFine-${idRic}" value="${ric.data_fine}">
                </div>
                <div>
                    <label for="posti-${idRic}">Numero di Posti Disponibili:</label>
                    <input type="number" id="posti-${idRic}" name="posti-${idRic}" min=1 value=${ric.n_slot}/>
                </div>
                <div class="saveEdit">
                    <button type="button" onclick="confirmEditRicevimento('${action}', ${idRic})">
                        Conferma
                    </button>
                    <button type="button" onclick="rollbackEditRicevimento('${action}', ${idRic})">
                        Annulla
                    </button>
                </div>
        </fieldset>
    `;
    function cleanSelect(){
        uff.remove(0);
    }
    function salvaInlineEdit() {
        const nProf = prof ? profTot.find(p => Number(p.matr) === Number(prof.value)) : null;
        const nUff = !online.checked ? uffTot.find(u => Number(u.codice) === Number(uff.value)) : null;
        inlineEdits[idRic] = {
            ...ric,
            online: online.checked ? 1 : 0,

            data_inizio: inizio.value,
            data_fine: fine.value,

            n_slot: posti,

            matricola: prof?.value,
            nome: nProf?.nome ?? null,
            cognome: nProf?.cognome ?? null,
            email: nProf?.email ?? null,

            codiceUfficio: online.checked
                ? null
                : Number(uff.value),
            cod_uni: online.checked
                ? null
                : Number(nUff.cod_uni),

            cod_stanza: online.checked
                ? null
                : Number(nUff.cod_stanza),

            nome_stanza: online.checked
                ? null
                : nUff.nome_stanza,

            capienza: online.checked
                ? null
                : Number(nUff.capienza)
        };
    }
    const prof = document.getElementById(`prof-${idRic}`);
    prof?.remove(1);
    const uff = document.getElementById(`uff-${idRic}`);
    const online = document.getElementById(`online-${idRic}`);
    const curUff = uffTot.find(u => Number(u.cod_uni) === Number(ric.cod_uni) && Number(u.cod_stanza) === Number(ric.cod_stanza));
    const newUffTot = curUff
        ? [
            curUff,
            ...uffTot.filter(u => !(Number(u.cod_uni) === Number(ric.cod_uni) && Number(u.cod_stanza) === Number(ric.cod_stanza)))
        ]
            : uffTot;
    prof?.addEventListener("change", () => {aggiornaUffici(prof, online, uff, newUffTot);});
    online.addEventListener("change", () => {aggiornaUffici(prof, online, uff, newUffTot);});
    uff.addEventListener("change", cleanSelect);
    aggiornaUffici(prof, online, uff, newUffTot);

    const posti = document.getElementById(`posti-${idRic}`);
    const inizio = document.getElementById(`dateInizio-${idRic}`);
    const fine = document.getElementById(`dateFine-${idRic}`);
    const temp = inlineEdits[idRic];
    if (temp) {
        if (prof) {
            prof.value = temp.matricola;
        }

        online.checked = Number(temp.online) === 1;

        if (!temp.online && temp.codiceUfficio != null) {
            uff.value = temp.codiceUfficio;
        }

        inizio.value = temp.data_inizio;
        fine.value = temp.data_fine;
        posti.value = temp.n_slot;
    }
    prof?.addEventListener("change", salvaInlineEdit);
    online.addEventListener("change", salvaInlineEdit);
    uff.addEventListener("change", salvaInlineEdit);
    posti.addEventListener("input", salvaInlineEdit);
    inizio.addEventListener("input", salvaInlineEdit);
    fine.addEventListener("input", salvaInlineEdit);
    aggiornaUffici(prof, online, uff, newUffTot);
}

function rollbackEditRicevimento(action, idRic){
    delete inlineEdits[idRic];
    refresh("lista-ricevimenti", listRicevimenti, action);
}

function confirmEditRicevimento(action, idRic){
    const ric =
        edits.find(p => Number(p.codice) === Number(idRic))
        ?? originals.find(p => Number(p.codice) === Number(idRic))
        ?? adds.find(p => Number(p.codice) === Number(idRic));
    const profElement = document.getElementById(`prof-${idRic}`);
    const onlineElement = document.getElementById(`online-${idRic}`);
    const uffElement = document.getElementById(`uff-${idRic}`);
    const postiElement = document.getElementById(`posti-${idRic}`);
    const inizioElement = document.getElementById(`dateInizio-${idRic}`);
    const fineElement = document.getElementById(`dateFine-${idRic}`);
    const nOnline = onlineElement.checked;
    const nProf = profElement
        ? Number(profElement.value)
        : Number(ric.matricola);
    const nCodUff = nOnline
        ? null
        : Number(uffElement.value);
    const nPosti = Number(postiElement.value);
    const nInizio = new Date(inizioElement.value);
    const nFine = new Date(fineElement.value);
    const nUff = !nOnline
        ? uffTot.find(u => Number(u.codice) === nCodUff)
        : null;

    if (!nOnline && !nUff) {
        alert("Seleziona un ufficio");
        return;
    }
    if (!Number.isFinite(nPosti) || nPosti < 1) {
        alert("Il numero di posti non è valido");
        return;
    }
    if (isNaN(nInizio.getTime()) || isNaN(nFine.getTime())) {
        alert("Le date non sono valide");
        return;
    }
    if (nFine <= nInizio) {
        alert("La data di fine deve essere successiva alla data di inizio");
        return;
    }

    const stessoUfficio =
        nOnline ||
        (
            Number(ric.cod_uni) === Number(nUff.cod_uni) &&
            Number(ric.cod_stanza) === Number(nUff.cod_stanza)
        );

    const nessunaModifica =
        nProf === Number(ric.matricola) &&
        nOnline === (Number(ric.online) === 1) &&
        stessoUfficio &&
        nPosti === Number(ric.n_slot) &&
        nInizio.getTime() === new Date(ric.data_inizio).getTime() &&
        nFine.getTime() === new Date(ric.data_fine).getTime();

    if (nessunaModifica) {
        alert("Nessuna modifica fatta");
        return;
    }

    const prof = profTot.find(p => Number(p.matr) === nProf);

    const modificato = {
        ...ric,
        online: nOnline ? 1 : 0,

        data_inizio: inizioElement.value,
        data_fine: fineElement.value,

        n_slot: nPosti,

        matricola: nProf,
        nome: prof?.nome ?? null,
        cognome: prof?.cognome ?? null,
        email: prof?.email ?? null,

        cod_uni: nOnline
            ? null
            : Number(nUff.cod_uni),

        cod_stanza: nOnline
            ? null
            : Number(nUff.cod_stanza),

        nome_stanza: nOnline
            ? null
            : nUff.nome_stanza,

        capienza: nOnline
            ? null
            : Number(nUff.capienza)
    };

    const addIndex = adds.findIndex( p => Number(p.codice) === Number(idRic));

    if (addIndex !== -1) {
        adds[addIndex] = modificato;
    } else {
        const editIndex = edits.findIndex(p => Number(p.codice) === Number(idRic));
        if (editIndex !== -1) {
            edits[editIndex] = modificato;
        } else {
            edits.push(modificato);
        }
    }
    delete inlineEdits[idRic];
    refresh("lista-ricevimenti", listRicevimenti, action);
}

function refresh(id, fun, ...args) {
    document.getElementById(id).innerHTML = fun(...args);
    if (id === "lista-ricevimenti") {
        Object.keys(inlineEdits).forEach(idRic => {
            editRicevimento(args[0], Number(idRic));
        });
    }
}

function prepareEdits() {
    return edits.length
    ? edits.map(e => {
        const originale = originals.find(
            r => Number(r.codice) === Number(e.codice)
        );

        const risultato = {
            codice: e.codice
        };

        if (e.online !== originale.online)
            risultato.online = e.online;

        if (e.data_inizio !== originale.data_inizio)
            risultato.data_inizio = e.data_inizio;

        if (e.data_fine !== originale.data_fine)
            risultato.data_fine = e.data_fine;

        if (e.n_slot !== originale.n_slot)
            risultato.n_slot = e.n_slot;

        if (e.cod_uni !== originale.cod_uni)
            risultato.cod_uni = e.cod_uni;

        if (e.cod_stanza !== originale.cod_stanza)
            risultato.cod_stanza = e.cod_stanza;

        if (e.matricola !== originale.matricola)
            risultato.matricola = e.matricola;

        return risultato;
    })
    : null;
}

async function saveAllChanges(level) {
    const action = document.getElementById("selectAction").value;
    if (Object.keys(inlineEdits).length > 0) {
        const continua = confirm(
            "Ci sono modifiche non confermate. " +
            "Se continui, verranno ignorate.\n\n" +
            "Vuoi continuare?"
        );

        if (!continua) {
            return;
        }
    }
    if(action.startsWith("add")){
        if(adds.length === 0){
            alert("Nessun inserimento");
            return;
        }
    } else if (action.startsWith("delete")){
        if(deletes.length === 0){
            alert("Nessuna cancellazione");
            return;
        }
    } else {
        if(adds.length === 0 && edits.length === 0 && deletes.length === 0){
            alert("Nessuna modifica");
            return;
        }
    }
    let dati = [
        action,
        level,
        adds.length > 0 ? adds : null,
        prepareEdits(),
        deletes.length > 0 ? deletes : null
    ];

    try{
        const res = await fetch("./Api/api-saveRicevimento.php", {
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
    } catch(error) {
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
function formatDateForView(value) {
    const [year, month, day] = value.split("-");
    return `${day}/${month}/${year}`;
}

function formatDateTimeForView(value) {
    if (!value) return "";
    const data = value.replace("T", " ");
    const [giorno, ora] = data.split(" ");
    const [year, month, day] = giorno.split("-");
    return `${day}/${month}/${year}${ora ? " " + ora.slice(0,5) : ""}`;
}

let openBozza = false;
let originaleEvento = null;
let totIdEvento = [];
let personeTot = [];
let luoghiTot = [];
let collaboratoriOriginali = [];
let collaboratoriDaRimuovere = [];
let collaboratoriDaAggiungere = [];
let promotoriTot = [];
let promotoriOriginali = [];
let promotoriDaRimuovere = [];
let promotoriDaAggiungere = [];
let orariOriginali = [];
let orariDaRimuovere = [];
let orariDaAggiungere = [];
let orariModificati = [];

function resetEvento() {

    openBozza = false;
    originaleEvento = null;
    totIdEvento = [];

    personeTot = [];
    luoghiTot = [];

    collaboratoriOriginali = [];
    collaboratoriDaRimuovere = [];
    collaboratoriDaAggiungere = [];

    promotoriTot = [];
    promotoriOriginali = [];
    promotoriDaRimuovere = [];
    promotoriDaAggiungere = [];

    orariOriginali = [];
    orariDaRimuovere = [];
    orariDaAggiungere = [];
    orariModificati = [];

}

function updateInnerList(list, update){
    update.forEach(u => {
        if (!list.some(l => l.codice === u.codice)) {
            list.push(u);
        }
    });
}

function idForHtml(tipo){
    tipo = tipo + 1;
    return tipo;
}

function renderPeopleList(lista, listaDaRimuovere, listaDaAggiungere, tipo) {

    lista = lista.concat(listaDaAggiungere);

    return lista.map(persona => {

        const rimosso =
            listaDaRimuovere.includes(persona.codice);

        return `
            <li class="${rimosso ? "pending-delete" : `${persona.codice}`}">

                <p>${persona.nome} ${tipo==="collaboratore" ? `${persona.cognome}` : ""} (${persona.codice})</p>
                ${
                    rimosso
                    ? `
                        <button
                            type="button"
                            onclick="restorePerson('${tipo}','${persona.codice}')">
                            Annulla
                        </button>
                    `
                    : `
                        <button
                            type="button"
                            onclick="removePerson('${tipo}','${persona.codice}')">
                            Rimuovi
                        </button>
                    `
                }

            </li>
        `;
    }).join("");
}

function renderUnlogged(logged, action, personeOptions = null){
    return logged ? `` :  `<h3> Richiedente </h3>
                            <select name="richiedente" id="richiedente">
                                <option value="">-- Nessuno --</option>
                                ${action === "add" ? promotoriOriginali.push(promotoriDaAggiungere) : personeOptions}
                            </select>`;
}

function renderMainEventi() {
    return `
    <header class="eventi-header">

        <h2 id="eventi-title">
            Azioni su Evento:
        </h2>

        <div class="filters">

            <select id="selectAction">
                <option value="nothing">
                    Scegli un'azione
                </option>
                <option value="add">
                    Aggiungi Evento
                </option>
                <option value="edit">
                    Modifica Evento
                </option>
                <option value="delete">
                    Elimina Evento
                </option>
                <option value="addOrario">
                    Aggiungi Orario
                </option>
                <option value="editOrario">
                    Modifica Orario
                </option>
                <option value="deleteOrario">
                    Elimina Orario
                </option>
            </select>

            <button id="visualizzaEventi">
                Visualizza Eventi
            </button>

        </div>

    </header>


    <form id="azioni-form" method="post"></form>
    `;
}

function renderForum(json) {
    const action = json.action;
    if (action === "nothing") {
        return `<p>Seleziona un'azione per visualizzare il forum.</p>`;
    }
    if (action === "add") {
        const personeOptions = json.persone.map(p => `
            <option value="${p.codice}">
                ${p.nome} ${p.cognome} (${p.codice})
            </option>
        `).join("");

        const promotoreOptions = json.totPromotori.map(p => `
            <option value="${p.codice}">
                ${p.nome} (${p.codice})
            </option>
        `).join("");

        const luoghiOptions = json.luoghi.map(l => `
            <option value="${l.codice}">
                ${l.nome_sede ? `${l.nome_sede}` : `${l.nome}`} c: ${l.capienza} (${l.indirizzo})
            </option>
        `).join("");

        updateInnerList(personeTot, json.persone);
        updateInnerList(promotoriTot, json.totPromotori);
        updateInnerList(luoghiTot, json.luoghi);

        totIdEvento = [...json.eventi.map(o => o.codice)];

        return `
            ${renderUnlogged(json.logged, action)}

            <label>Nome evento</label>
            <input type="text" name="nome"/>

            <label>Descrizione</label>
            <textarea name="descrizione"></textarea>

            <label>Posti</label>
            <input type="number" name="posti" min="1"/>

            <label>Data inizio</label>
            <input type="date" name="data_inizio"/>
            <label>Data fine</label>
            <input type="date" name="data_fine"/>

            <label>Rappresentante</label>
            <select name="rappresentante" id="rappresentante">
                <option value="">-- Nessuno --</option>
                ${personeOptions}
            </select>

            <h3>Collaboratori</h3>

            <p>Collaboratori selezionati: </p>

            <ul id="lista-collaboratori">
                ${renderPeopleList(
                    collaboratoriOriginali,
                    collaboratoriDaRimuovere,
                    collaboratoriDaAggiungere,
                    "collaboratore"
                )}
            </ul>

            <div class="row">
                <select id="nuovo-collaboratore">
                    <option value="">-- Nessuno --</option>
                    ${personeOptions}
                </select>

                <button type="button" id="add-collaboratore" onclick="addPerson('collaboratore')">
                    Aggiungi
                </button>
            </div>

            <h3>Promotori</h3>

            <p>Promotori selezionati: </p>

                    <ul id="lista-promotori">
                        ${renderPeopleList(
                            promotoriOriginali,
                            promotoriDaRimuovere,
                            promotoriDaAggiungere,
                            "promotore"
                        )}
                    </ul>

            <div class="row">
                <select id="nuovo-promotore">
                    <option value="">-- Nessuno --</option>
                    ${promotoreOptions}
                </select>

                <button type="button" id="add-promotore" onclick="addPerson('promotore')">
                    Aggiungi
                </button>
                <button type="button" id="azioniPromotori" onclick="saveAndChangePage('./azioniPromotori.php')">
                    Aggiungi Promotori Fuori Lista
                </button>
            </div>

            <h3>Orari</h3>

            <p>Orari selezionati: </p>

            <ul id="lista-orari">
                ${renderListaOrari("edit")}
            </ul>

            <select id="place-select">
                <option value="">-- Nessuno --</option>
                ${luoghiOptions}
            </select>
            <label for="orario-inizio">Orario inizio</label>
            <input
                type="datetime-local"
                id="orario-inizio"
                name="orario_inizio"
            />

            <label for="orario-fine">Orario fine</label>
            <input
                type="datetime-local"
                id="orario-fine"
                name="orario_fine"
            />

            <button type="button" id="add-orario" onclick='addOrario()'>
                Aggiungi orario
            </button>
            <button type="button" id="azioniLuoghi" onclick="saveAndChangePage('./azioniLuoghi.php')">
                Aggiungi Luoghi Fuori Lista
            </button>

            <br></br>

            <button type="submit" onclick="saveAllChanges()">
                Invia richiesta
            </button>
        `;

    }

    const eventiOptions = json.idEvento.map(e => `
        <option value="${e.codice}">
            ${e.nome}
        </option>
    `).join("");

    return `
        <label>Evento</label>

        <select id="evento-select">
            <option value="">-- Seleziona un evento --</option>
            ${eventiOptions}
        </select>

        <div id="evento-content"></div>
    `;
}

function renderEventoDetails(json) {
    const action = json.action;
    originaleEvento = json.evento;
    originaleEvento.inizio = originaleEvento.inizio.slice(0, 10);
    originaleEvento.fine = originaleEvento.fine.slice(0, 10);
    const personeOptions = json.persone.map(p => `
        <option value="${p.codice}">
            ${p.nome} ${p.cognome} (${p.codice})
        </option>
    `).join("");
    switch (action) {

        case "delete":
            return `${renderUnlogged(json.logged, action, personeOptions)}

                    <button type="submit" id="btnDeleteEvento" onclick="saveAllChanges()">
                        Elimina evento
                    </button>`

        case "edit":{
            const luoghiOptions = json.luoghi.map(l => `
                <option value="${l.codice}">
                    ${l.nome_sede ? `${l.nome_sede}` : `${l.nome}`} c: ${l.capienza} (${l.indirizzo})
                </option>
            `).join("");
            updateInnerList(personeTot, json.persone);
            updateInnerList(luoghiTot, json.luoghi);
            const promotoreOptions = json.totPromotori.map(p => `
                <option value="${p.codice}">
                    ${p.nome} (${p.codice})
                </option>
            `).join("");
            collaboratoriOriginali = json.collaboratori;
            promotoriOriginali = json.promotori;
            orariOriginali = json.orari;
            orariOriginali = json.orari.map(o => ({
                ...o,
                inizio: o.inizio.replace(" ", "T").slice(0,16),
                fine: o.fine.replace(" ", "T").slice(0,16)
            }));
            updateInnerList(promotoriTot, action, personeOptions);
            return `
                    ${renderUnlogged(json.logged)}

                    <h3>Modifica evento</h3>

                    <label>Nome</label>
                    <input
                        type="text"
                        id="edit-nome"
                        value="${json.evento.nome}"
                    />

                    <label>Descrizione</label>
                    <textarea id="edit-descrizione">${json.evento.descrizione}</textarea>

                    <label>Posti</label>
                    <input
                        type="number"
                        id="edit-posti"
                        value="${json.evento.posti}"
                    />

                    <label>Data inizio</label>
                    <input type="date" id="edit-data_inizio" value="${originaleEvento.inizio}"/>
                    <label>Data fine</label>
                    <input type="date" id="edit-data_fine" value="${originaleEvento.fine}"/>

                    <h3>Rappresentante</h3>

                    <p> Attuale: ${json.evento.nome_rappresentante} ${json.evento.cognome_rappresentante} (${json.evento.id_rappresentante}) </p>

                    <select id="edit-rappresentante">
                        <option value="">-- Nessuno --</option>
                        ${personeOptions}
                    </select>

                    <h3>Collaboratori attuali</h3>

                    <ul id="lista-collaboratori">
                        ${renderPeopleList(
                            collaboratoriOriginali,
                            collaboratoriDaRimuovere,
                            collaboratoriDaAggiungere,
                            "collaboratore"
                        )}
                    </ul>

                    <h3>Aggiungi collaboratore</h3>

                    <select id="nuovo-collaboratore">

                        <option value="">-- Nessuno --</option>
                        ${personeOptions}

                    </select>

                    <button type="button" id="add-collaboratore" onclick='addPerson("collaboratore")'>
                        Aggiungi
                    </button>

                    <h3>Promotori attuali</h3>

                    <ul id="lista-promotori">
                        ${renderPeopleList(
                            promotoriOriginali,
                            promotoriDaRimuovere,
                            promotoriDaAggiungere,
                            "promotore"
                        )}
                    </ul>

                    <h3>Aggiungi promotore</h3>

                    <select id="nuovo-promotore">

                        <option value="">-- Nessuno --</option>
                        ${promotoreOptions}

                    </select>

                    <button type="button" id="add-promotore" onclick="addPerson('promotore')">
                        Aggiungi
                    </button>
                    <button type="button" id="azioniPromotori" onclick="saveAndChangePage('./azioniPromotori.php')">
                        Aggiungi Promotori Fuori Lista
                    </button>

                    <h3>Orari</h3>

                    <p>Orari selezionati: </p>

                    <ul id="lista-orari">
                        ${renderListaOrari("edit")}
                    </ul>

                    <select id="place-select">
                        <option value="">-- Nessuno --</option>
                        ${luoghiOptions}
                    </select>
                    <label for="orario-inizio">Orario inizio</label>
                    <input
                        type="datetime-local"
                        id="orario-inizio"
                        name="orario_inizio"
                    />

                    <label for="orario-fine">Orario fine</label>
                    <input
                        type="datetime-local"
                        id="orario-fine"
                        name="orario_fine"
                    />

                    <button type="button" id="add-orario" onclick='addOrario()'>
                        Aggiungi orario
                    </button>
                    <button type="button" id="azioniLuoghi" onclick="saveAndChangePage('./azioniLuoghi.php')">
                        Aggiungi Luoghi Fuori Lista
                    </button>

                    <br></br>

                    <button type="submit" id="save-edit-evento" onclick="saveAllChanges()">
                        Salva modifiche
                    </button>

                    `;
                break;
                }

        case "addOrario":{
            const luoghiOptions = json.luoghi.map(l => `
                <option value="${l.codice}">
                    ${l.nome_sede ? `${l.nome_sede}` : `${l.nome}`} c: ${l.capienza} (${l.indirizzo})
                </option>
            `).join("");
            updateInnerList(luoghiTot, json.luoghi);
            orariOriginali = json.orari;
            orariOriginali = json.orari.map(o => ({
                ...o,
                inizio: o.inizio.replace(" ", "T").slice(0,16),
                fine: o.fine.replace(" ", "T").slice(0,16)
            }));

            return `
                    ${renderUnlogged(json.logged, action, personeOptions)}

                    <h3>Orari</h3>

                    <ul id="lista-orari">
                        ${renderListaOrari("visual")}
                    </ul>
                    <select id="place-select">
                        <option value="">-- Nessuna modifica --</option>
                        ${luoghiOptions}
                    </select>
                    <label for="orario-inizio">Orario inizio</label>
                    <input
                        type="datetime-local"
                        id="orario-inizio"
                        name="orario_inizio"
                    />

                    <label for="orario-fine">Orario fine</label>
                    <input
                        type="datetime-local"
                        id="orario-fine"
                        name="orario_fine"
                    />

                    <button type="button" id="add-orario">
                        Aggiungi orario
                    </button>
                    <button type="button" id="azioniLuoghi" onclick="saveAndChangePage('./azioniLuoghi.php')">
                        Aggiungi Luoghi Fuori Lista
                    </button>

                    <br></br>

                    <button type="submit" onclick="saveAllChanges()">
                        Invia richiesta
                    </button>`
                break;
                }

        case "editOrario":{
            const luoghiOptions = json.luoghi.map(l => `
                <option value="${l.codice}">
                    ${l.nome_sede ? `${l.nome_sede}` : `${l.nome}`} c: ${l.capienza} (${l.indirizzo})
                </option>
            `).join("");
            orariOriginali = json.orari;
            orariOriginali = json.orari.map(o => ({
                ...o,
                inizio: o.inizio.replace(" ", "T").slice(0,16),
                fine: o.fine.replace(" ", "T").slice(0,16)
            }));
            return `${renderUnlogged(json.logged, action, personeOptions)}

                    <h3>Orari</h3>

                    <p>Orari selezionati: </p>

                    <ul id="lista-orari">
                        ${renderListaOrari("edit")}
                    </ul>

                    <select id="place-select">
                        <option value="">-- Nessuno --</option>
                        ${luoghiOptions}
                    </select>
                    <label for="orario-inizio">Orario inizio</label>
                    <input
                        type="datetime-local"
                        id="orario-inizio"
                        name="orario_inizio"
                    />

                    <label for="orario-fine">Orario fine</label>
                    <input
                        type="datetime-local"
                        id="orario-fine"
                        name="orario_fine"
                    />

                    <button type="button" id="add-orario" onclick='addOrario()'>
                        Aggiungi orario
                    </button>
                    <button type="button" id="azioniLuoghi" onclick="saveAndChangePage('./azioniLuoghi.php')">
                        Aggiungi Luoghi Fuori Lista
                    </button>

                    <br></br>

                    <button type="submit" id="save-edit-evento" onclick="saveAllChanges()">
                        Salva modifiche
                    </button>`
            break;
        }
        case "deleteOrario":{
            return `${renderUnlogged(json.logged, action, personeOptions)}

                    <h3>Orari</h3>

                    <p>Orari selezionati: </p>

                    <ul id="lista-orari">
                        ${renderListaOrari("delete")}
                    </ul>

                    <button type="submit" id="save-edit-evento" onclick="saveAllChanges()">
                        Salva modifiche
                    </button>`
        }
    }
}

async function loadEvento() {

    const evento = document.getElementById("evento-select").value;

    if (!evento)
        return;

    const azione = document.getElementById("selectAction").value;

    const res = await fetch(
        `./Api/api-azioniEvento.php?azione=${azione}&evento=${evento}`
    );

    const json = await res.json();

    document.getElementById("evento-content").innerHTML =
        renderEventoDetails(json);

    aggiungiListenerDateEvento();
}

async function loadAzioni() {

    resetEvento();

    const azione = document.getElementById("selectAction").value;

    const res = await fetch(
        `./Api/api-azioniEvento.php?azione=${azione}`
    );

    const json = await res.json();

    document.getElementById("azioni-form").innerHTML = renderForum(json);

    const select = document.getElementById("evento-select");

    if (select) {
        select.addEventListener("change", loadEvento);
    }

    aggiungiListenerDateEvento();

    const bozza = JSON.parse(
        sessionStorage.getItem("bozzaEvento")
    );

    if(bozza?.ritorno && !openBozza){
        caricaBozzaEvento();
    }
}

function refreshCollaboratori() {
    document.getElementById("lista-collaboratori").innerHTML =
        renderPeopleList(
            collaboratoriOriginali,
            collaboratoriDaRimuovere,
            collaboratoriDaAggiungere,
            "collaboratore"
        );
}

function refreshPromotori() {
    document.getElementById("lista-promotori").innerHTML =
        renderPeopleList(
            promotoriOriginali,
            promotoriDaRimuovere,
            promotoriDaAggiungere,
            "promotore"
        );
}

function addPerson(tipo) {

    const lista = tipo === "collaboratore"
        ? personeTot
        : promotoriTot;

    const originali = tipo === "collaboratore"
        ? collaboratoriOriginali
        : promotoriOriginali;

    const aggiungere = tipo === "collaboratore"
        ? collaboratoriDaAggiungere
        : promotoriDaAggiungere;

    const refresh = tipo === "collaboratore"
        ? refreshCollaboratori
        : refreshPromotori;

    const codice = document.getElementById("nuovo-" + tipo).value;

    const elemento = lista.find(x => x.codice === codice);

    if (!elemento) return;

    if (originali.some(x => x.codice === codice))
        return;

    if (!aggiungere.some(x => x.codice === codice))
        aggiungere.push(elemento);

    refresh();
}

function removePerson(tipo, cf) {

    if (tipo === "collaboratore") {
        if(collaboratoriOriginali.some(c => c.codice === cf)){
            if (!collaboratoriDaRimuovere.includes(cf))
                collaboratoriDaRimuovere.push(cf);
        }else{
            collaboratoriDaAggiungere = collaboratoriDaAggiungere.filter(c => c.codice !== cf);
        }
        refreshCollaboratori();

    } else {
        if(promotoriOriginali.some(p => p.codice === cf)){
            if (!promotoriDaRimuovere.includes(cf))
                promotoriDaRimuovere.push(cf);
        }else{
            promotoriDaAggiungere = promotoriDaAggiungere.filter(p => p.codice !== cf);
        }
        refreshPromotori();
    }
}

function restorePerson(tipo, cf) {

    if (tipo === "collaboratore") {

        collaboratoriDaRimuovere =
            collaboratoriDaRimuovere.filter(x => x !== cf);

        refreshCollaboratori();

    } else {

        promotoriDaRimuovere =
            promotoriDaRimuovere.filter(x => x !== cf);

        refreshPromotori();
    }
}

function renderListaOrari(tipo) {

    const lista = orariOriginali
        .filter(o =>
            !orariDaRimuovere.some(r =>
                r.codice === o.codice &&
                r.codice_evento === o.codice_evento
            )
        )
        .map(o =>
            orariModificati.find(m =>
                m.codice === o.codice &&
                m.codice_evento === o.codice_evento
            ) ?? o
        )
        .concat(orariDaAggiungere);

    return lista.map(o => {

        const eliminato = orariDaRimuovere.some(r =>
            r.codice === o.codice &&
            r.codice_evento === o.codice_evento
        );

        const luogo = luoghiTot.find(
            l => l.codice === o.codice_luogo
        );

        let bottoni = "";

        if(tipo === "edit" || (tipo === "visual" && !orariOriginali.some(r => r.codice === o.codice && r.codice_evento === o.codice_evento))) {

            bottoni = eliminato
                ? `
                    <button onclick="restoreOrario(${o.codice}, ${o.codice_evento})">
                        Annulla
                    </button>
                `
                : `
                    <button onclick="editOrario(${o.codice}, ${o.codice_evento})">
                        Modifica
                    </button>

                    <button onclick="removeOrario(${o.codice}, ${o.codice_evento})">
                        Elimina
                    </button>
                `;

        } else if(tipo === "delete") {

            bottoni = eliminato
                ? `
                    <button onclick="restoreOrario(${o.codice}, ${o.codice_evento})">
                        Annulla
                    </button>
                `
                : `
                    <button onclick="removeOrario(${o.codice}, ${o.codice_evento})">
                        Elimina
                    </button>
                `;
        }

        return `
            <div id="orario-${o.codice}-${o.codice_evento}" class="${eliminato ? "pending-delete" : ""}">

                <p>Inizio: ${formatDateTimeForView(o.inizio)}</p>
                <p>Fine: ${formatDateTimeForView(o.fine)}</p>

                <p>
                    Luogo:
                    ${luogo.nome_sede ? luogo.nome_sede : luogo.nome}
                    c: ${luogo.capienza}
                    (${luogo.indirizzo})
                </p>

                ${bottoni}
            </div>
        `;
    }).join("");
}

function addOrario() {
    const tuttiCodici = [// un codice temporaneo, guardare codice in orariDaAggiungere + orariOriginali + 1
        ...orariOriginali.map(o => o.codice),
        ...orariDaAggiungere.map(o => o.codice)
    ];

    let codEvento;

    if(!originaleEvento){
        codEvento = totIdEvento.length > 0
            ? Math.max(...totIdEvento) + 1
            : 1;
    }else{
        codEvento = originaleEvento.codice;
    }

    const nuovoCodice = tuttiCodici.length > 0
        ? Math.max(...tuttiCodici) + 1
        : 1;

    const luogo = Number(document.getElementById("place-select").value);
    const inizio = document.getElementById("orario-inizio").value;
    const fine = document.getElementById("orario-fine").value;

    const mancanti = [];

    if(!inizio) mancanti.push("orario di inizio");
    if(!fine) mancanti.push("orario di fine");
    if(!luogo) mancanti.push("luogo");

    if(mancanti.length > 0){
        alert("Inserisci: " + mancanti.join(", "));
        return;
    }

    const maxPosti = capienzaMassimaConsentita();

    const inputPosti =
    document.getElementsByName("posti")[0] ??
    document.getElementById("edit-posti");

    let posti = null;

    if (inputPosti) {
        posti = Number(inputPosti.value);
    } else if (originaleEvento) {
        posti = Number(originaleEvento.posti);
    }

    if (posti !== null && maxPosti !== null && posti > maxPosti) {
        alert(
            `I posti (${posti}) superano la capienza minima dei luoghi selezionati (${maxPosti}).`
        );
        return;
    }

    const dataInizio = new Date(inizio);
    const dataFine = new Date(fine);

    if(dataFine <= dataInizio){
        alert("La data di fine deve essere successiva alla data di inizio");
        return;
    }

    const intervallo = intervalloEvento();

    if (intervallo) {

        if (
            dataInizio < intervallo.inizio ||
            dataFine > intervallo.fine
        ) {
            alert("L'orario deve rientrare nell'intervallo dell'evento.");
            return;
        }
    }

    const nuovo = {
        codice: nuovoCodice,
        codice_evento: codEvento,
        codice_luogo: luogo,
        inizio: inizio,
        fine: fine
    };

    if(controllaSovrapposizione(nuovo)){
        alert("L'orario inserito si sovrappone con un altro orario già presente");
        return;
    }

    orariDaAggiungere.push(nuovo);
    refreshOrari();
}

function refreshOrari() {
    document.getElementById("lista-orari").innerHTML =
        renderListaOrari();
}

function removeOrario(codice, codiceEvento){

    if(orariDaAggiungere.some(r => r.codice === codice && r.codice_evento === codiceEvento)){
        orariDaAggiungere = orariDaAggiungere.some(r => !(r.codice === codice && r.codice_evento === codiceEvento));
    } else if(orariModificati.some(r => r.codice === codice && r.codice_evento === codiceEvento)){
        orariModificati = orariModificati.some(r => !(r.codice === codice && r.codice_evento === codiceEvento));
    } else if(!orariDaRimuovere.some(r => r.codice === codice && r.codice_evento === codiceEvento)){
        orariDaRimuovere.push({
                codice: codice,
                codice_evento: codiceEvento
            });
    }

    refreshOrari();
}

function restoreOrario(codice, codiceEvento){

    orariDaRimuovere =
        orariDaRimuovere.filter(r => !(r.codice === codice && r.codice_evento === codiceEvento));

    refreshOrari();
}

function editOrario(codice, codiceEvento){
    const html = document.getElementById(`orario-${codice}-${codiceEvento}`);

    let lista;
    let o;

    // nuovo orario
    if(orariDaAggiungere.some(r =>
        r.codice === codice && r.codice_evento === codiceEvento
    )){

        lista = "orariDaAggiungere";
        o = orariDaAggiungere.find(r =>
            r.codice === codice && r.codice_evento === codiceEvento
        );

    }
    // orario già modificato
    else if(orariModificati.some(r =>
        r.codice === codice && r.codice_evento === codiceEvento
    )){

        lista = "orariModificati";
        o = orariModificati.find(r =>
            r.codice === codice && r.codice_evento === codiceEvento
        );

    }
    // orario originale
    else {

        lista = "orariModificati";
        o = orariOriginali.find(r =>
            r.codice === codice && r.codice_evento === codiceEvento
        );

    }

    const luoghiOptions = luoghiTot.map(l => `
            <option value="${l.codice}">
                ${l.nome_sede ? `${l.nome_sede}` : `${l.nome}`} c: ${l.capienza} (${l.indirizzo})
            </option>
        `).join("");

    const luogoCorrente = luoghiTot.find(
        l => l.codice === o.codice_luogo
    );

    html.innerHTML = `
        <div class="orario-${o.codice}-${o.codice_evento}">

            <p>Inizio corrente: ${formatDateTimeForView(o.inizio)}</p>
            <input
                type="datetime-local"
                id="edit-orario-inizio-${o.codice}-${codiceEvento}"
            />

            <p>Fine corrente: ${formatDateTimeForView(o.fine)}</p>
            <input
                type="datetime-local"
                id="edit-orario-fine-${o.codice}-${codiceEvento}"
            />

            <p>Luogo corrente: ${luogoCorrente.nome_sede ? `${luogoCorrente.nome_sede}` : `${luogoCorrente.nome}`} c: ${luogoCorrente.capienza} (${luogoCorrente.indirizzo})</p>

            <select id="edit-place-select-${o.codice}-${codiceEvento}">
                <option value="">-- Nessuna modifica --</option>
                ${luoghiOptions}
            </select>

            <button onclick="confirmEditOrario(${o.codice}, ${codiceEvento}, '${lista}')">
                Conferma
            </button>

        </div>
    `;
}

function confirmEditOrario(codice, codiceEvento, lista){

    const inizio =
        document.getElementById(`edit-orario-inizio-${codice}-${codiceEvento}`).value;
    const fine =
        document.getElementById(`edit-orario-fine-${codice}-${codiceEvento}`).value;
    const luogo =
        Number(document.getElementById(`edit-place-select-${codice}-${codiceEvento}`).value);

    if(luogo){
        let posti = null;

        const inputPosti = document.getElementById("edit-posti");

        if (inputPosti) {
            posti = Number(inputPosti.value);
        } else if (originaleEvento) {
            posti = Number(originaleEvento.posti);
        }
        const maxPosti = capienzaMassimaConsentita();

        if (posti !== null && maxPosti !== null && posti > maxPosti) {
            alert(
                `I posti (${posti}) superano la capienza minima dei luoghi selezionati (${maxPosti}).`
            );
            return;
        }
    }

    let vecchio;

    if(lista === "orariDaAggiungere"){
        vecchio = orariDaAggiungere.find(o =>
            o.codice === codice &&
            o.codice_evento === codiceEvento
        );
    } else {

        vecchio = orariModificati.find(o =>
            o.codice === codice &&
            o.codice_evento === codiceEvento
        );

        if(!vecchio){
            vecchio = orariOriginali.find(o =>
                o.codice === codice &&
                o.codice_evento === codiceEvento
            );
        }
    }

    if(!vecchio){
        alert("Orario non trovato");
        return;
    }

    const modificato = {
        ...vecchio,
        ...(inizio && { inizio }),
        ...(fine && { fine }),
        ...(luogo && { codice_luogo: luogo })
    };

    const dataInizio = new Date(modificato.inizio);
    const dataFine = new Date(modificato.fine);

    if(dataFine <= dataInizio){
        alert("La data di fine deve essere successiva alla data di inizio");
        return;
    }

    if(controllaSovrapposizione(modificato)){
        alert("La modifica crea una sovrapposizione con un altro orario");
        return;
    }

    const intervallo = intervalloEvento();

    if (intervallo) {

        if (
            dataInizio < intervallo.inizio ||
            dataFine > intervallo.fine
        ) {
            alert("L'orario deve rientrare nell'intervallo dell'evento.");
            return;
        }
    }

    if(lista === "orariDaAggiungere"){

        orariDaAggiungere =
            orariDaAggiungere.map(o =>
                o.codice === codice && o.codice_evento === codiceEvento ? modificato : o
            );

    } else {
        const indice = orariModificati.findIndex(o => o.codice === codice && o.codice_evento === codiceEvento);

        if(indice !== -1){
            orariModificati[indice] = modificato;
        } else {
            orariModificati.push(modificato);
        }
    }

    refreshOrari();
}

function controllaSovrapposizione(nuovoOrario) {

    const tuttiOrari = [
        ...orariOriginali
            .filter(o => !orariDaRimuovere.some(r => r.codice === o.codice && r.codice_evento === o.codice_evento))
            .map(o => orariModificati.find(m => m.codice === o.codice && m.codice_evento === o.codice_evento) ?? o),

        ...orariDaAggiungere
    ];


    return tuttiOrari.some(o => {

        // ignora se è lo stesso orario (caso modifica)
        if(o.codice === nuovoOrario.codice && o.codice_evento === nuovoOrario.codice_evento)
            return false;


        const inizioEsistente = new Date(o.inizio);
        const fineEsistente = new Date(o.fine);

        const inizioNuovo = new Date(nuovoOrario.inizio);
        const fineNuovo = new Date(nuovoOrario.fine);


        return (
            inizioNuovo < fineEsistente &&
            fineNuovo > inizioEsistente
        );
    });
}

function capienzaMassimaConsentita() {

    const selectLuoghi = document.querySelectorAll(
        'select[id^="place-select"], select[id^="edit-place-select"]'
    );

    const capienze = [];

    selectLuoghi.forEach(select => {

        if (!select.value)
            return;

        const codice = Number(select.value);

        const luogo = luoghiTot.find(
            l => l.codice === codice
        );

        if (luogo)
            capienze.push(Number(luogo.capienza));
    });

    return capienze.length > 0
        ? Math.min(...capienze)
        : null;
}

function intervalloEvento() {

    const inputInizio =
        document.getElementsByName("data_inizio")[0] ??
        document.getElementById("edit-data_inizio");

    const inputFine =
        document.getElementsByName("data_fine")[0] ??
        document.getElementById("edit-data_fine");

    const inizio = inputInizio
        ? inputInizio.value
        : originaleEvento?.inizio;

    const fine = inputFine
        ? inputFine.value
        : originaleEvento?.fine;

    if (!inizio || !fine)
        return null;

    return {
        inizio: new Date(inizio),
        fine: new Date(fine)
    };
}

function aggiungiListenerDateEvento() {

    const dataInizio =
        document.querySelector("[name='data_inizio']") ??
        document.getElementById("edit-data_inizio");

    const dataFine =
        document.querySelector("[name='data_fine']") ??
        document.getElementById("edit-data_fine");


    if (dataInizio) {
        dataInizio.dataset.vecchioValore = dataInizio.value;

        dataInizio.addEventListener("change", controllaDateEvento);
    }

    if (dataFine) {
        dataFine.dataset.vecchioValore = dataFine.value;

        dataFine.addEventListener("change", controllaDateEvento);
    }
}

function controllaDateEvento(evento) {

    const risultato = controllaIntervalloEvento();

    if (!risultato.valido) {

        alert(
            "Le nuove date dell'evento non comprendono tutti gli orari inseriti.\n " +
            "Modifica gli orari dell'evento oppure cancella quelli al di fuori prima di poter modificare le date.\n"+
            "Le nuove date dell'evento non comprendono questi orari:\n\n" +
            risultato.orari.map(o =>
                `${o.inizio} - ${o.fine}`
            ).join("\n")
        );

        // torna alla data precedente
        evento.target.value =
            evento.target.dataset.vecchioValore;

        return;
    }

    // salvo il nuovo valore come valido
    evento.target.dataset.vecchioValore =
        evento.target.value;
}

function controllaIntervalloEvento() {

    const inputInizio =
        document.querySelector("[name='data_inizio']") ??
        document.getElementById("edit-data_inizio");

    const inputFine =
        document.querySelector("[name='data_fine']") ??
        document.getElementById("edit-data_fine");

    const inizioEvento =
        inputInizio?.value || originaleEvento?.inizio;

    const fineEvento =
        inputFine?.value || originaleEvento?.fine;


    if (!inizioEvento || !fineEvento) {
        return {
            valido: true,
            orari: []
        };
    }

    const inizio = new Date(inizioEvento);
    const fine = new Date(fineEvento);

    const tuttiOrari = [
        ...orariOriginali
            .filter(o => !orariDaRimuovere.some(r => r.codice === o.codice && r.codice_evento === o.codice_evento))
            .map(o => orariModificati.find(m => m.codice === o.codice && m.codice_evento === o.codice_evento) ?? o),

        ...orariDaAggiungere
    ];

    const incompatibili = tuttiOrari.filter(o => {

        const inizioOrario = new Date(o.inizio);
        const fineOrario = new Date(o.fine);

        return (
            inizioOrario < inizio ||
            fineOrario > fine
        );
    });

    return {
        valido: incompatibili.length === 0,
        orari: incompatibili
    };
}

function salvaBozzaEvento(json){
    if(json.action!=="nothing"){

        let bozza = {
            action:json.action,
            collaboratoriDaRimuovere,
            collaboratoriDaAggiungere,
            promotoriDaRimuovere,
            promotoriDaAggiungere,
            orariDaRimuovere,
            orariDaAggiungere,
            orariModificati,
        };

        if(document.getElementById('richiedente')){
            bozza = {...bozza, richiedente: document.getElementById('richiedente').value}
        }

        switch(json.action){
            case "add":
                bozza = {...bozza,
                nome: document.querySelector("[name='nome']").value,
                descrizione: document.querySelector("[name='descrizione']").value,
                dataInizio: document.querySelector("[name='data_inizio']").value,
                dataFine: document.querySelector("[name='data_fine']").value,
                rappresentante: document.querySelector("[name='rappresentante']").value,
                posti: document.querySelector("[name='posti']").value,
                };
                break;
            case "edit":
                bozza = {...bozza,
                idEvento: json.evento.codice,
                nome: document.getElementById("edit-nome").value,
                descrizione: document.getElementById("edit-descrizione").value,
                posti: document.getElementById('edit-posti').value,
                dataInizio: document.getElementById('edit-data_inizio').value,
                dataFine: document.getElementById('edit-data_fine').value,
                rappresentante: document.getElementById('edit-rappresentante').value,
                };
                break;
            case "delete":
            case "addOrario":
            case "editOrario":
            case "deleteOrario":
                bozza = {...bozza, idEvento: json.evento.codice};
                break;
        }

        sessionStorage.setItem(
            "bozzaEvento",
            JSON.stringify(bozza)
        );
    }
}

async function caricaBozzaEvento(){

    const dati = sessionStorage.getItem("bozzaEvento");

    if(!dati)
        return;

    try {
        const bozza = JSON.parse(dati);
        openBozza = true;

        collaboratoriDaAggiungere = bozza.collaboratoriDaAggiungere ?? [];
        collaboratoriDaRimuovere = bozza.collaboratoriDaRimuovere ?? [];
        promotoriDaAggiungere = bozza.promotoriDaAggiungere ?? [];
        promotoriDaRimuovere = bozza.promotoriDaRimuovere ?? [];
        orariDaAggiungere = bozza.orariDaAggiungere ?? [];
        orariDaRimuovere = bozza.orariDaRimuovere ?? [];
        orariModificati = bozza.orariModificati ?? [];

        //cambia il valore del select di action
        const select = document.getElementById("selectAction");
        select.value = bozza.action;
        await loadAzioni();

        switch(bozza.action){
            case "add":
                    if (bozza.nome)
                        document.querySelector("[name='nome']").value = bozza.nome;
                    if (bozza.descrizione)
                        document.querySelector("[name='descrizione']").value = bozza.descrizione;
                    if (bozza.dataInizio)
                        document.querySelector("[name='data_inizio']").value = bozza.dataInizio;
                    if (bozza.dataFine)
                        document.querySelector("[name='data_fine']").value = bozza.dataFine;
                    if (bozza.rappresentante)
                        document.querySelector("[name='rappresentante']").value = bozza.rappresentante;
                    if (bozza.posti)
                        document.querySelector("[name='posti']").value = bozza.posti;
                    if(bozza.richiedente)
                        document.getElementById("richiedente").value = bozza.richiedente;
                    break;
            default:
                    if(bozza.idEvento){
                        const selectEvento = document.getElementById("evento-select");
                        selectEvento.value = bozza.idEvento;
                        await loadEvento();

                        if(bozza.richiedente){
                            document.getElementById("richiedente").value = bozza.richiedente;
                        }

                        switch(bozza.action){
                            case "edit":
                                if (bozza.nome)
                                    document.getElementById("edit-nome").value = bozza.nome;
                                if (bozza.descrizione)
                                    document.getElementById("edit-descrizione").value = bozza.descrizione;
                                if (bozza.dataInizio)
                                    document.getElementById("edit-data_inizio").value = bozza.dataInizio;
                                if (bozza.dataFine)
                                    document.getElementById("edit-data_fine").value = bozza.dataFine;
                                if (bozza.rappresentante)
                                    document.getElementById("edit-rappresentante").value = bozza.rappresentante;
                                if (bozza.posti)
                                    document.getElementById("edit-posti").value = bozza.posti;
                                break;
                            case "addOrario":
                            case "editOrario":
                            case "deleteOrario":
                            break;
                        }
                    }
        }
    } finally{ //delete bozza
        openBozza = false;
        sessionStorage.removeItem("bozzaEvento");
    }
}

function saveAndChangePage(where){

    salvaBozzaEvento();

    window.location.href = where;
}

function formattaDataDB(data) {
    if (!data) return null;

    return data
        .replace("T", " ")
        + (data.length === 10 ? " 00:00:00" : ":00");
}

async function saveAllChanges() {

    const action = document.getElementById("selectAction").value;

    const dati = {
        action
    };

    switch (action) {

            case "add":{

                const mancanti = [];

                const nome = document.querySelector("[name='nome']").value;
                const descrizione = document.querySelector("[name='descrizione']").value;
                const dataInizio = document.querySelector("[name='data_inizio']").value;
                const dataFine = document.querySelector("[name='data_fine']").value;
                const rappresentante = document.querySelector("[name='rappresentante']").value;
                const posti = Number(document.querySelector("[name='posti']").value);

                if (!nome) mancanti.push("nome");
                if (!descrizione) mancanti.push("descrizione");
                if (!dataInizio) mancanti.push("data di inizio");
                if (!dataFine) mancanti.push("data di fine");
                if (!rappresentante) mancanti.push("rappresentante");
                if (!posti) mancanti.push("posti");

                let richiedente;
                if(document.getElementById('richiedente')){
                    richiedente = document.getElementById('richiedente').value;
                    if(!richiedente) mancanti.push("richiedente");
                }

                if (collaboratoriDaAggiungere.length === 0)
                    mancanti.push("almeno un collaboratore");

                if (promotoriDaAggiungere.length === 0)
                    mancanti.push("almeno un promotore");

                if (orariDaAggiungere.length === 0)
                    mancanti.push("almeno un orario");

                if (mancanti.length > 0) {
                    alert("Inserisci: " + mancanti.join(", "));
                    return;
                }

                Object.assign(dati, {
                    nome,
                    descrizione,
                    dataInizio: formattaDataDB(dataInizio),
                    dataFine: formattaDataDB(dataFine),
                    rappresentante,
                    posti,
                    collaboratoriDaAggiungere,
                    promotoriDaAggiungere,
                    orariDaAggiungere: orariDaAggiungere.map(o => ({
                        ...o,
                        inizio: formattaDataDB(o.inizio),
                        fine: formattaDataDB(o.fine)
                    }))
                });

                if (document.getElementById("richiedente")) {
                    dati.richiedente = richiedente;
                }

                break;
        }
            case "edit":{
                if(!document.getElementById("evento-select").value){
                    alert("Salvataggio è andato storto");
                    return;
                }

                let richiedente;
                if(document.getElementById('richiedente')){
                    richiedente = document.getElementById('richiedente').value;
                    if(!richiedente) {
                        alert("Inserire riferente della richiesta.");
                        return;
                    }
                }

                const nome = document.getElementById("edit-nome").value
                const descrizione = document.getElementById("edit-descrizione").value;
                const dataInizio = document.getElementById("edit-data_inizio").value;
                const dataFine = document.getElementById("edit-data_fine").value;
                const rappresentante = document.getElementById("edit-rappresentante").value;
                const posti = Number(document.getElementById("edit-posti").value);

                const nessunaModifica =
                    nome === originaleEvento.nome &&
                    descrizione === originaleEvento.descrizione &&
                    dataInizio === originaleEvento.inizio &&
                    dataFine === originaleEvento.fine &&
                    rappresentante === originaleEvento.rappresentante &&
                    posti === originaleEvento.posti &&
                    orariModificati.length === 0 &&
                    orariDaRimuovere.length === 0 &&
                    orariDaAggiungere.length === 0 &&
                    collaboratoriDaAggiungere.length === 0 &&
                    collaboratoriDaRimuovere.length === 0 &&
                    promotoriDaAggiungere.length === 0 &&
                    promotoriDaRimuovere.length === 0;

                if(nessunaModifica){
                    alert("Nessuna modifica fatta");
                    return;
                }

                const promotoriFinali = [
                    ...promotoriOriginali
                        .filter(o => !promotoriDaRimuovere.some(r => r.codice === o.codice))
                        .map(o => promotoriModificati.find(m => m.codice === o.codice) ?? o),

                    ...promotoriDaAggiungere
                ];

                if(promotoriFinali.length === 0){
                    alert("Nessun promotore rimasto");
                    return;
                }

                const orariFinali = [
                    ...orariOriginali
                        .filter(o => !orariDaRimuovere.some(r => r.codice === o.codice && r.codice_evento === o.codice_evento))
                        .map(o => orariModificati.find(m => m.codice === o.codice && m.codice_evento === o.codice_evento) ?? o),

                    ...orariDaAggiungere
                ];

                if(orariFinali.length === 0){
                    alert("Nessun orario rimasto");
                    return;
                }

                const richiedeApprovazione =
                    rappresentante !== originaleEvento.rappresentante ||
                    orariModificati.length > 0 ||
                    orariDaRimuovere.length > 0 ||
                    orariDaAggiungere.length > 0;

                Object.assign(dati, {
                    idEvento: Number(document.getElementById("evento-select").value),
                    nome: nome !== originaleEvento.nome ? nome : null,
                    descrizione: descrizione !== originaleEvento.descrizione
                        ? descrizione
                        : null,
                    dataInizio: dataInizio !== originaleEvento.inizio
                        ? formattaDataDB(dataInizio)
                        : null,
                    dataFine: dataFine !== originaleEvento.fine
                        ? formattaDataDB(dataFine)
                        : null,
                    rappresentante: rappresentante !== originaleEvento.rappresentante
                        ? rappresentante
                        : null,
                    posti: posti !== originaleEvento.posti
                        ? posti
                        : null,
                    collaboratoriDaAggiungere: collaboratoriDaAggiungere.length > 0
                        ? collaboratoriDaAggiungere
                        : null,
                    collaboratoriDaRimuovere: collaboratoriDaRimuovere.length > 0
                        ? collaboratoriDaRimuovere
                        : null,
                    promotoriDaAggiungere: promotoriDaAggiungere.length > 0
                        ? promotoriDaAggiungere
                        : null,
                    promotoriDaRimuovere: promotoriDaRimuovere.length > 0
                        ? promotoriDaRimuovere
                        : null,
                    orariDaAggiungere: orariDaAggiungere.length > 0
                        ? orariDaAggiungere.map(o => ({
                            ...o,
                            inizio: formattaDataDB(o.inizio),
                            fine: formattaDataDB(o.fine)
                        }))
                        : null,
                    orariDaRimuovere: orariDaRimuovere.length > 0
                        ? orariDaRimuovere
                        : null,
                    orariModificati: orariModificati.length > 0
                        ? orariModificati.map(o => ({
                            ...o,
                            inizio: formattaDataDB(o.inizio),
                            fine: formattaDataDB(o.fine)
                        }))
                        : null,
                    richiedeApprovazione
                });

                if (document.getElementById("richiedente")) {
                    dati.richiedente = richiedente;
                }

                break;
        }
        case "delete":
            if(!document.getElementById("evento-select").value){
                alert("Cancellazione è andata storta");
                return;
            }

            let richiedente;
            if(document.getElementById('richiedente')){
                richiedente = document.getElementById('richiedente').value;
                if(!richiedente) {
                    alert("Inserire riferente della richiesta.");
                    return;
                }
            }

            if (!confirm("Sei sicuro di voler eliminare questo evento?"))
                return;

            Object.assign(dati, {
                idEvento: Number(document.getElementById("evento-select").value)
            });

            if (document.getElementById("richiedente")) {
                dati.richiedente = richiedente;
            }

            break;

        case "addOrario":
            let richiedente;
            if(document.getElementById('richiedente')){
                richiedente = document.getElementById('richiedente').value;
                if(!richiedente) {
                    alert("Inserire riferente della richiesta.");
                    return;
                }
            }

            if (orariDaAggiungere.length === 0){
                alert("Inserisci almeno un orario");
                return;
            }

            Object.assign(dati, {
                idEvento: Number(document.getElementById("evento-select").value),
                orariDaAggiungere: orariDaAggiungere.map(o => ({
                    ...o,
                    inizio: formattaDataDB(o.inizio),
                    fine: formattaDataDB(o.fine)
                }))
            });

            if (document.getElementById("richiedente")) {
                dati.richiedente = richiedente;
            }

            break;

        case "editOrario":
            if(!document.getElementById("evento-select").value ){
                alert("Salvataggio è andato storto");
                return;
            }

            let richiedente;
            if(document.getElementById('richiedente')){
                richiedente = document.getElementById('richiedente').value;
                if(!richiedente) {
                    alert("Inserire riferente della richiesta.");
                    return;
                }
            }

            if (orariModificati.length === 0 && orariDaRimuovere.length === 0 && orariDaAggiungere.length === 0){
                alert("Nessuna modifica fatta");
                return;
            }

            const orariFinali = [
                ...orariOriginali
                    .filter(o => !orariDaRimuovere.some(r => r.codice === o.codice && r.codice_evento === o.codice_evento))
                    .map(o => orariModificati.find(m => m.codice === o.codice && m.codice_evento === o.codice_evento) ?? o),

                ...orariDaAggiungere
            ];

            if(orariFinali.length === 0){
                alert("Nessun orario rimasto");
                return;
            }

            Object.assign(dati, {
                idEvento: Number(document.getElementById("evento-select").value),
                orariDaAggiungere: orariDaAggiungere.map(o => ({
                    ...o,
                    inizio: formattaDataDB(o.inizio),
                    fine: formattaDataDB(o.fine)
                })),
                orariDaRimuovere,
                orariModificati: orariModificati.map(o => ({
                    ...o,
                    inizio: formattaDataDB(o.inizio),
                    fine: formattaDataDB(o.fine)
                }))
            });

            if (document.getElementById("richiedente")) {
                dati.richiedente = richiedente;
            }

            break;

        case "deleteOrario":
            if(!document.getElementById("evento-select").value){
                alert("Cancellazione è andata storta");
                return;
            }

            let richiedente;
            if(document.getElementById('richiedente')){
                richiedente = document.getElementById('richiedente').value;
                if(!richiedente) {
                    alert("Inserire riferente della richiesta.");
                    return;
                }
            }

            if (orariDaRimuovere.length === 0) {
                alert("Nessuna modifica fatta");
                return;
            }

            const orariRimasti = orariOriginali.filter(o =>
                !orariDaRimuovere.some(r =>
                    r.codice === o.codice &&
                    r.codice_evento === o.codice_evento
                )
            );

            if (orariRimasti.length === 0) {
                alert("Un evento deve avere almeno un orario");
                return;
            }

            if (!confirm("Sei sicuro di voler eliminare questo orario?"))
                return;

            Object.assign(dati, {
                idEvento: Number(document.getElementById("evento-select").value),
                orariDaRimuovere
            });

            if (document.getElementById("richiedente")) {
                dati.richiedente = richiedente;
            }

            break;
    }

    const res = await fetch("./Api/api-saveEvento.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(dati)
    });

    const json = await res.json();

    alert(json.message);

    if (json.success) {
        sessionStorage.removeItem("bozzaEvento");
        resetEvento();
        await loadAzioni();
    }
}

// --- Avvio ---
const main = document.querySelector("main");

main.innerHTML = renderMainEventi();

document
    .getElementById("selectAction")
    .addEventListener("change", loadAzioni);

document
    .getElementById("visualizzaEventi")
    .addEventListener("click", () => {
        window.location.href = "../PHP/eventiPersona.php";
    });

loadAzioni()
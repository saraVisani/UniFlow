let idP = new Set();
let idC = new Set();
let idI = new Set();
let provincieDaAggiungere = [];
let cittaDaAggiungere = [];
let provincieDaEliminare = [];
let cittaDaEliminare = [];
let provincieModificare = [];
let cittaModificare = [];
let provincieOriginali = [];
let cittaOriginali = [];
let originalPlace = null;

function reset(){
    idP.clear();
    idC.clear();
    idI.clear();
    provincieDaAggiungere = [];
    cittaDaAggiungere = [];
    provincieDaEliminare = [];
    cittaDaEliminare = [];
    provincieModificare = [];
    cittaModificare = [];
    provincieOriginali = [];
    cittaOriginali = [];
    originalPlace = null;
}

function luoghiList(list){
    return list.map(l => `
            <option value="${l.codice}">
                ${l.nome_sede ? `${l.nome_sede}` : `${l.nome}`} c: ${l.capienza} (${l.indirizzo})
            </option>
        `).join("");
}

function provinciaList(list){
    return list.map(l => `
            <option value="${l.codice}">
                ${l.nome}
            </option>
        `).join("");
}

function cittaList(list){
    return list.map(l => `
            <option value="${l.cod_Prov}-${l.codice}">
                ${l.nome}
            </option>
        `).join("");
}

function viaList(list){
    return list.map(l => `
            <option value="${l.via}">
                ${l.via}
            </option>
        `).join("");
}

function persUnivList(list){
    return list.map(p => `
            <option value="${p.matr}">
                ${p.nome} ${p.cognome} (${p.matr})
            </option>
        `).join("");
}

function renderMainEventi() {
    return `
    <header class="Luoghi-header">

        <h2 id="eventi-title">
            Azioni su Luoghi:
        </h2>

        <div class="filters">
            <select id="selectAction">
            </select>
        </div>

    </header>


    <form id="azioni-form" method="post"></form>
    `;
}

function changeCities(cittalist){
    const citiesHTML = document.getElementById("citta");
    const provincia = document.getElementById("provincia").value;
    if(provincia === "") return;
    let citta = cittaList(cittalist.filter(c => c.cod_Prov == provincia));
    citiesHTML.innerHTML =
    `<option value="">-- Nessuno --</option>` + citta;
}

function addInputs(prof, provincie, via){
    const tipo = document.getElementById("tipo").value;
    const extra = document.getElementById("extra-inputs");
    let contTipo;

    if(!originalPlace){
        contTipo = true;
    } else {
        contTipo = tipo !== originalPlace.tipo;
    }

    let html = "";

    if (!originalPlace && tipo !== "alt" && contTipo) {
        html += `
            <label for="codClasse">Codice Stanza</label>
            <input type="number" id="codClasse"/>
        `;
    }

    if (tipo === "uff" && contTipo) {
        html += `
            <label>Assegnato</label>
            <select id="assegnato">
                <option value="">-- Nessuno --</option>
                ${persUnivList(prof)}
            </select>
        `;
    } else if (tipo === "cla" && contTipo) {
        html += `
            <label for="lab">Laboratorio</label>
            <input type="checkbox" id="lab"/>
        `;
    } else if (tipo === "alt") {
        const prov = provinciaList(provincie)
        const vie = viaList(via)
        html += `<label>Provincia</label>
                <select name="provincia" id="provincia">
                    <option value="">-- Nessuno --</option>
                    ${prov}
                </select>

                <label>Città</label>
                <select name="citta" id="citta">
                    <option value="">-- Nessuno --</option>
                </select>

                <label for="via">Via</label>
                <select name="via" id="via">
                    <option value="">-- Nessuno --</option>
                    ${vie}
                </select>

                <input type="text" id="nomeVia"/>

                <label for="civico">Numero Civico</label>
                <input type="number" id="civico" min="1"/>`
    }

    extra.innerHTML = html;
}

function adminForum(action, json){
    switch(action){
        case "add":{
            if(json.idI){
                idI = new Set(json.idI);
            }
            return `
                    <label for="nome">Nome Luogo</label>
                    <input type="text" id="nome"/>

                    <label for="capienza">Capienza</label>
                    <input type="number" id="capienza" min="1"/>

                    <label>Tipo Luogo</label>
                    <select name="tipo" id="tipo">
                        <option value="alt">Altro</option>
                        <option value="uni">Universitario</option>
                        <option value="uff">Ufficio</option>
                        <option value="cla">Classe</option>
                    </select>
                    <div id="extra-inputs"></div>

                    <br>
                    <button type="button" onclick="saveAllChanges()">
                        Invia richiesta
                    </button>
            `;}
        case "edit":
        case "delete":{
            const luoghi = luoghiList(json.luoghi);
            return `
                <label>Luoghi</label>

                <select id="luogo-select">
                    <option value="">-- Seleziona un luogo --</option>
                    ${luoghi}
                </select>

                <div id="luogo-content"></div>
            `;}
        case "addSede":{
            idI = new Set(json.idI);
            const prov = provinciaList(json.provincie);
            const vie = viaList(json.via);
            return `
                    <label>Provincia</label>
                    <select name="provincia" id="provincia">
                        <option value="">-- Nessuno --</option>
                        ${prov}
                    </select>

                    <label>Città</label>
                    <select name="citta" id="citta">
                        <option value="">-- Nessuno --</option>
                    </select>

                    <label>Via</label>
                    <select name="via" id="via">
                        <option value="">-- Nessuno --</option>
                        ${vie}
                    </select>

                    <input type="text" id="nomeVia"/>

                    <label>Numero Civico</label>
                    <input type="number" name="civico" min="1"/>

                    <label>Nome Sede</label>
                    <input type="text" id="nome"/>

                    <label>Descrizione</label>
                    <textarea id="descrizione"></textarea>

                    <label for="immagine">Immagine Sede</label>
                    <input
                        type="file"
                        id="immagine"
                        accept="image/*"
                    />

                    <label for="descrizioneImmagine">Descrizione Immagine</label>
                    <textarea
                        id="descrizioneImmagine"
                        name="descrizioneImmagine">
                    </textarea>

                    <br>
                    <button type="button" onclick="saveAllChanges()">
                        Invia richiesta
                    </button>
                    `;}
        case "editSede":
        case "deleteSede":{
            const sedi = provinciaList(json.sedi);
            return `
                <label>Sedi</label>

                <select id="luogo-select">
                    <option value="">-- Seleziona una sede --</option>
                    ${sedi}
                </select>

                <div id="luogo-content"></div>
            `;}
        case "addCitta":{
            idC = new Set(json.idC);
            idP = new Set(json.idP);
            const prov = provinciaList(json.provincie);
            return `
                <p>Città inserite: </p>

                <ul id="lista-citta">
                    ${renderListaCitta("add")}
                </ul>

                <label>Provincia</label>
                <select name="provincia" id="cod_prov">
                    <option value="">-- Nessuno --</option>
                    ${prov}
                </select>

                <label for="cod_citta">Codice Città</label>
                <input
                    type="text"
                    id="cod_citta"
                    name="cod_citta"
                />

                <label for="nome_citta">Nome Città</label>
                <input
                    type="text"
                    id="nome_citta"
                    name="nome_citta"
                />

                <button type="button" id="add-citta" onclick='addCitta("add")'>
                    Aggiungi città
                </button>
            `;}
        case "editCitta":{
            idC = new Set(json.idC);
            idP = new Set(json.idP);
            cittaOriginali = json.citta;
            provincieOriginali = json.provincie;
            const prov = provinciaList(json.provincie);
            return `
                <p>Città inserite: </p>

                <ul id="lista-citta">
                    ${renderListaCitta("edit")}
                </ul>

                <label>Provincia</label>
                <select name="provincia" id="cod_prov">
                    <option value="">-- Nessuno --</option>
                    ${prov}
                </select>

                <label for="cod_citta">Codice Città</label>
                <input
                    type="text"
                    id="cod_citta"
                    name="cod_citta"
                />

                <label for="nome_citta">Nome Città</label>
                <input
                    type="text"
                    id="nome_citta"
                    name="nome_citta"
                />

                <button type="button" id="add-citta" onclick='addCitta("edit")'>
                    Aggiungi città
                </button>

            `;
        }
        case "deleteCitta":{
            cittaOriginali = json.citta;
            provincieOriginali = json.provincie;
            const citta = cittaList(json.citta);
            return `
                <label>Città</label>

                <ul id="lista-citta">
                    ${renderListaCitta("delete")}
                </ul>
            `;}
        case "addProvincia":{
            idC = new Set(json.idC);
            idP = new Set(json.idP);
            return `
                <p>Provincie inserite: </p>

                <ul id="lista-provincie">
                    ${renderListaProvincie("add")}
                </ul>

                <label for="cod_prov">Codice Provincia</label>
                <input
                    type="text"
                    id="cod_prov"
                    name="cod_prov"
                />

                <label for="nome_provincia">Nome Provincia</label>
                <input
                    type="text"
                    id="nome_provincia"
                    name="nome_provincia"
                />

                <button type="button" id="add-provincia" onclick='addProvincia("add")'>
                    Aggiungi provincia
                </button>

            `;}
        case "editProvincia":{
            idC = new Set(json.idC);
            idP = new Set(json.idP);
            provincieOriginali = json.provincie;
            return `
                <p>Provincie inserite: </p>

                <ul id="lista-provincie">
                    ${renderListaProvincie("edit")}
                </ul>

                <label for="cod_prov">Codice Provincia</label>
                <input
                    type="text"
                    id="cod_prov"
                    name="cod_prov"
                />

                <label for="nome_provincia">Nome Provincia</label>
                <input
                    type="text"
                    id="nome_provincia"
                    name="nome_provincia"
                />

                <button type="button" id="add-provincia" onclick='addProvincia("edit")'>
                    Aggiungi provincia
                </button>

            `;
        }
        case "deleteProvincia":{
            provincieOriginali = json.provincie;
            return `
                <label>Provincie</label>

                <ul id="lista-provincie">
                    ${renderListaProvincie("delete")}
                </ul>
            `;}
    }
}

function renderForum(json) {
    const action = json.action;
    const logged = json.logged;
    const level = logged ? json.level : 0;
    if (action === "nothing") {
        return `<p>Seleziona un'azione per visualizzare il forum.</p>`;
    }
    if(level == 4){
        return adminForum(action, json);
    } else {
        if(action === "add"){
            idI = new Set(json.idI);
            const prov = provinciaList(json.provincie)
            const vie = viaList(json.via)

            return `
                    <label>Provincia</label>
                    <select name="provincia" id="provincia">
                        <option value="">-- Nessuno --</option>
                        ${prov}
                    </select>

                    <label>Città</label>
                    <select name="citta" id="citta">
                        <option value="">-- Nessuno --</option>
                    </select>

                    <label>Via</label>
                    <select name="via" id="via">
                        <option value="">-- Nessuno --</option>
                        ${vie}
                    </select>

                    <input type="text" id="nomeVia"/>

                    <label>Numero Civico</label>
                    <input type="number" name="civico" min="1"/>

                    <label>Nome Luogo</label>
                    <input type="text" id="nome"/>

                    <label>Capienza</label>
                    <input type="number" name="capienza" min="1"/>

                    <br>
                    <button type="button" onclick="saveAllChanges()">
                        Invia richiesta
                    </button>
            `;
        } else { //select luogo
            const luoghi = luoghiList(json.luoghi);
            return `
                <label>Luoghi</label>

                <select id="luogo-select">
                    <option value="">-- Seleziona un luogo --</option>
                    ${luoghi}
                </select>

                <div id="luogo-content"></div>
            `;
        }
    }
}

function adminLuogoDetails(json) {
    const action = json.action;
    if(action === "deleteSede"){
        return `
            <button
                type="button"
                id="btnDeleteSede"
                onclick="saveAllChanges()"
            >
                Elimina sede
            </button>
        `;
    } else{
        idI = new Set(json.idI);
        const prov = provinciaList(json.provincie)
        const vie = viaList(json.via)
        originalPlace = json.sede;
        return `
                <p>Provincia corrente: ${originalPlace.indirizzo.prov_nome}</p>

                <select name="provincia" id="provincia">
                    <option value="">-- Nessuno --</option>
                    ${prov}
                </select>

                <p>Città corrente: ${originalPlace.indirizzo.citta_nome}</p>

                <select name="citta" id="citta">
                    <option value="">-- Nessuno --</option>
                </select>

                <p>Via corrente:
                    ${originalPlace.indirizzo.via}
                    ${originalPlace.indirizzo.nome_via}
                    ${originalPlace.indirizzo.n_civico}
                </p>

                <select name="via" id="via">
                    <option value="">-- Nessuno --</option>
                    ${vie}
                </select>

                <input type="text" id="nomeVia">

                <input
                    type="number"
                    id="civico"
                    min="1"
                >

                <label>Nome della sede corrente: ${originalPlace.nome}</label>
                <input type="text" id="nome"/>

                <label>Descrizione corrente: ${originalPlace.descrizione}</label>
                <textarea id="descrizione"></textarea>

                <label for="immagine">Immagine corrente: </label>
                <img
                    src="${UPLOAD_DIR}${originalPlace.path}"
                    alt="Immagine sede"
                />
                <input
                    type="file"
                    id="immagine"
                    accept="image/*"
                />

                <label for="descrizioneImmagine">Descrizione immagine corrente: ${originalPlace.descrizione_img}</label>
                <textarea
                    id="descrizioneImmagine"
                    name="descrizioneImmagine">
                </textarea>

                <br>
                <button type="button" onclick="saveAllChanges()">
                    Invia richiesta
                </button>
                `;
    }
}

function renderLuogoDetails(json) {
    const logged = json.logged;
    const level = logged ? json.level : 0;
    const action = json.action;
    if(level == 4 && !(action === "edit" || action === "delete")){
        return adminLuogoDetails(json);
    } else {
        originalPlace = json.luogo;
        if(action === "delete"){
            return `
                    <button type="button" id="btnDeleteLuogo" onclick="saveAllChanges()">
                        Elimina luogo
                    </button>`;
        } else {
            idI = new Set(json.idI);
            originalPlace = json.luogo;
            const prov = provinciaList(json.provincie);
            const vie = viaList(json.via);

            let datiEsterno = "";

            if (originalPlace.tipo === "esterno") {
                datiEsterno = `
                    <p>Provincia corrente: ${originalPlace.indirizzo.prov_nome}</p>

                    <select name="provincia" id="provincia">
                        <option value="">-- Nessuno --</option>
                        ${prov}
                    </select>

                    <p>Città corrente: ${originalPlace.indirizzo.citta_nome}</p>

                    <select name="citta" id="citta">
                        <option value="">-- Nessuno --</option>
                    </select>

                    <p>Via corrente:
                        ${originalPlace.indirizzo.via}
                        ${originalPlace.indirizzo.nome_via}
                        ${originalPlace.indirizzo.n_civico}
                    </p>

                    <select name="via" id="via">
                        <option value="">-- Nessuno --</option>
                        ${vie}
                    </select>

                    <input type="text" id="nomeVia">

                    <input
                        type="number"
                        id="civico"
                        min="1"
                    >
                `;
            }

            let datiTipo = "";

            if (originalPlace.tipo === "classe") {

                datiTipo = `
                    <label for="codClasse">Codice stanza corrente: ${originalPlace.cod_stanza}</label>

                    <input
                        type="number"
                        id="codClasse"
                    />

                    <label for="lab">Laboratorio</label>

                    <input
                        type="checkbox"
                        id="lab"
                        ${originalPlace.lab ? "checked" : ""}
                    >

                    <label>Cambio tipo Luogo</label>

                    <select name="tipo" id="tipo">
                        <option value="cla">Classe</option>
                        <option value="uni">Universitario</option>
                        <option value="uff">Ufficio</option>
                    </select>

                    <div id="extra-inputs"></div>
                `;

            } else if (originalPlace.tipo === "ufficio") {

                datiTipo = `
                    <label for="codClasse">Codice stanza corrente: ${originalPlace.cod_stanza}</label>

                    <input
                        type="number"
                        id="codClasse"
                    />

                    <label>Assegnato correntemente: ${originalPlace.prof_nome} ${originalPlace.prof_matr}</label>

                    <select name="assegnato">
                        <option value="">-- Nessuno --</option>
                        ${persUnivList(json.professori)}
                    </select>

                    <label>Cambio tipo Luogo</label>

                    <select name="tipo" id="tipo">
                        <option value="uff">Ufficio</option>
                        <option value="cla">Classe</option>
                        <option value="uni">Universitario</option>
                    </select>

                    <div id="extra-inputs"></div>
                `;
            } else if (originalPlace.tipo === "universitario") {
                datiTipo = `
                    <label for="codClasse">Codice stanza corrente: ${originalPlace.cod_stanza}</label>

                    <input
                        type="number"
                        id="codClasse"
                    >

                    <label>Cambio tipo Luogo</label>

                    <select name="tipo" id="tipo">
                        <option value="uff">Ufficio</option>
                        <option value="cla">Classe</option>
                        <option value="uni">Universitario</option>
                    </select>

                    <div id="extra-inputs"></div>
                `;
            }

            return `
                ${datiEsterno}

                <p>Nome luogo corrente: ${originalPlace.nome}</p>

                <input
                    type="text"
                    id="nome"
                />

                <p>Capienza corrente: ${originalPlace.capienza}</p>

                <input
                    type="number"
                    id="capienza"
                    min="1"
                />

                ${datiTipo}

                <br>

                <button
                    type="button"
                    onclick="saveAllChanges()"
                >
                    Invia richiesta
                </button>
            `;
        }
    }
}

async function loadLuogo() {
    const luogo = document.getElementById("luogo-select").value;

    if (!luogo)
        return;

    const azione = document.getElementById("selectAction").value;

    const res = await fetch(
        `./Api/api-azioniLuogo.php?azione=${azione}&luogo=${luogo}`
    );

    const json = await res.json();

    document.getElementById("luogo-content").innerHTML =
        renderLuogoDetails(json);
}

async function loadAzioni() {

    reset();

    const azione = document.getElementById("selectAction").value;

    const url = azione === ""
    ? "./Api/api-azioniLuogo.php"
    : `./Api/api-azioniLuogo.php?azione=${azione}`;

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

        let select = document.getElementById("luogo-select");

        if (select) {
            select.addEventListener("change", loadLuogo);
        }

        select = document.getElementById("provincia");

        if (select) {
            select.addEventListener("change", () => changeCities(json.citta));
        }

        select = document.getElementById("tipo");

        if (select) {
            select.addEventListener("change", () => addInputs(json.professori, json.provincie, json.via));
        }
    }
}

function renderListaCitta(action, idProv = -1) {

    let lista;

    switch (action) {

        case "add":
            lista = cittaDaAggiungere;
            break;

        case "edit":
            lista = cittaOriginali
                .map(o =>
                    cittaModificare.find(m =>
                        m.codice_Rif === o.codice &&
                        m.cod_Prov_Rif === o.cod_Prov
                    ) ?? o)
                .concat(cittaDaAggiungere);
            break;

        case "delete":
            lista = cittaOriginali;
            break;
    }

    if (idProv !== -1) {
        lista = lista.filter(c => c.cod_Prov === idProv);
    }

    return lista.map(citta => {

        const eliminata = cittaDaEliminare.some(r =>
            r.codice === citta.codice &&
            r.cod_Prov === citta.cod_Prov
        );

        let buttons = "";

        if (eliminata) {

            buttons = `
                <button onclick="restoreCitta(${action}, ${citta.cod_Prov}, ${citta.codice})">
                    Annulla
                </button>
            `;

        } else {

            if (action !== "delete") {
                buttons += `
                    <button onclick="editCitta(${action}, ${citta.cod_Prov}, ${citta.codice})">
                        Modifica
                    </button>
                `;
            }

            buttons += `
                <button onclick="removeCitta(${action}, ${citta.cod_Prov}, ${citta.codice})">
                    Elimina
                </button>
            `;
        }

        return `
            <li
                id="citta-${citta.cod_Prov}-${citta.codice}"
                class="${eliminata ? "pending-delete" : ""}"
            >

                <p>Codice: ${citta.codice}</p>
                <p>Nome: ${citta.nome}</p>

                ${buttons}

            </li>
        `;
    }).join("");
}

function renderListaProvincie(action) {

    let lista;

    switch (action) {

        case "add":
            lista = provincieDaAggiungere;
            break;

        case "edit":
            lista = provincieOriginali
                .map(o =>
                    provincieModificare.find(m =>
                        m.codice_Rif === o.codice
                    ) ?? o)
                .concat(provincieDaAggiungere);
            break;

        case "delete":
            lista = provincieOriginali;
            break;
    }

    return lista.map(prov => {

        const eliminata = provincieDaEliminare.some(r =>
            r.codice === prov.codice
        );

        let buttons = "";

        if (eliminata) {

            buttons = `
                <button onclick="restoreProvincia(${action}, ${prov.codice})">
                    Annulla
                </button>
            `;

        } else {

            if (action !== "delete") {
                buttons += `
                    <button onclick="editProvincia(${action}, ${prov.codice})">
                        Modifica Provincia
                    </button>
                `;
            }

            buttons += `
                <button onclick="removeProvincia(${action}, ${prov.codice})">
                    Elimina Provincia
                </button>
            `;
        }

        return `
            <li id="provincia-${prov.codice}" class="${eliminata ? "pending-delete" : ""}">

                <p>Codice: ${prov.codice}</p>
                <p>Nome: ${prov.nome}</p>

                <p>Lista città correnti</p>

                <ul>
                    ${renderListaCitta(action, prov.codice)}
                </ul>

                <label for="nome_citta-${prov.codice}">Nome Città</label>
                <input
                    type="text"
                    id="nome_citta-${prov.codice}"
                    name="nome_citta-${prov.codice}"
                />

                <button type="button" id="add-citta-${prov.codice}" onclick='addCitta(${action}, ${prov.codice})'>
                    Aggiungi città
                </button>

                ${buttons}

            </li>
        `;
    }).join("");
}

function addCitta(action, idProv = -1) {
    let other = true;
    if(idProv === -1){
        idProv = document.getElementById("cod_prov").value;
        other = false;
    }
    let codice;
    let nomeCitta;
    if(other){
        codice = document.getElementById(`cod_citta-${idProv}`).value.trim();
        nomeCitta = document.getElementById(`nome_citta-${idProv}`).value;
    } else {
        codice = document.getElementById("cod_citta").value.trim();
        nomeCitta = document.getElementById("nome_citta").value;
    }
    let mancanti=[];
    if(!idProv) mancanti.push("la provincia");
    if (!codice) mancanti.push("codice città");
    if(!nomeCitta) mancanti.push("nome della città");

    if(mancanti.length > 0){
        alert("Inserisci: " + mancanti.join(", "));
        return;
    }

    if (idC.has(`${idProv}-${codice}`)) {
        alert("Esiste già una città con questo codice.");
        return;
    }

    const nuovo = {
        cod_Prov: idProv,
        codice,
        nome: nomeCitta
    };

    idC.add(`${idProv}-${codice}`);
    cittaDaAggiungere.push(nuovo);
    refresh(action, "lista-citta", renderListaCitta);
}

function restoreCitta(action, idProv, idCitta) {
    if(idC.has(`${idProv}-${idCitta}`)){
        alert("Impossibile il restore perchè il codice non è disponibile");
        return;
    } else {
        cittaDaEliminare =
            cittaDaEliminare.filter(r => !(r.codice === idCitta && r.cod_Prov === idProv));
        idC.add(`${idProv}-${idCitta}`);
        refresh(action, "lista-citta", renderListaCitta);
    }
}

function editCitta(action, idProv, idCitta) {
    const html = document.getElementById(`citta-${idProv}-${idCitta}`);
    let o;

    if(cittaDaAggiungere.some(c => c.codice === idCitta && c.cod_Prov === idProv)){
        o = cittaDaAggiungere.find(r =>
            r.codice === idCitta && r.cod_Prov === idProv
        );
    } else if(cittaModificare.some(c => c.codice_Rif === idCitta && c.cod_Prov_Rif === idProv)){
        o = cittaModificare.find(r =>
            r.codice_Rif === idCitta && r.cod_Prov_Rif === idProv
        );
    } else {
        o = cittaOriginali.find(r =>
            r.codice === idCitta && r.cod_Prov === idProv
        );
    }

    html.innerHTML = `
            <label>Provincia</label>
            <select name="provincia-${o.cod_Prov}-${o.codice}" id="provincia-${o.cod_Prov}-${o.codice}">
                <option value="${o.cod_Prov}">${provincieOriginali.find(p => p.codice === o.cod_Prov).nome}</option>
                ${provinciaList(provincieOriginali.filter(p => p.codice !== o.cod_Prov))}
            </select>

            <label>Codice</label>
            <input
                type="text"
                id="cod_citta-${o.cod_Prov}-${o.codice}"
                value="${o.codice}"
            >

            <label for="nome_citta-${o.cod_Prov}-${o.codice}">Nome Città</label>
            <input
                type="text"
                id="nome_citta-${o.cod_Prov}-${o.codice}"
                name="nome_citta-${o.cod_Prov}-${o.codice}"
                value = "${o.nome}"
            />
            <button onclick="confirmEditCitta(${action}, ${o.cod_Prov}, ${o.codice})">
                Conferma
            </button>
    `;
}

function removeCitta(action, idProv, idCitta) {
    if(cittaDaAggiungere.some(r => r.codice === idCitta && r.cod_Prov === idProv)){
        idC.delete(`${idProv}-${idCitta}`);
        cittaDaAggiungere = cittaDaAggiungere.filter(r => !(r.codice === idCitta && r.cod_Prov === idProv));
    } else if(cittaModificare.some(r => r.codice_Rif === idCitta && r.cod_Prov_Rif === idProv)){
        const c = cittaModificare.find(r => r.codice_Rif === idCitta && r.cod_Prov_Rif === idProv);
        if(!idC.has(`${idProv}-${idCitta}`)){
            cittaModificare = cittaModificare.filter(r => !(r.codice_Rif === idCitta && r.cod_Prov_Rif === idProv));
            idC.add(`${idProv}-${idCitta}`);
            idC.delete(`${c.cod_Prov}-${c.codice}`);
        }else{
            alert("Non è possibile eliminare la modifica perchè il codice originale è occupato");
            return;
        }
    } else {
        cittaDaEliminare.push({
            codice:idCitta,
            cod_Prov:idProv
        });
        idC.delete(`${idProv}-${idCitta}`);
    }
    refresh(action, "lista-citta", renderListaCitta);
}

function confirmEditCitta(action, idProv, idCitta){
    const nuovoCodiceProv =
        document.getElementById(`provincia-${idProv}-${idCitta}`).value.trim();

    const nuovoCodice =
        document.getElementById(`cod_citta-${idProv}-${idCitta}`).value.trim();

    const nuovoNome =
        document.getElementById(`nome_citta-${idProv}-${idCitta}`).value.trim();

    let citta =
    cittaDaAggiungere.find(c => c.cod_Prov === idProv && c.codice === idCitta)
    ?? cittaModificare.find(c => c.cod_Prov_Rif === idProv && c.codice_Rif === idCitta)
    ?? cittaOriginali.find(c => c.cod_Prov === idProv && c.codice === idCitta);

    if (nuovoCodiceProv === citta.cod_Prov && nuovoCodice === citta.codice && nuovoNome === citta.nome) {
        alert("Nessuna modifica effettuata");
        return;
    }

    const vecchiaChiave = `${idProv}-${idCitta}`;
    const nuovaChiave = `${nuovoCodiceProv}-${nuovoCodice}`;

    if (nuovaChiave !== vecchiaChiave && idC.has(nuovaChiave)) {
        alert("Codice città già esistente.");
        return;
    } else if (nuovaChiave !== vecchiaChiave){
        idC.delete(vecchiaChiave);
        idC.add(nuovaChiave);
    }

    if(cittaDaAggiungere.some(c => c.cod_Prov === idProv && c.codice === idCitta)){
        const modificato = {
            ...citta,
            ...(nuovoCodiceProv && { cod_Prov: nuovoCodiceProv }),
            ...(nuovoCodice && { codice: nuovoCodice }),
            ...(nuovoNome && { nome: nuovoNome }),
        };
        cittaDaAggiungere = cittaDaAggiungere.map(c =>
            c.cod_Prov === idProv && c.codice === idCitta
                ? modificato
                : c
        );
    } else {
        const modificato = {
            ...citta,
            cod_Prov_Rif: citta.cod_Prov_Rif ?? idProv,
            codice_Rif: citta.codice_Rif ?? idCitta,
            ...(nuovoCodiceProv && { cod_Prov: nuovoCodiceProv }),
            ...(nuovoCodice && { codice: nuovoCodice }),
            ...(nuovoNome && { nome: nuovoNome }),
        };
        const indice = cittaModificare.findIndex(c =>
            c.cod_Prov_Rif === idProv &&
            c.codice_Rif === idCitta
        );

        if (indice !== -1) {
            cittaModificare[indice] = modificato;
        } else {
            cittaModificare.push(modificato);
        }
    }
    refresh(action, "lista-citta", renderListaCitta);
}

function addProvincia(action) {
    const codice = document.getElementById("cod_prov").value.trim();
    let nomeProv = document.getElementById("nome_provincia").value;
    let mancanti=[];
    if(!codice) mancanti.push("codice provincia");
    if (!nomeProv) mancanti.push("nome della provincia");

    if(mancanti.length > 0){
        alert("Inserisci: " + mancanti.join(", "));
        return;
    }

    if(idP.has(codice)){
        alert("Questo codice è gia usato");
        return;
    }

    const nuovo = {
        codice: codice,
        nome: nomeProv
    };
    idP.add(codice);
    provincieDaAggiungere.push(nuovo);
    refresh(action, "lista-provincie", renderListaProvincie);
}

function restoreProvincia(action, idProv) {
    if(idP.has(idProv)){
        alert("Impossibile il restore perchè il codice non è disponibile");
        return;
    }
    provincieDaEliminare = provincieDaEliminare.filter(p => p.codice !== idProv);
    idP.add(idProv);
    refresh(action, "lista-provincie", renderListaProvincie);
}

function editProvincia(action, idProv) {
    const html = document.getElementById(`provincia-${idProv}`);
    let o;

    if(provincieDaAggiungere.some(p => p.codice === idProv)){
        o = provincieDaAggiungere.find(p => p.codice === idProv);
    } else if(provincieModificare.some(p => p.codice_Rif === idProv)){
        o = provincieModificare.find(p => p.codice_Rif === idProv);
    } else {
        o = provincieOriginali.find(p => p.codice === idProv);
    }

    html.innerHTML = `
            <label>Codice</label>
            <input
                type="text"
                id="cod_prov-${o.codice}"
                value="${o.codice}"
            >

            <label for="nome_provincia-${o.codice}">Nome Provincia</label>
            <input
                type="text"
                id="nome_provincia-${o.codice}"
                name="nome_provincia-${o.codice}"
                value = "${o.nome}"
            />

            <label for="nome_citta-${o.codice}">Nome Città</label>
            <input
                type="text"
                id="nome_citta-${o.codice}"
                name="nome_citta-${o.codice}"
            />

            <button type="button" id="add-citta-${o.codice}" onclick='addCitta(${action}, ${o.codice})'>
                Aggiungi città
            </button>

            <button onclick="confirmEditProvincia(${action}, ${o.codice})">
                Conferma
            </button>
    `;
}

function removeProvincia(action, idProv) {
    if(provincieDaAggiungere.some(p => p.codice === idProv)){
        provincieDaAggiungere = provincieDaAggiungere.filter(p => p.codice !== idProv);
        idP.delete(idProv);
    } else if(provincieModificare.some(p => p.codice_Rif === idProv)){
        if(!idP.has(idProv)){
            const p = provincieModificare.find(p => p.codice_Rif === idProv);
            provincieModificare = provincieModificare.filter(p => p.codice_Rif !== idProv);
            idP.delete(p.codice);
            idP.add(idProv);
        } else {
            alert("Non è possibile eliminare la modifica perchè il codice originale è occupato");
            return;
        }
    } else {
        provincieDaEliminare.push(idProv);
        idP.delete(idProv);
    }
    refresh(action, "lista-provincie", renderListaProvincie);
}

function confirmEditProvincia(action, idProv) {
    const codice = document.getElementById(`cod_prov-${idProv}`).value.trim();
    let nomeProv = document.getElementById(`nome_provincia-${idProv}`).value;
    const provincia =
    provincieDaAggiungere.find(c => c.codice === idProv)
    ?? provincieModificare.find(c => c.codice_Rif === idProv)
    ?? provincieOriginali.find(c => c.codice === idProv);

    if(provincia.nome === nomeProv && provincia.codice === codice){
        alert("Nessuna modifica è stata fatta");
        return;
    }

    if(provincia.codice !== codice && idP.has(codice)){
        alert("Modifica non accetabile il codice è impegnato");
        return;
    } else if (provincia.codice !== codice){
        idP.delete(provincia.codice);
        idP.add(codice);
    }

    if(provincieDaAggiungere.find(c => c.codice === idProv)){
        const modificato = {
            ...provincia,
            ...(codice && { codice: codice }),
            ...(nomeProv && { nome: nomeProv }),
        };
        provincieDaAggiungere = provincieDaAggiungere.map(c =>
            c.codice === idProv ? modificato : c
        );
    } else {
        const modificato = {
            ...provincia,
            codice_Rif: provincia.codice_Rif ?? idProv,
            ...(codice && { codice: codice }),
            ...(nomeProv && { nome: nomeProv }),
        };
        const indice = provincieModificare.findIndex(c =>
            c.codice_Rif === idProv
        );

        if (indice !== -1) {
            provincieModificare[indice] = modificato;
        } else {
            provincieModificare.push(modificato);
        }
    }
    refresh(action, "lista-provincie", renderListaProvincie);
}

function refresh(action, key, fun) {
    document.getElementById(key).innerHTML =
        fun(action);
}

async function saveAllChanges(){

    const action = document.getElementById("selectAction").value;

    const dati = {
        action
    };

    switch(action){

        case "add":{

            const nome = document.getElementById("nome").value.trim();
            const capienza = Number(document.getElementById("capienza").value);
            const tipo = document.getElementById("tipo")?.value || "alt";

            const mancanti = [];

            if(!nome)
                mancanti.push("nome");

            if(isNaN(capienza) || capienza <= 0)
                mancanti.push("capienza");

            const luogo = {
                nome,
                capienza,
            };

            if(tipo === "alt" ){
                const provincia = document.getElementById("provincia").value;
                const citta = document.getElementById("citta").value;
                const via = document.getElementById("via").value;
                const nomeVia = document.getElementById("nomeVia").value;
                const civico = document.getElementById("civico").value;

                if(!provincia) mancanti.push("provincia");
                if(!citta) mancanti.push("città");
                if(!via && !nomeVia) mancanti.push("via");
                if(!civico) mancanti.push("civico");

                if(idI.has(`${provincia}-${citta}-${civico}`)){
                    alert("Indirizzo inserito gia presente");
                    return;
                }

                luogo.tipo = "esterno";

                luogo.indirizzo = {
                    provincia,
                    citta,
                    via,
                    nomeVia,
                    civico
                };
            } else {
                const stanza = Number(document.getElementById("codClasse").value);
                if(isNaN(stanza) || stanza <= 0) mancanti.push("codice stanza")
                if(tipo === "uff"){
                    const ufficio = document.getElementById("assegnato").value;
                    if(!ufficio) mancanti.push("professore assegnato");
                    luogo.tipo = "ufficio";
                    luogo.assegnato = ufficio;
                } else if(tipo === "cla"){
                    const lab = document.getElementById("lab").checked;
                    luogo.tipo = "classe";
                    luogo.lab = lab;
                } else {
                    luogo.tipo = "universitario";
                }
                luogo.cod_stanza = stanza;
            }

            if(mancanti.length){
                alert("Inserisci: " + mancanti.join(", "));
                return;
            }

            Object.assign(dati, luogo);
            break;
        }

        case "edit":{
            const id =
                document.getElementById("luogo-select").value;
            if(!id){
                alert("Modifica andata storta");
                return;
            }

            const nome =
                document.getElementById("nome").value.trim();
            const capienza =
                Number(document.getElementById("capienza").value);

            const modifiche = {
                idLuogo: Number(id),
                nome:
                    nome !== originalPlace.nome
                    ? nome
                    : null,

                capienza:
                    (!isNaN(capienza) && capienza > 0 && capienza !== originalPlace.capienza)
                    ? capienza
                    : null,
                tipo_vecchio: originalPlace.tipo
            };

            if(originalPlace.tipo === "esterno"){
                const provincia = document.getElementById("provincia").value;
                const citta = document.getElementById("citta").value;
                const via = document.getElementById("via").value;
                const nomeVia = document.getElementById("nomeVia").value;
                const civico = document.getElementById("civico").value;

                const nuovaProv = provincia || originalPlace.indirizzo.provincia;
                const nuovaCitta = citta || originalPlace.indirizzo.citta;
                const nuovoCivico = civico || originalPlace.indirizzo.civico;

                modifiche.idProv = originalPlace.indirizzo.provincia;
                modifiche.idCitta = originalPlace.indirizzo.citta;
                modifiche.idCivico = originalPlace.indirizzo.civico;

                const vecchiaChiave =
                    `${originalPlace.indirizzo.provincia}-${originalPlace.indirizzo.citta}-${originalPlace.indirizzo.civico}`;

                const nuovaChiave =
                    `${nuovaProv}-${nuovaCitta}-${nuovoCivico}`;

                if (nuovaChiave !== vecchiaChiave && idI.has(nuovaChiave)) {
                    alert("Indirizzo già presente");
                    return;
                }

                modifiche.indirizzo = {
                    provincia: originalPlace.indirizzo.provincia !== provincia ? provincia : null,
                    citta: originalPlace.indirizzo.citta !== citta ? citta : null,
                    via: originalPlace.indirizzo.via !== via ? via : null,
                    nomeVia: originalPlace.indirizzo.nomeVia !== nomeVia ? nomeVia : null,
                    civico: originalPlace.indirizzo.civico !== civico ? civico : null
                };

                if (Object.values(modifiche.indirizzo).every(v => v === null)) {
                    modifiche.indirizzo = null;
                }
            } else {
                modifiche.idUni = Number(modifiche.cod_Uni);
                modifiche.idStanza =  Number(modifiche.cod_stanza);
                const tipo = document.getElementById("tipo").value;
                const valore = document.getElementById("codClasse").value.trim();
                modifiche.cod_stanza =
                    (valore || Number(valore) <= 0) && Number(valore) !== Number(originalPlace.cod_stanza)
                        ? Number(valore)
                        : null;
                if(originalPlace.tipo === "classe"){
                    const lab = document.getElementById("lab").checked;
                    modifiche.lab = originalPlace.lab !== lab ? lab : null;
                }
                if(originalPlace.tipo === "ufficio"){
                    const assegnato = document.getElementById("assegnato").value;
                    modifiche.assegnato = originalPlace.assegnato !== assegnato ? assegnato : null;
                }
                if(tipo === "cla" && originalPlace.tipo !== "classe"){
                    modifiche.nuovo_tipo = "classe";
                    const lab = document.getElementById("lab").checked;
                    modifiche.lab = lab;
                } else if(tipo === "uff" && originalPlace.tipo !== "ufficio"){
                    modifiche.nuovo_tipo = "ufficio";
                    const assegnato = document.getElementById("assegnato").value;
                    if(!assegnato){
                        alert("Inserire quale persona è assegnato");
                        return;
                    }
                    modifiche.assegnato = assegnato;
                } else if(tipo === "uni" && originalPlace.tipo !== "universitario"){
                    modifiche.nuovo_tipo = "universitario";
                }
            }

            const campi = [
                modifiche.nome,
                modifiche.capienza,
                modifiche.cod_stanza,
                modifiche.lab,
                modifiche.assegnato,
                modifiche.nuovo_tipo,
                modifiche.indirizzo
            ];

            const nessunaModifica = campi.every(v => {
                if (v === null || v === undefined) return true;

                if (typeof v === "object") {
                    return Object.values(v).every(x => x === null);
                }

                return false;
            });

            if (nessunaModifica) {
                alert("Nessuna modifica effettuata");
                return;
            }

            Object.assign(dati, modifiche);

            break;
        }

        case "delete":
            if(!document.getElementById("luogo-select").value){
                alert("Cancellazione è andata storta");
                return;
            }

            if (!confirm("Sei sicuro di voler eliminare questo luogo?"))
                return;

            Object.assign(dati, {
                idLuogo: Number(document.getElementById("luogo-select").value)
            });
            break;

        case "addSede":{
            const provincia =
                document.getElementById("provincia").value;
            const citta =
                document.getElementById("citta").value;
            const via =
                document.getElementById("via").value;
            const nomeVia =
                document.getElementById("nomeVia").value;
            const civico =
                document.getElementById("civico").value;

            const nome =
                document.getElementById("nome").value.trim();
            const descrizione =
                document.getElementById("descrizione").value;
            const immagine =
                document.getElementById("immagine");
            const descrizioneImm =
                document.getElementById("descrizioneImmagine").value;
            const mancanti=[];

            if(!provincia) mancanti.push("provincia");
            if(!citta) mancanti.push("città");
            if(!via) mancanti.push("tipo via");
            if(!nomeVia) mancanti.push("nome della via");
            if(!civico) mancanti.push("numero civico");
            if(!nome) mancanti.push("nome sede");
            if(!descrizione) mancanti.push("descrizione della sede");
            if(!immagine.files[0]) mancanti.push("immagine");
            if(!descrizioneImm) mancanti.push("descrizione dell'immagine");

            if(mancanti.length){
                alert("Inserisci: "+mancanti.join(", "));
                return;
            }

            if(idI.has(`${provincia}-${citta}-${civico}`)){
                alert("Indirizzo inserito gia presente");
                return;
            }

            Object.assign(dati,{
                provincia,
                citta,
                via,
                nomeVia,
                civico,
                nome,
                descrizione,
                descrizioneImm
            });

            const formData = new FormData();

            formData.append("action", "addSede");
            formData.append("file", immagine.files[0]);
            formData.append("dati", JSON.stringify(dati));

            const res = await fetch("./Api/api-saveLuogo.php", {
                method: "POST",
                body: formData
            });

            const json = await res.json();

            alert(json.message);

            if (json.success) {
                reset();
                await loadAzioni();
            }

            return;
        }

        case "editSede": {

            const id = document.getElementById("luogo-select").value;

            if (!id) {
                alert("Modifica andata storta");
                return;
            }

            const provincia = document.getElementById("provincia").value;
            const citta = document.getElementById("citta").value;
            const via = document.getElementById("via").value;
            const nomeVia = document.getElementById("nomeVia").value;
            const civico = document.getElementById("civico").value;

            const nome = document.getElementById("nome").value.trim();
            const descrizione = document.getElementById("descrizione").value;
            const descrizioneImm = document.getElementById("descrizioneImmagine").value;

            const inputImmagine = document.getElementById("immagine");
            const file = inputImmagine.files[0] ?? null;

            const nuovaProv = provincia || originalPlace.indirizzo.provincia;
            const nuovaCitta = citta || originalPlace.indirizzo.citta;
            const nuovoCivico = civico || originalPlace.indirizzo.civico;

            const vecchiaChiave =
                `${originalPlace.indirizzo.provincia}-${originalPlace.indirizzo.citta}-${originalPlace.indirizzo.civico}`;
            const nuovaChiave =
                `${nuovaProv}-${nuovaCitta}-${nuovoCivico}`;

            if (nuovaChiave !== vecchiaChiave && idI.has(nuovaChiave)) {
                alert("Indirizzo già presente");
                return;
            }

            const modifiche = {

                idSede: Number(id),

                nome:
                    nome !== originalPlace.nome
                        ? nome
                        : null,

                descrizione:
                    descrizione !== originalPlace.descrizione
                        ? descrizione
                        : null,

                descrizioneImmagine:
                    descrizioneImm !== originalPlace.descrizioneImmagine
                        ? descrizioneImm
                        : null,

                indirizzo: {

                    provincia:
                        provincia !== originalPlace.indirizzo.provincia
                            ? provincia
                            : null,

                    citta:
                        citta !== originalPlace.indirizzo.citta
                            ? citta
                            : null,

                    via:
                        via !== originalPlace.indirizzo.via
                            ? via
                            : null,

                    nomeVia:
                        nomeVia !== originalPlace.indirizzo.nomeVia
                            ? nomeVia
                            : null,

                    civico:
                        civico !== originalPlace.indirizzo.civico
                            ? civico
                            : null
                }
            };

            if (Object.values(modifiche.indirizzo).every(v => v === null)) {
                modifiche.indirizzo = null;
            }

            const campi = [
                modifiche.nome,
                modifiche.descrizione,
                modifiche.descrizioneImmagine,
                modifiche.indirizzo
            ];

            const nessunaModifica =
                campi.every(v => {

                    if(v === null || v === undefined)
                        return true;

                    if(typeof v === "object")
                        return Object.values(v).every(x => x === null);

                    return false;
                })
                && file === null;

            if (nessunaModifica) {
                alert("Nessuna modifica effettuata");
                return;
            }

            if (file) {
                modifiche.nuovaImmagine = true;
                const formData = new FormData();

                formData.append("action", "editSede");
                formData.append("file", file);
                formData.append("dati", JSON.stringify(modifiche));


                const res = await fetch("./Api/api-saveLuogo.php", {
                    method: "POST",
                    body: formData
                });


                const json = await res.json();

                alert(json.message);

                if(json.success){
                    reset();
                    await loadAzioni();
                }

                return;
            }

            Object.assign(dati, modifiche);
            break;
        }

        case "deleteSede":
            if(!document.getElementById("luogo-select").value){
                alert("Cancellazione è andata storta");
                return;
            }

            if (!confirm("Sei sicuro di voler eliminare questa sede?"))
                return;

            Object.assign(dati, {
                idSede: Number(document.getElementById("luogo-select").value)
            });
            break;

        case "addCitta":
            if (cittaDaAggiungere.length === 0){
                alert("Nessuna aggiunta fatta");
                return;
            }

            Object.assign(dati, {
                cittaDaAggiungere
            });
            break;

        case "editCitta":{
            if (cittaDaAggiungere.length === 0 && cittaDaEliminare.length === 0 && cittaModificare.length === 0){
                alert("Nessuna modifica fatta");
                return;
            }

            const cittaFinali = [
                ...cittaOriginali
                    .filter(o => !cittaDaEliminare.some(r => r.codice === o.codice && r.cod_Prov === o.cod_Prov))
                    .map(o =>
                        cittaModificare.find(m =>
                            m.codice_Rif === o.codice &&
                            m.cod_Prov_Rif === o.cod_Prov
                        ) ?? o
                    ),

                ...cittaDaAggiungere
            ];

            if(cittaFinali.length === 0){
                alert("Nessuna città rimasta");
                return;
            }

            Object.assign(dati, {
                cittaDaAggiungere,
                cittaDaEliminare,
                cittaModificare
            });
            break;
        }

        case "deleteCitta":{
            if (cittaDaEliminare.length === 0){
                alert("Nessuna eliminazione fatta");
                return;
            }

            const cittaFinali = [
                ...cittaOriginali
                    .filter(o => !cittaDaEliminare.some(r => r.codice === o.codice && r.cod_Prov === o.cod_Prov))
            ];

            if(cittaFinali.length === 0){
                alert("Nessuna città rimasta");
                return;
            }

            if (!confirm("Sei sicuro di voler eliminare questa città?"))
                return;

            Object.assign(dati, {
                cittaDaEliminare
            });
            break;
        }

        case "addProvincia":
            if (provincieDaAggiungere.length === 0){
                alert("Nessuna aggiunta fatta");
                return;
            }

            Object.assign(dati, {
                provincieDaAggiungere
            });
            break;

        case "editProvincia":{
            if (provincieDaAggiungere.length === 0 && provincieDaEliminare.length === 0 && provincieModificare.length === 0){
                alert("Nessuna modifica fatta");
                return;
            }

            const provincieFinali = [
                ...provincieOriginali
                    .filter(o => !provincieDaEliminare.some(r => r.codice === o.codice))
                    .map(o =>
                            provincieModificare.find(m =>
                                m.codice_Rif === o.codice
                            ) ?? o
                        ),

                ...provincieDaAggiungere
            ];

            if(provincieFinali.length === 0){
                alert("Nessuna provincia rimasta");
                return;
            }

            Object.assign(dati, {
                provincieDaAggiungere,
                provincieDaEliminare,
                provincieModificare
            });
            break;
        }

        case "deleteProvincia":{
            if (provincieDaEliminare.length === 0){
                alert("Nessuna eliminazione fatta");
                return;
            }

            const provincieFinali = [
                ...provincieOriginali
                    .filter(o => !provincieDaEliminare.some(r => r.codice === o.codice))
            ];

            if(provincieFinali.length === 0){
                alert("Nessuna provincia rimasta");
                return;
            }

            if (!confirm("Sei sicuro di voler eliminare questa provincia?"))
                return;

            Object.assign(dati, {
                provincieDaEliminare
            });
            break;
        }
    }


    const res = await fetch("./Api/api-saveLuogo.php",{
        method:"POST",
        headers:{
            "Content-Type":"application/json"
        },
        body:JSON.stringify(dati)
    });

    const json = await res.json();

    alert(json.message);

    if(json.success){
        reset();
        await loadAzioni();
    }
}

// --- Avvio ---
const main = document.querySelector("main");

main.innerHTML = renderMainEventi();

document
    .getElementById("selectAction")
    .addEventListener("change", loadAzioni);

loadAzioni()
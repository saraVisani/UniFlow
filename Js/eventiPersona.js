function formatDateForPHP(value) {
    const [year, month, day] = value.split("-");
    return `${day}/${month}/${year}`;
}

let selectedDateEventi = new Date();
let selectedRangeEventi = "week";
let selectedPlaceEventi = -1;

function renderMainEventi() {
    return `
    <header class="eventi-header">

        <h2 id="eventi-title">
            Tuoi Eventi
        </h2>

        <div class="filters">

            <label for="selectSede">
                Luogo:
            </label>

            <select id="selectSede">
                <option value="-1">
                    Tutti i luoghi
                </option>
            </select>


            <label for="date-eventi">
                Data:
            </label>

            <input type="date" id="date-eventi">


            <label for="range-eventi">
                Periodo:
            </label>

            <select id="range-eventi">
                <option value="day">
                    Giorno
                </option>
                <option value="week" selected>
                    Settimana
                </option>
                <option value="month">
                    Mese
                </option>
                <option value="year">
                    Anno
                </option>
            </select>

            <button id="btnLoadEventi">
                Cerca
            </button>

            <button id="azioniEvento">
                Azioni su Evento
            </button>

            <button id="totEventi">
                Tutti gli Eventi
            </button>

        </div>

    </header>


    <section id="eventi-section">

        <ul id="eventi-list"></ul>

    </section>
    `;
}

function renderEvents(eventi) {
    if (!eventi || eventi.length === 0) {
        return "<li>Nessun evento trovato.</li>";
    }

    return eventi.map(e => `
        <li class="evento">
            <h3>${e.Nome}</h3>
            <div><strong>Luogo:</strong> ${e.nome_luogo}</div>

            ${e.nome_sede
                ? `<div><strong>Sede:</strong> ${e.nome_sede}</div>`
                : ""
            }

            <div><strong>Indirizzo:</strong> ${e.indirizzo}</div>
            <div>${e.orario_inizio} - ${e.orario_fine}</div>
            <div><strong>Ruolo:</strong> ${e.ruolo}</div>
        </li>
    `).join("");
}

async function populatePlaces() {

    const select = document.getElementById("selectSede");

    const res = await fetch("./Api/api-personaEventi.php");
    const json = await res.json();

    select.innerHTML = '<option value="-1">Tutti i luoghi</option>';

    json.sede.forEach(l => {
        select.innerHTML += `
            <option value="${l.codice}">
                ${l.nome}
            </option>
        `;
    });
}

function saveSelectedPlace() {
    const select = document.getElementById("selectSede");
    selectedPlaceEventi = parseInt(select.value, 10);
    loadEventi();
}

function saveSelectedRange() {
    const select = document.getElementById("range-eventi");
    selectedRangeEventi = select.value;
    loadEventi();
}

function saveSelectedDate() {
    const input = document.getElementById("date-eventi");
    selectedDateEventi = new Date(input.value);
    loadEventi();
}

async function loadEventi() {

    const res = await fetch(
        `./Api/api-personaEventi.php?luogo=${selectedPlaceEventi}&range=${selectedRangeEventi}&date=${encodeURIComponent(selectedDateEventi.toISOString().split("T")[0])}`
    );

    const json = await res.json();

    document.getElementById("eventi-list").innerHTML =
        renderEvents(json.eventi);
}

async function initEventi() {
    await populatePlaces();
    await loadEventi();
}


// --- Avvio ---
const main = document.querySelector("main");

main.innerHTML = renderMainEventi();

const dateInput = document.getElementById("date-eventi");
dateInput.value = new Date().toISOString().split("T")[0];

document.getElementById("selectSede")
    .addEventListener("change", saveSelectedPlace);

document.getElementById("range-eventi")
    .addEventListener("change", saveSelectedRange);

document.getElementById("date-eventi")
    .addEventListener("change", saveSelectedDate);

document.getElementById("btnLoadEventi")
    .addEventListener("click", loadEventi);

document
    .getElementById("azioniEvento")
    .addEventListener("click", () => {
        window.location.href = "../PHP/azioniEvento.php";
    });

document
    .getElementById("totEventi")
    .addEventListener("click", () => {
        window.location.href = "../PHP/eventi.php";
    });

initEventi();
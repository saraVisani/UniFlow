const state = {
    forums: [],
    threads: [],
    comments: [],
    selectedChannel: null,
    selectedThread: null,
    view: "threads",
    commentMap: {}
};

let replyTarget = null;

function buildCommentMap() {
    state.commentMap = Object.fromEntries(
        state.comments.map(c => [c.id, c])
    );
}

function backToThreads() {
    state.view = "threads";
    render();
}

function replyTo(commentId) {
    replyTarget = commentId;

    const el = document.getElementById("commentText");
    if (el) {
        el.focus();
    }
}

function render() {
    if (state.view === "threads") {
        renderThreads();
    } else if (state.view === "comments") {
        renderCommenti();
    }
}

function renderSidebar() {
    document.querySelector("aside").innerHTML =
        state.forums.map(f => `
            <div>
                <h3>${f.nome}</h3>
                ${f.canali.map(c => `
                    <div onclick="loadThreads(${c.id})">
                        ${c.nome}
                    </div>
                `).join("")}
            </div>
        `).join("");
}

function renderThreads() {
    state.view = "threads";

    document.querySelector("main").innerHTML = `
        <div>
            <h3>Crea Thread</h3>

            <input id="threadTitle" placeholder="Titolo">
            <textarea id="threadText" placeholder="Testo"></textarea>

            <button onclick="createThread()">Crea</button>
        </div>

        <hr>

        ${state.threads.map(t => `
            <div onclick="loadCommenti(${t.id})">

                ${t.Pin ? '<div>📌 In evidenza</div>' : ''}

                <h4>${t.Titolo}</h4>

                <b>${t.Nome} ${t.Cognome}</b>

                <p>${t.Testo}</p>

            </div>
        `).join("")}
    `;
}

function renderCommenti() {
    state.view = "comments";

    document.querySelector("main").innerHTML = `
        <button onclick="backToThreads()">⬅ Back</button>

        <div>
            <h3>Nuovo commento</h3>

            <textarea id="commentText"></textarea>

            <button onclick="createComment()">Invia</button>
        </div>

        <hr>

        ${state.comments.map(c => {
            const referenced = c.Messaggio_Puntato
                ? state.commentMap[c.Messaggio_Puntato]
                : null;

            return `
                <div>

                    <button onclick="replyTo(${c.id})">Reply</button>

                    ${c.Pin_Speciale ? '<div>✅ Soluzione accettata</div>' : ''}
                    ${c.Pin ? '<div>📌 In evidenza</div>' : ''}

                    ${
                        referenced
                        ? `<blockquote>${referenced.Testo}</blockquote>`
                        : ''
                    }

                    <b>${c.Nome} ${c.Cognome}</b>

                    <p>${c.Testo}</p>

                </div>
            `;
        }).join("")}
    `;
}

async function loadForum() {
    const res = await fetch("/Api/api-forum.php");
    const json = await res.json();

    state.forums = json.forums;
    renderSidebar();
}

async function loadThreads(canaleId) {
    state.selectedChannel = canaleId;

    const res = await fetch(`/api/thread.php?canale=${canaleId}`);
    const json = await res.json();

    state.threads = json.threads;
    state.comments = [];

    state.view = "threads";
    renderThreads();
}

async function loadCommenti(threadId) {
    state.selectedThread = threadId;

    const res = await fetch(`/api/commenti.php?thread=${threadId}`);
    const json = await res.json();

    state.comments = json.commenti;

    buildCommentMap();
    render();
}

async function createThread() {
    const titolo = document.getElementById("threadTitle").value;
    const testo = document.getElementById("threadText").value;

    await fetch("/api/create-thread.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
            channelId: state.selectedChannel,
            titolo,
            testo
        })
    });

    loadThreads(state.selectedChannel);
}

async function createComment() {
    const testo = document.getElementById("commentText").value;

    await fetch("/api/create-comment.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
            threadId: state.selectedThread,
            testo,
            replyTo: replyTarget
        })
    });

    replyTarget = null;
    loadCommenti(state.selectedThread);
}

loadForum();

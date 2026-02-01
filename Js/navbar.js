function renderMainNavbar(buttonsLeft, buttonsRight, items){

    let leftButtonsHTML = "";
    let rightButtonsHTML = "";
    let itemsHTML = "";

    // Bottoni sinistra (es. Home)
    buttonsLeft.forEach(btn => {
        leftButtonsHTML += `
            <li><a href="${btn.link}">${btn.label}</a></li>
        `;
    });

    // Dropdown (Ateneo, Studiare, UniFlow)
    Object.entries(items).forEach(([sectionName, links]) => {

        let linksHTML = "";
        links.forEach(link => {
            linksHTML += `
                <li><a href="${link.link}">${link.label}</a></li>
            `;
        });

        itemsHTML += `
            <li class="dropdown">
                <button class="dropbtn">
                    ${sectionName} <span class="arrow">▾</span>
                </button>
                <ul class="dropdown-content">
                    ${linksHTML}
                </ul>
            </li>
        `;
    });

    // Bottoni destra (Rubrica, Login / Logout)
    buttonsRight.forEach(btn => {
        if(btn.link){
            rightButtonsHTML += `
                <li><a href="${btn.link}">${btn.label}</a></li>
            `;
        } else {
            rightButtonsHTML += `
                <li><button>${btn.label}</button></li>
            `;
        }
    });

    return `
        <nav class="navbar">
            <ul>
                ${leftButtonsHTML}
                ${itemsHTML}
                ${rightButtonsHTML}
            </ul>
        </nav>
    `;
}

async function loadNavbar(){

    const res = await fetch("./Api/api-navbar.php");
    const json = await res.json();

    const navbar = document.querySelector("nav");
    navbar.innerHTML = renderMainNavbar(json.buttons.left, json.buttons.right, json.items);

    new DropdownManager();
}

loadNavbar();

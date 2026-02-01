class DropdownManager {

    constructor(selector = ".dropdown"){
        this.dropdowns = document.querySelectorAll(selector);
        this.init();
    }

    init(){
        this.dropdowns.forEach(dropdown => {
            const button = dropdown.querySelector(".dropbtn");

            if(!button) return;

            button.addEventListener("click", (e) => {
                e.stopPropagation();
                this.toggleDropdown(dropdown);
            });
        });

        document.addEventListener("click", () => {
            this.closeAll();
        });
    }

    toggleDropdown(activeDropdown){
        this.dropdowns.forEach(dropdown => {
            if(dropdown !== activeDropdown){
                dropdown.classList.remove("active");
            }
        });

        activeDropdown.classList.toggle("active");
    }

    closeAll(){
        this.dropdowns.forEach(dropdown => {
            dropdown.classList.remove("active");
        });
    }
}

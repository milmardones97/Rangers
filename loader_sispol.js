document.addEventListener("DOMContentLoaded", () => {
    const loader = document.getElementById("pageLoader");
    const bar = document.getElementById("pageLoaderBar");
    const status = document.getElementById("pageLoaderStatus");
    const spinner = document.getElementById("pageLoaderSpinner");
    const module = document.getElementById("pageLoaderModule");

    if (!loader || !bar || !status || !spinner || !module) {
        return;
    }

    const titulo = document.title ? document.title.toUpperCase() : "MÓDULO";
    module.textContent = titulo;

    const frames = ["\\--", "|--", "/--", "---"];
    const estados = [
        "LEYENDO ARCHIVOS",
        "CARGANDO INTERFAZ",
        "PREPARANDO DATOS",
        "INICIALIZANDO PANEL",
        "FINALIZANDO"
    ];

    let i = 0;
    let progreso = 0;

    const anim = setInterval(() => {
        i++;
        spinner.textContent = frames[i % frames.length];

        if (progreso < 100) {
            progreso += Math.floor(Math.random() * 14) + 8;
            if (progreso > 100) progreso = 100;
            bar.style.width = progreso + "%";
        }

        const estadoIndex = Math.min(
            estados.length - 1,
            Math.floor((progreso / 100) * estados.length)
        );
        status.textContent = estados[estadoIndex];
    }, 100);

    setTimeout(() => {
        clearInterval(anim);
        bar.style.width = "100%";
        status.textContent = "MÓDULO CARGADO";
        spinner.textContent = "---";

        setTimeout(() => {
            loader.classList.add("hide");
        }, 180);
    }, 950);
});
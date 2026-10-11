document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("loginForm");
    const usuario = document.getElementById("usuario");
    const password = document.getElementById("password");
    const passwordFake = document.getElementById("passwordFake");
    const btn = document.getElementById("btnLogin");
    const loadingLine = document.getElementById("loadingLine");
    const loadingSpinner = document.getElementById("loadingSpinner");

    const bootSequence = document.getElementById("bootSequence");
    const bootLine = document.getElementById("bootLine");
    const bootStatus = document.getElementById("bootStatus");
    const bootProgress = document.getElementById("bootProgress");
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    function finishBoot() {
        if (!bootSequence || bootSequence.classList.contains("is-hidden")) {
            return;
        }

        document.body.classList.remove("login-loading");
        document.body.classList.add("login-ready");
        bootSequence.classList.add("is-hidden");
        window.setTimeout(() => bootSequence.remove(), 320);
        usuario.focus();
    }

    if (bootSequence && bootLine && bootStatus && bootProgress) {
        const steps = [
            ["> INICIANDO TERMINAL", "CARGANDO INTERFAZ SEGURA...", 28],
            ["> VALIDANDO PROTOCOLOS", "ESTABLECIENDO CONEXIÓN LOCAL...", 62],
            ["> SISTEMA LISTO", "ACCESO RESTRINGIDO · IDENTIFÍQUESE", 100]
        ];
        const stepDelay = reduceMotion ? 0 : 270;

        steps.forEach(([line, status, progress], index) => {
            window.setTimeout(() => {
                bootLine.textContent = line;
                bootStatus.textContent = status;
                bootProgress.style.width = `${progress}%`;
            }, index * stepDelay);
        });

        window.setTimeout(finishBoot, reduceMotion ? 30 : 1050);
        window.setTimeout(finishBoot, 1800);
    } else {
        usuario.focus();
    }

    usuario.addEventListener("input", () => {
        usuario.value = usuario.value.toUpperCase();
    });

    function actualizarPasswordVisual() {
        passwordFake.textContent = "X".repeat(password.value.length);
    }

    password.addEventListener("input", actualizarPasswordVisual);

    form.addEventListener("submit", (e) => {
        const user = usuario.value.trim();
        const pass = password.value.trim();

        if (user === "" || pass === "") {
            e.preventDefault();
            alert("Debes ingresar usuario y contraseña.");
            return;
        }

        e.preventDefault();

        btn.disabled = true;
        btn.textContent = "ACCEDIENDO...";
        usuario.readOnly = true;
        password.readOnly = true;

        const frames = ["\\--", "|--", "/--", "---"];
        let i = 0;

        loadingLine.classList.add("active");
        loadingSpinner.textContent = frames[0];

        const anim = setInterval(() => {
            i = (i + 1) % frames.length;
            loadingSpinner.textContent = frames[i];
        }, 120);

        setTimeout(() => {
            clearInterval(anim);
            form.submit();
        }, 1200);
    });

    actualizarPasswordVisual();
});

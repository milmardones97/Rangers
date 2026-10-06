document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("loginForm");
    const usuario = document.getElementById("usuario");
    const password = document.getElementById("password");
    const passwordFake = document.getElementById("passwordFake");
    const btn = document.getElementById("btnLogin");
    const loadingLine = document.getElementById("loadingLine");
    const loadingSpinner = document.getElementById("loadingSpinner");

    usuario.focus();

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
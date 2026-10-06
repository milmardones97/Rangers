# Firebase Realtime Database

1. Crea o habilita **Realtime Database** en el proyecto Firebase.
2. La configuración de SISPOL ya está incluida en `config/firebase.php`. Si tu instancia tiene otra URL, actualiza `database_url`. Si las reglas requieren autenticación, configura también `auth_token`.
3. Abre una sola vez `http://localhost/rangers/database/firebase_setup.php`.
4. Ingresa con `MILTON` / `123456` y cambia esa contraseña desde el control de usuarios.

La aplicación usa la API REST de Firebase desde PHP; no requiere un servidor SQL.

Para un entorno de producción, usa reglas que permitan acceso únicamente al servidor y no expongas el token en el repositorio.

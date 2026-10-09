document.addEventListener('DOMContentLoaded', () => {
    let agentes = window.sispolControlUsuariosData || [], actual = null, imageData = '';
    const byId = (id) => document.getElementById(id);
    const list = byId('agentList'), status = byId('statusText'), logs = byId('logList'), form = byId('userForm');
    const search = byId('agentSearch'), name = byId('formNombre'), rank = byId('formRango'), badge = byId('formPlaca'), password = byId('formPassword');
    const preview = byId('profilePreview'), save = byId('btnGuardar'), disable = byId('btnDesactivar'), level = byId('formNivel'), imageUrl = byId('formImagen'), toast = byId('saveToast');
    const rankIcons = {COMISIONADO:'Comisionado.webp',DIRECTOR:'Director.gif',SUBDIRECTOR:'Subdirector.png',SUPERINTENDENTE:'Superintendente.png',INSPECTOR:'Inspector.png',TENIENTE:'Teniente.png',SARGENTO:'Sargento.png',CABO:'Cabo.png',INVESTIGADOR:'Investigador.png','PARK RANGER':'Ranger.png'};
    const updateLevel = () => { const group = rank.selectedOptions[0]?.parentElement?.label || 'OFICIALES'; level.value = (group === 'JEFATURA' || group === 'COORDINACIÓN') ? 'JEFATURA' : (group === 'SUPERVISORES' ? 'SUPERVISOR' : 'OFICIAL'); };
    const normalizeImageUrl = (value) => {
        const url = value.trim();
        const match = url.match(/^https?:\/\/imgur\.com\/([a-zA-Z0-9]+)\/?$/);
        return match ? 'https://i.imgur.com/' + match[1] + '.jpg' : url;
    };
    const updatePreview = () => { imageData = normalizeImageUrl(imageUrl.value); preview.src = imageData; preview.style.display = imageData ? 'block' : 'none'; };
    const setStatus = (text, type = '') => { status.textContent = text; status.className = 'status-text ' + type; };
    const setFormMode = (mode = 'locked') => {
        const editable = mode === 'create' || mode === 'edit';
        [name, rank, badge, password, imageUrl].forEach(field => {
            field.disabled = !editable;
            if (editable) field.removeAttribute('disabled');
            else field.setAttribute('disabled', 'disabled');
        });
        level.disabled = true;
        save.disabled = !editable;
        disable.disabled = mode !== 'edit';
    };
    const showToast = (text) => { toast.textContent = text; toast.classList.add('show'); clearTimeout(window.sispolToastTimer); window.sispolToastTimer = setTimeout(() => toast.classList.remove('show'), 3200); };
    const startSaving = () => {
        const frames = ['/--', '--\\'];
        let frame = 0;
        clearInterval(window.sispolSavingTimer);
        toast.classList.add('show');
        toast.textContent = 'GUARDANDO ' + frames[frame];
        window.sispolSavingTimer = setInterval(() => {
            frame = (frame + 1) % frames.length;
            toast.textContent = 'GUARDANDO ' + frames[frame];
        }, 260);
    };
    const stopSaving = () => clearInterval(window.sispolSavingTimer);
    const api = async (payload = null) => {
        const response = await fetch('../api/users_control.php', payload ? {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)} : {});
        const json = await response.json(); if (!response.ok || !json.ok) throw new Error(json.message || 'No se pudo completar la operación.'); return json;
    };
    const renderLogs = (items = []) => { logs.innerHTML = ''; (items.length ? items : [{action:'SIN OPERACIONES REGISTRADAS.', at:''}]).forEach(log => { const item = document.createElement('li'); item.className = 'log-item'; item.textContent = (log.at ? '[' + log.at + '] ' : '') + (log.actor ? log.actor + ': ' : '') + log.action; logs.appendChild(item); }); };
    const render = () => {
        const term = search.value.trim().toUpperCase(); list.innerHTML = '';
        agentes.filter(a => !term || a.nombre.includes(term) || a.rango.includes(term) || a.username.includes(term)).forEach(a => {
            const card = document.createElement('button'); card.type='button'; card.className='agent-card' + (actual?.id === a.id ? ' active' : '');
            const photo = a.imagen ? '<img class="agent-avatar" src="' + a.imagen + '" alt="Perfil de ' + a.nombre + '">' : '<div class="agent-avatar empty">SIN FOTO</div>';
            const rankIcon = rankIcons[a.rango] ? '<img class="rank-icon" src="../rangosimg/' + rankIcons[a.rango] + '" alt=""> ' : '';
            card.innerHTML = '<div class="agent-card-content">' + photo + '<div><div class="agent-name">' + a.nombre + '</div><div class="agent-meta"><div>USUARIO: ' + a.username + '</div><div>RANGO: ' + rankIcon + a.rango + '</div><div>PLACA: ' + (a.placa || 'SIN PLACA') + '</div><div>ACCESO: ' + a.acceso + '</div></div></div></div>';
            card.onclick = () => select(a); list.appendChild(card);
        });
        byId('counterText').textContent = 'ARCHIVOS: ' + agentes.length;
    };
    const select = (agent) => {
        actual = agent; name.value = agent.nombre; rank.value = agent.rango; updateLevel(); badge.value = agent.placa || ''; password.value = ''; imageData = agent.imagen || ''; imageUrl.value = imageData;
        preview.src = imageData; preview.style.display = imageData ? 'block' : 'none';
        byId('detailNombre').textContent = agent.nombre; byId('detailRango').textContent = agent.rango; byId('detailEstatus').textContent = agent.estatus;
        byId('detailIngreso').textContent = agent.fecha_ingreso; byId('detailAcceso').textContent = agent.acceso + ' / ' + agent.nivel_permisos; byId('detailId').textContent = agent.username;
        byId('summary').style.display='grid'; byId('placeholder').style.display='none'; byId('passwordBox').style.display='grid'; byId('passwordValue').textContent='CLAVE PROTEGIDA';
        byId('accessChip').textContent = agent.activo ? 'ACTIVO' : 'DESACTIVADO'; byId('accessChip').className = 'access-chip ' + (agent.activo ? 'chip-ok' : 'chip-no');
        setFormMode('edit'); disable.textContent = agent.activo ? 'Desactivar' : 'Activar';
        renderLogs(agent.logs); render(); setStatus('Registro cargado: ' + agent.nombre, 'success');
    };
    const refresh = async () => { const result = await api(); agentes = result.data; render(); if (actual) { const updated = agentes.find(a => a.id === actual.id); if (updated) select(updated); } };
    const payload = () => ({agent_id: actual?.id || '', nombre:name.value, rango:rank.value, placa:badge.value, password:password.value, imagen:imageData});
    byId('btnBuscar').onclick = render; search.oninput = render;
    rank.onchange = updateLevel;
    byId('btnNuevo').onclick = () => { actual=null; form.reset(); updateLevel(); imageData=''; preview.style.display='none'; setFormMode('create'); byId('summary').style.display='none'; byId('placeholder').style.display='flex'; byId('placeholder').textContent='Completa la ficha para crear un usuario.'; renderLogs([]); render(); name.focus(); };
    save.onclick = async () => { startSaving(); save.disabled = true; try { const data = {...payload(), action: actual ? 'update' : 'create'}; const result = await api(data); const message = result.plain_password ? 'Usuario creado. Contraseña temporal: ' + result.plain_password : 'Registro guardado correctamente.'; setStatus(message, 'success'); actual = result.data; await refresh(); showToast(message); } catch (e) { setStatus(e.message, 'error'); showToast(e.message); } finally { stopSaving(); save.disabled = false; } };
    disable.onclick = async () => { if (!actual) return; try { await api({action:'set_active', agent_id:actual.id, active:!actual.activo}); setStatus(actual.activo ? 'Usuario desactivado.' : 'Usuario activado.', 'success'); await refresh(); } catch (e) { setStatus(e.message, 'error'); } };
    imageUrl.oninput = updatePreview;
    preview.onerror = () => { preview.style.display = 'none'; setStatus('No se pudo cargar la imagen desde esa URL.', 'error'); };
    document.querySelectorAll('.boot-line').forEach((line, index) => {
        setTimeout(() => line.classList.add('show'), 90 + (index * 90));
    });
    setFormMode();
    refresh().catch(error => setStatus(error.message, 'error'));
});

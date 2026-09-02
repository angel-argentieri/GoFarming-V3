const params = new URLSearchParams(window.location.search);
const id_planta = params.get('id');
let rega_id = null;

async function carregar() {
    const plantas = await get('plantas');
    const planta = plantas.find(p => p.id == id_planta);

    if (!planta) {
        window.location.href = 'dashboard.html';
        return;
    }

    document.title = planta.nome + ' — GoFarming';
    document.getElementById('nome').textContent = planta.nome;
    document.getElementById('especie').textContent = planta.especie || '';
    document.getElementById('frequencia').textContent = planta.frequencia_rega || 'Não definida';

    if (planta.foto_url) {
        document.getElementById('foto').src = planta.foto_url;
    } else {
        document.getElementById('foto').style.display = 'none';
    }

    if (planta.rega_hoje === 'pendente') {
        rega_id = planta.rega_id;
        document.getElementById('rega-hoje').style.display = 'block';
    }
}

async function regar() {
    const res = await post('regar', { id_rega: rega_id });

    if (res.error) {
        toast(res.error);
        return;
    }

    document.getElementById('rega-hoje').style.display = 'none';
    toast('Rega registrada! 💧');
}

async function perguntarIA(pergunta) {
    const box = document.getElementById('resposta-ia');
    box.style.display = 'block';
    box.innerHTML = '<div class="spinner" style="margin:12px auto;"></div>';

    const res = await post('chat', { id_planta, pergunta });

    if (res.error) {
        box.innerHTML = '<p style="color:var(--alerta);font-size:13px;">Não consegui responder agora.</p>';
        return;
    }

    box.innerHTML = '<div class="msg-ia">' + res.resposta + '</div>';
}

async function removerPlanta() {
    if (!confirm('Remover esta planta do jardim?')) return;

    const res = await del('plantas', { id: id_planta });

    if (res.error) {
        toast(res.error);
        return;
    }

    window.location.href = 'dashboard.html';
}

carregar();

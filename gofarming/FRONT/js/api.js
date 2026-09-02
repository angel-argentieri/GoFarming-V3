const BASE = '/gofarming/PUBLIC';

async function post(rota, dados) {
    const res = await fetch(BASE + '/' + rota, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(dados)
    });
    return res.json();
}

async function get(rota) {
    const res = await fetch(BASE + '/' + rota, {
        credentials: 'include'
    });
    return res.json();
}

async function del(rota, dados) {
    const res = await fetch(BASE + '/' + rota, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(dados)
    });
    return res.json();
}

function toast(msg) {
    let t = document.getElementById('toast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'toast';
        t.className = 'toast';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.classList.add('visivel');
    setTimeout(() => t.classList.remove('visivel'), 2500);
}

// --- Registro do Service Worker (PWA) ---
// O arquivo service-worker.js fica na raiz do projeto (fora de PUBLIC/),
// então o registro precisa apontar pra lá, não pra BASE (que é a API em PUBLIC/).
// Registrar dentro de PUBLIC/ fazia o navegador assumir escopo /gofarming/PUBLIC/,
// e todo o cache dos arquivos de FRONT/ era buscado no caminho errado (404 em cadeia).
const APP_ROOT = BASE.replace(/\/PUBLIC$/, '');

if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        // Remove registros antigos com escopo errado (ex: de versões anteriores
        // que registraram o SW dentro de PUBLIC/), pra não deixar dois workers
        // conflitando na mesma origem.
        const regs = await navigator.serviceWorker.getRegistrations();
        for (const reg of regs) {
            if (reg.scope !== new URL(APP_ROOT + '/', location.origin).href) {
                await reg.unregister();
            }
        }

        navigator.serviceWorker.register(APP_ROOT + '/service-worker.js', { scope: APP_ROOT + '/' })
            .then(reg => console.log('Service Worker ativo no escopo:', reg.scope))
            .catch(err => console.error('Erro ao registrar Service Worker:', err));
    });
}
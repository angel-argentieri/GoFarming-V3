let dadosPlanta = null;
let fotoBase64 = null;

// Inicia a câmera ao carregar
navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    .then(stream => {
        document.getElementById('video').srcObject = stream;
    })
    .catch(() => {
        // Câmera não disponível — abre input de arquivo
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.capture = 'environment';
        input.style = 'display:none';
        input.onchange = e => processarArquivo(e.target.files[0]);
        document.body.appendChild(input);
        document.getElementById('btn-captura').onclick = () => input.click();
    });

function capturar() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    fotoBase64 = canvas.toDataURL('image/jpeg', 0.75).split(',')[1];
    mostrarPreview(canvas.toDataURL('image/jpeg', 0.75));
    analisar();
}

function selecionarArquivo(e) {
    if (e.target.files && e.target.files[0]) {
        processarArquivo(e.target.files[0]);
    }
}

function processarArquivo(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        fotoBase64 = e.target.result.split(',')[1];
        mostrarPreview(e.target.result);
        analisar();
    };
    reader.readAsDataURL(file);
}

function mostrarPreview(src) {
    document.getElementById('laser').style.display = 'none';
    document.getElementById('btn-captura').style.display = 'none';
    
    const btnGaleria = document.getElementById('btn-galeria');
    if (btnGaleria) btnGaleria.style.display = 'none';

    document.getElementById('video').style.display = 'none';
    const preview = document.getElementById('preview');
    preview.src = src;
    preview.style.display = 'block';
}

async function analisar() {
    document.getElementById('analisando').style.display = 'block';

    const res = await post('identificar', { imagem: fotoBase64 });

    document.getElementById('analisando').style.display = 'none';

    if (res.error) {
        toast(res.error);
        reiniciar();
        return;
    }

    dadosPlanta = res;
    dadosPlanta.foto_base64 = fotoBase64;

    document.getElementById('foto-resultado').src = 'data:image/jpeg;base64,' + fotoBase64;
    document.getElementById('nome-planta').textContent = res.nome;
    document.getElementById('especie-planta').textContent = res.especie;
    document.getElementById('confianca-planta').textContent = res.confianca + '% de certeza';
    document.getElementById('frequencia-planta').textContent = res.frequencia_rega;

    document.getElementById('resultado').style.display = 'block';
}

async function salvarNoJardim() {
    const res = await post('plantas', {
        nome: dadosPlanta.nome,
        especie: dadosPlanta.especie,
        foto_url: 'data:image/jpeg;base64,' + dadosPlanta.foto_base64,
        frequencia_rega: dadosPlanta.frequencia_rega,
        access_token: dadosPlanta.access_token
    });

    if (res.error) {
        toast(res.error);
        return;
    }

    toast('Planta adicionada ao jardim!');
    setTimeout(() => window.location.href = 'dashboard.html', 1500);
}

function reiniciar() {
    dadosPlanta = null;
    fotoBase64 = null;
    document.getElementById('resultado').style.display = 'none';
    document.getElementById('preview').style.display = 'none';
    document.getElementById('video').style.display = 'block';
    document.getElementById('laser').style.display = 'block';
    document.getElementById('btn-captura').style.display = 'flex';

    const btnGaleria = document.getElementById('btn-galeria');
    if (btnGaleria) btnGaleria.style.display = 'flex';
}
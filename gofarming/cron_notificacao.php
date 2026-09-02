<?php

// Rodar todo dia às 20h via cron job:
// 0 20 * * * php /caminho/para/gofarming/cron_notificacao.php

require_once 'CONFIG/db.php';
require_once 'APP/MODEL/RegaModel.php';
require_once 'APP/MODEL/NotificacaoModel.php';

$database = new Database();
$db = $database->getConnection();
$modelRega = new RegaModel($db);
$modelNotificacao = new NotificacaoModel($db);

$pendentes = $modelRega->buscarPendentesHoje();

if (empty($pendentes)) {
    echo "Nenhuma rega pendente hoje.\n";
    exit;
}

// Grava a notificação no app (sino do dashboard), além do e-mail abaixo
foreach ($pendentes as $rega) {
    if (!$modelNotificacao->jaExisteHoje($rega['id_usuario'], $rega['id_planta'], 'rega')) {
        $modelNotificacao->criar(
            $rega['id_usuario'],
            'Hora de regar 💧',
            "{$rega['nome_planta']} está esperando por água hoje.",
            'rega',
            $rega['id_planta']
        );
    }
}

// Agrupa por email pra não mandar um email por planta
$porEmail = [];
foreach ($pendentes as $rega) {
    $porEmail[$rega['email']][] = $rega;
}

foreach ($porEmail as $email => $regas) {
    $nome_usuario = $regas[0]['nome_usuario'];
    $lista_plantas = implode(', ', array_column($regas, 'nome_planta'));

    $assunto = 'GoFarming — suas plantas precisam de água!';

    $mensagem = "
    <html>
    <body style='font-family: sans-serif; background: #000; color: #fff; padding: 30px;'>
        <h2 style='color: #B8A8FF;'>🌱 Hora de regar!</h2>
        <p>Olá, {$nome_usuario}!</p>
        <p>As seguintes plantas ainda não foram regadas hoje:</p>
        <p style='color: #B8A8FF; font-weight: bold;'>{$lista_plantas}</p>
        <p>Acesse o GoFarming e registre a rega.</p>
    </body>
    </html>
    ";

    $cabecalhos = "MIME-Version: 1.0\r\n";
    $cabecalhos .= "Content-type: text/html; charset=UTF-8\r\n";
    $cabecalhos .= "From: GoFarming <noreply@gofarming.com>\r\n";

    mail($email, $assunto, $mensagem, $cabecalhos);
    echo "Email enviado para {$email}\n";
}
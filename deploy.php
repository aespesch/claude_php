<?php
// Teste para ver se o deploy está funcionando

// Carrega as variáveis de ambiente do arquivo .env
function loadEnv($path) {
    if (!file_exists($path)) {
        die('Arquivo .env não encontrado');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, '"\'');

        putenv("$name=$value");
        $_ENV[$name] = $value;
    }
}

loadEnv(__DIR__ . '/.env');

$secret = getenv('github') ?: $_ENV['github'] ?? null;

if (!$secret) {
    http_response_code(500);
    die('Token não configurado');
}

// Aceita tanto GET quanto POST
$token = $_GET['token'] ?? $_POST['token'] ?? null;

if (!$token || $token !== $secret) {
    http_response_code(403);
    die('Acesso negado');
}

// Caminho para o repositório
$repo_dir = __DIR__ . '/claude';

// Executa git pull
$output = shell_exec("cd $repo_dir && git pull origin main 2>&1");

// Log
file_put_contents(__DIR__ . '/deploy.log', date('Y-m-d H:i:s') . "\n" . $output . "\n\n", FILE_APPEND);

http_response_code(200);
echo "Deploy realizado com sucesso!\n";
echo $output;
?>
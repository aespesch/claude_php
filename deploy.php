<?php
// teste
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

// O repositório está na pasta ATUAL
$repo_dir = __DIR__;

// Executa git pull
$output = shell_exec("cd $repo_dir && git pull origin main 2>&1");

// Log detalhado
$log_content = date('Y-m-d H:i:s') . "\n";
$log_content .= "Diretório: $repo_dir\n";
$log_content .= "Output:\n$output\n";
$log_content .= str_repeat('-', 50) . "\n\n";

file_put_contents(__DIR__ . '/deploy.log', $log_content, FILE_APPEND);

http_response_code(200);
echo "Deploy realizado com sucesso!\n";
echo "Diretório: $repo_dir\n";
echo $output;
?>
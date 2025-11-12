<?php
session_start();

// Load dependencies 444444
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/claude_api.php';
require_once 'includes/markdown_parser.php';
require_once 'includes/functions.php';

// Initialize
Config::loadEnv();
$db = Database::getInstance();

// Initialize session variables APENAS na primeira visita
if (!isset($_SESSION['initialized'])) {
    $_SESSION['conversation_id'] = null;
    $_SESSION['messages'] = [];
    $_SESSION['initialized'] = true;
}

// Handle actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$error = '';
$currentModel = 'claude-sonnet-4-5-20250929';
$currentTemp = 0.7;
$currentMaxTokens = 2000;

// Process actions
switch ($action) {
    case 'load_conversation':
        handleLoadConversation($db);
        break;
    case 'send':
        $result = handleSendMessage($db);
        $error = $result['error'] ?? '';
        $currentModel = $result['model'] ?? $currentModel;
        $currentTemp = $result['temperature'] ?? $currentTemp;
        $currentMaxTokens = $result['maxTokens'] ?? $currentMaxTokens;
        break;
    case 'clear':
        handleClearConversation($db);
        break;
}

// Get data for views
$recentConversations = getRecentConversations($db);
$maxTokensLimit = ClaudeAPI::getMaxTokens($currentModel);

// Load views
require_once 'views/header.php';
?>
<body>
    <?php require_once 'views/sidebar.php'; ?>
    <div class="main-content">
        <?php require_once 'views/chat.php'; ?>
    </div>
    <?php require_once 'views/footer.php'; ?>
</body>
</html>
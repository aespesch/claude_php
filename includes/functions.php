<?php
// Handle load conversation action
function handleLoadConversation($db) {
    $conversationId = $_GET['id'] ?? null;

    if (!$conversationId) {
        // Não carrega nada se não houver ID específico
        $_SESSION['conversation_id'] = null;
        $_SESSION['messages'] = [];
        return;
    }

    $conversation = $db->getConversation($conversationId);

    if ($conversation) {
        $_SESSION['conversation_id'] = $conversationId;
        $_SESSION['messages'] = json_decode($conversation['messages'], true) ?? [];
    }
}

function handleSendMessage($db) {
    $message = $_POST['message'] ?? '';
    $model = $_POST['model'] ?? 'claude-sonnet-4-5-20250929';
    $temperature = floatval($_POST['temperature'] ?? 0.7);
    $maxTokens = intval($_POST['max_tokens'] ?? 2000);

    $uploadedFiles = [];
    $fileNames = [];
    if (!empty($_FILES['files']['name'][0])) {
        for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $uploadedFiles[] = [
                    'name' => $_FILES['files']['name'][$i],
                    'type' => $_FILES['files']['type'][$i],
                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                    'error' => $_FILES['files']['error'][$i],
                    'size' => $_FILES['files']['size'][$i]
                ];
                $fileNames[] = $_FILES['files']['name'][$i];
            }
        }
    }

    if (!empty($message)) {
        try {
            $api = new ClaudeAPI();

            if (!$_SESSION['conversation_id']) {
                $_SESSION['conversation_id'] = $db->getOrCreateConversation(session_id(), $model, $temperature, $maxTokens);
            }

            $conversationId = $_SESSION['conversation_id'];
            $history = [];
            foreach ($_SESSION['messages'] as $msg) {
                $history[] = [
                    'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                    'content' => $msg['raw'] ?? $msg['content']
                ];
            }

            $displayMessage = $message;
            if (!empty($fileNames)) {
                $displayMessage .= "\n\n📎 **Attached files:** " . implode(', ', $fileNames);
            }

            $userMessageId = $db->saveMessage($conversationId, 'user', $displayMessage, $message);

            if (!empty($uploadedFiles)) {
                foreach ($uploadedFiles as $file) {
                    $fileContent = file_get_contents($file['tmp_name']);
                    $db->saveAttachment($userMessageId, $file['name'], $file['type'], $file['size'], $fileContent);
                }
            }

            $_SESSION['messages'][] = ['role' => 'user', 'content' => $displayMessage, 'raw' => $message];

            if (count($_SESSION['messages']) === 1) {
                $title = mb_substr($message, 0, 50) . (mb_strlen($message) > 50 ? '...' : '');
                $db->updateConversationTitle($conversationId, $title);
            }

            $response = $api->sendMessage($message, $model, $temperature, $maxTokens, $history, $uploadedFiles);
            $db->saveMessage($conversationId, 'assistant', $response);
            $_SESSION['messages'][] = ['role' => 'assistant', 'content' => $response];

            // 🟢 ADICIONE ESTAS LINHAS:
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;

        } catch (Exception $e) {
            return ['error' => $e->getMessage(), 'model' => $model, 'temperature' => $temperature, 'maxTokens' => $maxTokens];
        }
    }

    return ['model' => $model, 'temperature' => $temperature, 'maxTokens' => $maxTokens];
}

// Handle clear conversation action
function handleClearConversation($db) {
    $_SESSION['conversation_id'] = null;
    $_SESSION['messages'] = [];
    
    // Não remova o 'initialized' para manter o comportamento correto
    header('Location: index.php');
    exit;
}

// Get recent conversations
function getRecentConversations($db) {
    try {
        return $db->getRecentConversations(15);
    } catch (Exception $e) {
        return [];
    }
}

// Format time ago
function formatTimeAgo($datetime) {
    $date = new DateTime($datetime);
    $now = new DateTime();
    $diff = $now->diff($date);

    if ($diff->days == 0) {
        return $diff->h == 0 ? $diff->i . 'm ago' : $diff->h . 'h ago';
    } elseif ($diff->days == 1) {
        return 'Yesterday';
    } elseif ($diff->days < 7) {
        return $diff->days . 'd ago';
    } else {
        return $date->format('M d');
    }
}
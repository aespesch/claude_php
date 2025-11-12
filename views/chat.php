<!-- Área de exibição das mensagens -->
<div id="chatContainer" class="chat-container">
    <?php if (!empty($_SESSION['messages'])): ?>
        <?php foreach ($_SESSION['messages'] as $msg): ?>
            <div class="message message-<?= htmlspecialchars($msg['role']) ?>">
                <div class="message-header">
                    <span class="message-role">
                        <?= $msg['role'] === 'user' ? '👤 You' : '🤖 Assistant' ?>
                    </span>
                </div>
                <div class="message-content">
                    <?php 
                    // Renderizar markdown se houver função parseMarkdown
                    if (function_exists('parseMarkdown')) {
                        echo parseMarkdown($msg['content']);
                    } else {
                        // Fallback para texto simples com quebras de linha
                        echo nl2br(htmlspecialchars($msg['content']));
                    }
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <p>👋 Start a conversation by typing a message below</p>
        </div>
    <?php endif; ?>
</div>

<!-- Área de entrada de mensagem -->
<div class="input-area">
    <form method="POST" enctype="multipart/form-data" id="chatForm">
        <input type="hidden" name="action" value="send">
        <input type="hidden" name="model" id="chatFormModel" value="<?= $currentModel ?>">
        <input type="hidden" name="temperature" id="chatFormTemp" value="<?= $currentTemp ?>">
        <input type="hidden" name="max_tokens" id="chatFormMaxTokens" value="<?= $currentMaxTokens ?>">
        <div class="file-upload">
            <input type="file" name="files[]" id="files" multiple accept=".png,.jpg,.jpeg,.txt,.py,.csv,.md,.json,.cfg,.php,.sql,.js,.html,.css,.xml,.yaml,.yml,.sh,.bash,.ini,.log,.java,.cpp,.c,.h,.go,.rs,.ts,.jsx,.tsx,.vue,.r,.m,.swift,.kt,.scala,.rb,.pl,.lua" onchange="updateFilesList()">
            <label for="files">📎 Attach files</label>
            <div class="files-selected" id="filesSelected" style="display: none;"></div>
        </div>
        <div class="input-wrapper">
            <textarea name="message" placeholder="Type your message..." required></textarea>
            <button type="submit">Send</button>
        </div>
    </form>
</div>

<script>
// Sync settings from sidebar to chat form
document.addEventListener('DOMContentLoaded', function() {
    function syncSettings() {
        const model = document.getElementById('model');
        const temp = document.getElementById('temperature');
        const maxTokens = document.getElementById('max_tokens');

        if (model) document.getElementById('chatFormModel').value = model.value;
        if (temp) document.getElementById('chatFormTemp').value = temp.value;
        if (maxTokens) document.getElementById('chatFormMaxTokens').value = maxTokens.value;
    }

    // Sync on change
    const model = document.getElementById('model');
    const temp = document.getElementById('temperature');
    const maxTokens = document.getElementById('max_tokens');

    if (model) model.addEventListener('change', syncSettings);
    if (temp) temp.addEventListener('input', syncSettings);
    if (maxTokens) maxTokens.addEventListener('input', syncSettings);
});
</script>
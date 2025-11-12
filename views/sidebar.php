<div class="sidebar">
    <div class="conversations-section">
        <h2>💬 Recent Chats</h2>
        <button type="button" class="new-chat-btn" onclick="startNewChat()">➕ New Chat</button>
        <div class="conversations-list">
            <?php if (empty($recentConversations)): ?>
                <div class="no-conversations">No conversations yet.<br>Start chatting!</div>
            <?php else: ?>
                <?php foreach ($recentConversations as $conv): ?>
                    <?php $isActive = $_SESSION['conversation_id'] == $conv['cnvr_id']; ?>
                    <div class="conversation-item <?= $isActive ? 'active' : '' ?>" 
                         onclick="loadConversation(<?= $conv['cnvr_id'] ?>)"
                         title="<?= htmlspecialchars($conv['cnvr_title']) ?>">
                        <div class="conversation-title"><?= htmlspecialchars($conv['cnvr_title']) ?></div>
                        <div class="conversation-meta">
                            <span class="conversation-date"><?= formatTimeAgo($conv['cnvr_updated_at']) ?></span>
                            <span class="conversation-count"><?= $conv['message_count'] ?> msg<?= $conv['message_count'] != 1 ? 's' : '' ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="settings-section">
        <h2>⚙️ Settings</h2>
        <div class="form-group">
            <label for="model">Model</label>
            <select name="model" id="model" onchange="updateMaxTokens()">
                <?php foreach (ClaudeAPI::MODELS as $key => $label): ?>
                    <option value="<?= $key ?>" 
                            data-max-tokens="<?= ClaudeAPI::MODEL_MAX_TOKENS[$key] ?>"
                            <?= $key === $currentModel ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="info-box">📊 Max tokens: <span id="maxTokensDisplay"><?= number_format($maxTokensLimit) ?></span></div>
        <div class="form-group">
            <label for="temperature">Temperature</label>
            <input type="range" name="temperature" id="temperature" min="0" max="1" step="0.1" value="<?= $currentTemp ?>" oninput="updateSliderValue('temperature', this.value)">
            <div class="slider-value" id="temperature-value"><?= $currentTemp ?></div>
        </div>
        <div class="form-group">
            <label for="max_tokens">Max Tokens</label>
            <input type="range" name="max_tokens" id="max_tokens" min="100" max="<?= $maxTokensLimit ?>" step="100" value="<?= $currentMaxTokens ?>" oninput="updateSliderValue('max_tokens', this.value)">
            <div class="slider-value" id="max_tokens-value"><?= number_format($currentMaxTokens) ?></div>
        </div>
        <div class="button-group">
            <button type="button" onclick="clearAll()">🗑️ Clear</button>
            <button type="button" class="secondary" onclick="copyChat()">📋 Copy</button>
        </div>
    </div>
</div>
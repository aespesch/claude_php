// Initialize syntax highlighting on page load
document.addEventListener('DOMContentLoaded', function() {
    hljs.highlightAll();

    // Auto-scroll to latest message
    const chatContainer = document.getElementById('chatContainer');
    if (chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    // Re-highlight code blocks dynamically
    const observer = new MutationObserver(() => {
        document.querySelectorAll('pre code:not(.hljs)').forEach(block => {
            hljs.highlightElement(block);
        });
    });

    if (chatContainer) {
        observer.observe(chatContainer, {
            childList: true,
            subtree: true
        });
    }
});

// Update slider value display
function updateSliderValue(name, value) {
    const display = document.getElementById(name + '-value');
    if (display) {
        if (name === 'max_tokens') {
            display.textContent = parseInt(value).toLocaleString();
        } else {
            display.textContent = parseFloat(value).toFixed(1);
        }
    }
}

// Update max tokens when model changes
function updateMaxTokens() {
    const modelSelect = document.getElementById('model');
    const maxTokensInput = document.getElementById('max_tokens');
    const maxTokensDisplay = document.getElementById('maxTokensDisplay');

    if (!modelSelect || !maxTokensInput || !maxTokensDisplay) return;

    // Get max tokens from data attribute (set by PHP)
    const maxLimit = parseInt(modelSelect.options[modelSelect.selectedIndex].dataset.maxTokens) || 4000;

    maxTokensInput.max = maxLimit;
    maxTokensDisplay.textContent = maxLimit.toLocaleString();

    // Adjust current value if it exceeds new limit
    if (parseInt(maxTokensInput.value) > maxLimit) {
        maxTokensInput.value = maxLimit;
        updateSliderValue('max_tokens', maxLimit);
    }
}

// Track selected files
let selectedFiles = new DataTransfer();

// Update list of selected files
function updateFilesList() {
    const filesInput = document.getElementById('files');
    const filesSelected = document.getElementById('filesSelected');

    if (!filesInput || !filesSelected) return;

    // Add new files to the DataTransfer object
    Array.from(filesInput.files).forEach(file => {
        // Check if file already exists
        let exists = false;
        for (let i = 0; i < selectedFiles.files.length; i++) {
            if (selectedFiles.files[i].name === file.name && 
                selectedFiles.files[i].size === file.size) {
                exists = true;
                break;
            }
        }
        if (!exists) {
            selectedFiles.items.add(file);
        }
    });

    // Update the file input with all selected files
    filesInput.files = selectedFiles.files;

    // Display files
    displaySelectedFiles();
}

// Display selected files with remove buttons
function displaySelectedFiles() {
    const filesSelected = document.getElementById('filesSelected');
    const filesInput = document.getElementById('files');

    if (!filesSelected || !filesInput) return;

    if (selectedFiles.files.length > 0) {
        let html = '<strong>Files to attach:</strong><div style="margin-top: 10px;">';
        Array.from(selectedFiles.files).forEach((file, index) => {
            const fileSize = (file.size / 1024).toFixed(2);
            html += `
                <div class="file-item" data-index="${index}">
                    <span class="file-tag">📄 ${escapeHtml(file.name)} (${fileSize} KB)</span>
                    <button type="button" class="remove-file" onclick="removeFile(${index})" title="Remove file">×</button>
                </div>
            `;
        });
        html += '</div>';
        filesSelected.innerHTML = html;
        filesSelected.style.display = 'block';
    } else {
        filesSelected.style.display = 'none';
        filesInput.value = '';
    }
}

// Remove a file from selection
function removeFile(index) {
    const filesInput = document.getElementById('files');
    const newDataTransfer = new DataTransfer();

    Array.from(selectedFiles.files).forEach((file, i) => {
        if (i !== index) {
            newDataTransfer.items.add(file);
        }
    });

    selectedFiles = newDataTransfer;
    if (filesInput) {
        filesInput.files = selectedFiles.files;
    }
    displaySelectedFiles();
}

// Copy code block
function copyCode(button) {
    const code = button.getAttribute('data-code');

    navigator.clipboard.writeText(code).then(() => {
        const originalHTML = button.innerHTML;
        button.classList.add('copied');
        button.innerHTML = '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/></svg> Copied!';

        setTimeout(() => {
            button.classList.remove('copied');
            button.innerHTML = originalHTML;
        }, 2000);
    }).catch(() => {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = code;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('✅ Code copied!');
    });
}

// Handle form submission with loading state
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chatForm');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const messageInput = this.querySelector('textarea[name="message"]');

            if (!messageInput.value.trim()) {
                e.preventDefault();
                alert('⚠️ Please enter a message');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Sending...';
            submitBtn.classList.add('loading');
        });
    }
});

// Copy entire chat
function copyChat() {
    const messages = document.querySelectorAll('.message');
    if (messages.length === 0) {
        alert('⚠️ No messages to copy');
        return;
    }

    let text = '';
    messages.forEach(msg => {
        const role = msg.querySelector('.message-role').textContent;
        const content = msg.querySelector('.message-content').textContent;
        text += `${role}:\n${content}\n\n`;
    });

    navigator.clipboard.writeText(text).then(() => {
        alert('✅ Chat copied to clipboard!');
    }).catch(() => {
        prompt('Copy this text:', text);
    });
}

// Clear everything (including files)
function clearAll() {
    if (confirm('Are you sure you want to clear all messages? (Conversation will be archived in database)')) {
        // Clear selected files
        selectedFiles = new DataTransfer();
        const filesInput = document.getElementById('files');
        if (filesInput) {
            filesInput.files = selectedFiles.files;
        }
        displaySelectedFiles();

        // Redirect to clear action
        window.location.href = window.location.pathname + '?action=clear';
    }
}

// Load a specific conversation
function loadConversation(id) {
    window.location.href = window.location.pathname + '?action=load_conversation&id=' + id;
}

// Start a new chat
function startNewChat() {
    if (confirm('Start a new conversation? (Current conversation will be saved)')) {
        window.location.href = window.location.pathname + '?action=clear';
    }
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
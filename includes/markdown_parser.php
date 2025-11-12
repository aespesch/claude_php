<?php
class MarkdownParser {
    public static function parse($text) {
        $codeBlocks = [];
        $blockId = 0;

        // Extract code blocks
        $text = preg_replace_callback('/```(\w*)\n(.*?)\n```/s', function($matches) use (&$codeBlocks, &$blockId) {
            $lang = $matches[1] ?: 'plaintext';
            $code = htmlspecialchars($matches[2]);
            $id = "CODE_BLOCK_{$blockId}";
            $codeBlocks[$id] = "<div class=\"code-block-wrapper\">
                <div class=\"code-header\">
                    <span class=\"code-lang\">{$lang}</span>
                    <button class=\"copy-btn\" onclick=\"copyCode(this)\" data-code=\"" . htmlspecialchars($matches[2], ENT_QUOTES) . "\">
                        <svg width=\"16\" height=\"16\" fill=\"currentColor\" viewBox=\"0 0 16 16\">
                            <path d=\"M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H6z\"/>
                            <path d=\"M2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1H2z\"/>
                        </svg>
                        Copy
                    </button>
                </div>
                <pre><code class=\"language-{$lang}\">{$code}</code></pre>
            </div>";
            $blockId++;
            return $id;
        }, $text);

        // Parse markdown
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);
        $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $text);
        $text = preg_replace('/^\* (.+)$/m', '<li>$1</li>', $text);
        $text = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $text);
        $text = str_replace("\n\n", '</p><p>', $text);
        $text = '<p>' . $text . '</p>';

        // Restore code blocks
        foreach ($codeBlocks as $id => $block) {
            $text = str_replace($id, $block, $text);
        }
        return $text;
    }
}
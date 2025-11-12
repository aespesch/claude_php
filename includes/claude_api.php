<?php
class ClaudeAPI {
    const API_URL = 'https://api.anthropic.com/v1/messages';
    const API_VERSION = '2023-06-01';

    const MODELS = [
        'claude-sonnet-4-5-20250929' => 'Claude Sonnet 4.5 (Smartest for Agents and Coding)',
        'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (Fastest, Near-Frontier Intelligence)',
        'claude-opus-4-1-20250805' => 'Claude Opus 4.1 (Exceptional for Specialized Reasoning)',
        'claude-sonnet-4-20250514' => 'Claude Sonnet 4 (Legacy)',
        'claude-3-7-sonnet-20250219' => 'Claude 3.7 Sonnet (Legacy)',
        'claude-opus-4-20250514' => 'Claude Opus 4 (Legacy)',
        'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku (Legacy)',
        'claude-3-haiku-20240307' => 'Claude 3 Haiku (Legacy)',
    ];

    const MODEL_MAX_TOKENS = [
        'claude-sonnet-4-5-20250929' => 64000,
        'claude-haiku-4-5-20251001' => 64000,
        'claude-opus-4-1-20250805' => 64000,
        'claude-sonnet-4-20250514' => 8192,
        'claude-3-7-sonnet-20250219' => 8192,
        'claude-opus-4-20250514' => 16384,
        'claude-3-5-haiku-20241022' => 8192,
        'claude-3-haiku-20240307' => 4096,
    ];

    private $apiKey;

    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey ?: Config::getApiKey();
        if (!$this->apiKey) throw new Exception('❌ API key not found!');
    }

    public static function getMaxTokens($model) {
        return self::MODEL_MAX_TOKENS[$model] ?? 4000;
    }

    public function sendMessage($message, $model, $temperature = 0.7, $maxTokens = 2000, $history = [], $files = []) {
        $content = [['type' => 'text', 'text' => $message]];
        if (!empty($files)) {
            foreach ($files as $file) {
                $content = array_merge($content, $this->processFile($file));
            }
        }
        $messages = $history;
        $messages[] = ['role' => 'user', 'content' => $content];
        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => $messages
        ];
        return $this->makeRequest($payload);
    }

    private function processFile($file) {
        $content = [];
        $filePath = $file['tmp_name'];
        $fileName = $file['name'];
        $fileType = $file['type'];

        try {
            if (strpos($fileType, 'image/') === 0) {
                $imageData = base64_encode(file_get_contents($filePath));
                $content[] = [
                    'type' => 'image',
                    'source' => ['type' => 'base64', 'media_type' => $fileType, 'data' => $imageData]
                ];
            } elseif (preg_match('/\.(txt|py|csv|md|json|php|cfg|sql|js|html|css|xml|yaml|yml|sh|bash|ini|log|java|cpp|c|h|go|rs|ts|jsx|tsx|vue|r|m|swift|kt|scala|rb|pl|lua)$/i', $fileName)) {
                $textContent = file_get_contents($filePath);
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $langMap = [
                    'php' => 'php', 'sql' => 'sql', 'py' => 'python', 'json' => 'json',
                    'js' => 'javascript', 'ts' => 'typescript', 'jsx' => 'jsx', 'tsx' => 'tsx',
                    'html' => 'html', 'css' => 'css', 'xml' => 'xml', 'yaml' => 'yaml',
                    'yml' => 'yaml', 'sh' => 'bash', 'bash' => 'bash', 'java' => 'java'
                ];
                $lang = $langMap[$extension] ?? '';
                $content[] = ['type' => 'text', 'text' => "\n📄 **{$fileName}**:\n```{$lang}\n{$textContent}\n```"];
            }
        } catch (Exception $e) {
            // Ignore file processing errors
        }
        return $content;
    }

    private function makeRequest($payload) {
        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . self::API_VERSION
            ]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) throw new Exception("❌ cURL Error: {$error}");
        $result = json_decode($response, true);
        if ($httpCode !== 200) {
            $errorMessage = $result['error']['message'] ?? 'Unknown error';
            throw new Exception("❌ API Error [{$httpCode}]: {$errorMessage}");
        }
        return $result['content'][0]['text'] ?? 'No response';
    }
}
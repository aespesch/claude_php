<?php
class Database {
    private $conn;
    private static $instance = null;

    private function __construct() {
        $config = Config::getDbConfig();
        $this->conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);

        if ($this->conn->connect_error) {
            throw new Exception("❌ Database Error: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8mb4");
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function getOrCreateConversation($sessionId, $model, $temperature, $maxTokens) {
        $title = "Chat " . date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare(
            "INSERT INTO conversation (cnvr_title, cnvr_model, cnvr_temperature, cnvr_max_tokens) 
             VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE 
             cnvr_model = VALUES(cnvr_model), cnvr_temperature = VALUES(cnvr_temperature),
             cnvr_max_tokens = VALUES(cnvr_max_tokens), cnvr_updated_at = CURRENT_TIMESTAMP"
        );
        $stmt->bind_param("ssdi", $title, $model, $temperature, $maxTokens);
        $stmt->execute();
        $conversationId = $this->conn->insert_id ?: $this->getLastConversationId($title);
        $stmt->close();
        return $conversationId;
    }

    private function getLastConversationId($title) {
        $stmt = $this->conn->prepare("SELECT cnvr_id FROM conversation WHERE cnvr_title = ?");
        $stmt->bind_param("s", $title);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['cnvr_id'];
    }

    public function getParticipantId($name) {
        $stmt = $this->conn->prepare("SELECT prtc_id FROM participant WHERE prtc_name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? $row['prtc_id'] : null;
    }

    public function saveMessage($conversationId, $participantName, $content, $rawContent = null) {
        $participantId = $this->getParticipantId($participantName);
        if (!$participantId) throw new Exception("Invalid participant: $participantName");

        $stmt = $this->conn->prepare(
            "INSERT INTO message (mssg_cnvr_id, mssg_prtc_id, mssg_content, mssg_raw_content) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("iiss", $conversationId, $participantId, $content, $rawContent);
        $stmt->execute();
        $messageId = $this->conn->insert_id;
        $stmt->close();
        return $messageId;
    }

    public function saveAttachment($messageId, $fileName, $fileType, $fileSize, $fileContent) {
        $fileHash = hash('sha256', $fileContent);
        $stmt = $this->conn->prepare(
            "INSERT INTO attachments (attc_mssg_id, attc_file_name, attc_file_type, attc_file_size, attc_file_content, attc_file_hash) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ississ", $messageId, $fileName, $fileType, $fileSize, $fileContent, $fileHash);
        $stmt->execute();
        $attachmentId = $this->conn->insert_id;
        $stmt->close();
        return $attachmentId;
    }

    public function loadConversationHistory($conversationId, $limit = 50) {
        $stmt = $this->conn->prepare(
            "SELECT m.mssg_content, m.mssg_raw_content, p.prtc_name 
             FROM message m JOIN participant p ON m.mssg_prtc_id = p.prtc_id
             WHERE m.mssg_cnvr_id = ? ORDER BY m.mssg_created_at ASC LIMIT ?"
        );
        $stmt->bind_param("ii", $conversationId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = [
                'role' => $row['prtc_name'],
                'content' => $row['mssg_content'],
                'raw' => $row['mssg_raw_content']
            ];
        }
        $stmt->close();
        return $messages;
    }

    public function updateConversationTitle($conversationId, $title) {
        $stmt = $this->conn->prepare("UPDATE conversation SET cnvr_title = ? WHERE cnvr_id = ?");
        $stmt->bind_param("si", $title, $conversationId);
        $stmt->execute();
        $stmt->close();
    }

    public function archiveConversation($conversationId) {
        $stmt = $this->conn->prepare("UPDATE conversation SET cnvr_is_archived = TRUE WHERE cnvr_id = ?");
        $stmt->bind_param("i", $conversationId);
        $stmt->execute();
        $stmt->close();
    }

    public function getRecentConversations($limit = 15) {
        $stmt = $this->conn->prepare(
            "SELECT cnvr_id, cnvr_title, cnvr_updated_at, cnvr_created_at,
                    (SELECT COUNT(*) FROM message WHERE mssg_cnvr_id = cnvr_id) as message_count
             FROM conversation WHERE cnvr_is_archived = FALSE ORDER BY cnvr_updated_at DESC LIMIT ?"
        );
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $conversations = [];
        while ($row = $result->fetch_assoc()) {
            $conversations[] = $row;
        }
        $stmt->close();
        return $conversations;
    }

    public function loadConversation($conversationId) {
        $stmt = $this->conn->prepare(
            "SELECT cnvr_id, cnvr_title, cnvr_model, cnvr_temperature, cnvr_max_tokens 
             FROM conversation WHERE cnvr_id = ? AND cnvr_is_archived = FALSE"
        );
        $stmt->bind_param("i", $conversationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $conversation = $result->fetch_assoc();
        $stmt->close();
        return $conversation;
    }
}
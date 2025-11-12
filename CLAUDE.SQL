-- Teste de publicação automática 2
-- conversation
CREATE TABLE conversation (
    cnvr_id INT PRIMARY KEY AUTO_INCREMENT,
    cnvr_title VARCHAR(255) NOT NULL,
    cnvr_model VARCHAR(100) NOT NULL DEFAULT 'claude-sonnet-4-5-20250929',
    cnvr_temperature DECIMAL(2,1) DEFAULT 0.7,
    cnvr_max_tokens INT DEFAULT 2000,
    cnvr_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cnvr_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cnvr_is_archived BOOLEAN DEFAULT FALSE,
    cnvr_is_starred BOOLEAN DEFAULT FALSE,
    INDEX idx_cnvr_updated (cnvr_updated_at),
    UNIQUE KEY uk_cnvr_title (cnvr_title)
);

-- participant
CREATE TABLE participant (
    prtc_id INT PRIMARY KEY AUTO_INCREMENT,
    prtc_name VARCHAR(255) NOT NULL,
    prtc_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    prtc_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_prtc_updated (prtc_updated_at)
);

insert into participant (prtc_name) values ('user'), ('assistant'), ('system');

-- message
CREATE TABLE message (
    mssg_id INT PRIMARY KEY AUTO_INCREMENT,
    mssg_cnvr_id INT NOT NULL,
    mssg_prtc_id INT NOT NULL,
    mssg_content TEXT NOT NULL,
    mssg_raw_content TEXT, -- Original message without file indicators
    mssg_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    mssg_token_count INT DEFAULT NULL,
    FOREIGN KEY (mssg_cnvr_id) REFERENCES conversation(cnvr_id) ON DELETE CASCADE,
    INDEX idx_mssg_cnvr_id (mssg_cnvr_id),
    INDEX idx_mssg_created (mssg_created_at)
);

-- attachments
CREATE TABLE attachments (
    attc_id INT PRIMARY KEY AUTO_INCREMENT,
    attc_mssg_id INT NOT NULL,
    attc_file_name VARCHAR(255) NOT NULL,
    attc_file_type VARCHAR(100),
    attc_file_size INT,
    attc_file_content LONGBLOB, -- Store file content
    attc_file_hash VARCHAR(64), -- SHA256 for deduplication
    attc_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attc_mssg_id) REFERENCES message(mssg_id) ON DELETE CASCADE,
    INDEX idx_attc_mssg_id (attc_mssg_id),
    INDEX idx_attc_file_hash (attc_file_hash)
);
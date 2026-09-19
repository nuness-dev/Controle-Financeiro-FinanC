CREATE DATABASE IF NOT EXISTS financ CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE financ;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    foto VARCHAR(255) NULL,
    perfil VARCHAR(20) NOT NULL DEFAULT 'usuario',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_acesso DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    nome VARCHAR(80) NOT NULL,
    tipo VARCHAR(30) NOT NULL DEFAULT 'corrente',
    saldo_inicial DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    instituicao VARCHAR(80) NULL,
    cor CHAR(7) NOT NULL DEFAULT '#6366f1',
    ativa TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_accounts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    KEY idx_accounts_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    nome VARCHAR(80) NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    cor CHAR(7) NOT NULL DEFAULT '#6366f1',
    icone VARCHAR(40) NULL,
    ativa TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    KEY idx_categories_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE cards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    nome VARCHAR(80) NOT NULL,
    banco VARCHAR(80) NULL,
    ultimos_digitos CHAR(4) NOT NULL,
    limite DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    dia_fechamento TINYINT UNSIGNED NOT NULL,
    dia_vencimento TINYINT UNSIGNED NOT NULL,
    cor CHAR(7) NOT NULL DEFAULT '#8b5cf6',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cards_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    KEY idx_cards_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE card_invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    mes_referencia CHAR(7) NOT NULL,
    valor_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    data_fechamento DATE NOT NULL,
    data_vencimento DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'aberta',
    transacao_pagamento_id INT UNSIGNED NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoices_card FOREIGN KEY (card_id) REFERENCES cards (id) ON DELETE CASCADE,
    CONSTRAINT fk_invoices_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    UNIQUE KEY uq_invoice_card_mes (card_id, mes_referencia),
    KEY idx_invoices_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE card_purchases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    categoria_id INT UNSIGNED NULL,
    descricao VARCHAR(180) NOT NULL,
    valor_total DECIMAL(15,2) NOT NULL,
    parcelas_total SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    data_compra DATE NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchases_card FOREIGN KEY (card_id) REFERENCES cards (id) ON DELETE CASCADE,
    CONSTRAINT fk_purchases_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_purchases_category FOREIGN KEY (categoria_id) REFERENCES categories (id) ON DELETE SET NULL,
    KEY idx_purchases_user (user_id),
    KEY idx_purchases_card (card_id)
) ENGINE=InnoDB;

CREATE TABLE card_purchase_installments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_purchase_id INT UNSIGNED NOT NULL,
    card_invoice_id INT UNSIGNED NOT NULL,
    numero_parcela SMALLINT UNSIGNED NOT NULL,
    valor_parcela DECIMAL(15,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pendente',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_installments_purchase FOREIGN KEY (card_purchase_id) REFERENCES card_purchases (id) ON DELETE CASCADE,
    CONSTRAINT fk_installments_invoice FOREIGN KEY (card_invoice_id) REFERENCES card_invoices (id) ON DELETE RESTRICT,
    KEY idx_installments_purchase (card_purchase_id),
    KEY idx_installments_invoice (card_invoice_id)
) ENGINE=InnoDB;

CREATE TABLE transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NULL,
    tipo VARCHAR(20) NOT NULL,
    direcao VARCHAR(10) NOT NULL,
    descricao VARCHAR(180) NOT NULL,
    valor DECIMAL(15,2) NOT NULL,
    data DATE NOT NULL,
    vencimento DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pendente',
    observacao TEXT NULL,
    recorrencia_tipo VARCHAR(20) NULL,
    recorrencia_grupo_id CHAR(36) NULL,
    parcela_atual SMALLINT UNSIGNED NULL,
    parcela_total SMALLINT UNSIGNED NULL,
    parcela_grupo_id CHAR(36) NULL,
    transferencia_par_id INT UNSIGNED NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_transactions_account FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE RESTRICT,
    CONSTRAINT fk_transactions_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL,
    CONSTRAINT fk_transactions_par FOREIGN KEY (transferencia_par_id) REFERENCES transactions (id) ON DELETE SET NULL,
    KEY idx_transactions_user_data (user_id, data),
    KEY idx_transactions_user_status (user_id, status),
    KEY idx_transactions_account (account_id),
    KEY idx_transactions_category (category_id),
    KEY idx_transactions_recorrencia (recorrencia_grupo_id),
    KEY idx_transactions_parcela (parcela_grupo_id)
) ENGINE=InnoDB;

ALTER TABLE card_invoices
    ADD CONSTRAINT fk_invoices_transacao FOREIGN KEY (transacao_pagamento_id) REFERENCES transactions (id) ON DELETE SET NULL;

CREATE TABLE transaction_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    nome_arquivo VARCHAR(64) NOT NULL,
    mime VARCHAR(100) NOT NULL,
    tamanho INT UNSIGNED NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attachments_transaction FOREIGN KEY (transaction_id) REFERENCES transactions (id) ON DELETE CASCADE,
    CONSTRAINT fk_attachments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    KEY idx_attachments_transaction (transaction_id)
) ENGINE=InnoDB;

CREATE TABLE goals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(120) NOT NULL,
    valor_objetivo DECIMAL(15,2) NOT NULL,
    valor_atual DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    prazo DATE NULL,
    cor CHAR(7) NOT NULL DEFAULT '#22c55e',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_goals_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    KEY idx_goals_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE goal_contributions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goal_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    valor DECIMAL(15,2) NOT NULL,
    observacao VARCHAR(255) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contributions_goal FOREIGN KEY (goal_id) REFERENCES goals (id) ON DELETE CASCADE,
    CONSTRAINT fk_contributions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    KEY idx_contributions_goal (goal_id)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expira_em DATETIME NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    KEY idx_password_resets_token (token_hash)
) ENGINE=InnoDB;

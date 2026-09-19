USE financ;

-- admin123 / usuario123
INSERT INTO users (id, nome, email, senha_hash, perfil, ativo) VALUES
    (1, 'Administradora FinanC', 'admin@financ.dev', '$2y$10$Wbsq/tTve3TFEOHD8BszN.8IQUud1FJ5a1p6LJNXsizeJJxR3VPHK', 'admin', 1),
    (2, 'Usuária Demo', 'usuario@financ.dev', '$2y$10$W8QsMAosmB/6/LwJURmWg.BC5zqPvKOyEq3p5Z.xT7lSQfoLW8dMW', 'usuario', 1);

INSERT INTO accounts (id, user_id, nome, tipo, saldo_inicial, instituicao, cor) VALUES
    (1, 2, 'Banco Principal', 'corrente', 2000.00, 'Banco do Brasil', '#3b82f6'),
    (2, 2, 'Carteira', 'carteira', 150.00, NULL, '#22c55e');

INSERT INTO categories (id, user_id, nome, tipo, cor, icone) VALUES
    (1, 2, 'Salário', 'receita', '#22c55e', 'wallet'),
    (2, 2, 'Freelance', 'receita', '#0ea5e9', 'laptop'),
    (3, 2, 'Alimentação', 'despesa', '#f59e0b', 'utensils'),
    (4, 2, 'Transporte', 'despesa', '#ef4444', 'car'),
    (5, 2, 'Moradia', 'despesa', '#8b5cf6', 'home'),
    (6, 2, 'Lazer', 'despesa', '#ec4899', 'gamepad-2');

-- Receita do mês, paga
INSERT INTO transactions (user_id, account_id, category_id, tipo, direcao, descricao, valor, data, status) VALUES
    (2, 1, 1, 'receita', 'entrada', 'Salário mensal', 4500.00, DATE_FORMAT(CURDATE(), '%Y-%m-05'), 'pago');

-- Receita do mês passado, paga (histórico pro gráfico de evolução)
INSERT INTO transactions (user_id, account_id, category_id, tipo, direcao, descricao, valor, data, status) VALUES
    (2, 1, 1, 'receita', 'entrada', 'Salário mensal', 4500.00, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-05'), 'pago'),
    (2, 1, 2, 'receita', 'entrada', 'Projeto freelance', 800.00, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'pago');

-- Despesas pagas do mês
INSERT INTO transactions (user_id, account_id, category_id, tipo, direcao, descricao, valor, data, status) VALUES
    (2, 1, 5, 'despesa', 'saida', 'Aluguel', 1200.00, DATE_FORMAT(CURDATE(), '%Y-%m-10'), 'pago'),
    (2, 2, 3, 'despesa', 'saida', 'Supermercado', 380.50, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'pago'),
    (2, 2, 4, 'despesa', 'saida', 'Combustível', 220.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'pago'),
    (2, 1, 6, 'despesa', 'saida', 'Cinema', 60.00, DATE_SUB(CURDATE(), INTERVAL 1 MONTH), 'pago');

-- Despesa pendente (conta a pagar futura)
INSERT INTO transactions (user_id, account_id, category_id, tipo, direcao, descricao, valor, data, vencimento, status) VALUES
    (2, 1, 5, 'despesa', 'saida', 'Conta de energia', 245.90, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 DAY), 'pendente');

-- Transferência entre as duas contas (duas pernas)
INSERT INTO transactions (id, user_id, account_id, tipo, direcao, descricao, valor, data, status) VALUES
    (100, 2, 1, 'transferencia', 'saida', 'Transferência para Carteira', 300.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'pago'),
    (101, 2, 2, 'transferencia', 'entrada', 'Transferência de Banco Principal', 300.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'pago');
UPDATE transactions SET transferencia_par_id = 101 WHERE id = 100;
UPDATE transactions SET transferencia_par_id = 100 WHERE id = 101;

-- Cartão de crédito com uma fatura aberta e uma compra parcelada em 3x
INSERT INTO cards (id, user_id, nome, banco, ultimos_digitos, limite, dia_fechamento, dia_vencimento, cor) VALUES
    (1, 2, 'Cartão Principal', 'Nubank', '4321', 3000.00, 5, 12, '#8b5cf6');

INSERT INTO card_invoices (id, card_id, user_id, mes_referencia, valor_total, data_fechamento, data_vencimento, status) VALUES
    (1, 1, 2, DATE_FORMAT(CURDATE(), '%Y-%m'), 333.34, DATE_FORMAT(CURDATE(), '%Y-%m-05'), DATE_FORMAT(CURDATE(), '%Y-%m-12'), 'aberta');

INSERT INTO card_purchases (id, card_id, user_id, categoria_id, descricao, valor_total, parcelas_total, data_compra) VALUES
    (1, 1, 2, 6, 'Notebook novo', 1000.00, 3, DATE_SUB(CURDATE(), INTERVAL 4 DAY));

INSERT INTO card_purchase_installments (card_purchase_id, card_invoice_id, numero_parcela, valor_parcela, status) VALUES
    (1, 1, 1, 333.34, 'pendente');
-- as parcelas 2/3 e 3/3 (333.33 cada) cairiam nas faturas dos próximos meses — não criadas ainda no seed por simplicidade.

-- Meta financeira com uma contribuição
INSERT INTO goals (id, user_id, titulo, valor_objetivo, valor_atual, prazo, cor) VALUES
    (1, 2, 'Viagem de férias', 5000.00, 1200.00, DATE_ADD(CURDATE(), INTERVAL 6 MONTH), '#f59e0b');

INSERT INTO goal_contributions (goal_id, user_id, valor, observacao) VALUES
    (1, 2, 1200.00, 'Depósito inicial');

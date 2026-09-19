<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Csrf;
use App\Core\Session;
use App\Helpers\Money;
use App\Repository\AccountRepository;
use App\Repository\AttachmentRepository;
use App\Repository\CategoryRepository;
use App\Repository\InvoiceRepository;
use App\Repository\TransactionRepository;
use App\Service\AccountService;
use App\Service\AuthService;
use App\Service\CardService;
use App\Service\GoalService;
use App\Service\InvoiceService;
use App\Service\ReportService;
use App\Service\TransactionService;

function ok(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new RuntimeException('Falhou: ' . $mensagem);
    }

    echo '[ok] ' . $mensagem . PHP_EOL;
}

function falha(callable $callback, string $mensagem): void
{
    try {
        $callback();
    } catch (Throwable $e) {
        echo '[ok] ' . $mensagem . PHP_EOL;
        return;
    }

    throw new RuntimeException('Falhou: ' . $mensagem);
}

function cents(string $valor): int
{
    return Money::paraCentavos($valor);
}

$root = dirname(__DIR__);
$sessionDir = $root . '/storage/test-sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0755, true);
}
session_save_path($sessionDir);

$host = '127.0.0.1';
$port = '3306';
$user = 'root';
$pass = '';
$db = 'financ_test';

$pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec("DROP DATABASE IF EXISTS {$db}");
$pdo->exec("CREATE DATABASE {$db} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE {$db}");

$schema = file_get_contents($root . '/database/schema.sql');
$schema = preg_replace('/CREATE DATABASE.*?;\s*/is', '', $schema);
$schema = preg_replace('/USE\s+financ\s*;\s*/i', '', $schema);
$pdo->exec($schema);

$_ENV['DB_HOST'] = $host;
$_ENV['DB_PORT'] = $port;
$_ENV['DB_DATABASE'] = $db;
$_ENV['DB_USERNAME'] = $user;
$_ENV['DB_PASSWORD'] = $pass;

Session::start();
$csrf = Csrf::token();
ok(Csrf::verify($csrf), 'CSRF aceita token da sessão');
ok(!Csrf::verify('token-invalido'), 'CSRF rejeita token inválido');

$auth = new AuthService();
$usuarioA = $auth->registrar('Usuário A', 'a@teste.local', 'senha123', 'senha123');
$usuarioB = $auth->registrar('Usuário B', 'b@teste.local', 'senha123', 'senha123');
$userA = (int) $usuarioA['id'];
$userB = (int) $usuarioB['id'];

ok($auth->autenticar('a@teste.local', 'senha123')['email'] === 'a@teste.local', 'login funciona com password_verify');
falha(fn () => $auth->autenticar('a@teste.local', 'errada'), 'login rejeita senha inválida');

$tokenReset = $auth->solicitarRedefinicao('a@teste.local');
ok(is_string($tokenReset) && strlen($tokenReset) > 20, 'recuperação de senha gera token');
$auth->redefinirSenha($tokenReset, 'novaSenha123', 'novaSenha123');
ok($auth->autenticar('a@teste.local', 'novaSenha123')['email'] === 'a@teste.local', 'redefinição de senha troca a senha');

$accounts = new AccountService();
$categories = new CategoryRepository();
$transactions = new TransactionService();
$accountRepo = new AccountRepository();
$transactionRepo = new TransactionRepository();

$contaA1 = $accounts->criar($userA, [
    'nome' => 'Banco A',
    'tipo' => 'corrente',
    'saldo_inicial' => '1000.00',
    'instituicao' => 'Teste',
    'cor' => '#059669',
]);
$contaA2 = $accounts->criar($userA, [
    'nome' => 'Carteira A',
    'tipo' => 'carteira',
    'saldo_inicial' => '100.00',
    'cor' => '#2563eb',
]);
$contaB = $accounts->criar($userB, [
    'nome' => 'Banco B',
    'tipo' => 'corrente',
    'saldo_inicial' => '50.00',
    'cor' => '#dc2626',
]);

$catReceita = $categories->criar($userA, ['nome' => 'Salário', 'tipo' => 'receita', 'cor' => '#22c55e', 'icone' => 'wallet']);
$catDespesa = $categories->criar($userA, ['nome' => 'Casa', 'tipo' => 'despesa', 'cor' => '#ef4444', 'icone' => 'home']);
$catB = $categories->criar($userB, ['nome' => 'Outra', 'tipo' => 'despesa', 'cor' => '#64748b', 'icone' => 'tag']);

$receita = $transactions->criar($userA, [
    'tipo' => 'receita',
    'descricao' => 'Receita paga',
    'valor' => '500.00',
    'account_id' => $contaA1,
    'category_id' => $catReceita,
    'data' => '2026-09-01',
    'status' => 'pago',
]);
$despesa = $transactions->criar($userA, [
    'tipo' => 'despesa',
    'descricao' => 'Despesa paga',
    'valor' => '125.30',
    'account_id' => $contaA1,
    'category_id' => $catDespesa,
    'data' => '2026-09-02',
    'status' => 'pago',
]);
$pendente = $transactions->criar($userA, [
    'tipo' => 'despesa',
    'descricao' => 'Despesa pendente',
    'valor' => '74.70',
    'account_id' => $contaA1,
    'category_id' => $catDespesa,
    'data' => '2026-09-03',
    'vencimento' => '2026-09-20',
    'status' => 'pendente',
]);

$saldo = $accountRepo->calcularSaldo($contaA1, $userA);
ok($saldo['atual'] === '1374.70', 'receita paga aumenta e despesa paga reduz saldo atual');
ok($saldo['previsto'] === '1300.00', 'transação pendente entra apenas no saldo previsto');

$totalAntes = $accountRepo->calcularSaldoTotal($userA);
$transferencia = $transactions->criar($userA, [
    'tipo' => 'transferencia',
    'descricao' => 'Reserva para carteira',
    'valor' => '200.00',
    'account_id' => $contaA1,
    'conta_destino_id' => $contaA2,
    'data' => '2026-09-04',
]);
$saldoA1 = $accountRepo->calcularSaldo($contaA1, $userA);
$saldoA2 = $accountRepo->calcularSaldo($contaA2, $userA);
$totalDepois = $accountRepo->calcularSaldoTotal($userA);
ok($saldoA1['atual'] === '1174.70' && $saldoA2['atual'] === '300.00', 'transferência reduz uma conta e aumenta outra');
ok($totalAntes['atual'] === $totalDepois['atual'], 'transferência não altera o patrimônio total');

$resumo = $transactionRepo->resumoPeriodo($userA, '2026-09-01', '2026-09-30');
ok($resumo['receita'] === '500.00' && $resumo['despesa'] === '125.30', 'transferência não entra como receita ou despesa');

$parcelada = $transactions->criar($userA, [
    'tipo' => 'despesa',
    'descricao' => 'Compra parcelada',
    'valor' => '100.00',
    'account_id' => $contaA1,
    'category_id' => $catDespesa,
    'data' => '2026-09-05',
    'status' => 'pendente',
    'parcela_total' => 3,
]);
$grupoParcelas = $parcelada['parcela_grupo_id'];
$stmtParcelas = $pdo->prepare('SELECT valor FROM transactions WHERE parcela_grupo_id = ? ORDER BY parcela_atual ASC');
$stmtParcelas->execute([$grupoParcelas]);
$valoresParcelas = array_column($stmtParcelas->fetchAll(), 'valor');
ok($valoresParcelas === ['33.34', '33.33', '33.33'], 'parcelamento distribui centavos sem perder o total');
ok(array_sum(array_map('cents', $valoresParcelas)) === cents('100.00'), 'soma das parcelas é exatamente o valor original');

$recorrente = $transactions->criar($userA, [
    'tipo' => 'receita',
    'descricao' => 'Receita recorrente',
    'valor' => '10.00',
    'account_id' => $contaA1,
    'category_id' => $catReceita,
    'data' => '2026-09-06',
    'status' => 'pago',
    'recorrencia_tipo' => 'mensal',
]);
$stmtRec = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE recorrencia_grupo_id = ?');
$stmtRec->execute([$recorrente['recorrencia_grupo_id']]);
ok((int) $stmtRec->fetchColumn() === 12, 'recorrência mensal gera 12 ocorrências iniciais');

$cards = new CardService();
$invoices = new InvoiceService();
$cardId = $cards->criar($userA, [
    'nome' => 'Cartão Teste',
    'banco' => 'Banco',
    'ultimos_digitos' => '1234',
    'limite' => '5000.00',
    'dia_fechamento' => 10,
    'dia_vencimento' => 15,
    'cor' => '#8b5cf6',
]);
$faturaAntes = $invoices->determinarFatura($cardId, $userA, '2026-09-09');
$faturaDepois = $invoices->determinarFatura($cardId, $userA, '2026-09-11');
ok($faturaAntes['mes_referencia'] === '2026-09', 'compra antes do fechamento cai na fatura do mês');
ok($faturaDepois['mes_referencia'] === '2026-10', 'compra depois do fechamento cai na próxima fatura');

$cardVirada = $cards->criar($userA, [
    'nome' => 'Cartão Virada',
    'banco' => 'Banco',
    'ultimos_digitos' => '9999',
    'limite' => '1000.00',
    'dia_fechamento' => 31,
    'dia_vencimento' => 31,
    'cor' => '#0f766e',
]);
$faturaFevereiro = $invoices->determinarFatura($cardVirada, $userA, '2028-02-29');
ok($faturaFevereiro['data_fechamento'] === '2028-02-29', 'fatura respeita fevereiro em ano bissexto');
$faturaAno = $invoices->determinarFatura($cardId, $userA, '2026-12-11');
ok($faturaAno['mes_referencia'] === '2027-01', 'fatura trata virada de ano');

$saldoAntesCartao = $accountRepo->calcularSaldo($contaA1, $userA)['atual'];
$compra = $invoices->registrarCompra($userA, $cardId, [
    'descricao' => 'Compra no cartão',
    'categoria_id' => $catDespesa,
    'valor_total' => '1200.00',
    'parcelas_total' => 12,
    'data_compra' => '2026-09-09',
]);
$saldoDepoisCartao = $accountRepo->calcularSaldo($contaA1, $userA)['atual'];
ok($saldoAntesCartao === $saldoDepoisCartao, 'compra no cartão não reduz saldo da conta imediatamente');

$parcelasCartao = $invoices->listarParcelas((int) $compra['id'], $userA);
ok(count($parcelasCartao) === 12, 'compra parcelada no cartão gera todas as parcelas');
ok(array_sum(array_map(fn ($p) => cents($p['valor_parcela']), $parcelasCartao)) === cents('1200.00'), 'parcelas do cartão somam o valor original');

$invoiceRepo = new InvoiceRepository();
$faturaParaPagar = $invoiceRepo->buscarPorCartaoEMes($cardId, '2026-09');
$saldoAntesPagamento = $accountRepo->calcularSaldo($contaA1, $userA)['atual'];
$invoices->pagarFatura((int) $faturaParaPagar['id'], $userA, $contaA1);
$saldoAposPagamento = $accountRepo->calcularSaldo($contaA1, $userA)['atual'];
ok(cents($saldoAntesPagamento) - cents($saldoAposPagamento) === cents($faturaParaPagar['valor_total']), 'pagamento de fatura reduz saldo da conta');
falha(fn () => $invoices->pagarFatura((int) $faturaParaPagar['id'], $userA, $contaA1), 'fatura paga não pode ser paga novamente');

$goals = new GoalService();
$goalId = $goals->criar($userA, [
    'titulo' => 'Notebook',
    'valor_objetivo' => '1000.00',
    'prazo' => '2026-12-31',
    'cor' => '#f59e0b',
]);
$goal = $goals->contribuir($goalId, $userA, ['valor' => '1000.00', 'observacao' => 'Depósito']);
ok($goal['valor_atual'] === '1000.00' && $goal['concluida'], 'meta aceita contribuição positiva e marca como concluída');
falha(fn () => $goals->contribuir($goalId, $userA, ['valor' => '0.00']), 'meta rejeita contribuição zero');

$attachmentRepo = new AttachmentRepository();
$attachmentId = $attachmentRepo->criar((int) $despesa['id'], $userA, [
    'nome_original' => 'comprovante.pdf',
    'nome_arquivo' => 'arquivo-teste.pdf',
    'mime' => 'application/pdf',
    'tamanho' => 123,
]);
ok($attachmentRepo->buscarPorIdEUsuario($attachmentId, $userA) !== null, 'anexo pertence ao usuário correto');
ok($attachmentRepo->buscarPorIdEUsuario($attachmentId, $userB) === null, 'usuário B não acessa anexo do usuário A');

falha(fn () => $accounts->obter($contaA1, $userB), 'usuário B não visualiza conta do usuário A');
falha(fn () => $transactions->obter((int) $despesa['id'], $userB), 'usuário B não visualiza transação do usuário A');
falha(fn () => $transactions->atualizar((int) $despesa['id'], $userB, [
    'tipo' => 'despesa',
    'descricao' => 'Tentativa',
    'valor' => '1.00',
    'account_id' => $contaB,
    'category_id' => $catB,
    'data' => '2026-09-01',
    'status' => 'pago',
]), 'usuário B não edita transação do usuário A');
falha(fn () => $accounts->excluir($contaA1, $userA), 'conta com movimentações não pode ser excluída');

$categories->excluir($catDespesa, $userA);
$despesaSemCategoria = $transactions->obter((int) $despesa['id'], $userA);
ok($despesaSemCategoria['category_id'] === null, 'excluir categoria não exclui transações');

$report = (new ReportService())->resumo($userA, 'personalizado', '2026-09-01', '2026-09-30');
ok(isset($report['receitas'], $report['despesas'], $report['gastos_por_categoria']), 'relatórios carregam totais e categorias');

echo PHP_EOL . 'Todos os testes manuais passaram.' . PHP_EOL;

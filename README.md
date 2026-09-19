# FinanC

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)

O FinanC é um sistema de controle financeiro web que desenvolvi para o meu portfólio. 

A ideia principal desse projeto foi construir uma aplicação do zero utilizando PHP puro. Eu quis evitar propositalmente frameworks robustos (como Laravel ou Symfony) para conseguir entender na prática como resolver problemas de arquitetura, lidar com regras de negócio mais complexas e aplicar conceitos de segurança escrevendo o código "na mão".

## Funcionalidades

- **Gestão de contas:** Acompanhamento do saldo atual (consolidado) e do saldo previsto (considerando movimentações futuras).
- **Cartões de crédito:** Lógica para controle de faturas, identificando em qual mês a compra vai cair baseado no dia de fechamento do cartão. Pagar a fatura gera automaticamente a despesa na conta.
- **Transações:** 
  - Parcelamentos que dividem os centavos corretamente (ex: R$ 100 em 3x divide de forma a não perder 1 centavo).
  - Transferências entre contas que afetam o saldo de ambas simultaneamente.
  - Receitas e despesas recorrentes.
- **Autenticação:** Cadastro, login, proteção de rotas e recuperação de senha.
- **Relatórios:** Filtros por período e gráficos montados com Chart.js.

## Desafios Técnicos e Aprendizados

Como não usei um framework que entrega muita coisa pronta, esbarrei em alguns desafios legais que serviram de aprendizado:

1. **Arquitetura MVC e PSR-4:** Configurei o autoload do Composer para organizar o projeto no padrão MVC. Deu pra entender bem como separar a regra de negócio (Services) das consultas ao banco (Repositories) e do controle de fluxo (Controllers).
2. **Lógica de Faturas:** Foi a parte que mais deu trabalho. Lidar com datas em PHP e calcular faturas levando em conta meses de 28, 30 e 31 dias, além de anos bissextos.
3. **Segurança:** Tive que implementar minhas próprias proteções: uso de PDO com Prepared Statements contra SQL Injection, tokens CSRF para validação de formulários e um sistema de upload onde os comprovantes financeiros ficam fora da pasta `public`, acessíveis apenas se o usuário estiver logado.
4. **Requisições Assíncronas:** Em vez de recarregar a página a cada ação, criei alguns endpoints retornando JSON no PHP e consumi no frontend utilizando o `fetch` nativo do JavaScript.

## Tecnologias Utilizadas

- **Backend:** PHP 8.0, Arquitetura MVC, Composer (para autoload e variáveis de ambiente com `vlucas/phpdotenv`).
- **Banco de Dados:** MySQL usando PDO.
- **Frontend:** HTML, CSS (TailwindCSS via CDN), JavaScript Vanilla e Chart.js.

## Como rodar o projeto

Recomendo o uso do XAMPP ou ambiente similar.

1. Clone o repositório na sua pasta pública (`htdocs` se for XAMPP):
```bash
cd C:\xampp\htdocs\sistemas
git clone https://github.com/SEU_USUARIO/financ.git
```

2. Instale as dependências pelo Composer:
```bash
cd financ
composer install
```

3. Configure o banco de dados:
Copie o arquivo `.env.example` e renomeie para `.env`. Atualize com as credenciais do seu banco local.

4. Importe o banco de dados:
No terminal MySQL ou phpMyAdmin, crie um banco chamado `financ` e importe as tabelas e dados iniciais:
```sql
source database/schema.sql;
source database/seed.sql;
```

5. Acesse no navegador:
`http://localhost/sistemas/financ/public`

**Usuários de teste criados no seed:**
- Admin: `admin@financ.dev` (senha: `admin123`)
- Usuário: `usuario@financ.dev` (senha: `usuario123`)

## Screenshots

*(Coloque aqui 2 ou 3 prints do sistema rodando, principalmente do dashboard e da tela de transações)*

## Próximos passos (To-do)

- [ ] Implementar testes unitários com PHPUnit.
- [ ] Melhorar a edição em lote de transações recorrentes.
- [ ] Exportação de relatórios em PDF.

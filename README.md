# BIBLIOFÁCIL — API REST (Laravel 12 + SQLite + Sanctum)

API da biblioteca BIBLIOFÁCIL: acervo (livros e exemplares), empréstimos, vendas, usuários e relatórios.
Todas as respostas são JSON. Todas as rotas (exceto o login) exigem token Bearer.

## Stack

- PHP 8.2+ · Laravel 12 (penúltima versão principal) · SQLite
- Laravel Sanctum (autenticação por token)
- Mensagens de validação em pt-BR

## Instalação

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite          # Windows: type nul > database\database.sqlite
php artisan migrate --seed
php artisan serve                       # http://localhost:8000
php artisan test                        # testes automatizados
```

Usuários criados pelo seeder:

| Perfil | E-mail | Senha |
|---|---|---|
| Admin | ana.silva@example.com | senha123 |
| Funcionário | roberto.faria@example.com | senha123 |

Clientes não têm senha e não acessam a API (são os leitores/compradores).

O arquivo [`docs/requisicoes.http`](docs/requisicoes.http) traz exemplos prontos (extensão REST Client do VS Code).

## Arquitetura

```
Request → FormRequest (validação/autorização) → Controller (orquestra)
        → Service (regras de negócio, transações) → Repository (consultas Eloquent)
        → Model → API Resource (formato JSON)
```

| Pasta | Responsabilidade |
|---|---|
| `app/Http/Controllers/Api/V1` | Recebem a requisição, chamam o service/repository e devolvem o Resource. Sem regra de negócio. |
| `app/Http/Requests` | Validação de entrada (ex.: `cliente_id` precisa ser do tipo cliente) e autorização. |
| `app/Services` | Regras de negócio: emprestar, devolver, vender, cadastrar usuário, relatórios. Usam `DB::transaction` e `lockForUpdate()`. |
| `app/Repositories` | Todas as consultas ao banco (filtros, paginação, contadores, agregações). |
| `app/Http/Resources` | Formato JSON entregue ao front. |
| `app/Policies/UsuarioPolicy.php` | Quem pode criar/editar/excluir cada tipo de usuário. |
| `app/Http/Middleware/VerificarTipoUsuario.php` | Middleware `tipo:admin` / `tipo:funcionario,admin`. |
| `app/Enums` | `TipoUsuario`, `EstadoExemplar`, `SituacaoExemplar`, `StatusEmprestimo`. |
| `app/Exceptions/RegraNegocioException.php` | Violação de regra → JSON 422 padronizado. |
| `app/Support/Periodo.php` | Value object dos filtros de data (`de`/`ate` ou `mes`). |
| `config/bibliofacil.php` | Prazo padrão, limite de empréstimos por leitor, bloqueio por atraso. |

Padrões usados (só onde fazem sentido): Service Layer, Repository, Value Object (`Periodo`), Policy, Middleware, API Resource, Form Request, Enums. Injeção de dependências pelo construtor (DIP do SOLID).

## Regras do modelo (conforme o DER)

- `livros` é o título (ISBN único, dois preços). `exemplares` é cada unidade física, com código de barras único.
- **Não existe coluna de quantidade**: estoque = exemplares com `situacao = disponivel`.
- O cadastro de livro **não recebe quantidade**: cadastra-se o título (opcionalmente já com o primeiro exemplar) e cada novo exemplar é adicionado um por vez em `POST /livros/{id}/exemplares`.
- Emprestar → exemplar `emprestado`; devolver → volta a `disponivel`; vender → `vendido` (não volta).
- Empréstimo e venda copiam o preço do momento (`valor_cobrado`, `preco_unitario`).
- Atraso é calculado: sem `data_devolucao` e `data_devolucao_prevista` anterior a hoje.
- Venda com quantidade 2 gera 2 linhas em `itens_venda`, cada uma com um exemplar diferente.
- Emprestar, devolver e vender rodam em `DB::transaction` com `lockForUpdate()`.
- Leitor com empréstimo em atraso, ou no limite de empréstimos em aberto (padrão 3), não pode pegar outro (configurável no `.env`).
- **Formas de pagamento** são cadastradas previamente pelo admin (tabela `formas_pagamento`); a venda guarda `forma_pagamento_id`. Formas já usadas não são excluídas, só desativadas.

## Permissões

| Ação | Funcionário | Admin |
|---|:-:|:-:|
| Dashboard, busca, consultar livros/exemplares | ✔ | ✔ |
| Emprestar, renovar, dar baixa (devolução), ver ativos/atrasados | ✔ | ✔ |
| Realizar vendas e consultá-las | ✔ | ✔ |
| Cadastrar/editar **clientes** | ✔ | ✔ |
| Cadastrar/editar funcionários e **admins** | ✘ | ✔ |
| Excluir usuários | ✘ | ✔ |
| Cadastrar/editar/excluir livros e exemplares | ✘ | ✔ |
| Cadastrar formas de pagamento | ✘ | ✔ |
| Relatórios | ✘ | ✔ |

Respostas: `401` sem token · `403` sem permissão · `404` registro inexistente · `422` validação/regra de negócio · `429` excesso de tentativas de login.

## Endpoints (prefixo `/api/v1`)

### Autenticação
| Método | Rota | Descrição |
|---|---|---|
| POST | `/auth/login` | `{email, password}` → `{token, usuario}` (limite: 5/min) |
| GET | `/auth/me` | Usuário logado (cabeçalho das telas) |
| POST | `/auth/logout` | Revoga o token atual (`?todos=1` revoga todos) |
| PUT | `/auth/senha` | `{senha_atual, password, password_confirmation}` |

### Dashboard
| Método | Rota | Descrição |
|---|---|---|
| GET | `/dashboard` | Usuário logado, indicadores (livros disponíveis, empréstimos ativos, em atraso, clientes) e atalhos filtrados pela permissão |
| GET | `/busca?q=` | Busca global em livros e clientes |
| GET | `/opcoes` | Valores para selects (tipos de usuário, estados, situações, prazo padrão) |

### Livros e exemplares
| Método | Rota | Acesso | Descrição |
|---|---|---|---|
| GET | `/livros?busca=&situacao=todos\|disponiveis\|indisponiveis&per_page=` | equipe | Acervo paginado + `meta.contadores` das abas |
| GET | `/livros/{id}` | equipe | Detalhe com todos os exemplares |
| POST | `/livros` | admin | `{titulo, autor, isbn, preco_venda, preco_emprestimo, exemplar?: {codigo_barras, estado}}` |
| PUT/PATCH | `/livros/{id}` | admin | Editar título/preços |
| DELETE | `/livros/{id}` | admin | Só exclui se nunca foi emprestado/vendido |
| GET | `/livros/{id}/exemplares?situacao=` | equipe | Exemplares do título |
| POST | `/livros/{id}/exemplares` | admin | Adiciona **um** exemplar `{codigo_barras, estado}` |
| GET | `/exemplares/codigo/{codigo}` | equipe | Leitura de código de barras |
| GET | `/exemplares/{id}` | equipe | Detalhe |
| PUT/PATCH | `/exemplares/{id}` | admin | Editar código/estado |
| DELETE | `/exemplares/{id}` | admin | Só sem histórico |

### Empréstimos
| Método | Rota | Descrição |
|---|---|---|
| GET | `/emprestimos?status=todos\|ativos\|atrasados\|devolvidos\|em_aberto&busca=&de=&ate=` | Lista paginada (leitor, livro, retirada, previsto, devolvido, situação) + `meta.contadores` |
| GET | `/emprestimos/ativos` · `/emprestimos/atrasados` | Atalhos |
| GET | `/emprestimos/{id}` | Painel "Detalhes" (ISBN, estado, registrado por, dias de atraso) |
| GET | `/clientes?busca=` | Autocomplete do campo "Leitor", já com pendências |
| GET | `/clientes/{id}/pendencias` | Empréstimos em aberto/atrasados do leitor e se pode emprestar |
| POST | `/emprestimos/previa` | `{cliente_id, livro_id \| codigo_barras}` → dados do livro, preço, disponibilidade e bloqueios, sem gravar |
| POST | `/emprestimos` | `{cliente_id, livro_id \| codigo_barras, data_devolucao_prevista, observacoes?}` |
| PATCH | `/emprestimos/{id}` | Renovar prazo / observações |
| PATCH | `/emprestimos/{id}/devolucao` | Dar baixa `{data_devolucao?, estado?, observacoes?}` |

### Vendas
| Método | Rota | Descrição |
|---|---|---|
| GET | `/formas-pagamento?ativas=1` | Métodos de pagamento cadastrados |
| POST | `/vendas/previa` | `{itens:[{livro_id, quantidade}], desconto}` → preço unitário, qtd., total por item, subtotal, desconto, total a pagar |
| POST | `/vendas` | "Finalizar venda": `{cliente_id, forma_pagamento_id, desconto, itens:[...]}` |
| GET | `/vendas?de=&ate=&mes=&forma_pagamento_id=&cliente_id=` | Histórico |
| GET | `/vendas/{id}` | Comprovante com itens agrupados por título e exemplares |
| POST/PUT/DELETE | `/formas-pagamento[/{id}]` | Admin: cadastro prévio das formas de pagamento |

O carrinho fica no front-end. A cada item adicionado, o front chama `/vendas/previa` para atualizar o resumo e, ao finalizar, envia tudo em uma única requisição `POST /vendas`.

### Usuários
| Método | Rota | Descrição |
|---|---|---|
| GET | `/usuarios?tipo=cliente\|funcionario\|admin&busca=&per_page=` | Lista paginada + `meta.contadores` |
| GET | `/usuarios/{id}` | Detalhe |
| POST | `/usuarios` | `{nome, email, telefone, tipo, password?, password_confirmation?, cpf?, endereco?, matricula?, cargo?}`. Senha obrigatória para funcionário/admin. Só admin cria funcionário/admin |
| PUT/PATCH | `/usuarios/{id}` | Funcionário edita só clientes |
| DELETE | `/usuarios/{id}` | Admin; não exclui a si mesmo, o último admin nem quem tem movimentações |

### Relatórios (admin) — filtros `?de=YYYY-MM-DD&ate=YYYY-MM-DD` ou `?mes=YYYY-MM`
| Método | Rota | Descrição |
|---|---|---|
| GET | `/relatorios` | Tela completa em uma chamada |
| GET | `/relatorios/resumo` | Empréstimos realizados, devolvidos, em atraso (hoje), vendas (quantidade e total) |
| GET | `/relatorios/livros-mais-emprestados?limite=` | `{titulo, emprestimos}` |
| GET | `/relatorios/vendas-por-pagamento` | `{metodo, vendas, total}` + `total_geral` |
| GET | `/relatorios/vendas-por-mes` | Totais agrupados por mês |
| GET | `/relatorios/emprestimos-atrasados` | `{leitor, livro, previsto, dias_atraso}` |

## Formato das respostas

```json
// Lista paginada
{ "data": [ ... ], "links": { ... }, "meta": { "current_page": 1, "total": 48, "contadores": { "todos": 48, "ativos": 36, "atrasados": 4, "devolvidos": 12 } } }

// Erro de validação / regra de negócio (422)
{ "message": "O leitor possui pendências e não pode realizar novo empréstimo.", "errors": { "cliente_id": ["O leitor possui 1 empréstimo(s) em atraso."] } }
```

Datas em ISO 8601 (`2026-10-21`) e valores monetários como número (`53.0`). A formatação (`21/10`, `R$ 53,00`) fica no front.

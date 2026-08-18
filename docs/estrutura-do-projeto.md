# PilaControl — Organização de Arquivos

Documento de convenção: define **onde cada coisa mora**. Atualizado após a instalação do
esqueleto, para refletir o que existe de fato no repositório.

Referência visual: protótipo `Financial control app.zip` (Figma Make, React) — este documento
mapeia cada tela e entidade do protótipo para o seu lugar no Laravel.

## Stack instalada

| Camada | Versão |
|---|---|
| PHP | 8.4.24 |
| Laravel | 13.26 |
| Livewire | 4.1 |
| Flux UI | 2.13 (edição free) |
| Fortify | 1.37 — autenticação |
| Tailwind CSS | 4.1 |
| Vite | 8.2 |
| Pest | 5.1 |

Instalado via `laravel new --livewire --livewire-class-components --pest --database=sqlite`.
Ferramental extra que veio junto: Pint (estilo), Larastan (análise estática), Pail (logs).

---

## 1. Princípios

1. **Componente Livewire é fino.** Recebe input, chama uma Action, devolve estado para a view.
   Nada de regra de negócio dentro de método de componente.
2. **Uma Action = uma operação de negócio.** Classe com um único método público `handle()`.
3. **Espelhamento obrigatório.** O caminho da classe Livewire e o da sua view Blade são o mesmo,
   trocando PascalCase por kebab-case. Sem exceção.
4. **Flux é a única fonte de UI.** Não escrevemos botão, input, modal ou tabela do zero.
   Se um componente Flux não atende, publicamos o override — não criamos um paralelo.
5. **Dinheiro é inteiro.** Valores monetários em centavos (`amount_cents`), nunca `float`.
6. **Autenticação não se reescreve.** Fortify já resolve; nós só ajustamos aparência e idioma.

---

## 2. Árvore de diretórios

Legenda: sem marca = já existe no repositório · `+` = a criar conforme o projeto avança.

```
PilaControl/
├── app/
│   ├── Actions/
│   │   └── Fortify/                 # CreateNewUser, ResetUserPassword (do starter kit)
│   │   +   Budgets/                 # casos de uso — 1 classe, 1 método handle()
│   │   +   Categories/
│   │   +   Goals/
│   │   +   Transactions/
│   │
│   ├── Concerns/                    # traits compartilhadas (Password/ProfileValidationRules)
│   │
│   +── Enums/
│   │   +   CategoryType.php         # income | expense | both
│   │   +   TransactionType.php      # income | expense
│   │
│   ├── Http/Controllers/            # mínimo: só o que Livewire não cobre (export, webhook)
│   │
│   ├── Livewire/
│   │   ├── Actions/Logout.php
│   │   ├── Settings/                # Profile, Security, Appearance, TwoFactor
│   │   +   Budgets/
│   │   +   Categories/
│   │   +   Dashboard/
│   │   +   Forms/                   # Livewire Form Objects
│   │   +   Goals/
│   │   +   Reports/
│   │   +   Transactions/
│   │
│   ├── Models/User.php
│   │   +   Budget.php  Category.php  Goal.php  Transaction.php
│   │
│   ├── Providers/                   # AppServiceProvider, FortifyServiceProvider
│   │
│   +── Policies/                    # todo dado é do usuário logado — policy por model
│   +── Queries/                     # agregações de leitura (dashboard, relatórios)
│   +── Support/Money.php            # value object de valor monetário
│
├── bootstrap/  config/  public/  storage/
│
├── database/
│   ├── factories/
│   ├── migrations/                  # users, cache, jobs, passkeys, two_factor
│   ├── seeders/                     # + categorias padrão vêm daqui
│   └── database.sqlite              # ignorado pelo git
│
├── docs/
│   └── estrutura-do-projeto.md      # este arquivo
│
├── resources/
│   ├── css/app.css                  # Tailwind v4 + Flux + design tokens
│   ├── js/
│   │   ├── app.js
│   │   ├── passkeys.js
│   │   +   charts/                  # Chart.js isolado aqui — ver 5.3
│   └── views/
│       ├── components/              # app-logo, auth-header, settings/layout…
│       │   +   ui/                  # componentes próprios em cima do Flux
│       ├── flux/                    # overrides do Flux (icons, navlist/group)
│       ├── layouts/
│       │   ├── app.blade.php        # shell logado: sidebar + header
│       │   ├── app/                 # sidebar.blade.php, header.blade.php
│       │   ├── auth.blade.php       # shell deslogado
│       │   └── auth/                # card, simple, split
│       ├── livewire/
│       │   ├── auth/                # telas do Fortify — Blade puro, ver 3.4
│       │   ├── settings/            # componentes Livewire de verdade
│       │   +   dashboard/ transactions/ budgets/ goals/ reports/ categories/
│       ├── partials/
│       ├── dashboard.blade.php      # placeholder do starter kit
│       └── welcome.blade.php        # placeholder do starter kit
│
├── routes/
│   ├── console.php
│   ├── settings.php                 # rotas de perfil/segurança/aparência
│   └── web.php                      # + rotas do domínio financeiro
│
└── tests/
    ├── Feature/
    │   ├── Auth/                    # 6 arquivos, já passando
    │   ├── Settings/
    │   +   Actions/
    │   +   Livewire/                # espelha app/Livewire/
    └── Unit/
```

---

## 3. Mapa: protótipo → Laravel

### 3.1 Telas

| Tela do protótipo | Onde vive | Rota | Status |
|---|---|---|---|
| `AuthScreen` (login) | `views/livewire/auth/login.blade.php` | `/login` | pronto (Fortify) |
| `AuthScreen` (cadastro) | `views/livewire/auth/register.blade.php` | `/register` | pronto (Fortify) |
| `AuthScreen` (esqueci senha) | `views/livewire/auth/forgot-password.blade.php` | `/forgot-password` | pronto (Fortify) |
| `AuthScreen` (redefinir) | `views/livewire/auth/reset-password.blade.php` | `/reset-password/{token}` | pronto (Fortify) |
| — | `views/livewire/auth/two-factor-challenge.blade.php` | — | extra do kit |
| `Dashboard` | `Livewire\Dashboard\Index` | `/` | a fazer |
| `TransactionsView` | `Livewire\Transactions\Index` | `/transacoes` | a fazer |
| `BudgetView` | `Livewire\Budgets\Index` | `/orcamento` | a fazer |
| `GoalsView` | `Livewire\Goals\Index` | `/metas` | a fazer |
| `ReportsView` | `Livewire\Reports\Index` | `/relatorios` | a fazer |
| `TransactionModal` | `Livewire\Transactions\TransactionModal` | — (modal) | a fazer |
| `CategoriesModal` | `Livewire\Categories\CategoriesModal` | — (modal) | a fazer |
| `StatCard`, `TxRow`, `Pill` | `components/ui/` ou Flux | — | a fazer |

Registro de rota no Livewire 4 usa o helper novo:

```php
Route::livewire('transacoes', Transactions\Index::class)->name('transactions.index');
```

### 3.2 Entidades

| Tipo TS (`src/data.ts`) | Model | Tabela | Status |
|---|---|---|---|
| `User` (`src/auth.ts`) | `User` | `users` | pronto |
| `Category` | `Category` | `categories` | a fazer |
| `Transaction` | `Transaction` | `transactions` | a fazer |
| `Budget` | `Budget` | `budgets` | a fazer |
| `Goal` | `Goal` | `goals` | a fazer |

**Notas de modelagem** (a validar na fase de banco):

- Todas as tabelas de domínio carregam `user_id` — os dados são por usuário.
- `Category.type` e `Transaction.type` viram enums PHP backed em string.
- `Budget` é único por (`user_id`, `category_id`, `month`) — o protótipo já trata assim ao salvar.
- `Goal.current` provavelmente deriva de aportes; se virar histórico, entra `goal_contributions`.

### 3.3 Handlers do protótipo → Actions

| Função em `App.tsx` | Action |
|---|---|
| `addTransaction` | `Actions\Transactions\CreateTransaction` |
| `deleteTransaction` | `Actions\Transactions\DeleteTransaction` |
| `updateBudget` | `Actions\Budgets\SetCategoryBudget` |
| `addCategory` | `Actions\Categories\CreateCategory` |
| `deleteCategory` | `Actions\Categories\DeleteCategory` |
| `addGoal` | `Actions\Goals\CreateGoal` |
| `updateGoal` | `Actions\Goals\UpdateGoal` |
| `deleteGoal` | `Actions\Goals\DeleteGoal` |

Os cálculos do dashboard e dos relatórios (totais por mês, gasto por categoria, saldo,
evolução) **não são Actions** — são leitura. Vão para `app/Queries/`, ex.:
`Queries\MonthlySummary`, `Queries\SpendingByCategory`, `Queries\BalanceTimeline`.

### 3.4 Autenticação — Fortify

O protótipo guardava usuários em `localStorage`, com hash caseiro (`Math.imul`) e código de
6 dígitos em `sessionStorage` — sem expiração, sem uso único, sem rate limit. Nada disso
sobrevive. O starter kit trouxe **Laravel Fortify**, que já resolve tudo:

| Aspecto | Protótipo | PilaControl |
|---|---|---|
| Hash de senha | `Math.imul` (reversível na prática) | Bcrypt |
| Sessão | token em `localStorage` | sessão do Laravel, cookie `httpOnly` |
| Reset | código de 6 dígitos em `sessionStorage` | token + link assinado por e-mail |
| Expiração do reset | nenhuma | 60 min (`config/auth.php`) |
| Uso único | não | sim, token apagado no consumo |
| Rate limit | nenhum | throttle do Fortify |
| 2FA | não existia | incluso (TOTP + códigos de recuperação) |
| Passkeys | não existia | incluso (`@laravel/passkeys`) |

**Detalhe importante para não se confundir depois:** os arquivos em
`resources/views/livewire/auth/` **não são componentes Livewire**, apesar do caminho. São
Blade puro com `<form method="POST">` apontando para rotas do Fortify (`login.store` etc.).
Quem processa é o Fortify no servidor. Não existe classe em `app/Livewire/Auth/`.

Onde mexer, quando for o caso:

- Aparência das telas → as Blades em `resources/views/livewire/auth/`.
- Regras de validação → `app/Concerns/PasswordValidationRules.php`.
- Criação de usuário e reset → `app/Actions/Fortify/`.
- Configuração de features → `config/fortify.php` e `FortifyServiceProvider`.

Traduzir a interface para pt-BR é trabalho pendente: as strings estão em `__()`, então basta
criar `lang/pt_BR/`. Nada de reescrever a lógica.

### 3.5 Helpers de formatação

`fmt`, `fmtShort`, `monthLabel`, `fmtDate`, `currentMonth`, `daysUntil` do protótipo **não**
viram helpers globais:

- Formatação de dinheiro → `Support\Money` (`Money::fromCents()->format()`).
- Formatação de data/mês → `Carbon` + locale `pt_BR` direto na Blade.
- `daysUntil` → método no model `Goal` (`$goal->daysRemaining()`).

---

## 4. Convenções de nome

| Coisa | Convenção | Exemplo |
|---|---|---|
| Model | Singular, PascalCase | `Transaction` |
| Tabela | Plural, snake_case | `transactions` |
| Componente Livewire | PascalCase, namespace = domínio | `App\Livewire\Transactions\Index` |
| View Livewire | Espelho em kebab-case | `resources/views/livewire/transactions/index.blade.php` |
| Action | Verbo + objeto, método `handle()` | `CreateTransaction::handle()` |
| Query | Substantivo do resultado | `SpendingByCategory` |
| Form Object | Model + `Form` | `App\Livewire\Forms\TransactionForm` |
| Trait compartilhada | `app/Concerns/`, sufixo descritivo | `Concerns\WithMonthFilter` |
| Enum | Singular, casos em minúsculo | `TransactionType::Income` → `'income'` |
| Rota (nome) | `dominio.acao` | `transactions.index` |
| Rota (URL) | Português, kebab-case | `/transacoes`, `/orcamento` |
| Teste | Classe + `Test` | `tests/Feature/Livewire/Transactions/IndexTest.php` |

As rotas de autenticação ficam em inglês (`/login`, `/register`) porque vêm do Fortify.
Renomear daria trabalho sem ganho real — só as rotas do domínio financeiro são em português.

Regra de espelhamento, na prática:

```
app/Livewire/Transactions/TransactionModal.php
resources/views/livewire/transactions/transaction-modal.blade.php
tests/Feature/Livewire/Transactions/TransactionModalTest.php
```

---

## 5. Frontend: Flux

### 5.1 Onde cada coisa mora

- **Layouts** — `resources/views/layouts/`. O `app.blade.php` já traz sidebar e header do
  starter kit; é ali que a navegação do protótipo (Dashboard, Transações, Orçamento, Metas,
  Relatórios) vai entrar.
- **Overrides do Flux** — `resources/views/flux/`, via `php artisan flux:publish`. O kit já
  publicou alguns ícones e o `navlist/group`. Só publicamos o que precisa mudar.
- **Componentes próprios** — `resources/views/components/ui/`. Reservado para o que o Flux
  não cobre e se repete (ex.: `<x-ui.stat-card>`, `<x-ui.category-pill>`).
- **Design tokens** — `resources/css/app.css`, no bloco `@theme`. É lá que a paleta do
  protótipo (`#0d1117`, `#00e676`, `#f85149`…) e o `--radius: 4px` serão declarados.
  Nenhum hex solto em Blade.

O `app.css` já vem com os `@import`/`@source` corretos do Flux e do Tailwind v4 — não mexer
nessas linhas.

Coincidência boa: o starter kit já usa **Instrument Sans**, a mesma fonte do protótipo.
Falta só a JetBrains Mono para os valores monetários.

### 5.2 Equivalências

| Protótipo (React) | Flux |
|---|---|
| `<button>` estilizado | `<flux:button>` |
| `<input>` / `<select>` do `Field` | `<flux:input>`, `<flux:select>`, `<flux:field>` |
| `TransactionModal`, `CategoriesModal` | `<flux:modal>` |
| Sidebar + `NAV` | `<flux:navlist>` no layout já existente |
| Tabela de transações | `<flux:table>` |
| `Pill` | `<flux:badge>` |
| Ícones `▦ ⇄ ◫ ◎ ◈` | `<flux:icon.*>` (Heroicons) |
| `recharts` | Chart.js via `<x-ui.chart>` — ver 5.3 |

### 5.3 Gráficos — Chart.js

O `<flux:chart>` é componente **Flux Pro** (pago) e está fora — temos a edição free. Os
gráficos de Relatórios usam **Chart.js**, encapsulado num wrapper. Cobre os três tipos do
protótipo (pie, bar, line), é leve e não arrasta framework junto.

**Regra de isolamento:** a lib só aparece em dois lugares. Nenhum componente Livewire e
nenhuma Blade de tela importa ou referencia Chart.js diretamente.

```
resources/js/charts/
├── index.js                  # registro dos tipos usados (tree-shaking)
├── theme.js                  # lê os design tokens do CSS → paleta dos gráficos
└── chart.js                  # componente Alpine (Alpine.data('chart', ...))

resources/views/components/ui/
└── chart.blade.php           # <x-ui.chart type="pie" :data="..." />
```

- `theme.js` **não** tem cores hardcoded: lê as CSS custom properties do `@theme` do
  `app.css`. Assim gráfico e interface nunca saem de sincronia.
- A cor de cada categoria vem do próprio registro (`Category.color`), não da paleta do tema.
- O container do canvas precisa de `wire:ignore` — o Livewire não pode re-renderizar por cima
  do DOM que o Chart.js controla. Atualização de dados via evento Livewire que o Alpine
  escuta e repassa para `chart.update()`.

Se os relatórios pedirem interação mais pesada (zoom, brush, séries sincronizadas), o plano B
é ApexCharts — a troca fica contida nos quatro arquivos acima.

---

## 6. Testes

Pest 5. A suíte espelha a aplicação:

- `tests/Feature/Livewire/**` — um arquivo por componente, usando `Livewire::test()`.
- `tests/Feature/Actions/**` — um arquivo por Action, cobrindo o caso de uso isolado.
- `tests/Unit/**` — só código sem banco: `Support\Money`, enums, cálculos puros.

`tests/Feature/Auth/` e `tests/Feature/Settings/` vieram do starter kit e já passam (33 testes,
81 asserções). Servem de modelo de estilo para os nossos.

Toda factory em `database/factories/`, uma por model.

---

## 7. Decisões e pendências

### Fechadas

| Decisão | Escolha |
|---|---|
| Organização do domínio | Laravel padrão + `app/Actions` |
| Starter kit | Livewire com class components |
| Autenticação | Fortify (veio pronto, com 2FA e passkeys) |
| UI | Flux free — sem componentes Pro |
| Gráficos | Chart.js isolado em `resources/js/charts/` |
| Banco | SQLite |
| Testes | Pest |

### Em aberto

- Se `Goal.current` é campo ou soma de aportes (`goal_contributions`).
- Driver de e-mail para o reset de senha funcionar fora do ambiente local.
- Manter ou remover 2FA e passkeys — vieram de brinde e ainda não foram decididos.
- Traduzir a interface do starter kit para pt-BR (`lang/pt_BR/`).
- Multi-moeda / contas bancárias: fora do escopo do protótipo, não modelado.

### Limpeza pendente

- `resources/views/welcome.blade.php` e `dashboard.blade.php` são placeholders do kit.
- `Financial control app.zip` na raiz — referência temporária, a remover.

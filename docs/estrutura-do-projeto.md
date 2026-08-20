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
│   ├── Enums/
│   │   ├── CategoryType.php         # income | expense | both
│   │   └── TransactionType.php      # income | expense
│   │
│   ├── Http/Controllers/            # mínimo: só o que Livewire não cobre
│   │   └── Auth/GoogleController.php   # OAuth2 do Google — ver 3.4
│   │
│   ├── Livewire/
│   │   ├── Actions/Logout.php
│   │   ├── Settings/                # Profile, Security, Appearance, TwoFactor
│   │   ├── Budgets/Index.php
│   │   ├── Dashboard/Index.php
│   │   ├── Transactions/Index.php
│   │   +   Categories/
│   │   +   Forms/                   # Livewire Form Objects
│   │   +   Goals/
│   │   +   Reports/
│   │
│   ├── Models/User.php
│   │   +   Budget.php  Category.php  Goal.php  Transaction.php
│   │
│   ├── Providers/                   # AppServiceProvider, FortifyServiceProvider
│   │
│   +── Policies/                    # todo dado é do usuário logado — policy por model
│   │
│   ├── Queries/                     # agregações de leitura (dashboard, relatórios)
│   │   ├── BalanceTimeline.php      # série mensal receita vs despesa
│   │   ├── BudgetOverview.php       # gasto contra limite, por categoria
│   │   ├── MonthlySummary.php       # receitas, despesas, saldo de um período
│   │   ├── SpendingByCategory.php   # ranking de gasto no mês
│   │   └── Results/                 # objetos readonly de retorno — ver 7
│   │
│   └── Support/
│       ├── CategoryPresets.php      # ícones e cores sugeridos no cadastro
│       ├── Money.php                # value object de valor monetário
│       ├── MonthLabel.php           # rótulos de mês e data — ver 3.5
│       ├── DemoData.php             # PROVISÓRIO: dados do protótipo — ver 3.2
│       └── Demo/                    # PROVISÓRIO: stand-in dos models
│
├── bootstrap/  config/  public/  storage/
│
├── database/
│   ├── factories/
│   ├── migrations/                  # users, cache, jobs, two_factor, google
│   ├── seeders/                     # + categorias padrão vêm daqui
│   └── database.sqlite              # ignorado pelo git
│
├── docs/
│   └── estrutura-do-projeto.md      # este arquivo
│
├── resources/
│   ├── css/app.css                  # Tailwind v4 + Flux + design tokens
│   ├── js/
│   │   ├── app.js                   # registra o componente Alpine do gráfico
│   │   └── charts/                  # Chart.js isolado aqui — ver 5.3
│   └── views/
│       ├── components/              # app-logo, auth-header, settings/layout…
│       │   └── ui/                  # panel, stat-card, category-pill,
│       │                            # meter, tx-table, chart — ver 5.1
│       ├── flux/                    # overrides do Flux (icons, navlist/group)
│       ├── layouts/
│       │   ├── app.blade.php        # shell logado: sidebar + header
│       │   ├── app/                 # sidebar.blade.php, header.blade.php
│       │   ├── auth.blade.php       # shell deslogado
│       │   └── auth/                # card, simple, split
│       ├── livewire/
│       │   ├── auth/                # telas do Fortify — Blade puro, ver 3.4
│       │   ├── settings/            # componentes Livewire de verdade
│       │   ├── budgets.blade.php    # atenção ao nome — ver 3.1
│       │   ├── dashboard.blade.php
│       │   ├── transactions.blade.php
│       │   +   goals/ reports/ categories/
│       ├── partials/
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
    │   ├── FinanceScreensTest.php   # smoke das três rotas, página completa
    │   ├── Livewire/                # espelha app/Livewire/
    │   +   Actions/
    └── Unit/                        # MoneyTest, MonthLabelTest
```

---

## 3. Mapa: protótipo → Laravel

### 3.1 Telas

| Tela do protótipo | Onde vive | Rota | Status |
|---|---|---|---|
| `AuthScreen` (login) | `views/livewire/auth/login.blade.php` | `/login` | frontend pronto |
| `AuthScreen` (cadastro) | `views/livewire/auth/register.blade.php` | `/register` | frontend pronto |
| `AuthScreen` (esqueci senha) | `views/livewire/auth/forgot-password.blade.php` | `/forgot-password` | frontend pronto |
| `AuthScreen` (redefinir) | `views/livewire/auth/reset-password.blade.php` | `/reset-password/{token}` | pronto (Fortify) |
| — | `views/livewire/auth/create-password.blade.php` | `/definir-senha` | primeira senha de quem veio do Google |
| — | `views/livewire/auth/two-factor-challenge.blade.php` | — | extra do kit |
| `Dashboard` | `Livewire\Dashboard\Index` | `/dashboard` | frontend pronto |
| `TransactionsView` | `Livewire\Transactions\Index` | `/transacoes` | frontend pronto |
| `BudgetView` | `Livewire\Budgets\Index` | `/orcamento` | frontend pronto |
| `GoalsView` | `Livewire\Goals\Index` | `/metas` | frontend pronto |
| `ReportsView` | `Livewire\Reports\Index` | `/relatorios` | frontend pronto |
| `TransactionModal` | dentro de `Livewire\Transactions\Index` | — (modal) | frontend pronto |
| `CategoriesModal` | `Livewire\Categories\CategoriesModal` | — (modal) | frontend pronto |
| `StatCard`, `TxRow`, `Pill` | `components/ui/` | — | pronto |

"frontend pronto" significa: layout, interações e formatação fiéis ao protótipo, mas lendo de
`Support\DemoData` — os models ainda não existem. Ver 3.2 e 7.

`/` continua sendo a `welcome` do starter kit; o dashboard mora em `/dashboard` porque é para
lá que o Fortify redireciona depois do login, e é o que os testes de autenticação esperam.

O modal de categorias é montado no `layouts/app/sidebar.blade.php`, não numa tela: o gatilho
é o botão no rodapé da sidebar, e por isso ele existe em toda página logada. Nenhum item da
navegação ficou desabilitado.

Registro de rota no Livewire 4 usa o helper novo:

```php
Route::livewire('transacoes', Transactions\Index::class)->name('transactions.index');
```

**Atenção à resolução da view.** O Livewire 4 remove o `.index` do nome do componente:
`App\Livewire\Transactions\Index` resolve para `resources/views/livewire/transactions.blade.php`,
**não** para `livewire/transactions/index.blade.php`. A regra de espelhamento do item 1.3 vale
para todos os componentes, com essa exceção dos `Index` — que é convenção do framework, não
nossa. Componentes irmãos seguem o espelhamento normal:

```
app/Livewire/Transactions/Index.php            → views/livewire/transactions.blade.php
app/Livewire/Transactions/TransactionModal.php → views/livewire/transactions/transaction-modal.blade.php
```

### 3.2 Entidades

| Tipo TS (`src/data.ts`) | Model | Tabela | Status |
|---|---|---|---|
| `User` (`src/auth.ts`) | `User` | `users` | pronto |
| `Category` | `Category` | `categories` | a fazer (hoje `Support\Demo\Category`) |
| `Transaction` | `Transaction` | `transactions` | a fazer |
| `Budget` | `Budget` | `budgets` | a fazer |
| `Goal` | `Goal` | `goals` | a fazer (hoje `Support\Demo\Goal`) |

**Notas de modelagem** (a validar na fase de banco):

- Todas as tabelas de domínio carregam `user_id` — os dados são por usuário.
- `Category.type` e `Transaction.type` viram enums PHP backed em string.
- `Budget` é único por (`user_id`, `category_id`, `month`) — o protótipo já trata assim ao salvar.
- `Goal.current` provavelmente deriva de aportes; se virar histórico, entra `goal_contributions`.

**Ponte provisória.** As três telas prontas leem de `Support\DemoData`, que devolve objetos de
`Support\Demo\{Category,Transaction}` — classes `readonly` com exatamente as colunas que as
tabelas vão ter (`amount_cents`, `date`, `type`, `category`). É um único ponto de troca:

| Ao criar os models | O que muda |
|---|---|
| `app/Support/DemoData.php` | apagar |
| `app/Support/Demo/` | apagar |
| `app/Queries/*` | trocar o type hint `Support\Demo\X` por `Models\X` |
| `app/Livewire/*/Index.php` | trocar `DemoData::…` por consulta Eloquent |
| views, `components/ui/`, `app/Queries/Results/` | nada |

O estado de escrita também é provisório: `Budgets\Index::$limits`,
`Transactions\Index::$added`/`$removed`, `Goals\Index::$added`/`$removed`/`$deposits` e
`Categories\CategoriesModal::$added`/`$removed` guardam as alterações no próprio componente, e
desaparecem ao sair da página. Uma consequência visível: categoria criada no modal ainda não
aparece no seletor da tela de Transações, porque cada componente lê o registro por conta
própria. Some quando a tabela `categories` existir. Somem junto com
o `DemoData`, dando lugar às Actions de 3.3.

**Armadilha de nome no Livewire.** O framework trata `hydrate{Propriedade}` como hook de ciclo
de vida e tenta chamá-lo de fora — um método `private hydrateAdded()` ao lado de uma
propriedade `public $added` estoura com "does not exist" na primeira ação do componente. Os
auxiliares que montam o estado adicionado chamam-se `addedGoals()` e `addedTransactions()`
justamente por isso.

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

**Detalhe importante para não se confundir depois:** os arquivos em
`resources/views/livewire/auth/` **não são componentes Livewire**, apesar do caminho. São
Blade puro com `<form method="POST">` apontando para rotas do Fortify (`login.store` etc.).
Quem processa é o Fortify no servidor. Não existe classe em `app/Livewire/Auth/`.

A casca das telas deslogadas é o `layouts/auth.blade.php`: fundo escuro com a malha de
pontos e o brilho verde do `AuthScreen`, marca `₢` e subtítulo por tela (`:subtitle`). O
cartão é o `<x-auth-card>`. Os três layouts do starter kit (`auth/card`, `auth/simple`,
`auth/split`) ficaram sem uso — entram na limpeza pendente.

A única diferença de estilo entre o `Field` do app e o `InputField` do `AuthScreen` é o label
em caixa alta. Vale só dentro da casca de auth, por uma regra no fim do `app.css` presa ao
atributo `data-auth`.

### 3.4.1 Login com Google

O botão da tela de login abre o fluxo *authorization code* do Google, falado direto por HTTP
em `Http\Controllers\Auth\GoogleController` — sem `laravel/socialite`, que ainda exige Guzzle 7
e obrigaria a rebaixar o Guzzle 8 do projeto inteiro por causa de um botão.

| Peça | Onde |
|---|---|
| Credenciais | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` no `.env`; leitura em `config/services.php` |
| Rotas | `google.redirect` e `google.callback`, em `routes/web.php`, sob `guest` |
| Botão | `components/google-button.blade.php` |
| Colunas | `users.google_id` (única), `users.avatar_url`; `users.password` virou anulável |

Três detalhes que não são negociáveis:

- O `state` aleatório vai para a sessão na ida e é conferido com `hash_equals` na volta. É a
  defesa de CSRF do OAuth2 — sem ela o callback aceita retorno forjado.
- Só entra conta com `email_verified` do Google. É isso que torna seguro ligar a conta Google
  a um usuário já cadastrado pelo mesmo e-mail.
- Sem credenciais no `.env`, as duas rotas devolvem 404 e o botão não vai para produção.
  Em `local` ele aparece desabilitado dizendo o que falta: sumir calado faz quem está
  desenvolvendo achar que a tela é que não foi feita.

Se o Socialite passar a aceitar Guzzle 8, a classe some e vira `Socialite::driver('google')`.
As colunas em `users` continuam as mesmas.

**Primeira senha.** O Google não passa senha, então a conta nasce com `password` nulo — e sem
senha o usuário fica preso: não entra por e-mail, e Configurações › Segurança pede a senha
atual para trocar. O `Middleware\EnsurePasswordIsSet` (alias `password.set`, aplicado aos
grupos autenticados de `web.php` e `settings.php`) segura essa conta em `/definir-senha` até
ela escolher uma. Depois disso os dois caminhos de entrada funcionam.

`Auth\CreatePasswordController` não é o reset do Fortify: ali existe senha e token de e-mail
no meio; aqui a sessão já está aberta e o campo está nulo — só há o que preencher.

**Passkeys foram removidos** (a pedido): saíram o `Features::passkeys()` do Fortify, a seção da
tela de Segurança, as rotas, o `@laravel/passkeys`, o `passkeys.js` e a tabela.

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

- `fmt` e `fmtShort` → `Support\Money` (`format()`, `format(sign: true)`, `short()`).
- `monthLabel`, `fmtDate`, `currentMonth` → `Support\MonthLabel` (`short()`, `long()`, `date()`,
  `weekdayDate()`, `key()`, `currentKey()`).
- `daysUntil` → método no model `Goal` (`$goal->daysRemaining()`), quando o model existir.

`Support\CategoryPresets` segue a mesma lógica: as dezesseis cores e os trinta e seis ícones
do cadastro de categoria são **conteúdo do design**, não dado de vitrine. Por isso não moram
no `DemoData` — eles sobrevivem à criação da tabela `categories`, que o `DemoData` não. O
campo continua aceitando qualquer emoji e qualquer cor; as listas são só o caminho rápido.

`MonthLabel` existe em vez de `Carbon` direto na Blade por dois motivos: as listas de filtro
precisam dos rótulos em PHP, e as abreviações do protótipo ("Ago/26") não batem com as do
locale `pt_BR` do Carbon ("ago./26"). As doze abreviações estão fixas na classe: são conteúdo
do design, não interface traduzível.

Uma diferença deliberada em relação ao protótipo, em `Money::format()`: o protótipo aplica
`Math.abs` a todo valor e deixa só a cor comunicar o sinal, o que torna um déficit
indistinguível de um superávit em texto. Aqui, valor negativo recebe `−`.

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

- **Layouts** — `resources/views/layouts/`. `app/sidebar.blade.php` traz a casca do protótipo:
  sidebar de 224px com a navegação de cinco itens, e header de 56px com título da tela, data e
  usuário. A lista de navegação é um array no topo do arquivo; item com `route` nulo renderiza
  desabilitado.
- **Overrides do Flux** — `resources/views/flux/`, via `php artisan flux:publish`. O kit já
  publicou alguns ícones e o `navlist/group`. Só publicamos o que precisa mudar.
- **Componentes próprios** — `resources/views/components/ui/`. O que o Flux não cobre e se
  repete: `panel` (card com cabeçalho em caixa alta), `stat-card`, `category-pill`, `meter`
  (barra com cor por instância), `tx-table` (lista de lançamentos sobre `flux:table`),
  `chart` e `alert` (o `Alert` do protótipo — o `flux:callout` só aceita cores nomeadas
  do Flux, mesma razão do `meter`).
- **Design tokens** — `resources/css/app.css`, no bloco `@theme`, com os mesmos nomes
  semânticos do protótipo (`background`, `card`, `secondary`, `muted-foreground`, `border`,
  `income`, `expense`). Nenhum hex solto em Blade — a única cor literal nas views é a de
  `Category.color`, que vem do registro.

O `app.css` já vem com os `@import`/`@source` corretos do Flux e do Tailwind v4 — não mexer
nessas linhas. Três decisões de tema que valem registrar:

- **`--color-accent` é o verde.** É o token que o Flux lê para `variant="primary"`. Precisa
  estar declarado sob `.dark`, num `@layer theme`, porque o próprio `flux.css` define o valor
  dele ali — declarar só em `:root` perde a disputa de especificidade.
- **O `--accent` azul do protótipo virou `--color-info`**, para não colidir com o accent do
  Flux. É a cor de estado selecionado, de link e de foco.
- **A escala `--radius-*` foi sobrescrita para 2–4px**, o que aplica o canto de 4px do
  protótipo também aos componentes do Flux, que por padrão usam `rounded-lg`/`rounded-xl`.

Onde os stubs do Flux trazem tons de `zinc` fixos que não dá para passar por atributo (bordas
de tabela, fundo de input, tamanho do label), há um punhado de regras no fim do `app.css`.
São regras de tema, não de comportamento — é o lugar delas.

Coincidência boa: o starter kit já usa **Instrument Sans**, a mesma fonte do protótipo. A
**JetBrains Mono** dos valores monetários entrou pelo mesmo caminho (`bunny()` no
`vite.config.js`) e sai no utilitário `font-mono`.

### 5.2 Equivalências

| Protótipo (React) | Flux |
|---|---|
| `<button>` estilizado | `<flux:button>` |
| `<input>` / `<select>` do `Field` | `<flux:input>`, `<flux:select>`, `<flux:field>` |
| `TransactionModal`, `CategoriesModal` | `<flux:modal>` |
| Sidebar + `NAV` | `<flux:sidebar>` / `<flux:sidebar.item>` no layout |
| Tabela de transações | `<flux:table>`, embrulhada em `<x-ui.tx-table>` |
| `Pill` | `<x-ui.category-pill>` |
| Barras de progresso | `<x-ui.meter>` |
| Ícones `▦ ⇄ ◫ ◎ ◈` | `<flux:icon.*>` (Heroicons) |
| `recharts` | Chart.js via `<x-ui.chart>` — ver 5.3 |

Duas linhas saíram do plano original, e vale saber por quê:

- **`Pill` não é `flux:badge`.** O badge do Flux só aceita cores nomeadas do próprio Flux, e a
  cor da pílula vem de `Category.color` — hex arbitrário do registro. Mesma razão para o
  `meter` não ser `flux:progress`.
- **`flux:table` continua sendo a tabela**, mas sempre via `<x-ui.tx-table>`: as larguras de
  coluna, o hover que revela o botão de remover e as cores dos valores se repetem em duas
  telas, e não vale duplicar.

### 5.3 Gráficos — Chart.js

O `<flux:chart>` é componente **Flux Pro** (pago) e está fora — temos a edição free. Os
gráficos usam **Chart.js** (`chart.js` no `package.json`), encapsulado num wrapper. Cobre os
três tipos do protótipo (doughnut, bar, line), é leve e não arrasta framework junto.

Já em uso no dashboard, no gráfico de receitas vs despesas por mês.

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
- A Blade passa **nome de token** (`'income'`, `'expense'`, `'info'`), não cor — o `chart.js`
  resolve pelo tema. Uma cor literal também passa: é o caso da cor da categoria, que vem do
  registro (`Category.color`) e não da paleta.
- **Gráfico que muda de dados sem recarregar a página precisa de `name`.** Como o canvas está
  sob `wire:ignore`, o Livewire não o redesenha: o componente chama
  `$this->dispatch('chart:data', name: ..., labels: ..., series: ...)` e cada canvas aceita só
  o evento que traz o seu próprio nome. É o que os Relatórios fazem ao trocar o período; o
  Dashboard não passa `name` porque os dados dele não mudam depois de montados.
- Valores vão para o JS **em reais** (`Money::toReais()`), não em centavos. É a única coisa que
  sai do domínio em ponto flutuante, e serve só para desenhar.
- O container do canvas precisa de `wire:ignore` — o Livewire não pode re-renderizar por cima
  do DOM que o Chart.js controla. Atualização de dados por evento `chart:data` no elemento,
  que o Alpine escuta e repassa para `chart.update()`.
- O `Alpine.data('chart', …)` é registrado dentro de `alpine:init`, chamado por
  `resources/js/app.js`. O Alpine vem embutido no script do Livewire, que carrega no fim do
  `<body>`; registrar fora desse evento não garante ordem.

Se os relatórios pedirem interação mais pesada (zoom, brush, séries sincronizadas), o plano B
é ApexCharts — a troca fica contida nos quatro arquivos acima.

---

## 6. Testes

Pest 5. A suíte espelha a aplicação:

- `tests/Feature/Livewire/**` — um arquivo por componente, usando `Livewire::test()`.
- `tests/Feature/Actions/**` — um arquivo por Action, cobrindo o caso de uso isolado.
- `tests/Unit/**` — só código sem banco: `Support\Money`, enums, cálculos puros.

`tests/Feature/Auth/` e `tests/Feature/Settings/` vieram do starter kit e servem de modelo de
estilo para os nossos. A suíte hoje: **69 testes, 198 asserções**.

O `Livewire::test()` renderiza o componente, não o layout — por isso existe também o
`FinanceScreensTest`, que faz `GET` nas três rotas e verifica a página inteira. É o que pega
erro de Blade no layout, na sidebar e nos componentes de `ui/`.

Além do Pest, o `composer ci:check` roda Pint e Larastan no **nível 7**. Duas consequências
práticas para quem escrever código novo aqui:

- Propriedade computada de Livewire (`#[Computed]`) não é vista pelo PHPStan. Declare no
  docblock da classe: `@property-read Collection<int, Transaction> $transactions`.
- Nada de `(object) [...]`: o nível 7 rejeita acesso a propriedade de `object`. Retorno de
  Query é classe `readonly` em `app/Queries/Results/`.

Toda factory em `database/factories/`, uma por model.

---

## 7. Decisões e pendências

### Fechadas

| Decisão | Escolha |
|---|---|
| Organização do domínio | Laravel padrão + `app/Actions` |
| Starter kit | Livewire com class components |
| Autenticação | Fortify + login com Google; passkeys removidos |
| UI | Flux free — sem componentes Pro |
| Gráficos | Chart.js isolado em `resources/js/charts/` |
| Banco | SQLite |
| Testes | Pest |
| Rota do dashboard | `/dashboard`, não `/` — é onde o Fortify aterra |
| Resultado de Query | objeto `readonly` em `app/Queries/Results/`, nunca array solto |
| Dinheiro na interface | sempre `Support\Money`; float só ao serializar gráfico |

### Em aberto

- **Models de `Category`, `Transaction` e `Budget`.** É o próximo passo natural: as três telas
  estão prontas e esperando. Ver a tabela de troca em 3.2.
- Telas de Metas e Relatórios — hoje desabilitadas na navegação.
- Se `Goal.current` é campo ou soma de aportes (`goal_contributions`).
- Driver de e-mail para o reset de senha funcionar fora do ambiente local.
- Manter ou remover o 2FA — veio de brinde e ainda não foi decidido. Os passkeys já saíram.
- Traduzir a interface do starter kit para pt-BR (`lang/pt_BR/`). As telas do domínio já
  nasceram em português, com as strings direto na Blade; quando o `lang/pt_BR/` existir, elas
  passam por `__()` junto com o resto.
- Multi-moeda / contas bancárias: fora do escopo do protótipo, não modelado.

### Limpeza pendente

- `resources/views/welcome.blade.php` é placeholder do kit. O `dashboard.blade.php` já saiu,
  substituído pelo componente Livewire.
- `layouts/auth/card.blade.php`, `layouts/auth/simple.blade.php` e `layouts/auth/split.blade.php`
  ficaram sem uso quando o `layouts/auth.blade.php` virou a casca própria.
- `components/placeholder-pattern.blade.php` e `components/desktop-user-menu.blade.php`
  ficaram sem uso quando o layout foi reescrito.
- `app/Support/DemoData.php` e `app/Support/Demo/` — provisórios por construção, ver 3.2.
- `Financial control app.zip` na raiz — referência temporária, a remover.

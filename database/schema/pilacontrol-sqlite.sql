-- =====================================================================
-- PilaControl — schema completo (SQLite)
-- Do zero: infraestrutura do starter kit + tabelas de domínio.
--
-- Dialeto: SQLite. Em SQLite NÃO existe "CREATE DATABASE" — o banco é o
-- arquivo. Crie-o antes de rodar este script:
--
--   PowerShell:  New-Item -ItemType File database\database.sqlite
--   PhpStorm:    Database > + > Data Source > SQLite
--                File: <projeto>\database\database.sqlite  (Create)
--
-- Dinheiro é INTEGER em centavos (nunca REAL). O teto de validação da
-- interface é R$ 99.999.999 => 9.999.999.900 centavos, acima do int de
-- 32 bits: em SQLite o INTEGER é de 64 bits e cobre; ao portar para
-- MySQL/Postgres use BIGINT nessas colunas.
-- =====================================================================

PRAGMA foreign_keys = ON;   -- SQLite ignora FK por padrão; a sessão precisa ligar

BEGIN TRANSACTION;

-- ---------------------------------------------------------------------
-- 1. Infraestrutura (equivalente às migrations do starter kit)
-- ---------------------------------------------------------------------

CREATE TABLE "migrations" (
    "id"        integer primary key autoincrement not null,
    "migration" varchar not null,
    "batch"     integer not null
);

CREATE TABLE "users" (
    "id"                        integer primary key autoincrement not null,
    "name"                      varchar not null,
    "email"                     varchar not null,
    "google_id"                 varchar,          -- login com Google
    "avatar_url"                varchar,
    "email_verified_at"         datetime,
    "password"                  varchar,          -- nulo: quem entrou pelo Google ainda não definiu senha
    "remember_token"            varchar,
    "created_at"                datetime,
    "updated_at"                datetime,
    "two_factor_secret"         text,
    "two_factor_recovery_codes" text,
    "two_factor_confirmed_at"   datetime
);
CREATE UNIQUE INDEX "users_email_unique"     on "users" ("email");
CREATE UNIQUE INDEX "users_google_id_unique" on "users" ("google_id");

CREATE TABLE "password_reset_tokens" (
    "email"      varchar not null,
    "token"      varchar not null,
    "created_at" datetime,
    primary key ("email")
);

CREATE TABLE "sessions" (
    "id"            varchar not null,
    "user_id"       integer,
    "ip_address"    varchar,
    "user_agent"    text,
    "payload"       text not null,
    "last_activity" integer not null,
    primary key ("id")
);
CREATE INDEX "sessions_user_id_index"       on "sessions" ("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions" ("last_activity");

CREATE TABLE "cache" (
    "key"        varchar not null,
    "value"      text not null,
    "expiration" integer not null,
    primary key ("key")
);
CREATE INDEX "cache_expiration_index" on "cache" ("expiration");

CREATE TABLE "cache_locks" (
    "key"        varchar not null,
    "owner"      varchar not null,
    "expiration" integer not null,
    primary key ("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks" ("expiration");

CREATE TABLE "jobs" (
    "id"           integer primary key autoincrement not null,
    "queue"        varchar not null,
    "payload"      text not null,
    "attempts"     integer not null,
    "reserved_at"  integer,
    "available_at" integer not null,
    "created_at"   integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs" ("queue");

CREATE TABLE "job_batches" (
    "id"             varchar not null,
    "name"           varchar not null,
    "total_jobs"     integer not null,
    "pending_jobs"   integer not null,
    "failed_jobs"    integer not null,
    "failed_job_ids" text not null,
    "options"        text,
    "cancelled_at"   integer,
    "created_at"     integer not null,
    "finished_at"    integer,
    primary key ("id")
);

CREATE TABLE "failed_jobs" (
    "id"         integer primary key autoincrement not null,
    "uuid"       varchar not null,
    "connection" varchar not null,
    "queue"      varchar not null,
    "payload"    text not null,
    "exception"  text not null,
    "failed_at"  datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs" ("uuid");
CREATE INDEX "failed_jobs_connection_queue_failed_at_index"
    on "failed_jobs" ("connection", "queue", "failed_at");

-- ---------------------------------------------------------------------
-- 2. Domínio
-- Toda tabela carrega user_id: os dados são por usuário (doc, 3.2).
-- ---------------------------------------------------------------------

-- 2.1 categories — as gavetas do dinheiro
CREATE TABLE "categories" (
    "id"         integer primary key autoincrement not null,
    "user_id"    integer not null,
    "name"       varchar(40) not null,                    -- limite do formulário
    "icon"       varchar(8) not null,                     -- emoji
    "color"      varchar(7) not null,                     -- hex "#rrggbb", minúsculo
    "type"       varchar not null
        check ("type" in ('income', 'expense', 'both')),  -- App\Enums\CategoryType
    "created_at" datetime,
    "updated_at" datetime,
    foreign key ("user_id") references "users" ("id") on delete cascade
);
CREATE INDEX "categories_user_id_type_index" on "categories" ("user_id", "type");
-- Duas categorias "Outros" convivem porque diferem no tipo (uma receita, uma despesa).
CREATE UNIQUE INDEX "categories_user_id_type_name_unique"
    on "categories" ("user_id", "type", "name");

-- 2.2 transactions — os lançamentos
CREATE TABLE "transactions" (
    "id"           integer primary key autoincrement not null,
    "user_id"      integer not null,
    "category_id"  integer not null,
    "date"         date not null,
    "description"  varchar(255) not null,
    "amount_cents" integer not null check ("amount_cents" > 0),  -- o sinal vem do type
    "type"         varchar not null
        check ("type" in ('income', 'expense')),                 -- App\Enums\TransactionType
    "notes"        varchar(255),
    "created_at"   datetime,
    "updated_at"   datetime,
    foreign key ("user_id")     references "users" ("id")      on delete cascade,
    -- restrict: categoria com lançamento não se apaga — o histórico não pode perder a gaveta.
    -- Se preferir permitir, troque por "on delete set null" e deixe category_id nulo.
    foreign key ("category_id") references "categories" ("id") on delete restrict
);
CREATE INDEX "transactions_user_id_date_index"        on "transactions" ("user_id", "date");
CREATE INDEX "transactions_user_id_type_index"        on "transactions" ("user_id", "type");
CREATE INDEX "transactions_user_id_category_id_index" on "transactions" ("user_id", "category_id");

-- 2.3 budgets — o limite combinado por categoria e mês
CREATE TABLE "budgets" (
    "id"          integer primary key autoincrement not null,
    "user_id"     integer not null,
    "category_id" integer not null,
    "month"       varchar(7) not null,                 -- chave "Y-m" (Support\MonthLabel::key)
    "limit_cents" integer not null check ("limit_cents" >= 0),
    "created_at"  datetime,
    "updated_at"  datetime,
    foreign key ("user_id")     references "users" ("id")      on delete cascade,
    foreign key ("category_id") references "categories" ("id") on delete cascade
);
-- Um limite por categoria por mês — é o upsert que SetCategoryBudget faz.
CREATE UNIQUE INDEX "budgets_user_id_category_id_month_unique"
    on "budgets" ("user_id", "category_id", "month");
CREATE INDEX "budgets_user_id_month_index" on "budgets" ("user_id", "month");

-- 2.4 goals — as metas
CREATE TABLE "goals" (
    "id"            integer primary key autoincrement not null,
    "user_id"       integer not null,
    "name"          varchar(255) not null,
    "icon"          varchar(8) not null,
    "target_cents"  integer not null check ("target_cents" > 0),
    "current_cents" integer not null default 0 check ("current_cents" >= 0),
    "deadline"      date not null,
    "created_at"    datetime,
    "updated_at"    datetime,
    foreign key ("user_id") references "users" ("id") on delete cascade
);
CREATE INDEX "goals_user_id_deadline_index" on "goals" ("user_id", "deadline");

-- 2.5 goal_contributions — OPCIONAL, pendência aberta (doc, seção 7).
-- A tela de Metas só mostra total e barra de progresso; nenhum consumidor pede
-- histórico hoje. Se um dia pedir, descomente e current_cents vira soma daqui.
--
-- CREATE TABLE "goal_contributions" (
--     "id"           integer primary key autoincrement not null,
--     "user_id"      integer not null,
--     "goal_id"      integer not null,
--     "amount_cents" integer not null check ("amount_cents" > 0),
--     "date"         date not null,
--     "created_at"   datetime,
--     "updated_at"   datetime,
--     foreign key ("user_id") references "users" ("id") on delete cascade,
--     foreign key ("goal_id") references "goals" ("id") on delete cascade
-- );
-- CREATE INDEX "goal_contributions_goal_id_date_index"
--     on "goal_contributions" ("goal_id", "date");

COMMIT;

-- =====================================================================
-- 3. Registro das migrations
-- Sem isto, `php artisan migrate` tenta criar de novo o que já existe e
-- estoura em "table already exists". Rode só se os arquivos de migration
-- correspondentes existirem em database/migrations/.
-- =====================================================================

INSERT INTO "migrations" ("migration", "batch") VALUES
    ('0001_01_01_000000_create_users_table', 1),
    ('0001_01_01_000001_create_cache_table', 1),
    ('0001_01_01_000002_create_jobs_table', 1),
    ('2025_08_14_170933_add_two_factor_columns_to_users_table', 1),
    ('2026_08_19_120000_drop_passkeys_table', 1),
    ('2026_08_19_120100_add_google_columns_to_users_table', 1),
    ('2026_08_21_000000_create_categories_table', 1),
    ('2026_08_21_000100_create_transactions_table', 1),
    ('2026_08_21_000200_create_budgets_table', 1),
    ('2026_08_21_000300_create_goals_table', 1);

-- =====================================================================
-- 4. Categorias padrão de um usuário (App\Support\DefaultCategories)
-- Na aplicação isto é feito pelo Actions\Categories\CreateDefaultCategories,
-- chamado no UserObserver: toda conta nova nasce com estas treze. O bloco
-- abaixo é o mesmo conteúdo em SQL, para quem estiver montando o banco à mão.
--
-- PRECONDIÇÃO: o usuário tem de existir — categories.user_id é FK. Troque
-- o e-mail abaixo pelo dono das categorias; se não casar com ninguém, o
-- INSERT não grava nada (nenhuma linha à esquerda do CROSS JOIN).
-- =====================================================================

INSERT INTO "categories" ("user_id", "name", "icon", "color", "type", "created_at", "updated_at")
SELECT
    "dono"."id",
    "preset"."name",
    "preset"."icon",
    "preset"."color",
    "preset"."type",
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
FROM (SELECT "id" FROM "users" WHERE "email" = 'test@example.com') AS "dono"
CROSS JOIN (
              SELECT 'Salário'       AS "name", '💼' AS "icon", '#00e676' AS "color", 'income'  AS "type"
    UNION ALL SELECT 'Freelance',          '💻',      '#29b6f6',      'income'
    UNION ALL SELECT 'Investimentos',      '📈',      '#ab47bc',      'income'
    UNION ALL SELECT 'Outros',             '💰',      '#ffca28',      'income'
    UNION ALL SELECT 'Moradia',            '🏠',      '#f85149',      'expense'
    UNION ALL SELECT 'Alimentação',        '🍽️',      '#ff7043',      'expense'
    UNION ALL SELECT 'Transporte',         '🚗',      '#ffa726',      'expense'
    UNION ALL SELECT 'Saúde',              '🏥',      '#26a69a',      'expense'
    UNION ALL SELECT 'Educação',           '📚',      '#42a5f5',      'expense'
    UNION ALL SELECT 'Lazer',              '🎯',      '#ec407a',      'expense'
    UNION ALL SELECT 'Compras',            '🛍️',      '#7e57c2',      'expense'
    UNION ALL SELECT 'Contas',             '📄',      '#8d6e63',      'expense'
    UNION ALL SELECT 'Outros',             '📦',      '#78909c',      'expense'
) AS "preset";

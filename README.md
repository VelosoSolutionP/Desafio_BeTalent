# Payment API — Multi-Gateway

API RESTful de gerenciamento de pagamentos multi-gateway desenvolvida com **Laravel 11**, **Eloquent ORM** e **MySQL**.

**Nível implementado: Nível 2**
- Valor da compra calculado pelo back-end (produto × quantidade)
- Gateways com autenticação
- Roles de usuários (ADMIN, MANAGER, FINANCE, USER)
- Docker Compose com MySQL, aplicação e mock dos gateways

---

## Tecnologias

- **PHP 8.2+**
- **Laravel 11**
- **Eloquent ORM**
- **MySQL 8**
- **JWT Auth** (`tymon/jwt-auth`)
- **Docker Compose**
- **PHPUnit** (testes)

---

## Requisitos

- PHP 8.2+
- Composer
- Docker e Docker Compose

---

## Instalação e execução

### 1. Clone o repositório

```bash
git clone https://github.com/VelosoSolutionP/Desafio_BeTalent.git
cd Desafio_BeTalent
```

### 2. Instale as dependências

```bash
composer install
```

### 3. Configure o ambiente

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Edite o `.env` com suas credenciais do banco:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=payment_api
DB_USERNAME=root
DB_PASSWORD=root
```

### 4. Suba MySQL e os mocks dos gateways

```bash
docker compose up mysql gateways -d
```

### 5. Execute as migrations e seed

```bash
php artisan migrate --seed
```

### 6. Inicie o servidor

```bash
php artisan serve
```

A API estará disponível em **http://localhost:8000/api**

---

### Rodar tudo com Docker

```bash
docker compose up --build
```

---

## Usuários padrão (seed)

| Email | Senha | Role |
|---|---|---|
| admin@payment.com | admin123 | ADMIN |
| manager@payment.com | manager123 | MANAGER |
| finance@payment.com | finance123 | FINANCE |
| user@payment.com | user123 | USER |

---

## Autenticação

A API usa **JWT Bearer Token**. Para autenticar:

```
POST /api/login
Body: { "email": "...", "password": "..." }
```

Use o token retornado no header de todas as rotas privadas:
```
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

---

## Rotas da API

### Públicas

| Método | Rota | Descrição |
|---|---|---|
| POST | `/api/login` | Autenticação — retorna JWT |
| POST | `/api/purchase` | Realizar compra |

#### POST /api/login
```json
{
  "email": "admin@payment.com",
  "password": "admin123"
}
```

**Resposta (200):**
```json
{
  "token": "eyJ...",
  "type": "bearer",
  "user": { "id": 1, "name": "Admin", "email": "admin@payment.com", "role": "ADMIN" }
}
```

#### POST /api/purchase
```json
{
  "client_name": "João Silva",
  "client_email": "joao@email.com",
  "card_number": "5569000000006063",
  "cvv": "010",
  "items": [
    { "product_id": 1, "quantity": 2 }
  ]
}
```

O valor total é **calculado pelo back-end** (produto x quantidade). Nunca enviado pelo cliente.

**Resposta (201):**
```json
{
  "id": 1,
  "status": "APPROVED",
  "amount": 5980,
  "card_last_numbers": "6063",
  "external_id": "uuid-do-gateway",
  "client": { "id": 1, "name": "João Silva", "email": "joao@email.com" },
  "gateway": { "id": 1, "name": "Gateway1" },
  "products": [
    { "id": 1, "name": "Plano Basic", "amount": 2990, "pivot": { "quantity": 2 } }
  ]
}
```

---

### Privadas (Bearer Token obrigatório)

#### Usuários

| Método | Rota | Roles permitidas |
|---|---|---|
| GET | `/api/users` | ADMIN, MANAGER |
| GET | `/api/users/{id}` | ADMIN, MANAGER |
| POST | `/api/users` | ADMIN |
| PUT | `/api/users/{id}` | ADMIN, MANAGER |
| DELETE | `/api/users/{id}` | ADMIN |

#### Produtos

| Método | Rota | Roles permitidas |
|---|---|---|
| GET | `/api/products` | Todos autenticados |
| GET | `/api/products/{id}` | Todos autenticados |
| POST | `/api/products` | ADMIN, MANAGER, FINANCE |
| PUT | `/api/products/{id}` | ADMIN, MANAGER, FINANCE |
| DELETE | `/api/products/{id}` | ADMIN, MANAGER |

#### Clientes

| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/clients` | Lista todos os clientes |
| GET | `/api/clients/{id}` | Detalhe com histórico de compras |

#### Transações

| Método | Rota | Roles permitidas |
|---|---|---|
| GET | `/api/transactions` | Todos autenticados |
| GET | `/api/transactions/{id}` | Todos autenticados |
| POST | `/api/transactions/{id}/refund` | ADMIN, FINANCE |

#### Gateways

| Método | Rota | Roles permitidas |
|---|---|---|
| GET | `/api/gateways` | Todos autenticados |
| PATCH | `/api/gateways/{id}/toggle` | ADMIN |
| PATCH | `/api/gateways/{id}/priority` | ADMIN |

---

## Roles e Permissões

| Ação | ADMIN | MANAGER | FINANCE | USER |
|---|:---:|:---:|:---:|:---:|
| CRUD usuários | ✅ | Parcial | ❌ | ❌ |
| CRUD produtos | ✅ | ✅ | ✅ | ❌ |
| Listar clientes/transações | ✅ | ✅ | ✅ | ✅ |
| Reembolso | ✅ | ❌ | ✅ | ❌ |
| Gerenciar gateways | ✅ | ❌ | ❌ | ❌ |

---

## Lógica Multi-Gateway

1. Busca gateways **ativos** ordenados por **prioridade**
2. Tenta cobrar no gateway de menor prioridade (priority = 1 primeiro)
3. Se falhar, tenta o próximo automaticamente
4. Só retorna erro se **todos** falharem (HTTP 502)
5. Registra em qual gateway a transação foi processada

### Adicionar novo gateway

1. Criar classe em `app/Services/Gateways/` implementando `GatewayInterface`
2. Registrar em `app/Services/Gateways/GatewayRegistry.php`
3. Adicionar configuração no `.env` e `config/services.php`
4. Inserir registro na tabela `gateways`

---

## Estrutura do Projeto

```
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Services/Gateways/
│       ├── GatewayInterface.php
│       ├── Gateway1.php
│       ├── Gateway2.php
│       └── GatewayRegistry.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/api.php
├── config/
├── docker-compose.yml
└── .env.example
```

---

## Testes

```bash
php artisan test
```

---

## Códigos de Resposta

| Situação | Status |
|---|---|
| Sucesso | 200 / 201 |
| Sem conteúdo | 204 |
| Dados inválidos | 422 |
| Não autenticado | 401 |
| Sem permissão | 403 |
| Não encontrado | 404 |
| Conflito | 409 |
| Todos os gateways falharam | 502 |
| Nenhum gateway ativo | 503 |
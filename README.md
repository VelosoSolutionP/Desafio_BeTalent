# Payment API — Multi-Gateway

API RESTful de gerenciamento de pagamentos multi-gateway desenvolvida com **Laravel 11**, **Eloquent ORM** e **MySQL**.

---

## Tecnologias

- **Laravel 11** (PHP 8.2+)
- **Eloquent ORM**
- **MySQL 8**
- **JWT Auth** (`tymon/jwt-auth`)
- **Docker Compose**

---

## Requisitos

- PHP 8.2+
- Composer
- MySQL 8 (ou Docker)

---

## Instalação e execução

### 1. Clone e instale as dependências

```bash
git clone https://github.com/seu-usuario/payment-api.git
cd payment-api
composer install
```

### 2. Configure o ambiente

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

### 3. Suba MySQL e os mocks dos gateways via Docker

```bash
docker compose up mysql gateways -d
```

### 4. Execute as migrations e seed

```bash
php artisan migrate
php artisan db:seed
```

### 5. Inicie o servidor

```bash
php artisan serve
```

A API estará disponível em **http://localhost:8000/api**

---

### Rodar tudo com Docker (opcional)

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

O valor total é **calculado pelo back-end** (produto × quantidade).

---

### Privadas

```
Authorization: Bearer <token>
```

#### Usuários

| Método | Rota | Roles |
|---|---|---|
| GET | `/api/users` | ADMIN, MANAGER |
| GET | `/api/users/:id` | ADMIN, MANAGER |
| POST | `/api/users` | ADMIN |
| PUT | `/api/users/:id` | ADMIN, MANAGER |
| DELETE | `/api/users/:id` | ADMIN |

#### Produtos

| Método | Rota | Roles |
|---|---|---|
| GET | `/api/products` | Todos autenticados |
| GET | `/api/products/:id` | Todos autenticados |
| POST | `/api/products` | ADMIN, MANAGER, FINANCE |
| PUT | `/api/products/:id` | ADMIN, MANAGER, FINANCE |
| DELETE | `/api/products/:id` | ADMIN, MANAGER |

#### Clientes

| Método | Rota |
|---|---|
| GET | `/api/clients` |
| GET | `/api/clients/:id` — inclui histórico de compras |

#### Transações

| Método | Rota | Roles |
|---|---|---|
| GET | `/api/transactions` | Todos autenticados |
| GET | `/api/transactions/:id` | Todos autenticados |
| POST | `/api/transactions/:id/refund` | ADMIN, FINANCE |

#### Gateways

| Método | Rota | Roles |
|---|---|---|
| GET | `/api/gateways` | Todos autenticados |
| PATCH | `/api/gateways/:id/toggle` | ADMIN |
| PATCH | `/api/gateways/:id/priority` | ADMIN |

```json
// PATCH /api/gateways/:id/toggle
{ "is_active": false }

// PATCH /api/gateways/:id/priority
{ "priority": 2 }
```

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
2. Tenta cobrar no gateway de menor prioridade
3. Se falhar, tenta o próximo automaticamente
4. Só retorna erro se **todos** falharem (HTTP 502)
5. Para adicionar um novo gateway: implementar `GatewayInterface` e registrar em `GatewayRegistry`

---

## Estrutura do Projeto

```
├── app/
│   ├── Http/Controllers/   # Auth, User, Product, Client, Transaction, Gateway
│   ├── Models/             # User, Gateway, Client, Product, Transaction
│   └── Services/Gateways/  # GatewayInterface, Gateway1, Gateway2, GatewayRegistry
├── database/
│   ├── migrations/         # Todas as tabelas em um arquivo
│   └── seeders/            # Dados iniciais
├── routes/
│   └── api.php             # Todas as rotas
├── config/
│   ├── auth.php            # Guard JWT
│   └── services.php        # Configuração dos gateways
├── docker-compose.yml
└── .env.example
```

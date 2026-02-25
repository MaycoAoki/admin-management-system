# Stage 7 — Disputes

## Objetivo

Implementar o módulo de contestações: o cliente pode abrir uma disputa sobre um pagamento já realizado (chargeback / contestação), acompanhar o status e retirar a contestação enquanto ainda estiver em aberto.

Em produção, a transição de status (`open → under_review → won/lost`) seria disparada por webhooks do gateway. Nesta etapa o `StubGateway` mantém a disputa em `open`, e a resolução manual fica fora do escopo do portal do cliente.

---

## Escopo

| # | Endpoint | Descrição |
|---|----------|-----------|
| 1 | `POST /api/v1/payments/{id}/disputes` | Abrir disputa sobre um pagamento |
| 2 | `GET /api/v1/disputes` | Listar disputas do usuário (paginado) |
| 3 | `GET /api/v1/disputes/{id}` | Detalhe de uma disputa |
| 4 | `DELETE /api/v1/disputes/{id}` | Retirar disputa (apenas `open`) |

---

## Novos Artefatos de Domínio

### Enum `DisputeStatus`

| Valor | Descrição |
|-------|-----------|
| `open` | Disputa aberta pelo cliente, aguardando análise |
| `under_review` | Em análise pelo gateway / time de suporte |
| `won` | Resolvida a favor do cliente |
| `lost` | Resolvida contra o cliente |
| `withdrawn` | Retirada pelo próprio cliente |

### Enum `DisputeReason`

| Valor | Descrição |
|-------|-----------|
| `fraudulent` | Transação não reconhecida / fraude |
| `duplicate` | Cobrança duplicada |
| `product_not_received` | Produto/serviço não recebido |
| `product_not_as_described` | Produto/serviço diferente do anunciado |
| `unrecognized` | Não reconhece a cobrança |
| `other` | Outro motivo |

### Migration `create_disputes_table`

```
disputes
  id (pk)
  user_id (fk → users)
  payment_id (fk → payments)
  status (DisputeStatus, default: open)
  reason (DisputeReason)
  description (text, nullable)
  gateway_dispute_id (string, nullable)
  resolved_at (timestamp, nullable)
  withdrawn_at (timestamp, nullable)
  timestamps
```

### Índices

- `(user_id, status)` — filtragem por status na listagem
- `(payment_id)` — verificação de disputa existente no pagamento

---

## Arquitetura de Implementação

```
Route (auth:sanctum)
 └─► DisputeController
      ├─► OpenDisputeRequest
      └─► UseCase::execute()
           ├─► PaymentRepository::findById()
           └─► DisputeRepository
```

### Novos arquivos

```
app/
  Enums/
    DisputeStatus.php
    DisputeReason.php
  Models/
    Dispute.php
  Repositories/
    Contracts/DisputeRepositoryInterface.php
    DisputeRepository.php
  UseCases/
    OpenDispute.php
    ListDisputes.php
    GetDisputeDetail.php
    WithdrawDispute.php
  Http/
    Controllers/Api/V1/DisputeController.php
    Requests/OpenDisputeRequest.php
    Resources/V1/DisputeResource.php
database/
  migrations/..._create_disputes_table.php
  factories/DisputeFactory.php
```

---

## Endpoint 1 — Abrir Disputa

### `POST /api/v1/payments/{id}/disputes`

#### Request Body

```json
{
  "reason": "fraudulent",
  "description": "Não reconheço esta cobrança no meu cartão."
}
```

| Campo | Tipo | Regra |
|-------|------|-------|
| `reason` | string | Obrigatório. Um dos valores de `DisputeReason`. |
| `description` | string | Opcional. Máx. 1000 caracteres. |

#### Regras de Negócio

| Regra | Detalhe |
|-------|---------|
| Pagamento deve existir | 404 se não encontrado |
| Posse do pagamento | `payment.user_id === auth.id`, senão `403` |
| Status do pagamento | Apenas pagamentos `succeeded` podem ser disputados. Senão `422` com "Only succeeded payments can be disputed." |
| Disputa duplicada | Se já existe disputa `open` ou `under_review` para o pagamento, `422` com "Payment already has an active dispute." |
| `gateway_dispute_id` | StubGateway: `stub_dispute_` + `Str::random(16)` |
| Status inicial | `open` |

#### Resposta `201 Created`

```json
{
  "data": {
    "id": 1,
    "status": "open",
    "reason": "fraudulent",
    "description": "Não reconheço esta cobrança no meu cartão.",
    "gateway_dispute_id": "stub_dispute_abc123",
    "resolved_at": null,
    "withdrawn_at": null,
    "payment": {
      "id": 8,
      "status": "succeeded",
      "amount_in_cents": 9990,
      "amount_formatted": "R$ 99,90",
      "payment_method_type": "credit_card"
    },
    "created_at": "2026-02-25T01:00:00.000000Z"
  }
}
```

#### Respostas de Erro

| Status | Cenário |
|--------|---------|
| `401` | Não autenticado |
| `403` | Pagamento de outro usuário |
| `404` | Pagamento não encontrado |
| `422` | Campos inválidos |
| `422` | Pagamento não está `succeeded` |
| `422` | Pagamento já tem disputa ativa |

---

## Endpoint 2 — Listar Disputas

### `GET /api/v1/disputes`

#### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `page` | integer | `1` | Página atual |
| `per_page` | integer | `15` | Itens por página (máx. 50, silencioso) |
| `status` | string | — | Filtra por `DisputeStatus` (opcional) |

#### Resposta `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "status": "open",
      "reason": "fraudulent",
      "description": "...",
      "gateway_dispute_id": "stub_dispute_abc123",
      "resolved_at": null,
      "withdrawn_at": null,
      "created_at": "2026-02-25T01:00:00.000000Z"
    }
  ],
  "meta": { "current_page": 1, "per_page": 15, "total": 1 }
}
```

> A listagem **não** embarca o pagamento para evitar N+1. O detalhe inclui o pagamento.

---

## Endpoint 3 — Detalhe de Disputa

### `GET /api/v1/disputes/{id}`

#### Resposta `200 OK`

Mesmo esquema do POST, com `payment` embarcado.

#### Respostas de Erro

| Status | Cenário |
|--------|---------|
| `401` | Não autenticado |
| `403` | Disputa de outro usuário |
| `404` | Disputa não encontrada |

---

## Endpoint 4 — Retirar Disputa

### `DELETE /api/v1/disputes/{id}`

#### Regras de Negócio

| Regra | Detalhe |
|-------|---------|
| Autorização | `dispute.user_id === auth.id`, senão `403` |
| Status permitido | Apenas disputas `open`. Se `under_review`, `won`, `lost` ou `withdrawn` → `422` com "Dispute cannot be withdrawn in its current status." |
| Efeito | `status = withdrawn`, `withdrawn_at = now()` |

#### Resposta `200 OK`

```json
{
  "data": {
    "id": 1,
    "status": "withdrawn",
    "withdrawn_at": "2026-02-25T02:00:00.000000Z",
    ...
  }
}
```

---

## `DisputeRepositoryInterface`

```php
public function create(array $attributes): Dispute;
public function findById(int $id): ?Dispute;
public function findByIdWithPayment(int $id): ?Dispute;
public function paginateForUser(int $userId, int $perPage, ?DisputeStatus $status): LengthAwarePaginator;
public function hasActiveForPayment(int $paymentId): bool;
public function update(Dispute $dispute, array $attributes): Dispute;
```

---

## `PaymentGatewayInterface` — adição

```php
public function openDispute(Payment $payment, string $reason): string; // retorna gateway_dispute_id
```

`StubGateway`: retorna `'stub_dispute_'.Str::random(16)`.

---

## Testes

| Cenário | Endpoint |
|---------|----------|
| Abre disputa com `reason` e `description` → `201` com `gateway_dispute_id` | POST |
| Pagamento de outro usuário → `403` | POST |
| Pagamento inexistente → `404` | POST |
| Pagamento não `succeeded` → `422` | POST |
| Pagamento já tem disputa ativa → `422` | POST |
| `reason` inválido → `422` | POST |
| Listagem retorna apenas disputas do usuário | GET list |
| Filtra por `status` corretamente | GET list |
| Detalhe inclui pagamento embarcado | GET detail |
| Detalhe de outro usuário → `403` | GET detail |
| Retira disputa `open` → `withdrawn` + `withdrawn_at` | DELETE |
| Retirar disputa `under_review` → `422` | DELETE |
| Retirar disputa de outro usuário → `403` | DELETE |
| Requer autenticação (todos os endpoints) | todos |

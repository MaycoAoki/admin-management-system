# Stage 5 — Payment Methods

## Objetivo

Implementar o gerenciamento de métodos de pagamento do cliente: listagem, adição, remoção e definição do método padrão. Em produção, cartões seriam tokenizados via gateway antes do armazenamento; nesta etapa o `StubGateway` aceita diretamente os metadados (last_four, brand, etc.) sem I/O real.

---

## Escopo

| # | Endpoint | Descrição |
|---|----------|-----------|
| 1 | `GET /api/v1/payment-methods` | Listar métodos de pagamento do usuário |
| 2 | `POST /api/v1/payment-methods` | Adicionar novo método de pagamento |
| 3 | `GET /api/v1/payment-methods/{id}` | Detalhe de um método de pagamento |
| 4 | `DELETE /api/v1/payment-methods/{id}` | Remover método de pagamento |
| 5 | `PATCH /api/v1/payment-methods/{id}/default` | Definir método como padrão |

---

## Arquitetura de Implementação

```
Route (auth:sanctum)
 └─► PaymentMethodController
      ├─► AddPaymentMethodRequest / UpdateDefaultPaymentMethodRequest
      └─► UseCase::execute()
           └─► PaymentMethodRepository
```

### Novos arquivos

```
app/
  UseCases/
    ListPaymentMethods.php
    AddPaymentMethod.php
    GetPaymentMethodDetail.php
    RemovePaymentMethod.php
    SetDefaultPaymentMethod.php
  Http/
    Controllers/Api/V1/
      PaymentMethodController.php
    Requests/
      AddPaymentMethodRequest.php
    Resources/V1/
      PaymentMethodResource.php
  Policies/
    PaymentMethodPolicy.php
```

---

## Endpoint 1 — Listar Métodos de Pagamento

### `GET /api/v1/payment-methods`

Retorna todos os métodos não deletados do usuário autenticado (sem paginação — a lista tende a ser pequena).

#### Resposta `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "type": "credit_card",
      "is_default": true,
      "brand": "visa",
      "last_four": "4242",
      "expiry_month": 12,
      "expiry_year": 2028,
      "holder_name": "João Silva",
      "pix_key": null,
      "bank_name": null,
      "created_at": "2026-02-25T01:00:00.000000Z"
    },
    {
      "id": 2,
      "type": "pix",
      "is_default": false,
      "brand": null,
      "last_four": null,
      "expiry_month": null,
      "expiry_year": null,
      "holder_name": null,
      "pix_key": "joao@email.com",
      "bank_name": null,
      "created_at": "2026-02-25T01:00:00.000000Z"
    }
  ]
}
```

---

## Endpoint 2 — Adicionar Método de Pagamento

### `POST /api/v1/payment-methods`

#### Request Body — Cartão de crédito/débito

```json
{
  "type": "credit_card",
  "last_four": "4242",
  "brand": "visa",
  "expiry_month": 12,
  "expiry_year": 2028,
  "holder_name": "João Silva"
}
```

#### Request Body — PIX

```json
{
  "type": "pix",
  "pix_key": "joao@email.com"
}
```

#### Request Body — Débito bancário

```json
{
  "type": "bank_debit",
  "bank_name": "Itaú",
  "holder_name": "João Silva"
}
```

#### Campos por tipo

| Campo | Tipo | Tipos que exigem |
|-------|------|-----------------|
| `type` | string | Todos (obrigatório) |
| `last_four` | string (4 dígitos) | `credit_card`, `debit_card` |
| `brand` | string | `credit_card`, `debit_card` |
| `expiry_month` | integer (1–12) | `credit_card`, `debit_card` |
| `expiry_year` | integer (≥ ano atual) | `credit_card`, `debit_card` |
| `holder_name` | string | `credit_card`, `debit_card`, `bank_debit` |
| `pix_key` | string | `pix` |
| `bank_name` | string | `bank_debit` |

#### Regras de Negócio

| Regra | Detalhe |
|-------|---------|
| Stub de tokenização | O gateway é chamado via `StubGateway::tokenize()` que devolve um `gateway_token` fake — nenhuma dado real é transmitido |
| Primeiro método | Automaticamente definido como `is_default = true` |
| Métodos subsequentes | `is_default = false` por padrão |
| `gateway` | Sempre `stub` nesta etapa |

#### Resposta `201 Created`

```json
{
  "data": {
    "id": 3,
    "type": "credit_card",
    "is_default": false,
    "brand": "visa",
    "last_four": "4242",
    "expiry_month": 12,
    "expiry_year": 2028,
    "holder_name": "João Silva",
    "pix_key": null,
    "bank_name": null,
    "created_at": "2026-02-25T01:00:00.000000Z"
  }
}
```

#### Respostas de Erro

| Status | Cenário |
|--------|---------|
| `401` | Não autenticado |
| `422` | Campos obrigatórios ausentes por tipo |
| `422` | `expiry_year` no passado |
| `422` | `last_four` não numérico ou não tem 4 dígitos |
| `422` | `type` inválido |

---

## Endpoint 3 — Detalhe de Método de Pagamento

### `GET /api/v1/payment-methods/{id}`

Retorna o método de pagamento. Mesma estrutura do item na listagem.

#### Respostas de Erro

| Status | Cenário |
|--------|---------|
| `401` | Não autenticado |
| `403` | Método pertence a outro usuário |
| `404` | Método não encontrado |

---

## Endpoint 4 — Remover Método de Pagamento

### `DELETE /api/v1/payment-methods/{id}`

Soft-deleta o método de pagamento. Não remove métodos que estejam vinculados a pagamentos em andamento.

#### Regras de Negócio

| Regra | Detalhe |
|-------|---------|
| Autorização | `payment_method.user_id === auth.id`, senão `403` |
| Método default | Ao remover o método padrão, nenhum outro é promovido automaticamente — fica sem padrão até o usuário definir um novo |
| Pagamentos pendentes | Se houver pagamentos com `status = pending` vinculados ao método, retorna `422` com mensagem "Payment method has pending payments." |

#### Resposta `204 No Content`

Sem corpo.

#### Respostas de Erro

| Status | Cenário |
|--------|---------|
| `401` | Não autenticado |
| `403` | Método pertence a outro usuário |
| `404` | Método não encontrado |
| `422` | Método possui pagamentos pendentes |

---

## Endpoint 5 — Definir Método Padrão

### `PATCH /api/v1/payment-methods/{id}/default`

Define o método como padrão do usuário, removendo o flag `is_default` de todos os outros.

#### Regras de Negócio

| Regra | Detalhe |
|-------|---------|
| Autorização | `payment_method.user_id === auth.id`, senão `403` |
| Atomicidade | Troca de padrão feita em transação: desmarca todos, marca o alvo |

#### Resposta `200 OK`

```json
{
  "data": {
    "id": 2,
    "type": "pix",
    "is_default": true,
    ...
  }
}
```

#### Respostas de Erro

| Status | Cenário |
|--------|---------|
| `401` | Não autenticado |
| `403` | Método pertence a outro usuário |
| `404` | Método não encontrado |

---

## Atualização no `PaymentGatewayInterface`

Adicionar o método de tokenização ao contrato:

```php
public function tokenize(array $attributes): string; // retorna gateway_token
```

O `StubGateway` retorna um token fake: `stub_token_` + `Str::random(20)`.

---

## Atualização no `PaymentMethodRepository`

Implementar `setDefault()` — já declarado na interface:

```php
public function setDefault(PaymentMethod $paymentMethod): void
{
    DB::transaction(function () use ($paymentMethod) {
        PaymentMethod::query()
            ->where('user_id', $paymentMethod->user_id)
            ->update(['is_default' => false]);

        $paymentMethod->update(['is_default' => true]);
    });
}
```

---

## Testes

| Cenário | Endpoint |
|---------|----------|
| Listagem retorna apenas métodos do usuário autenticado | GET list |
| Listagem de usuário sem métodos retorna array vazio | GET list |
| Adicionar cartão de crédito retorna `201` com dados corretos | POST |
| Adicionar PIX retorna `201` com `pix_key` | POST |
| Primeiro método adicionado é `is_default = true` | POST |
| Segundo método adicionado é `is_default = false` | POST |
| Campos obrigatórios de cartão ausentes → `422` | POST |
| `expiry_year` no passado → `422` | POST |
| `type` inválido → `422` | POST |
| Detalhe retorna o método correto | GET detail |
| Detalhe de método de outro usuário → `403` | GET detail |
| Detalhe de método inexistente → `404` | GET detail |
| Remover método próprio → `204` | DELETE |
| Remover método de outro usuário → `403` | DELETE |
| Remover método com pagamento pendente → `422` | DELETE |
| Definir padrão desmarca método anterior | PATCH default |
| Definir padrão de método de outro usuário → `403` | PATCH default |
| Requer autenticação (todos os endpoints) | todos |

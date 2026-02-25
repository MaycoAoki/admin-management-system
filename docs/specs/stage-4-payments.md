# Stage 4 — Payments

## Objetivo

Implementar o módulo de pagamentos: o cliente pode pagar uma fatura específica escolhendo o método (PIX, boleto, cartão de crédito, débito). A integração com gateways reais é abstraída por uma interface — nesta etapa a implementação é um `StubGateway` substituível sem afetar as camadas superiores.

---

## Escopo

| # | Endpoint | Descrição |
|---|----------|-----------|
| 1 | `POST /api/v1/invoices/{id}/payments` | Iniciar pagamento de uma fatura |
| 2 | `GET /api/v1/payments` | Histórico de pagamentos paginado |
| 3 | `GET /api/v1/payments/{id}` | Detalhe de um pagamento |

---

## Arquitetura de Implementação

```
Route (auth:sanctum)
 └─► PaymentController
      └─► InitiatePaymentRequest (valida method, amount, payment_method_id)
           └─► InitiatePayment::execute()
                ├─► InvoiceRepository::findById()          – verifica posse e status
                ├─► PaymentMethodRepository::findById()    – verifica posse do método
                ├─► PaymentRepository::create()            – cria registro Pending
                ├─► PaymentGatewayInterface::charge()      – chama gateway (Stub)
                ├─► PaymentRepository::update()            – persiste resultado
                └─► InvoiceRepository::update()            – atualiza saldo/status
```

### Abstração do Gateway

```
app/
  Contracts/
    PaymentGatewayInterface.php    ← contrato único
  DTOs/
    GatewayResponse.php            ← resultado tipado do gateway
    InitiatePaymentData.php        ← dados de entrada do use case
  Gateways/
    StubGateway.php                ← implementação de teste (sem I/O real)
```

`AppServiceProvider` faz o binding `PaymentGatewayInterface → StubGateway`. Para produção, bastará criar `AsaasGateway` (ou similar) e trocar o binding.

---

## Endpoint 1 — Iniciar Pagamento

### `POST /api/v1/invoices/{id}/payments`

#### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

#### Request Body

**PIX:**
```json
{ "method": "pix" }
```

**Boleto:**
```json
{ "method": "boleto" }
```

**Cartão de crédito (token armazenado):**
```json
{
  "method": "credit_card",
  "payment_method_id": 1,
  "amount_in_cents": 4990
}
```

| Campo | Tipo | Regra |
|-------|------|-------|
| `method` | string | Obrigatório. Um dos valores de `PaymentMethodType` |
| `payment_method_id` | integer | Obrigatório quando `method` é `credit_card` ou `debit_card`. Deve pertencer ao usuário. |
| `amount_in_cents` | integer | Opcional. Padrão = `amount_due_in_cents`. Mínimo = 1. Máximo = `amount_due_in_cents`. |

#### Resposta de Sucesso PIX — `201 Created`
```json
{
  "data": {
    "id": 7,
    "status": "pending",
    "payment_method_type": "pix",
    "amount_in_cents": 9990,
    "amount_formatted": "R$ 99,90",
    "pix_qr_code": "00020126...",
    "pix_expires_at": "2026-02-25T02:00:00.000000Z",
    "boleto_url": null,
    "boleto_barcode": null,
    "boleto_expires_at": null,
    "failure_reason": null,
    "paid_at": null,
    "created_at": "2026-02-25T01:00:00.000000Z"
  }
}
```

#### Resposta de Sucesso Cartão — `201 Created`
```json
{
  "data": {
    "id": 8,
    "status": "succeeded",
    "payment_method_type": "credit_card",
    "amount_in_cents": 9990,
    "amount_formatted": "R$ 99,90",
    "pix_qr_code": null,
    "pix_expires_at": null,
    "paid_at": "2026-02-25T01:00:00.000000Z",
    "failure_reason": null,
    "created_at": "2026-02-25T01:00:00.000000Z"
  }
}
```

#### Respostas de Erro

| Status | Cenário |
|--------|---------|
| `401` | Não autenticado |
| `403` | Fatura pertence a outro usuário |
| `404` | Fatura não encontrada |
| `422` | Validação: campo inválido, método inválido, `payment_method_id` ausente para cartão |
| `422` | `amount_in_cents` maior que `amount_due_in_cents` |
| `422` | Fatura já está `paid`, `void` ou `uncollectible` |

#### Regras de Negócio

| Regra | Detalhe |
|-------|---------|
| Posse da fatura | `invoice.user_id === auth.id`, senão `403` |
| Status da fatura | Deve ser `open`, senão `422` com mensagem "Invoice is not payable." |
| Valor padrão | Se `amount_in_cents` não enviado, usa `invoice.amount_due_in_cents` |
| Valor máximo | `amount_in_cents` ≤ `invoice.amount_due_in_cents`, senão `422` |
| Posse do método | `payment_method.user_id === auth.id`, senão `422` |
| Registro inicial | `Payment` criado com `status = pending` antes de chamar o gateway |
| Resultado do gateway | Payment atualizado com `status`, campos PIX/boleto, `paid_at` |
| Atualização da fatura | Se `succeeded`: incrementa `amount_paid_in_cents`; se total coberto, muda `status = paid` + `paid_at` |
| Pagamento parcial | `amount_paid_in_cents` atualizado mas `status` permanece `open` |

#### Comportamento do StubGateway

| Método | Status retornado | Observações |
|--------|-----------------|-------------|
| `pix` | `pending` | Retorna QR code fake + `expires_at = now() + 1h` |
| `boleto` | `pending` | Retorna URL e barcode fake + `expires_at = now() + 3 days` |
| `credit_card` | `succeeded` | `paid_at = now()`. Sem dados PIX/boleto. |
| `debit_card` | `succeeded` | `paid_at = now()`. Sem dados PIX/boleto. |

---

## Endpoint 2 — Histórico de Pagamentos

### `GET /api/v1/payments`

#### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `page` | integer | `1` | Página atual |
| `per_page` | integer | `15` | Itens por página (máx. 50, silencioso) |

#### Resposta `200 OK`
```json
{
  "data": [
    {
      "id": 8,
      "status": "succeeded",
      "payment_method_type": "credit_card",
      "amount_in_cents": 9990,
      "amount_formatted": "R$ 99,90",
      "paid_at": "2026-02-25T01:00:00.000000Z",
      "created_at": "2026-02-25T01:00:00.000000Z"
    }
  ],
  "links": { ... },
  "meta": { "current_page": 1, "per_page": 15, "total": 3 }
}
```

---

## Endpoint 3 — Detalhe de Pagamento

### `GET /api/v1/payments/{id}`

#### Resposta `200 OK`
```json
{
  "data": {
    "id": 7,
    "status": "pending",
    "payment_method_type": "pix",
    "amount_in_cents": 9990,
    "amount_formatted": "R$ 99,90",
    "pix_qr_code": "00020126...",
    "pix_expires_at": "2026-02-25T02:00:00.000000Z",
    "boleto_url": null,
    "boleto_barcode": null,
    "boleto_expires_at": null,
    "failure_reason": null,
    "paid_at": null,
    "failed_at": null,
    "invoice": {
      "id": 3,
      "invoice_number": "INV-2026-00003",
      "status": "open",
      "amount_in_cents": 9990,
      "amount_due_in_cents": 9990
    },
    "created_at": "2026-02-25T01:00:00.000000Z"
  }
}
```

#### Regras

| Regra | Detalhe |
|-------|---------|
| Autorização | `payment.user_id === auth.id`, senão `403` |
| Eager loading | `invoice` carregado com `findByIdWithRelations` |

---

## Testes

| Cenário | Endpoint |
|---------|----------|
| Pagar com PIX → `pending` + QR code | POST |
| Pagar com boleto → `pending` + barcode | POST |
| Pagar com cartão → `succeeded` + fatura marcada como `paid` | POST |
| Pagamento parcial → fatura permanece `open` | POST |
| Fatura de outro usuário → `403` | POST |
| Fatura inexistente → `404` | POST |
| Fatura já paga → `422` | POST |
| `amount_in_cents` > `amount_due` → `422` | POST |
| `credit_card` sem `payment_method_id` → `422` | POST |
| `payment_method_id` de outro usuário → `422` | POST |
| Listagem retorna apenas pagamentos do usuário | GET list |
| Detalhe inclui invoice | GET detail |
| Detalhe de outro usuário → `403` | GET detail |

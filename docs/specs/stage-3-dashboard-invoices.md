# Stage 3 — Dashboard & Invoices

## Objetivo

Implementar os dois primeiros módulos do portal de autoatendimento: o **dashboard de conta** e o **módulo de faturas**. Estes endpoints entregam o núcleo da Iteração 1 do PRD: o cliente pode ver seu saldo devedor, status da assinatura, próximos vencimentos e consultar o histórico completo de faturas.

---

## Escopo

| # | Endpoint | Descrição |
|---|----------|-----------|
| 1 | `GET /api/v1/dashboard` | Resumo da conta |
| 2 | `GET /api/v1/invoices` | Listagem paginada de faturas |
| 3 | `GET /api/v1/invoices/{id}` | Detalhe de uma fatura |

Todos os endpoints requerem autenticação via `auth:sanctum`. O usuário só acessa seus próprios dados (escopo por `user_id`).

---

## Arquitetura de Implementação

```
Route (auth:sanctum)
 └─► Controller
      └─► UseCase::execute()
           └─► Repository (interface)
                └─► Eloquent Model
                     └─► Resource (resposta JSON)
```

### Novos arquivos

```
app/
  Http/
    Controllers/Api/V1/
      DashboardController.php
      InvoiceController.php
    Resources/V1/
      DashboardResource.php
      InvoiceResource.php
      InvoiceCollection.php
      PaymentResource.php
      PlanResource.php
      SubscriptionResource.php
  UseCases/
    GetDashboardSummary.php
    ListInvoices.php
    GetInvoiceDetail.php
```

---

## Endpoint 1 — Dashboard

### `GET /api/v1/dashboard`

Retorna o resumo consolidado da conta do usuário autenticado.

#### Headers

```
Authorization: Bearer {token}
Accept: application/json
```

#### Resposta de Sucesso — `200 OK`

```json
{
  "data": {
    "balance": {
      "outstanding_in_cents": 14980,
      "outstanding_formatted": "R$ 149,80",
      "open_invoices_count": 2,
      "overdue_invoices_count": 1
    },
    "next_due": {
      "invoice_number": "INV-2026-00003",
      "amount_due_in_cents": 9990,
      "amount_due_formatted": "R$ 99,90",
      "due_date": "2026-03-05",
      "is_overdue": false,
      "days_until_due": 9
    },
    "subscription": {
      "status": "active",
      "plan_name": "Pro",
      "plan_slug": "pro",
      "billing_cycle": "monthly",
      "price_in_cents": 9990,
      "price_formatted": "R$ 99,90",
      "current_period_end": "2026-03-15T00:00:00.000000Z",
      "auto_renew": true,
      "is_trial": false,
      "trial_ends_at": null
    }
  }
}
```

#### Resposta — sem assinatura ativa

Quando o usuário não possui assinatura ativa, `subscription` retorna `null`. O campo `next_due` também retorna `null` se não houver faturas abertas.

```json
{
  "data": {
    "balance": {
      "outstanding_in_cents": 0,
      "outstanding_formatted": "R$ 0,00",
      "open_invoices_count": 0,
      "overdue_invoices_count": 0
    },
    "next_due": null,
    "subscription": null
  }
}
```

#### Regras de Negócio

| Regra | Detalhe |
|-------|---------|
| `outstanding_in_cents` | Soma de `amount_in_cents - amount_paid_in_cents` para todas as faturas com `status = open` do usuário (`InvoiceRepository::outstandingBalanceForUser`) |
| `open_invoices_count` | Contagem de faturas com `status = open` |
| `overdue_invoices_count` | Contagem de faturas com `status = open` e `due_date < hoje` |
| `next_due` | Primeira fatura com `status = open` e `due_date >= hoje`, ordenada por `due_date ASC`. Se todas as faturas abertas estiverem vencidas, retorna a mais antiga |
| `days_until_due` | `due_date` menos hoje em dias. Valor negativo = vencida |
| `is_overdue` | `due_date < hoje` |
| `subscription` | Resultado de `User::activeSubscription()` com `plan` eager-loaded. Null se não houver |
| `is_trial` | `status = trialing` |

#### Use Case — `GetDashboardSummary`

```php
final class GetDashboardSummary
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
    ) {}

    public function execute(int $userId): DashboardData
    {
        $openInvoices    = $this->invoiceRepository->openForUser($userId);
        $nextDue         = $this->invoiceRepository->nextDueForUser($userId);
        $outstanding     = $this->invoiceRepository->outstandingBalanceForUser($userId);
        $subscription    = $this->subscriptionRepository->findActiveForUser($userId);

        return new DashboardData(
            outstandingInCents: $outstanding,
            openInvoicesCount: $openInvoices->count(),
            overdueInvoicesCount: $openInvoices->filter(fn ($i) => $i->due_date->isPast())->count(),
            nextDue: $nextDue,
            subscription: $subscription,
        );
    }
}
```

`DashboardData` é um DTO simples (readonly class PHP 8.2) que o `DashboardResource` recebe e formata.

---

## Endpoint 2 — Listagem de Faturas

### `GET /api/v1/invoices`

Retorna a lista paginada de faturas do usuário autenticado, da mais recente para a mais antiga.

#### Headers

```
Authorization: Bearer {token}
Accept: application/json
```

#### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `page` | integer | `1` | Página atual |
| `per_page` | integer | `15` | Itens por página (máx. 50) |
| `status` | string | — | Filtro por status: `open`, `paid`, `void`, `uncollectible`, `draft` |

#### Resposta de Sucesso — `200 OK`

```json
{
  "data": [
    {
      "id": 3,
      "invoice_number": "INV-2026-00003",
      "status": "open",
      "amount_in_cents": 9990,
      "amount_paid_in_cents": 0,
      "amount_due_in_cents": 9990,
      "amount_formatted": "R$ 99,90",
      "currency": "BRL",
      "description": "Plano Pro — Março 2026",
      "due_date": "2026-03-05",
      "paid_at": null,
      "period_start": "2026-03-01",
      "period_end": "2026-03-31",
      "is_overdue": false,
      "created_at": "2026-02-25T01:00:00.000000Z"
    }
  ],
  "links": {
    "first": "/api/v1/invoices?page=1",
    "last": "/api/v1/invoices?page=3",
    "prev": null,
    "next": "/api/v1/invoices?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 15,
    "to": 15,
    "total": 42
  }
}
```

#### Resposta — sem faturas

```json
{
  "data": [],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 0 }
}
```

#### Regras de Negócio

| Regra | Detalhe |
|-------|---------|
| Escopo | Sempre filtrado por `user_id` do usuário autenticado — nunca expõe faturas de outros usuários |
| Ordenação | `due_date DESC` por padrão (faturas mais recentes primeiro) |
| Filtro de status | Quando `status` é informado, valida contra os valores do `InvoiceStatus` enum. Retorna `422` se inválido |
| `per_page` | Limitado a no máximo 50. Valores maiores são silenciosamente truncados para 50 |
| `is_overdue` | `status = open AND due_date < hoje` |
| `amount_due_in_cents` | `amount_in_cents - amount_paid_in_cents` |
| `amount_formatted` | Formatado como moeda BRL: `"R$ 99,90"` |

#### Validação — `ListInvoicesRequest`

```php
public function rules(): array
{
    return [
        'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        'status'   => ['sometimes', 'string', Rule::in(InvoiceStatus::values())],
    ];
}
```

> `InvoiceStatus::values()` retorna `['draft', 'open', 'paid', 'void', 'uncollectible']` via método estático no enum.

#### Use Case — `ListInvoices`

```php
final class ListInvoices
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function execute(int $userId, int $perPage = 15, ?InvoiceStatus $status = null): LengthAwarePaginator
    {
        return $this->invoiceRepository->paginateForUser(
            userId: $userId,
            perPage: min($perPage, 50),
            status: $status,
        );
    }
}
```

> Obs.: `InvoiceRepositoryInterface::paginateForUser` receberá um parâmetro opcional `?InvoiceStatus $status` nesta etapa.

---

## Endpoint 3 — Detalhe de Fatura

### `GET /api/v1/invoices/{id}`

Retorna o detalhe completo de uma fatura, incluindo os pagamentos associados.

#### Headers

```
Authorization: Bearer {token}
Accept: application/json
```

#### Path Parameters

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID da fatura |

#### Resposta de Sucesso — `200 OK`

```json
{
  "data": {
    "id": 3,
    "invoice_number": "INV-2026-00003",
    "status": "open",
    "amount_in_cents": 9990,
    "amount_paid_in_cents": 0,
    "amount_due_in_cents": 9990,
    "amount_formatted": "R$ 99,90",
    "currency": "BRL",
    "description": "Plano Pro — Março 2026",
    "due_date": "2026-03-05",
    "paid_at": null,
    "period_start": "2026-03-01",
    "period_end": "2026-03-31",
    "is_overdue": false,
    "pdf_url": null,
    "subscription": {
      "id": 1,
      "status": "active",
      "plan_name": "Pro"
    },
    "payments": [
      {
        "id": 1,
        "amount_in_cents": 9990,
        "amount_formatted": "R$ 99,90",
        "status": "failed",
        "payment_method_type": "credit_card",
        "gateway": "stub",
        "failure_reason": "card_declined",
        "paid_at": null,
        "failed_at": "2026-02-24T20:00:00.000000Z",
        "created_at": "2026-02-24T19:55:00.000000Z"
      }
    ],
    "created_at": "2026-02-25T01:00:00.000000Z"
  }
}
```

#### Resposta — fatura não encontrada

```json
{
  "message": "Resource not found."
}
```
**Status:** `404 Not Found`

#### Resposta — fatura de outro usuário

```json
{
  "message": "This action is unauthorized."
}
```
**Status:** `403 Forbidden`

#### Regras de Negócio

| Regra | Detalhe |
|-------|---------|
| Autorização | O `user_id` da fatura deve ser igual ao `id` do usuário autenticado. Usar `Gate::authorize` ou Policy |
| Eager loading | `subscription.plan` e `payments` são carregados juntos para evitar N+1 |
| `pdf_url` | Null por enquanto (geração de PDF é Iteração 2). Campo reservado |
| `payments` | Ordenados por `created_at DESC`. Inclui falhas e bem-sucedidos |
| `subscription` | Versão resumida (id, status, plan_name) — sem sobrecarga de dados |

#### Use Case — `GetInvoiceDetail`

```php
final class GetInvoiceDetail
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    /**
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function execute(int $invoiceId, int $userId): Invoice
    {
        $invoice = $this->invoiceRepository->findByIdWithRelations($invoiceId);

        if (! $invoice) {
            throw new ModelNotFoundException;
        }

        if ($invoice->user_id !== $userId) {
            throw new AuthorizationException;
        }

        return $invoice;
    }
}
```

> Adicionar `findByIdWithRelations(int $id): ?Invoice` à interface e implementação do `InvoiceRepository`, que carrega `subscription.plan` e `payments` via eager loading.

---

## Policy — `InvoicePolicy`

Gerencia as regras de acesso às faturas. Registrada automaticamente pelo Laravel por convenção de nome.

```php
class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }
}
```

O controller chama `$this->authorize('view', $invoice)` antes de retornar a resposta.

---

## Resources

### `InvoiceResource`

Usado tanto na listagem quanto no detalhe (com campos condicionais).

```php
[
    'id'                    => $this->id,
    'invoice_number'        => $this->invoice_number,
    'status'                => $this->status->value,
    'amount_in_cents'       => $this->amount_in_cents,
    'amount_paid_in_cents'  => $this->amount_paid_in_cents,
    'amount_due_in_cents'   => $this->amount_due_in_cents,       // accessor
    'amount_formatted'      => $this->formatCurrency($this->amount_in_cents, $this->currency),
    'currency'              => $this->currency,
    'description'           => $this->description,
    'due_date'              => $this->due_date?->toDateString(),
    'paid_at'               => $this->paid_at,
    'period_start'          => $this->period_start?->toDateString(),
    'period_end'            => $this->period_end?->toDateString(),
    'is_overdue'            => $this->status === InvoiceStatus::Open && $this->due_date?->isPast(),
    'created_at'            => $this->created_at,
    // Campos exclusivos do detalhe (whenLoaded)
    'pdf_url'               => $this->when($this->resource->relationLoaded('subscription'), fn () => null),
    'subscription'          => $this->whenLoaded('subscription', fn () => new SubscriptionSummaryResource($this->subscription)),
    'payments'              => $this->whenLoaded('payments', fn () => PaymentResource::collection($this->payments)),
]
```

> `whenLoaded` garante que os campos relacionais só aparecem no endpoint de detalhe, não na listagem.

### `DashboardResource`

Recebe um `DashboardData` DTO e o transforma em JSON, aplicando formatação de moeda.

### `PaymentResource`

Campos: `id`, `amount_in_cents`, `amount_formatted`, `status`, `payment_method_type`, `gateway`, `failure_reason`, `paid_at`, `failed_at`, `created_at`.
Campos PIX/boleto omitidos nesta etapa (aparecem na Stage 4 — Pagamentos).

### `SubscriptionSummaryResource`

Versão reduzida para embed em fatura: `id`, `status`, `plan_name`.

---

## Formatação de Moeda

Criar um helper ou trait reutilizável `FormatsMonetary`:

```php
protected function formatCurrency(int $amountInCents, string $currency = 'BRL'): string
{
    return match ($currency) {
        'BRL' => 'R$ '.number_format($amountInCents / 100, 2, ',', '.'),
        default => number_format($amountInCents / 100, 2).' '.$currency,
    };
}
```

Usado nos resources para formatar `amount_formatted`, `outstanding_formatted`, `price_formatted`.

---

## Atualização do `InvoiceRepositoryInterface`

Dois métodos novos em relação à Stage 2:

```php
// Adicionar parâmetro opcional de filtro
public function paginateForUser(int $userId, int $perPage = 15, ?InvoiceStatus $status = null): LengthAwarePaginator;

// Novo método com eager loading para o detalhe
public function findByIdWithRelations(int $id): ?Invoice;
```

---

## Rotas

```php
// routes/api.php — dentro do grupo auth:sanctum
Route::get('dashboard', [DashboardController::class, 'show'])->name('dashboard');

Route::prefix('invoices')->name('invoices.')->group(function () {
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
    Route::get('{invoice}', [InvoiceController::class, 'show'])->name('show');
});
```

---

## Testes

### `DashboardTest`

| Cenário | Asserção |
|---------|----------|
| Usuário com assinatura ativa e faturas abertas | `assertSuccessful()` + estrutura JSON completa |
| Usuário sem assinatura | `subscription = null` |
| Usuário sem faturas abertas | `outstanding_in_cents = 0`, `next_due = null` |
| Usuário com faturas vencidas | `overdue_invoices_count > 0`, `is_overdue = true` no `next_due` |
| Usuário não autenticado | `assertUnauthorized()` |

### `InvoiceTest`

| Cenário | Asserção |
|---------|----------|
| Listagem retorna faturas do usuário | `assertSuccessful()` + paginação |
| Filtro por `status=open` | Apenas faturas abertas |
| `per_page` acima de 50 é limitado a 50 | Meta `per_page = 50` |
| `status` inválido | `assertUnprocessable()` |
| Detalhe de fatura própria | `assertSuccessful()` + `payments` e `subscription` presentes |
| Detalhe de fatura de outro usuário | `assertForbidden()` |
| Fatura inexistente | `assertNotFound()` |
| Usuário não autenticado | `assertUnauthorized()` |

---

## Diagrama de Fluxo

```
Cliente
 │
 ├─► GET /api/v1/dashboard
 │    └─► DashboardController::show()
 │         └─► GetDashboardSummary::execute($userId)
 │              ├─► InvoiceRepository::openForUser()
 │              ├─► InvoiceRepository::nextDueForUser()
 │              ├─► InvoiceRepository::outstandingBalanceForUser()
 │              └─► SubscriptionRepository::findActiveForUser()  ← eager: plan
 │                   └─► DashboardResource → 200
 │
 ├─► GET /api/v1/invoices?status=open&per_page=10
 │    └─► InvoiceController::index()
 │         └─► ListInvoicesRequest (valida status, per_page)
 │              └─► ListInvoices::execute($userId, $perPage, $status)
 │                   └─► InvoiceRepository::paginateForUser()
 │                        └─► InvoiceResource::collection() → 200
 │
 └─► GET /api/v1/invoices/3
      └─► InvoiceController::show()
           └─► GetInvoiceDetail::execute($invoiceId, $userId)
                ├─► InvoiceRepository::findByIdWithRelations()  ← eager: subscription.plan, payments
                ├─► Verifica user_id  →  403 se diferente
                └─► InvoiceResource → 200
```

---

## Checklist de Implementação

- [ ] DTO `DashboardData` (readonly class)
- [ ] Use Case `GetDashboardSummary`
- [ ] Use Case `ListInvoices`
- [ ] Use Case `GetInvoiceDetail`
- [ ] `InvoicePolicy` + registro no `AuthServiceProvider`
- [ ] Trait/helper `FormatsMonetary`
- [ ] `DashboardResource`
- [ ] `InvoiceResource` (com `whenLoaded`)
- [ ] `InvoiceCollection`
- [ ] `PaymentResource`
- [ ] `SubscriptionSummaryResource`
- [ ] `PlanResource` (básico)
- [ ] `ListInvoicesRequest` (valida status e per_page)
- [ ] `DashboardController`
- [ ] `InvoiceController`
- [ ] Atualizar `InvoiceRepositoryInterface` (+ `status` filter + `findByIdWithRelations`)
- [ ] Atualizar `InvoiceRepository` (implementação)
- [ ] Rotas em `routes/api.php`
- [ ] Testes: `DashboardTest`, `InvoiceTest`
- [ ] `vendor/bin/pint --dirty`

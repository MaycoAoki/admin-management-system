# Stage 6 — Subscriptions

## Objetivo

Implementar o gerenciamento de assinatura do cliente: visualização da assinatura ativa, listagem de planos disponíveis, contratação de um plano, troca de plano (upgrade/downgrade) e cancelamento. A lógica de faturamento (geração de faturas, cobranças recorrentes) é responsabilidade do sistema de billing interno e **não** faz parte deste módulo — os endpoints aqui apenas gerenciam o estado da assinatura.

---

## Escopo

| # | Endpoint | Descrição |
|---|----------|-----------|
| 1 | `GET /api/v1/plans` | Listar planos ativos disponíveis |
| 2 | `GET /api/v1/subscription` | Assinatura ativa do usuário |
| 3 | `POST /api/v1/subscription` | Contratar um plano |
| 4 | `PATCH /api/v1/subscription/plan` | Trocar de plano (upgrade ou downgrade) |
| 5 | `DELETE /api/v1/subscription` | Cancelar assinatura |

---

## Arquitetura de Implementação

```
Route (auth:sanctum)
 └─► SubscriptionController
      ├─► SubscribeToPlanRequest / ChangePlanRequest
      └─► UseCase::execute()
           ├─► PlanRepository
           └─► SubscriptionRepository
```

### Novos arquivos

```
app/
  UseCases/
    ListPlans.php
    GetCurrentSubscription.php
    SubscribeToPlan.php
    ChangePlan.php
    CancelSubscription.php
  Http/
    Controllers/Api/V1/
      SubscriptionController.php
      PlanController.php
    Requests/
      SubscribeToPlanRequest.php
      ChangePlanRequest.php
    Resources/V1/
      PlanResource.php
      SubscriptionResource.php
```

---

## Endpoint 1 — Listar Planos

### `GET /api/v1/plans`

Retorna todos os planos com `is_active = true`.

#### Resposta `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "name": "Starter",
      "slug": "starter",
      "description": "Plano básico para começar.",
      "price_in_cents": 2990,
      "price_formatted": "R$ 29,90",
      "currency": "BRL",
      "billing_cycle": "monthly",
      "trial_days": 14
    },
    {
      "id": 2,
      "name": "Pro",
      "slug": "pro",
      "description": "Para times em crescimento.",
      "price_in_cents": 9990,
      "price_formatted": "R$ 99,90",
      "currency": "BRL",
      "billing_cycle": "monthly",
      "trial_days": 0
    }
  ]
}
```

---

## Endpoint 2 — Assinatura Atual

### `GET /api/v1/subscription`

Retorna a assinatura ativa (`active` ou `trialing`) do usuário. Inclui o plano embarcado.

#### Resposta `200 OK`

```json
{
  "data": {
    "id": 3,
    "status": "active",
    "auto_renew": true,
    "current_period_start": "2026-02-01T00:00:00.000000Z",
    "current_period_end": "2026-03-01T00:00:00.000000Z",
    "trial_ends_at": null,
    "canceled_at": null,
    "cancel_at": null,
    "plan": {
      "id": 2,
      "name": "Pro",
      "slug": "pro",
      "price_in_cents": 9990,
      "price_formatted": "R$ 99,90",
      "billing_cycle": "monthly"
    }
  }
}
```

#### Respostas de Erro

| Status | Cenário |
|--------|---------|
| `401` | Não autenticado |
| `404` | Usuário não tem assinatura ativa |

---

## Endpoint 3 — Contratar Plano

### `POST /api/v1/subscription`

Cria uma nova assinatura para o usuário.

#### Request Body

```json
{ "plan_id": 1 }
```

| Campo | Tipo | Regra |
|-------|------|-------|
| `plan_id` | integer | Obrigatório. Deve existir e ser `is_active = true`. |

#### Regras de Negócio

| Regra | Detalhe |
|-------|---------|
| Assinatura existente | Se o usuário já tem assinatura `active` ou `trialing`, retorna `422` com "User already has an active subscription." |
| Trial | Se `plan.trial_days > 0`, status inicial = `trialing`, `trial_ends_at = now() + trial_days` |
| Status padrão | Se sem trial, status = `active` |
| Período | `current_period_start = now()`, `current_period_end` calculado pelo `billing_cycle` do plano |
| `auto_renew` | `true` por padrão |
| Plano inativo | Retorna `422` (tratado pela validação `exists` + escopo) |

#### Cálculo do `current_period_end` por `billing_cycle`

| Ciclo | Período |
|-------|---------|
| `monthly` | `+ 1 mês` |
| `quarterly` | `+ 3 meses` |
| `semiannual` | `+ 6 meses` |
| `annual` | `+ 1 ano` |

#### Resposta `201 Created`

```json
{
  "data": {
    "id": 4,
    "status": "trialing",
    "auto_renew": true,
    "current_period_start": "2026-02-24T00:00:00.000000Z",
    "current_period_end": "2026-03-24T00:00:00.000000Z",
    "trial_ends_at": "2026-03-10T00:00:00.000000Z",
    "canceled_at": null,
    "cancel_at": null,
    "plan": { ... }
  }
}
```

#### Respostas de Erro

| Status | Cenário |
|--------|---------|
| `401` | Não autenticado |
| `422` | `plan_id` inválido ou plano inativo |
| `422` | Usuário já tem assinatura ativa |

---

## Endpoint 4 — Trocar de Plano

### `PATCH /api/v1/subscription/plan`

Muda o plano da assinatura ativa. A troca tem efeito imediato — o novo `current_period_end` é recalculado a partir de `current_period_start` original (não reinicia o período).

#### Request Body

```json
{ "plan_id": 2 }
```

| Campo | Tipo | Regra |
|-------|------|-------|
| `plan_id` | integer | Obrigatório. Deve existir e ser `is_active = true`. |

#### Regras de Negócio

| Regra | Detalhe |
|-------|---------|
| Sem assinatura ativa | Se o usuário não tem assinatura `active` ou `trialing`, retorna `422` com "No active subscription found." |
| Mesmo plano | Se `plan_id` é igual ao plano atual, retorna `422` com "Already subscribed to this plan." |
| Efeito imediato | `plan_id` atualizado imediatamente. `current_period_end` preservado. |

#### Resposta `200 OK`

```json
{
  "data": {
    "id": 3,
    "status": "active",
    "plan": {
      "id": 2,
      "name": "Pro",
      ...
    }
  }
}
```

#### Respostas de Erro

| Status | Cenário |
|--------|---------|
| `401` | Não autenticado |
| `422` | Plano inválido ou inativo |
| `422` | Sem assinatura ativa |
| `422` | Já está no plano solicitado |

---

## Endpoint 5 — Cancelar Assinatura

### `DELETE /api/v1/subscription`

Cancela a assinatura ativa. O acesso continua até o fim do período corrente (`current_period_end`).

#### Regras de Negócio

| Regra | Detalhe |
|-------|---------|
| Sem assinatura ativa | Retorna `422` com "No active subscription found." |
| Cancelamento | `status = canceled`, `canceled_at = now()`, `cancel_at = current_period_end`, `auto_renew = false` |
| Idempotência | Assinatura já cancelada retorna `422` (status `canceled` não é `active`/`trialing`) |

#### Resposta `200 OK`

```json
{
  "data": {
    "id": 3,
    "status": "canceled",
    "canceled_at": "2026-02-24T00:00:00.000000Z",
    "cancel_at": "2026-03-01T00:00:00.000000Z",
    "auto_renew": false,
    "plan": { ... }
  }
}
```

#### Respostas de Erro

| Status | Cenário |
|--------|---------|
| `401` | Não autenticado |
| `422` | Sem assinatura ativa para cancelar |

---

## Atualização no `SubscriptionRepositoryInterface`

Adicionar método para busca com relações:

```php
public function findActiveForUserWithPlan(int $userId): ?Subscription;
```

---

## Testes

| Cenário | Endpoint |
|---------|----------|
| Retorna planos ativos; exclui inativos | GET plans |
| Retorna assinatura ativa com plano embarcado | GET subscription |
| Retorna 404 se usuário sem assinatura | GET subscription |
| Contrata plano sem trial → status `active` | POST |
| Contrata plano com trial → status `trialing` + `trial_ends_at` | POST |
| Retorna 422 se já tem assinatura ativa | POST |
| Retorna 422 para plano inativo | POST |
| Troca de plano com sucesso | PATCH plan |
| Retorna 422 se sem assinatura ativa | PATCH plan |
| Retorna 422 se mesmo plano | PATCH plan |
| Cancela assinatura → `canceled` + `cancel_at` preenchido | DELETE |
| Retorna 422 se sem assinatura ativa para cancelar | DELETE |
| Todos os endpoints requerem autenticação | todos |

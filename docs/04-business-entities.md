# Сущности и связи

## Основные сущности

- User — пользователь CRM.
- Invite — приглашение.
- Client — клиент.
- Counterparty — контрагент.
- CounterpartyContact — контактное лицо контрагента.
- Deal — сделка.
- Task — задача.
- Document — договор или другой документ.
- LeadSubmission — исходная заявка с лендинга.
- ActivityLog — история изменений.
- Notification — уведомление.

## Связи

```text
User 1 ──── много Deal
User 1 ──── много Task

Client 1 ──── много Deal
Client 1 ──── много Document
Client 1 ──── много Task

Counterparty 1 ──── много CounterpartyContact
Counterparty 1 ──── много Deal
Counterparty 1 ──── много Document
Counterparty 1 ──── много Task

Deal 1 ──── 1 Client
Deal 1 ──── 1 Counterparty
Deal 1 ──── много Task
Deal 1 ──── много Document

Deal 1 ──── много ActivityLog
Client 1 ──── много ActivityLog
Counterparty 1 ──── много ActivityLog
```

## Служебные связи

- `Client` ссылается сам на себя (`merged_into_id`): у влитого дубля указано,
  в какого клиента он влит; `merged_by` — пользователь, выполнивший
  слияние (`docs/18-deduplication.md`).
- `ActivityLog` — универсальная запись истории с полиморфной связью
  (тип и id сущности) и необязательным автором (`user_id`, пусто для
  системных действий). Поля: событие (`created`, `updated`, `merged` и
  т. д.), изменения в JSON (для `updated` — по каждому полю старое и новое
  значение), время. Подключается трейтом `LogsActivity` к любой модели
  (сейчас `Client`; в Фазах 3–4 — контрагенты и сделки). Записи только
  добавляются, из интерфейса не редактируются и не удаляются.

## Важное правило

Сделка одновременно является мероприятием.
Отдельная сущность Event не создаётся.
# Laravel Code Quality Rules

**Version:** 1.0

**Purpose:** Define a consistent coding standard and architecture for all Laravel projects. Every developer must follow these rules to ensure clean, maintainable, scalable, and high-quality code.

> **How this applies in `schweb`:** These rules are the standing standard for all code written in this repo. The web CMS is Inertia-based and follows the thin **Controller → Service → Model** flow documented in [DEVELOPING_CRUDS.md](DEVELOPING_CRUDS.md) (which is the concrete expression of Parts 1). **Part 2 (Action + Pipeline)** applies when building a dedicated **API feature** — use it for API endpoints, not for the existing Inertia CRUD controllers.

---

# Part 1 - Backend Development Rules

## 1. Core Principles

Every developer must follow:

* SOLID Principles
* DRY (Don't Repeat Yourself)
* KISS (Keep It Simple)
* YAGNI (You Aren't Gonna Need It)
* Separation of Concerns
* Clean Code principles

---

## 2. Single Responsibility

* Every class must have a single responsibility.
* Every method must perform only one task.
* Keep methods short and readable.
* Extract repeated logic into reusable methods.

Good

```php
calculateTotal()
createInvoice()
sendNotification()
```

Bad

```php
createInvoiceAndSendNotificationAndUpdateStock()
```

---

## 3. Controllers

Controllers should only:

* Receive requests
* Validate requests
* Authorize requests
* Call Services or Actions
* Return responses

Controllers must never contain:

* Business logic
* Database queries
* File upload logic
* Payment logic
* Complex conditions

Controllers should always remain thin.

---

## 4. Services

Services should contain reusable business logic.

Rules

* One service = One business responsibility
* Never return HTTP responses
* Throw exceptions when necessary
* Keep services reusable

---

## 5. Models

Models should only contain:

* Relationships
* Query Scopes
* Accessors
* Mutators

Models must never contain:

* Business workflows
* API calls
* Email logic
* File upload logic

---

## 6. Reusable Code

Avoid duplicate code.

Reuse logic using:

* Private methods
* Traits
* Services

Traits should only contain reusable functionality.

Example

```php
HasFileUpload
HasActivityLog
HasAuditLog
```

---

## 7. Method Naming

Method names should be:

* Short
* Clear
* Action-oriented

Good

```php
store()
update()
delete()
approve()
assign()
calculate()
sync()
upload()
```

Bad

```php
storeUserAndGenerateInvoiceAndSendMail()
```

---

## 8. Variable Naming

Use meaningful names.

Good

```php
$totalAmount
$currentUser
$studentFee
```

Bad

```php
$data
$temp
$x
$value1
```

---

## 9. Laravel First Development

Always use Laravel features before native PHP functions whenever applicable.

Prefer

```php
Collection
Str
Arr
Carbon
Storage
Http
Cache
config()
now()
today()
filled()
blank()
data_get()
```

Avoid directly using native PHP functions when Laravel provides a better alternative.

Example

Instead of

```php
in_array()
array_map()
array_filter()
array_merge()
array_key_exists()
date()
strtotime()
file_get_contents()
```

Use Laravel Collections, Arr, Str, Carbon, Storage, and Http Client.

---

## 10. Validation

* Always use Form Requests.
* Never validate inside Controllers.
* Keep validation rules reusable.

---

## 11. Authorization

Always use:

* Policies
* Gates
* Middleware

Never place authorization inside business logic.

---

## 12. Database

Always

* Use Eloquent
* Use Query Scopes
* Use Transactions
* Use Eager Loading

Never

* Query inside loops
* Create N+1 queries
* Duplicate queries

---

## 13. Transactions

Use transactions whenever multiple database operations must succeed together.

```php
DB::transaction()
```

---

## 14. Exception Handling

* Throw exceptions.
* Handle exceptions globally.
* Never expose internal exception messages.

---

## 15. Logging

Never commit

```php
dd()
dump()
print_r()
var_dump()
```

Use

```php
Log::info()
Log::warning()
Log::error()
```

---

## 16. Queue & Events

Use Jobs for:

* Emails
* Notifications
* Reports
* Image processing
* Heavy background tasks

Use Events for:

* Activity logs
* Audit logs
* Notifications
* Business events

---

## 17. Security

Always

* Validate input
* Authorize requests
* Hash passwords
* Protect against Mass Assignment
* Sanitize uploaded files

Never trust client input.

---

## 18. Performance

* Always eager load relationships.
* Use pagination for large datasets.
* Use chunk(), cursor(), or lazy() where appropriate.
* Cache frequently accessed data.

---

## 19. Testing

Every feature should include:

* Feature Tests
* Unit Tests
* Validation Tests
* Authorization Tests

---

## 20. Code Review Checklist

Before merging:

* No business logic in Controllers.
* No duplicate code.
* Methods are short.
* Classes have one responsibility.
* Proper naming is used.
* Laravel helpers are preferred over native PHP functions where appropriate.
* No N+1 queries.
* No debug statements.
* Tests pass.
* Laravel Pint passes.
* PHPStan/Larastan passes.

---

# Part 2 - API Development Rules (Action + Pipeline)

## 1. API Architecture

Every API must follow the same architecture.

```text
Request
    ↓
Controller
    ↓
Action
    ↓
Pipeline
    ↓
Pipeline Steps
    ↓
Service
    ↓
Repository
    ↓
Model
```

Business logic belongs inside Pipeline Steps or Services.

---

## 2. Controller Rules

Controllers should only:

* Validate requests
* Authorize requests
* Call Actions
* Return API Resources

Nothing else.

---

## 3. Action Rules

Each API feature must have one Action.

Responsibilities

* Receive validated data
* Build DTO or Context
* Execute Pipeline
* Return result

Actions must never contain business logic.

Example

```php
CreateStudentAction
UpdateStudentAction
ApproveInvoiceAction
```

---

## 4. Pipeline Rules

A Pipeline manages the feature workflow.

Rules

* One Action = One Pipeline
* Pipeline controls execution order
* Pipeline contains no business logic
* Pass data using a DTO or Context object
* Stop execution on failure

---

## 5. Pipeline Step Rules

Each Step must perform only one business rule.

Examples

```php
ValidateStudentStep
CheckDuplicateStep
CalculateFeeStep
GenerateInvoiceStep
CreatePaymentStep
SendNotificationStep
```

Rules

* One Step = One Responsibility
* Independent
* Reusable
* Testable
* Throw exceptions on failure

---

## 6. DTO / Context Rules

* Never pass raw arrays between Steps.
* Always use a DTO or Context object.
* Keep the DTO strongly typed.
* Do not store business logic inside the DTO.

---

## 7. Service Rules

Use Services for reusable business operations shared across multiple features.

Do not duplicate business logic inside multiple Pipeline Steps.

---

## 8. Transactions

Wrap the Pipeline execution in a transaction when multiple database changes must succeed together.

---

## 9. Events & Jobs

After successful execution:

* Dispatch Events for business notifications.
* Dispatch Jobs for heavy background work.

Do not perform heavy operations inside Pipeline Steps.

---

## 10. API Response

Always return:

* API Resources
* Consistent response structure

Example

```json
{
    "success": true,
    "message": "Student created successfully.",
    "data": {}
}
```

---

## 11. API Development Checklist

Before merging:

* One API = One Action.
* One Action = One Pipeline.
* One Pipeline Step = One Business Rule.
* Controller is thin.
* Business logic is not inside Controller or Action.
* DTO or Context is used.
* Services are reused where appropriate.
* Transactions are implemented where required.
* Events and Jobs are used appropriately.
* API Resources are returned.
* Tests pass.

---

# Development Principles

Every developer must remember:

* Write readable code before clever code.
* Keep code modular.
* Prefer Laravel features over custom implementations.
* Reuse existing code before writing new code.
* One Class = One Responsibility.
* One Method = One Responsibility.
* One Pipeline Step = One Business Rule.
* Keep methods, classes, and files small.
* Follow consistent naming conventions.
* Always optimize for maintainability.
* Code should be easy for another developer to understand within a few minutes.

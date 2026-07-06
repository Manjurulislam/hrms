# Mobile Employee API Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a stateless, Sanctum-token REST API exposing the employee panel (auth, attendance, leave, notices) for a native mobile app.

**Architecture:** Single-action invokable controllers under `App\Http\Controllers\Api\<Domain>\` delegate to the existing service layer and return JSON through a ported `ResponseHandler` facade. Routing uses `apiPrefix: 'v1'` with per-domain route files, matching the hibachef project's conventions.

**Tech Stack:** Laravel 12, PHP 8.2, Laravel Sanctum (personal access tokens), PHPUnit (sqlite `:memory:`).

## Global Constraints

- API prefix is `v1` (endpoints resolve at `/v1/...`), set via `apiPrefix: 'v1'` — NOT `/api/v1`.
- Success envelope: `{ "success": true, "message": string, "data": ... }` (data omitted when null).
- Error envelope: `{ "success": false, "message": string, "error": { "code": string, "details": mixed } }`.
- All responses go through the `ResponseHandler` facade — never `response()->json(...)` directly in controllers.
- Controllers are single-action (`__invoke`), thin, wrapped in try/catch with `Log::error` on failure.
- Every protected controller resolves `$request->user()->employee`; missing → `ResponseHandler::errorForbidden()`.
- FormRequest rules use array syntax (project rule).
- Mobile check-in never applies office-hours/office-network gates; it keeps only duplicate-session, on-leave, and daily max-sessions guards; `lat`/`long` are stored, never gating.

---

### Task 1: Port the ResponseHandler facade

**Files:**
- Create: `app/Services/Lock/ServiceApiResponse.php`
- Create: `app/Facades/ResponseHandler.php`
- Modify: `app/Providers/AppServiceProvider.php` (register `api-response` singleton)
- Test: `tests/Unit/ServiceApiResponseTest.php`

**Interfaces:**
- Produces: `ResponseHandler::success(mixed $data=[], ?string $message=null, int $code=200): JsonResponse`, `created($data=null, $message)`, `respondWithResource(JsonResource, $status=200, $message)`, `respondWithPagination(JsonResource, $status=200, $message)`, `respondWithMessage(string, $status=200)`, `noContent()`, `errorValidation(array $errors, $message)`, `errorForbidden($message)`, `errorUnauthorized($message)`, `errorNotFound($message)`, `errorConflict($message, $details)`, `errorWrongArgs($message, $status)`, `errorInternalError($message)`, `customError($message, $code, $status, $details)`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Unit/ServiceApiResponseTest.php
namespace Tests\Unit;

use App\Services\Lock\ServiceApiResponse;
use Tests\TestCase;

class ServiceApiResponseTest extends TestCase
{
    public function test_success_wraps_data_in_envelope(): void
    {
        $res  = (new ServiceApiResponse())->success(['a' => 1], 'ok');
        $body = $res->getData(true);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertTrue($body['success']);
        $this->assertSame('ok', $body['message']);
        $this->assertSame(['a' => 1], $body['data']);
    }

    public function test_error_validation_uses_error_block(): void
    {
        $res  = (new ServiceApiResponse())->errorValidation(['email' => ['required']]);
        $body = $res->getData(true);

        $this->assertSame(422, $res->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertSame('GEN-VALIDATION', $body['error']['code']);
        $this->assertSame(['email' => ['required']], $body['error']['details']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ServiceApiResponseTest`
Expected: FAIL — class `App\Services\Lock\ServiceApiResponse` not found.

- [ ] **Step 3: Create `ServiceApiResponse`**

Copy the implementation from hibachef verbatim (`/home/dev/Projects/hibachef/app/Services/Lock/ServiceApiResponse.php`) into `app/Services/Lock/ServiceApiResponse.php`. It defines the `GEN-*` constants and all methods listed under Interfaces above (success/created/respondWith*/error*/customError/noContent).

- [ ] **Step 4: Create the facade**

```php
<?php
// app/Facades/ResponseHandler.php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class ResponseHandler extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'api-response';
    }
}
```

- [ ] **Step 5: Bind the singleton**

In `app/Providers/AppServiceProvider.php`, inside `register()`:

```php
$this->app->singleton('api-response', fn () => new \App\Services\Lock\ServiceApiResponse());
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --filter=ServiceApiResponseTest`
Expected: PASS (2 tests).

- [ ] **Step 7: Commit**

```bash
git add app/Services/Lock/ServiceApiResponse.php app/Facades/ResponseHandler.php app/Providers/AppServiceProvider.php tests/Unit/ServiceApiResponseTest.php
git commit -m "feat(api): port ResponseHandler facade for JSON responses"
```

---

### Task 2: Wire API routing, Sanctum tokens, and JSON exception handling

**Files:**
- Modify: `app/Models/User.php` (add `HasApiTokens`)
- Create: `routes/api.php`
- Create: `routes/apis/auth-routes.php`, `attendance-routes.php`, `leave-routes.php`, `notice-routes.php` (empty groups for now)
- Modify: `bootstrap/app.php` (register api routing + exception → JSON mapping)
- Test: `tests/Feature/Api/ApiInfraTest.php`

**Interfaces:**
- Produces: routes under `/v1`; unauthenticated JSON `401`; `Illuminate\Validation\ValidationException` → `422` `GEN-VALIDATION`; `ModelNotFoundException`/`NotFoundHttpException` → `404` `GEN-NOT-FOUND`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Api/ApiInfraTest.php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiInfraTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_route_without_token_returns_json_401(): void
    {
        $res = $this->getJson('/v1/auth/me');

        $res->assertStatus(401);
        $res->assertJson(['success' => false]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ApiInfraTest`
Expected: FAIL — 404 (route not registered) instead of 401.

- [ ] **Step 3: Add `HasApiTokens` to User**

In `app/Models/User.php`: add `use Laravel\Sanctum\HasApiTokens;` import and add `HasApiTokens` to the class's `use` traits line.

- [ ] **Step 4: Create the per-domain route stubs**

```php
<?php
// routes/apis/auth-routes.php
use Illuminate\Support\Facades\Route;
// filled in Task 4
```

Create `routes/apis/attendance-routes.php`, `routes/apis/leave-routes.php`, `routes/apis/notice-routes.php` with the same header comment (filled in later tasks).

- [ ] **Step 5: Create `routes/api.php`**

```php
<?php

require __DIR__ . '/apis/auth-routes.php';
require __DIR__ . '/apis/attendance-routes.php';
require __DIR__ . '/apis/leave-routes.php';
require __DIR__ . '/apis/notice-routes.php';
```

- [ ] **Step 6: Register routing + exception mapping in `bootstrap/app.php`**

In `->withRouting(...)` add the `api` file and prefix:

```php
->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    apiPrefix: 'v1',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
)
```

In `->withExceptions(function (Exceptions $exceptions) { ... })` add JSON rendering for API requests:

```php
$exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
    if (! $request->is('v1/*')) {
        return null; // let web handling proceed
    }

    $api = app('api-response');

    if ($e instanceof \Illuminate\Auth\AuthenticationException) {
        return $api->errorUnauthorized();
    }
    if ($e instanceof \Illuminate\Validation\ValidationException) {
        return $api->errorValidation($e->errors(), $e->getMessage());
    }
    if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
        || $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
        return $api->errorNotFound();
    }
    if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
        return $api->errorForbidden();
    }
    return null;
});
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --filter=ApiInfraTest`
Expected: PASS. (`/v1/auth/me` exists only after Task 4; until then this asserts 401 via the `auth:sanctum` group added in Task 4. If running Task 2 alone, temporarily assert 404→ but prefer running Task 2+4 together. See note.)

> Note: the 401 assertion needs the `auth:sanctum` route from Task 4. If executing strictly task-by-task, change this test to hit a throwaway `Route::middleware('auth:sanctum')->get('/v1/_ping', fn () => 1)` added in Step 5, then remove it in Task 4. Otherwise run Tasks 2 and 4 as a pair.

- [ ] **Step 8: Commit**

```bash
git add app/Models/User.php routes/api.php routes/apis bootstrap/app.php tests/Feature/Api/ApiInfraTest.php
git commit -m "feat(api): register v1 routing, sanctum tokens, JSON exception mapping"
```

---

### Task 3: Test factories

**Files:**
- Create: `database/factories/CompanyFactory.php`, `EmployeeFactory.php`, `LeaveTypeFactory.php`, `LeaveRequestFactory.php`, `NoticeFactory.php`
- Modify: `app/Models/{Company,Employee,LeaveType,LeaveRequest,Notice}.php` (add `use HasFactory;` where missing)
- Test: `tests/Feature/Api/FactorySmokeTest.php`

**Interfaces:**
- Produces: `Employee::factory()`, `Company::factory()`, `LeaveType::factory()`, `LeaveRequest::factory()`, `Notice::factory()`, and `User::factory()` (exists). Tests link a `User` to an `Employee` via `User::factory()->create(['employee_id' => $employee->id])`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Api/FactorySmokeTest.php
namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactorySmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_and_user_link(): void
    {
        $employee = Employee::factory()->create();
        $user     = User::factory()->create(['employee_id' => $employee->id]);

        $this->assertSame($employee->id, $user->employee->id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FactorySmokeTest`
Expected: FAIL — `Employee::factory()` not found.

- [ ] **Step 3: Create the factories**

```php
<?php
// database/factories/CompanyFactory.php
namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return ['name' => $this->faker->company()];
    }
}
```

```php
<?php
// database/factories/EmployeeFactory.php
namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'id_no'      => $this->faker->unique()->numerify('EMP-#####'),
            'first_name' => $this->faker->firstName(),
            'last_name'  => $this->faker->lastName(),
            'email'      => $this->faker->unique()->safeEmail(),
            'company_id' => Company::factory(),
            'status'     => true,
        ];
    }
}
```

```php
<?php
// database/factories/LeaveTypeFactory.php
namespace Database\Factories;

use App\Models\Company;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'name'         => $this->faker->randomElement(['Casual', 'Sick', 'Annual']),
            'max_per_year' => 12,
            'company_id'   => Company::factory(),
            'status'       => true,
        ];
    }
}
```

```php
<?php
// database/factories/LeaveRequestFactory.php
namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        return [
            'title'         => $this->faker->sentence(3),
            'notes'         => $this->faker->sentence(),
            'total_days'    => 1,
            'company_id'    => Company::factory(),
            'employee_id'   => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'status'        => 'pending',
            'started_at'    => now()->addDay()->toDateString(),
            'ended_at'      => now()->addDay()->toDateString(),
        ];
    }
}
```

```php
<?php
// database/factories/NoticeFactory.php
namespace Database\Factories;

use App\Models\Company;
use App\Models\Notice;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoticeFactory extends Factory
{
    protected $model = Notice::class;

    public function definition(): array
    {
        return [
            'title'        => $this->faker->sentence(4),
            'description'  => $this->faker->paragraph(),
            'company_id'   => Company::factory(),
            'created_by'   => null,
            'published_at' => now()->subDay(),
            'status'       => true,
        ];
    }
}
```

- [ ] **Step 4: Ensure models use `HasFactory`**

For each of `Company`, `Employee`, `LeaveType`, `LeaveRequest`, `Notice`: verify the model has `use Illuminate\Database\Eloquent\Factories\HasFactory;` and `use HasFactory;` in the class. Add where missing.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=FactorySmokeTest`
Expected: PASS. If a NOT NULL column errors, add that column to the relevant factory `definition()` with a sensible value.

- [ ] **Step 6: Commit**

```bash
git add database/factories app/Models
git commit -m "test(api): add factories for employee/company/leave/notice"
```

---

### Task 4: Auth endpoints + EmployeeResource

**Files:**
- Create: `app/Http/Resources/Api/EmployeeResource.php`
- Create: `app/Http/Controllers/Api/Auth/LoginController.php`, `LogoutController.php`, `MeController.php`
- Create: `app/Http/Requests/Api/ApiLoginRequest.php`
- Modify: `routes/apis/auth-routes.php`
- Test: `tests/Feature/Api/AuthApiTest.php`

**Interfaces:**
- Consumes: `ResponseHandler` (Task 1), `User`/`Employee` factories (Task 3).
- Produces: `POST /v1/auth/login` → `{token, employee}`; `POST /v1/auth/logout`; `GET /v1/auth/me`. `EmployeeResource` fields: `id, id_no, first_name, last_name, full_name, email, phone, company, department, designation`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Api/AuthApiTest.php
namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(string $password = 'secret123'): User
    {
        $employee = Employee::factory()->create();
        return User::factory()->create([
            'employee_id' => $employee->id,
            'password'    => Hash::make($password),
        ]);
    }

    public function test_login_returns_token(): void
    {
        $user = $this->employeeUser();

        $res = $this->postJson('/v1/auth/login', [
            'email'       => $user->email,
            'password'    => 'secret123',
            'device_name' => 'pixel-8',
        ]);

        $res->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['token', 'employee' => ['id', 'full_name']]]);
    }

    public function test_login_with_bad_password_fails(): void
    {
        $user = $this->employeeUser();

        $this->postJson('/v1/auth/login', [
            'email' => $user->email, 'password' => 'wrong', 'device_name' => 'x',
        ])->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_me_requires_token_and_returns_profile(): void
    {
        $user = $this->employeeUser();
        $token = $user->createToken('x')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/v1/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $user->employee->id);
    }

    public function test_logout_revokes_token(): void
    {
        $user  = $this->employeeUser();
        $token = $user->createToken('x')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/v1/auth/logout')->assertStatus(200);

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AuthApiTest`
Expected: FAIL — routes/controllers missing.

- [ ] **Step 3: Create `EmployeeResource`**

```php
<?php
// app/Http/Resources/Api/EmployeeResource.php
namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'id_no'       => $this->id_no,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'full_name'   => $this->full_name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'company'     => $this->whenLoaded('company', fn () => $this->company?->name),
            'department'  => $this->whenLoaded('department', fn () => $this->department?->name),
            'designation' => $this->whenLoaded('designation', fn () => $this->designation?->title),
        ];
    }
}
```

- [ ] **Step 4: Create `ApiLoginRequest`**

```php
<?php
// app/Http/Requests/Api/ApiLoginRequest.php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ApiLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }
}
```

- [ ] **Step 5: Create the three controllers**

```php
<?php
// app/Http/Controllers/Api/Auth/LoginController.php
namespace App\Http\Controllers\Api\Auth;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiLoginRequest;
use App\Http\Resources\Api\EmployeeResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __invoke(ApiLoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return ResponseHandler::errorValidation(
                ['email' => ['These credentials do not match our records.']]
            );
        }

        if (! $user->employee) {
            return ResponseHandler::errorForbidden('No employee profile linked to this account.');
        }

        $token = $user->createToken($request->device_name)->plainTextToken;
        $user->employee->load(['company', 'department', 'designation']);

        return ResponseHandler::success([
            'token'    => $token,
            'employee' => new EmployeeResource($user->employee),
        ], 'Logged in successfully.');
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Auth/LogoutController.php
namespace App\Http\Controllers\Api\Auth;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ResponseHandler::respondWithMessage('Logged out successfully.');
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Auth/MeController.php
namespace App\Http\Controllers\Api\Auth;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\EmployeeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return ResponseHandler::errorForbidden('No employee profile linked to this account.');
        }

        $employee->load(['company', 'department', 'designation']);

        return ResponseHandler::respondWithResource(new EmployeeResource($employee));
    }
}
```

- [ ] **Step 6: Fill `routes/apis/auth-routes.php`**

```php
<?php
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', LoginController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', LogoutController::class);
        Route::get('me', MeController::class);
    });
});
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --filter=AuthApiTest`
Expected: PASS (4 tests).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Resources/Api/EmployeeResource.php app/Http/Requests/Api/ApiLoginRequest.php app/Http/Controllers/Api/Auth routes/apis/auth-routes.php tests/Feature/Api/AuthApiTest.php
git commit -m "feat(api): auth endpoints (login/logout/me) with sanctum tokens"
```

---

### Task 5: Attendance endpoints

**Files:**
- Create: `app/Http/Requests/Api/ApiCheckInRequest.php`, `ApiCheckOutRequest.php`
- Create: `app/Http/Controllers/Api/Attendance/{TodayStatusController,CheckInController,CheckOutController,MonthlyController,RecordsController}.php`
- Modify: `routes/apis/attendance-routes.php`
- Test: `tests/Feature/Api/AttendanceApiTest.php`

**Interfaces:**
- Consumes: `AttendanceService::{getTodayCompleteData,checkIn,checkOut,getMonthlyData,getAttendanceRecords}`, `ResponseHandler`, `AttendanceValidation` trait (`getTodaySessionCount`, `companySetting`, `resolvedClientIp`).
- Produces: `GET /v1/attendance/today`, `POST /v1/attendance/check-in`, `POST /v1/attendance/check-out`, `GET /v1/attendance/monthly?month=YYYY-MM`, `GET /v1/attendance/records?months=N`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Api/AttendanceApiTest.php
namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingEmployee(): Employee
    {
        $employee = Employee::factory()->create();
        $user     = User::factory()->create(['employee_id' => $employee->id]);
        Sanctum::actingAs($user);
        return $employee;
    }

    public function test_check_in_creates_session_and_stores_location(): void
    {
        $this->actingEmployee();

        $res = $this->postJson('/v1/attendance/check-in', [
            'lat' => 23.7808, 'long' => 90.4142, 'note' => 'field visit',
        ]);

        $res->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('attendance_sessions', [
            'check_in_lat' => 23.78080000, 'status' => 'active',
        ]);
    }

    public function test_today_status_returns_data(): void
    {
        $this->actingEmployee();
        $this->getJson('/v1/attendance/today')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['sessions', 'summary']]);
    }

    public function test_check_in_requires_token(): void
    {
        $this->postJson('/v1/attendance/check-in', [])->assertStatus(401);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AttendanceApiTest`
Expected: FAIL — routes/controllers missing.

- [ ] **Step 3: Create `ApiCheckInRequest`**

```php
<?php
// app/Http/Requests/Api/ApiCheckInRequest.php
namespace App\Http\Requests\Api;

use App\Traits\AttendanceValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ApiCheckInRequest extends FormRequest
{
    use AttendanceValidation;

    public function authorize(): bool
    {
        return (bool) $this->getEmployee();
    }

    public function rules(): array
    {
        return [
            'lat'      => ['nullable', 'numeric', 'between:-90,90'],
            'long'     => ['nullable', 'numeric', 'between:-180,180'],
            'note'     => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }

    // Mobile keeps ONLY the daily max-sessions guard; no office-hours/network gates.
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $employee = $this->getEmployee();
            if (! $employee) {
                return;
            }
            $max = $this->companySetting($employee->company, 'max_sessions');
            if ($this->getTodaySessionCount($employee) >= $max) {
                $validator->errors()->add('session', "Maximum {$max} sessions allowed per day.");
            }
        });
    }

    public function getSanitizedData(): array
    {
        return [
            'location' => $this->input('location', 'office'),
            'lat'      => $this->input('lat'),
            'long'     => $this->input('long'),
            'note'     => $this->input('note'),
        ];
    }
}
```

- [ ] **Step 4: Create `ApiCheckOutRequest`**

```php
<?php
// app/Http/Requests/Api/ApiCheckOutRequest.php
namespace App\Http\Requests\Api;

use App\Traits\AttendanceValidation;
use Illuminate\Foundation\Http\FormRequest;

class ApiCheckOutRequest extends FormRequest
{
    use AttendanceValidation;

    public function authorize(): bool
    {
        return (bool) $this->getEmployee();
    }

    public function rules(): array
    {
        return [
            'lat'      => ['nullable', 'numeric', 'between:-90,90'],
            'long'     => ['nullable', 'numeric', 'between:-180,180'],
            'note'     => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function getSanitizedData(): array
    {
        return [
            'location' => $this->input('location', 'office'),
            'lat'      => $this->input('lat'),
            'long'     => $this->input('long'),
            'note'     => $this->input('note'),
        ];
    }
}
```

- [ ] **Step 5: Create the controllers**

```php
<?php
// app/Http/Controllers/Api/Attendance/CheckInController.php
namespace App\Http\Controllers\Api\Attendance;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiCheckInRequest;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;

class CheckInController extends Controller
{
    public function __construct(private readonly AttendanceService $service) {}

    public function __invoke(ApiCheckInRequest $request): JsonResponse
    {
        $result = $this->service->checkIn(
            $request->user()->employee,
            $request->resolvedClientIp(),
            $request->getSanitizedData()
        );

        if (! $result['success']) {
            return ResponseHandler::errorConflict($result['message']);
        }

        return ResponseHandler::created(
            ['session' => $result['session'] ?? null],
            $result['message']
        );
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Attendance/CheckOutController.php
namespace App\Http\Controllers\Api\Attendance;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiCheckOutRequest;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;

class CheckOutController extends Controller
{
    public function __construct(private readonly AttendanceService $service) {}

    public function __invoke(ApiCheckOutRequest $request): JsonResponse
    {
        $result = $this->service->checkOut(
            $request->user()->employee,
            $request->resolvedClientIp(),
            $request->getSanitizedData()
        );

        if (! $result['success']) {
            return ResponseHandler::errorConflict($result['message']);
        }

        return ResponseHandler::success($result, $result['message']);
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Attendance/TodayStatusController.php
namespace App\Http\Controllers\Api\Attendance;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodayStatusController extends Controller
{
    public function __construct(private readonly AttendanceService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        return ResponseHandler::success(
            $this->service->getTodayCompleteData($request->user()->employee)
        );
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Attendance/MonthlyController.php
namespace App\Http\Controllers\Api\Attendance;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonthlyController extends Controller
{
    public function __construct(private readonly AttendanceService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['month' => ['required', 'date_format:Y-m']]);
        [$year, $month] = explode('-', $request->query('month'));

        return ResponseHandler::success(
            $this->service->getMonthlyData($request->user()->employee, (int) $year, (int) $month)
        );
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Attendance/RecordsController.php
namespace App\Http\Controllers\Api\Attendance;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecordsController extends Controller
{
    public function __construct(private readonly AttendanceService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        $months = (int) $request->query('months', 3);

        return ResponseHandler::success(
            $this->service->getAttendanceRecords($request->user()->employee, $months)
        );
    }
}
```

- [ ] **Step 6: Fill `routes/apis/attendance-routes.php`**

```php
<?php
use App\Http\Controllers\Api\Attendance\CheckInController;
use App\Http\Controllers\Api\Attendance\CheckOutController;
use App\Http\Controllers\Api\Attendance\MonthlyController;
use App\Http\Controllers\Api\Attendance\RecordsController;
use App\Http\Controllers\Api\Attendance\TodayStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('attendance')->group(function () {
    Route::get('today', TodayStatusController::class);
    Route::post('check-in', CheckInController::class);
    Route::post('check-out', CheckOutController::class);
    Route::get('monthly', MonthlyController::class);
    Route::get('records', RecordsController::class);
});
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --filter=AttendanceApiTest`
Expected: PASS (3 tests).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Requests/Api/ApiCheckInRequest.php app/Http/Requests/Api/ApiCheckOutRequest.php app/Http/Controllers/Api/Attendance routes/apis/attendance-routes.php tests/Feature/Api/AttendanceApiTest.php
git commit -m "feat(api): attendance endpoints (today/check-in/out/monthly/records)"
```

---

### Task 6: Leave self-service endpoints

**Files:**
- Create: `app/Http/Resources/Api/LeaveResource.php`
- Create: `app/Http/Controllers/Api/Leave/{LeaveListController,LeaveTypeListController,LeaveBalanceController,ApplyLeaveController,CancelLeaveController}.php`
- Create: `app/Http/Requests/Api/ApiLeaveStoreRequest.php`
- Modify: `routes/apis/leave-routes.php`
- Test: `tests/Feature/Api/LeaveApiTest.php`

**Interfaces:**
- Consumes: `LeaveRequestService::{list(Request):array, store(Employee,array):LeaveRequest|string, cancel(LeaveRequest):bool|string, getBalances(Employee,int):array}`, `LeaveType`, `ResponseHandler`.
- Produces: `GET /v1/leave`, `GET /v1/leave/types`, `GET /v1/leave/balance`, `POST /v1/leave`, `POST /v1/leave/{leaveRequest}/cancel`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Api/LeaveApiTest.php
namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingEmployee(): Employee
    {
        $company  = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs(User::factory()->create(['employee_id' => $employee->id]));
        return $employee;
    }

    public function test_leave_types_are_company_scoped(): void
    {
        $employee = $this->actingEmployee();
        LeaveType::factory()->create(['company_id' => $employee->company_id, 'name' => 'Casual']);
        LeaveType::factory()->create(['company_id' => 9999, 'name' => 'Other']);

        $this->getJson('/v1/leave/types')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Casual'])
            ->assertJsonMissing(['name' => 'Other']);
    }

    public function test_cancel_rejects_other_employees_request(): void
    {
        $this->actingEmployee();
        $foreign = LeaveRequest::factory()->create(); // different employee

        $this->postJson("/v1/leave/{$foreign->id}/cancel")->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LeaveApiTest`
Expected: FAIL — routes/controllers missing.

- [ ] **Step 3: Create `LeaveResource`**

```php
<?php
// app/Http/Resources/Api/LeaveResource.php
namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'notes'      => $this->notes,
            'total_days' => $this->total_days,
            'status'     => $this->status,
            'started_at' => $this->started_at,
            'ended_at'   => $this->ended_at,
            'leave_type' => $this->whenLoaded('leaveType', fn () => $this->leaveType?->name),
        ];
    }
}
```

- [ ] **Step 4: Create `ApiLeaveStoreRequest`** (mirror the web `LeaveRequestFormRequest` rules)

```php
<?php
// app/Http/Requests/Api/ApiLeaveStoreRequest.php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ApiLeaveStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->employee;
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'title'         => ['required', 'string', 'max:255'],
            'notes'         => ['nullable', 'string', 'max:1000'],
            'started_at'    => ['required', 'date'],
            'ended_at'      => ['required', 'date', 'after_or_equal:started_at'],
        ];
    }
}
```

> Before implementing, open `app/Http/Requests/LeaveRequestFormRequest.php` and reconcile these rules with the web version so the service receives the same shape. Adjust field names if the web request differs.

- [ ] **Step 5: Create the controllers**

```php
<?php
// app/Http/Controllers/Api/Leave/LeaveListController.php
namespace App\Http\Controllers\Api\Leave;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Services\Backend\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveListController extends Controller
{
    public function __construct(private readonly LeaveRequestService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        return ResponseHandler::success($this->service->list($request));
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Leave/LeaveTypeListController.php
namespace App\Http\Controllers\Api\Leave;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $types = LeaveType::where('company_id', $request->user()->employee->company_id)
            ->where('status', true)
            ->get(['id', 'name', 'max_per_year']);

        return ResponseHandler::success($types);
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Leave/LeaveBalanceController.php
namespace App\Http\Controllers\Api\Leave;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Services\Backend\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    public function __construct(private readonly LeaveRequestService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        $year = (int) $request->query('year', now()->year);

        return ResponseHandler::success(
            $this->service->getBalances($request->user()->employee, $year)
        );
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Leave/ApplyLeaveController.php
namespace App\Http\Controllers\Api\Leave;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiLeaveStoreRequest;
use App\Http\Resources\Api\LeaveResource;
use App\Services\Backend\LeaveRequestService;
use Illuminate\Http\JsonResponse;

class ApplyLeaveController extends Controller
{
    public function __construct(private readonly LeaveRequestService $service) {}

    public function __invoke(ApiLeaveStoreRequest $request): JsonResponse
    {
        $result = $this->service->store($request->user()->employee, $request->validated());

        // Service returns a LeaveRequest on success or a string error message.
        if (is_string($result)) {
            return ResponseHandler::errorWrongArgs($result);
        }

        return ResponseHandler::created(
            new LeaveResource($result->load('leaveType')),
            'Leave request submitted.'
        );
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Leave/CancelLeaveController.php
namespace App\Http\Controllers\Api\Leave;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\Backend\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CancelLeaveController extends Controller
{
    public function __construct(private readonly LeaveRequestService $service) {}

    public function __invoke(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        if ($leaveRequest->employee_id !== $request->user()->employee->id) {
            return ResponseHandler::errorForbidden('You cannot cancel this request.');
        }

        $result = $this->service->cancel($leaveRequest);

        if (is_string($result)) {
            return ResponseHandler::errorWrongArgs($result);
        }

        return ResponseHandler::respondWithMessage('Leave request cancelled.');
    }
}
```

- [ ] **Step 6: Fill `routes/apis/leave-routes.php`** (self-service portion)

```php
<?php
use App\Http\Controllers\Api\Leave\ApplyLeaveController;
use App\Http\Controllers\Api\Leave\CancelLeaveController;
use App\Http\Controllers\Api\Leave\LeaveBalanceController;
use App\Http\Controllers\Api\Leave\LeaveListController;
use App\Http\Controllers\Api\Leave\LeaveTypeListController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('leave')->group(function () {
    Route::get('/', LeaveListController::class);
    Route::get('types', LeaveTypeListController::class);
    Route::get('balance', LeaveBalanceController::class);
    Route::post('/', ApplyLeaveController::class);
    Route::post('{leaveRequest}/cancel', CancelLeaveController::class);
    // approval routes appended in Task 7
});
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --filter=LeaveApiTest`
Expected: PASS (2 tests). If `store()`/`cancel()` need extra fields, adjust `ApiLeaveStoreRequest` after reading the web request/service.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Resources/Api/LeaveResource.php app/Http/Requests/Api/ApiLeaveStoreRequest.php app/Http/Controllers/Api/Leave routes/apis/leave-routes.php tests/Feature/Api/LeaveApiTest.php
git commit -m "feat(api): leave self-service endpoints (list/types/balance/apply/cancel)"
```

---

### Task 7: Leave approval endpoints (manager)

**Files:**
- Create: `app/Http/Controllers/Api/Leave/{ApprovalListController,ShowApprovalController,ApproveLeaveController,RejectLeaveController}.php`
- Modify: `routes/apis/leave-routes.php` (append approval routes inside the `leave` group)
- Test: `tests/Feature/Api/LeaveApprovalApiTest.php`

**Interfaces:**
- Consumes: `LeaveApprovalService::{approve(LeaveRequest,Employee,?string):array, reject(LeaveRequest,Employee,?string):array}`, `LeaveRequest` (`current_approver_id`), `ResponseHandler`.
- Produces: `GET /v1/leave/approvals`, `GET /v1/leave/approvals/{leaveRequest}`, `POST /v1/leave/approvals/{leaveRequest}/approve`, `POST /v1/leave/approvals/{leaveRequest}/reject`.
- Authorization rule (from web): the acting employee must be `leaveRequest.current_approver_id`; else `403`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Api/LeaveApprovalApiTest.php
namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveApprovalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_approver_sees_pending_and_can_approve(): void
    {
        $manager = Employee::factory()->create();
        Sanctum::actingAs(User::factory()->create(['employee_id' => $manager->id]));

        $lr = LeaveRequest::factory()->create([
            'current_approver_id' => $manager->id,
            'status'              => 'pending',
        ]);

        $this->getJson('/v1/leave/approvals')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $lr->id]);

        $this->postJson("/v1/leave/approvals/{$lr->id}/approve", ['remarks' => 'ok'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_non_approver_cannot_approve(): void
    {
        $other = Employee::factory()->create();
        Sanctum::actingAs(User::factory()->create(['employee_id' => $other->id]));

        $lr = LeaveRequest::factory()->create([
            'current_approver_id' => Employee::factory()->create()->id,
        ]);

        $this->postJson("/v1/leave/approvals/{$lr->id}/approve", [])->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LeaveApprovalApiTest`
Expected: FAIL — routes/controllers missing.

- [ ] **Step 3: Create the controllers**

```php
<?php
// app/Http/Controllers/Api/Leave/ApprovalListController.php
namespace App\Http\Controllers\Api\Leave;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $items = LeaveRequest::with(['employee:id,first_name,last_name,id_no', 'leaveType:id,name'])
            ->where('current_approver_id', $request->user()->employee->id)
            ->orderByDesc('created_at')
            ->get();

        return ResponseHandler::success($items);
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Leave/ShowApprovalController.php
namespace App\Http\Controllers\Api\Leave;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowApprovalController extends Controller
{
    public function __invoke(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        if ($leaveRequest->current_approver_id !== $request->user()->employee->id) {
            return ResponseHandler::errorForbidden('You are not the current approver.');
        }

        $leaveRequest->load([
            'employee:id,first_name,last_name,id_no',
            'leaveType:id,name',
            'approvals' => fn ($q) => $q->orderBy('created_at'),
            'approvals.approver:id,first_name,last_name',
        ]);

        return ResponseHandler::success($leaveRequest);
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Leave/ApproveLeaveController.php
namespace App\Http\Controllers\Api\Leave;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\Backend\LeaveApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApproveLeaveController extends Controller
{
    public function __construct(private readonly LeaveApprovalService $service) {}

    public function __invoke(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $employee = $request->user()->employee;

        if ($leaveRequest->current_approver_id !== $employee->id) {
            return ResponseHandler::errorForbidden('You are not the current approver.');
        }

        $result = $this->service->approve($leaveRequest, $employee, $request->input('remarks'));

        return ResponseHandler::respondWithMessage($result['message'] ?? 'Leave approved.');
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Leave/RejectLeaveController.php
namespace App\Http\Controllers\Api\Leave;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\Backend\LeaveApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RejectLeaveController extends Controller
{
    public function __construct(private readonly LeaveApprovalService $service) {}

    public function __invoke(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $request->validate(['remarks' => ['required', 'string', 'max:1000']]);

        $employee = $request->user()->employee;

        if ($leaveRequest->current_approver_id !== $employee->id) {
            return ResponseHandler::errorForbidden('You are not the current approver.');
        }

        $result = $this->service->reject($leaveRequest, $employee, $request->input('remarks'));

        return ResponseHandler::respondWithMessage($result['message'] ?? 'Leave rejected.');
    }
}
```

- [ ] **Step 4: Append approval routes** inside the `leave` group in `routes/apis/leave-routes.php`

```php
    Route::prefix('approvals')->group(function () {
        Route::get('/', \App\Http\Controllers\Api\Leave\ApprovalListController::class);
        Route::get('{leaveRequest}', \App\Http\Controllers\Api\Leave\ShowApprovalController::class);
        Route::post('{leaveRequest}/approve', \App\Http\Controllers\Api\Leave\ApproveLeaveController::class);
        Route::post('{leaveRequest}/reject', \App\Http\Controllers\Api\Leave\RejectLeaveController::class);
    });
```

> Route ordering: register `approvals` BEFORE the `{leaveRequest}/cancel` route is fine because `approvals` is a static segment; Laravel matches static segments before the `{leaveRequest}` wildcard. Verify `/v1/leave/approvals` does not get captured by `{leaveRequest}`.

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=LeaveApprovalApiTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Leave routes/apis/leave-routes.php tests/Feature/Api/LeaveApprovalApiTest.php
git commit -m "feat(api): leave approval endpoints (list/show/approve/reject)"
```

---

### Task 8: Notice endpoints

**Files:**
- Create: `app/Http/Resources/Api/NoticeResource.php`
- Create: `app/Http/Controllers/Api/Notice/{NoticeListController,ShowNoticeController}.php`
- Modify: `routes/apis/notice-routes.php`
- Test: `tests/Feature/Api/NoticeApiTest.php`

**Interfaces:**
- Consumes: `NoticeService::employeeNotices(Request, Employee)`, `Notice`, `ResponseHandler`.
- Produces: `GET /v1/notices`, `GET /v1/notices/{notice}`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Api/NoticeApiTest.php
namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\Notice;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_returns_notices(): void
    {
        $employee = Employee::factory()->create();
        Sanctum::actingAs(User::factory()->create(['employee_id' => $employee->id]));
        Notice::factory()->create(['company_id' => $employee->company_id]);

        $this->getJson('/v1/notices')->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_list_requires_token(): void
    {
        $this->getJson('/v1/notices')->assertStatus(401);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=NoticeApiTest`
Expected: FAIL — routes/controllers missing.

- [ ] **Step 3: Create `NoticeResource`**

```php
<?php
// app/Http/Resources/Api/NoticeResource.php
namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoticeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'description'  => $this->description,
            'published_at' => $this->published_at,
            'expired_at'   => $this->expired_at,
        ];
    }
}
```

- [ ] **Step 4: Create the controllers**

```php
<?php
// app/Http/Controllers/Api/Notice/NoticeListController.php
namespace App\Http\Controllers\Api\Notice;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Services\Backend\NoticeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoticeListController extends Controller
{
    public function __construct(private readonly NoticeService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        return ResponseHandler::success(
            $this->service->employeeNotices($request, $request->user()->employee)
        );
    }
}
```

```php
<?php
// app/Http/Controllers/Api/Notice/ShowNoticeController.php
namespace App\Http\Controllers\Api\Notice;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NoticeResource;
use App\Models\Notice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowNoticeController extends Controller
{
    public function __invoke(Request $request, Notice $notice): JsonResponse
    {
        if ($notice->company_id !== $request->user()->employee->company_id) {
            return ResponseHandler::errorNotFound('Notice not found.');
        }

        return ResponseHandler::respondWithResource(new NoticeResource($notice));
    }
}
```

> Verify `NoticeService`'s namespace/method (`app/Services/Backend/NoticeService.php`, `employeeNotices(Request, Employee)`). Adjust the `use` import if it lives elsewhere.

- [ ] **Step 5: Fill `routes/apis/notice-routes.php`**

```php
<?php
use App\Http\Controllers\Api\Notice\NoticeListController;
use App\Http\Controllers\Api\Notice\ShowNoticeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('notices')->group(function () {
    Route::get('/', NoticeListController::class);
    Route::get('{notice}', ShowNoticeController::class);
});
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --filter=NoticeApiTest`
Expected: PASS (2 tests).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Resources/Api/NoticeResource.php app/Http/Controllers/Api/Notice routes/apis/notice-routes.php tests/Feature/Api/NoticeApiTest.php
git commit -m "feat(api): notice endpoints (list/show)"
```

---

### Task 9: Dashboard endpoint + API reference doc

**Files:**
- Create: `app/Http/Controllers/Api/Dashboard/DashboardController.php`
- Modify: `routes/apis/auth-routes.php` (add `/dashboard` inside the sanctum group) OR create `routes/apis/dashboard-routes.php` and require it in `routes/api.php`
- Create: `docs/api/mobile-employee-api.md`
- Test: `tests/Feature/Api/DashboardApiTest.php`

**Interfaces:**
- Consumes: `AttendanceService::{getOfficeHours,getMonthlyStats,getTodayCompleteData}` (already used by the web `EmployeeAttendanceController::index`).
- Produces: `GET /v1/dashboard`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Api/DashboardApiTest.php
namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_sections(): void
    {
        $employee = Employee::factory()->create();
        Sanctum::actingAs(User::factory()->create(['employee_id' => $employee->id]));

        $this->getJson('/v1/dashboard')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['officeHours', 'monthlyStats', 'todayData']]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DashboardApiTest`
Expected: FAIL — route/controller missing.

- [ ] **Step 3: Create the controller**

```php
<?php
// app/Http/Controllers/Api/Dashboard/DashboardController.php
namespace App\Http\Controllers\Api\Dashboard;

use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly AttendanceService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        return ResponseHandler::success([
            'officeHours'  => $this->service->getOfficeHours($employee),
            'monthlyStats' => $this->service->getMonthlyStats($employee),
            'todayData'    => $this->service->getTodayCompleteData($employee),
        ]);
    }
}
```

- [ ] **Step 4: Add the route** — inside the sanctum group in `routes/apis/auth-routes.php`:

```php
Route::middleware('auth:sanctum')->get('dashboard', \App\Http\Controllers\Api\Dashboard\DashboardController::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=DashboardApiTest`
Expected: PASS.

- [ ] **Step 6: Write the API reference doc**

Create `docs/api/mobile-employee-api.md` documenting: base URL + `/v1` prefix, the `Authorization: Bearer` header, the success/error envelopes, and every endpoint from Tasks 4–9 with method, path, request fields, and a sample JSON response. Keep it in sync with the routes.

- [ ] **Step 7: Run the full API test suite**

Run: `php artisan test --filter=Api`
Expected: ALL PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Api/Dashboard routes/apis docs/api/mobile-employee-api.md tests/Feature/Api/DashboardApiTest.php
git commit -m "feat(api): dashboard endpoint and mobile API reference doc"
```

---

## Self-Review Notes

- **Spec coverage:** auth (Task 4), profile/`me` (Task 4), dashboard (Task 9), attendance today/check-in/out/monthly/records (Task 5), leave list/types/balance/apply/cancel (Task 6), approvals list/show/approve/reject (Task 7), notices list/show (Task 8), ResponseHandler port (Task 1), routing+sanctum+exceptions (Task 2), tests (each task) — all spec sections mapped.
- **Response envelope:** every controller returns via `ResponseHandler`; exception handler covers uncaught validation/auth/not-found → JSON.
- **Known verification points to confirm during implementation (not placeholders — explicit checks):** the web `LeaveRequestFormRequest` field names (Task 6 Step 4), `NoticeService` namespace (Task 8 Step 4), and any NOT NULL columns the factories miss (Task 3 Step 5). Each has a concrete fallback action.

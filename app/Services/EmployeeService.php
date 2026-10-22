<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Address;
use App\Models\Client;
use App\Models\Employee;
use App\Models\JobOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Support\ActivityLogger;

/**
 * Writes the employee records and the accounts that belong to them.
 *
 * Every write that touches more than one table runs in a transaction, so a
 * failed write never leaves an employee without an address or an account
 * behind.
 */
class EmployeeService
{
    /**
     * The event that is recorded when the role of an account is changed.
     */
    public const string EVENT_ROLE_CHANGED = 'role-changed';

    /**
     * The event that is recorded when the status of an account is changed.
     */
    public const string EVENT_STATUS_CHANGED = 'status-changed';

    public function __construct(private readonly RoleAssignmentService $roles) {}

    /**
     * Create an employee, and optionally the account that belongs to it.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data): Employee {
            $address = Address::create([
                'line_address' => $data['line_address'],
                'city_id' => $data['city_id'],
            ]);

            $user = null;

            if ($data['create_account'] ?? false) {
                $user = $this->createAccount($data);
            }

            return Employee::create([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'position' => $data['position'],
                'birthday' => $data['birthday'],
                'contact_number' => $data['contact_number'],
                'emergency_contact_person_name' => $data['emergency_contact_person_name'],
                'emergency_contact_person_number' => $data['emergency_contact_person_number'],
                'address_id' => $address->getKey(),
                'user_id' => $user?->getKey(),
            ]);
        });
    }

    /**
     * Change the personal details of an employee.
     *
     * The account of the employee is only touched when the caller is allowed
     * to manage it, so a user without that permission cannot take over the
     * account of an employee.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data, bool $manageAccount = false): Employee
    {
        return DB::transaction(function () use ($employee, $data, $manageAccount): Employee {
            $employee->fill([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'position' => $data['position'],
                'birthday' => $data['birthday'],
                'contact_number' => $data['contact_number'],
                'emergency_contact_person_name' => $data['emergency_contact_person_name'],
                'emergency_contact_person_number' => $data['emergency_contact_person_number'],
            ]);

            $employee->address?->update([
                'line_address' => $data['line_address'],
                'city_id' => $data['city_id'],
            ]);

            $employee->save();

            if ($manageAccount && $employee->user) {
                $employee->user->forceFill([
                    'name' => $this->fullName($data),
                    'email' => $data['email'],
                ])->save();
            }

            return $employee;
        });
    }

    /**
     * Remove an employee together with the records that belong to it.
     *
     * An employee that is the artist in charge of a job order cannot be
     * removed, because the job order would lose the person that is answerable
     * for it. Such an employee is deactivated instead.
     */
    public function delete(Employee $employee): void
    {
        if ($employee->jobOrders()->exists()) {
            throw ValidationException::withMessages([
                'employee' => __('This employee is the artist in charge of a job order and cannot be removed. Deactivate the account instead.'),
            ]);
        }

        DB::transaction(function () use ($employee): void {
            $user = $employee->user;
            $address = $employee->address;

            $employee->delete();

            if ($address !== null && ! Client::withTrashed()->where('address_id', $address->getKey())->exists()) {
                $address->delete();
            }

            if ($user === null) {
                return;
            }

            if ($this->accountIsReferenced($user)) {
                $user->forceFill(['is_active' => false])->save();

                return;
            }

            $user->delete();
        });
    }

    /**
     * Whether a job order still points at the account of the employee.
     *
     * The job orders keep the account that created or changed them, so the
     * account cannot be removed while those records exist.
     */
    protected function accountIsReferenced(User $user): bool
    {
        return JobOrder::query()
            ->where('created_by', $user->getKey())
            ->orWhere('updated_by', $user->getKey())
            ->exists();
    }

    /**
     * Activate or deactivate the account of an employee.
     */
    public function setAccountStatus(Employee $employee, bool $active): void
    {
        $user = $employee->user;

        if ($user === null) {
            throw ValidationException::withMessages([
                'employee' => __('This employee does not have an account.'),
            ]);
        }

        $user->forceFill(['is_active' => $active])->save();

        $this->recordActivity($user, self::EVENT_STATUS_CHANGED, [
            'is_active' => $active,
        ], $active ? 'Employee Account Activated' : 'Employee Account Deactivated');
    }

    /**
     * Give the account of an employee a role.
     */
    public function assignRole(Employee $employee, Role $role): void
    {
        $user = $employee->user;

        if ($user === null) {
            throw ValidationException::withMessages([
                'employee' => __('This employee does not have an account.'),
            ]);
        }

        $this->roles->assign($user, $role);

        $this->recordActivity($user, self::EVENT_ROLE_CHANGED, [
            'roles' => [$role->value],
        ], 'Employee Role Changed');
    }

    /**
     * The name that the account of an employee carries.
     *
     * @param  array<string, mixed>  $data
     */
    protected function fullName(array $data): string
    {
        return trim($data['first_name'].' '.$data['last_name']);
    }

    /**
     * Create the account that belongs to a new employee.
     *
     * The deployment cannot send mail, so the account starts with a temporary
     * password and is forced to choose its own password at the next sign in.
     *
     * @param  array<string, mixed>  $data
     */
    protected function createAccount(array $data): User
    {
        $user = new User;

        $user->forceFill([
            'name' => $this->fullName($data),
            'email' => $data['email'],
            'password' => $data['password'],
            'must_change_password' => true,
        ])->save();

        $this->roles->assign($user, Role::from($data['role']));

        return $user;
    }

    /**
     * Record a change of the account of an employee.
     *
     * @param  array<string, mixed>  $properties
     */
    protected function recordActivity(User $user, string $event, array $properties, string $description): void
    {
        app(ActivityLogger::class)
            ->useLog('user')
            ->event($event)
            ->performedOn($user)
            ->withProperties($properties)
            ->log($description);
    }
}

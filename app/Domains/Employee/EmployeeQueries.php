<?php

declare(strict_types=1);

namespace App\Domains\Employee;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Designation\DesignationQueries;
use App\Domains\Employee\DataObjects\EmployeeData;
use App\Domains\Employee\DataObjects\StoreManagerEmployeeData;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\EmployeeTransaction\EmployeeTransactionQueries;
use App\Domains\LoyaltyPoint\Interfaces\LoyaltyPointsInterface;
use App\Domains\Media\MediaQueries;
use App\Domains\Member\MemberQueries;
use App\Models\Employee;
use Closure;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class EmployeeQueries implements LoyaltyPointsInterface
{
    public function superAdminListQuery(array $filterData): LengthAwarePaginator
    {
        $companyQueries = new CompanyQueries();

        return Employee::query()
            ->with(['company:' . $companyQueries->getBasicColumnNames()])
            ->whereHas('company', function ($query): void {
                $query->whereNull('deleted_at');
            })
            ->select('id', 'first_name', 'last_name', 'email', 'mobile_number', 'company_id', 'status', 'staff_id')
            ->when($filterData['search_text'], function ($query) use ($companyQueries, $filterData): void {
                $query->where(function ($query) use ($filterData, $companyQueries): void {
                    $query
                        ->whereAny(
                            ['first_name', 'email', 'mobile_number', 'staff_id'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhereHas('company', $companyQueries->searchByName($filterData['search_text']));
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function adminListQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return Employee::query()
            ->select(
                'id',
                'first_name',
                'last_name',
                'email',
                'mobile_number',
                'card_number',
                'status',
                'staff_id',
                'is_email_verified'
            )
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['first_name', 'mobile_number', 'email', 'staff_id'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->where('company_id', $companyId)
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getByCompanyId(int $companyId): Collection
    {
        return Employee::where('company_id', $companyId)
            ->select('id', 'first_name', 'last_name', 'staff_id')
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,first_name,last_name,email,staff_id,company_id,mobile_number,designation_id';
    }

    public function getBasicColumnWithGroup(): string
    {
        return 'id,first_name,last_name,group_id';
    }

    public function getBasicColumnNamesWithStatus(): string
    {
        return 'id,company_id,first_name,last_name,email,mobile_number,ic_number,staff_id,status';
    }

    public function getColumnNamesForMeApiEndPoint(): string
    {
        return 'id,first_name,last_name,company_id,email,mobile_number,address_line_1,address_line_2,city,area_code,date_of_joining,staff_id';
    }

    public function getFirstAndLastNameColumns(): string
    {
        return 'id,first_name,last_name,company_id';
    }

    public function getNameAndStaffIdColumns(): string
    {
        return 'id,first_name,last_name,staff_id';
    }

    public static function getStatusAndCompanyIdColumns(): string
    {
        return 'id,company_id,status';
    }

    public function checkActiveCompanyAndGetStatusAndCompanyIdColumns(): Closure
    {
        return fn ($query) => $query->select(['id', 'company_id', 'status'])
            ->whereHas('company', function ($query): void {
                $query->whereNull('deleted_at');
            });
    }

    public function getColumnsForPanelHeader(int $employeeId): Employee
    {
        return Employee::select('id', 'first_name', 'last_name', 'staff_id')
            ->findOrFail($employeeId);
    }

    public static function getNamesAndStatusColumns(): string
    {
        return 'id,first_name,last_name,status,staff_id';
    }

    public function getColumnNamesForAdmin(): string
    {
        return 'id,company_id';
    }

    public function getColumnNamesForPromoter(): string
    {
        return 'id,company_id,first_name,last_name,email,mobile_number,ic_number,staff_id,status,primary_contact_name,primary_contact_phone,city,area_code,home_contact,address_line_1,address_line_2';
    }

    public function getColumnNamesForEmployeeSalesReport(): string
    {
        return 'id,first_name,last_name,mobile_number,loyalty_points';
    }

    public function getBasicColumnsForEmployeeMember(): string
    {
        return 'id,group_id,job_type,ic_number,staff_id';
    }

    public function searchByBasicColumns(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')
            ->whereAny(['first_name', 'last_name', 'email', 'staff_id'], 'LIKE', '%' . $searchText . '%');
    }

    public function searchByFirstAndLastName(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')
            ->whereAny(['first_name', 'last_name'], 'LIKE', '%' . $searchText . '%');
    }

    public function getByIdWithMedia(int $employeeId): Employee
    {
        $mediaQueries = resolve(MediaQueries::class);
        $designationQueries = resolve(DesignationQueries::class);

        return Employee::select(
            'id',
            'company_id',
            'designation_id',
            'group_id',
            'first_name',
            'last_name',
            'email',
            'mobile_number',
            'home_contact',
            'address_line_1',
            'address_line_2',
            'city',
            'area_code',
            'date_of_joining',
            'primary_contact_name',
            'primary_contact_phone',
            'staff_id',
            'ic_number',
            'job_type',
            'status',
            'created_at',
            'membership_id',
            'is_email_verified'
        )->with([
            'designation:' . $designationQueries->getBasicColumnNames(),
            'media:' . $mediaQueries->getBasicColumnNames()])
        ->findOrFail($employeeId);
    }

    public function addNew(EmployeeData $employeeData, User $user): Employee
    {
        $employeeRecord = collect($employeeData)->forget('photo');

        $employeeRecord['card_number'] = $this->generateUniqueCardNumber();

        $employeeRecord['created_by_id'] = $user->id;
        $employeeRecord['created_by_type'] = ModelMapping::getCaseName($user::class);

        $employee = Employee::create($employeeRecord->toArray());

        $this->uploadPhoto($employee, $employeeData);

        return $employee;
    }

    public function addNewForStoreManagerApp(
        StoreManagerEmployeeData $employeeData,
        User $user,
        int $companyId
    ): Employee {
        $employeeRecord = collect($employeeData)->forget('photo');

        $employeeRecord['card_number'] = $this->generateUniqueCardNumber();

        $employeeRecord['created_by_id'] = $user->id;
        $employeeRecord['company_id'] = $companyId;
        $employeeRecord['created_by_type'] = ModelMapping::getCaseName($user::class);

        $employee = Employee::create($employeeRecord->toArray());

        $this->uploadPhoto($employee, $employeeData);

        return $employee;
    }

    public function update(EmployeeData|StoreManagerEmployeeData $employeeData, User $user, int $employeeId): void
    {
        $employeeRecord = collect($employeeData)->forget('photo');
        $employee = Employee::findOrFail($employeeId);
        $this->updateMemberEmployeeId($employeeData->status, $employee, $user);
        $employee->update($employeeRecord->toArray());
        $this->uploadPhoto($employee, $employeeData);
        $this->setUpdatedAt($employee);
    }

    public function setUpdatedAt(Employee $employee): void
    {
        $employee->touch();
    }

    public function filterByCompanyAndStaffId(string $staffId, int $companyId): Closure
    {
        return fn ($query) => $query->select('id')
            ->where('staff_id', $staffId)
            ->where('company_id', $companyId)
            ->where('status', true);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('company_id', $companyId);
    }

    public function filterByStatus(): Closure
    {
        return fn ($query) => $query->select('id')->where('status', true);
    }

    public function getWithBasicColumns(int $companyId): Collection
    {
        return Employee::select('id', 'first_name', 'last_name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function getById(int $employeeId, int $companyId): Employee
    {
        return Employee::select('id', 'email', 'is_email_verified')
            ->where('company_id', $companyId)
            ->findOrFail($employeeId);
    }

    public function getByIdWithGroup(int $employeeId, int $companyId): ?Employee
    {
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        return Employee::select('id', 'group_id', 'first_name', 'last_name')
            ->with(['employeeGroup:' . $employeeGroupQueries->getBasicColumnNames()])
            ->where('company_id', $companyId)
            ->where('id', $employeeId)
            ->first();
    }

    public function getByIdWithMembership(int $employeeId): Employee
    {
        return Employee::select('id', 'spent_till_now', 'membership_id')
            ->with('membership')
            ->findOrFail($employeeId);
    }

    public function getFormattedEmployeesOf(int $companyId): Collection
    {
        $collection = $this->getByCompanyId($companyId);

        return $collection->transform(fn ($employee): array => [
            'id' => $employee->id,
            'name' => $employee->getFullName() . ' (' . $employee->staff_id . ')',
        ]);
    }

    public function updateSpentTillNow(float $amount, int $employeeId): void
    {
        $employee = Employee::query()
            ->findOrFail($employeeId);
        $employee->spent_till_now += $amount;
        $employee->save();
    }

    public function setMembershipId(int $membershipId, int $employeeId): void
    {
        $employee = Employee::query()
            ->findOrFail($employeeId);
        $employee->membership_id = $membershipId;
        $employee->save();
    }

    public function decreaseLoyaltyPoints(int $employeeId, int $loyaltyPoints): void
    {
        $employee = Employee::query()
            ->findOrFail($employeeId);

        $employee->loyalty_points -= $loyaltyPoints;
        $employee->save();
    }

    public function decreaseExpiredLoyaltyPoints(Employee $employee, int $loyaltyPoints): void
    {
        $employee->loyalty_points -= $loyaltyPoints;
        $employee->total_expired_points += $loyaltyPoints;
        $employee->save();
    }

    public function getByIdWithMembershipAndLoyaltyPoints(int $companyId, int $employeeId): Employee
    {
        return Employee::select('id', 'loyalty_points', 'membership_id')
            ->where('company_id', $companyId)
            ->findOrFail($employeeId);
    }

    public function increaseLoyaltyPoints(EloquentModel $employeeRecord, int $loyaltyPoints): void
    {
        /** @var Employee $employee */
        $employee = $employeeRecord;

        $employee->loyalty_points += $loyaltyPoints;

        $employee->save();
    }

    public function superAdminSetStatus(int $employeeId, bool $status, User $user): void
    {
        $employee = Employee::query()
            ->findOrFail($employeeId);

        $this->updateMemberEmployeeId($status, $employee, $user);

        $employee->status = $status;
        $employee->save();
    }

    public function adminSetStatus(int $employeeId, int $companyId, bool $status, User $user): void
    {
        $employee = Employee::query()
            ->where('company_id', $companyId)
            ->findOrFail($employeeId);

        $this->updateMemberEmployeeId($status, $employee, $user);

        $employee->status = $status;
        $employee->save();
    }

    public function searchByName(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')
            ->whereAny(['first_name', 'last_name'], 'LIKE', '%' . $searchText . '%');
    }

    public function generateUniqueCardNumber(): string
    {
        $cardNumber = CommonFunctions::getTwelveDigitNumber();

        $existCardNumbers = Employee::whereCaseSensitive('card_number', $cardNumber)->exists();

        if ($existCardNumbers) {
            return $this->generateUniqueCardNumber();
        }

        return $cardNumber;
    }

    public function mobileNumberExist(string $mobileNumber, int $companyId): bool
    {
        return Employee::whereCaseSensitive('mobile_number', $mobileNumber)->where('company_id', $companyId)->exists();
    }

    public function emailExist(string $email, int $companyId): bool
    {
        return Employee::whereCaseSensitive('email', $email)->where('company_id', $companyId)->exists();
    }

    public function emailTakenByAnotherEmployee(string $email, int $companyId, string $mobileNumber): bool
    {
        return Employee::whereNot('mobile_number', $mobileNumber)
            ->whereCaseSensitive('email', $email)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function filterById(int $employeeId): Closure
    {
        return fn ($query) => $query->where('id', $employeeId);
    }

    public function getAdminEmployeesExport(array $filterData, int $companyId): Collection
    {
        return Employee::query()
            ->select(...$this->getColumnNames())
            ->with('designation')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['first_name', 'email', 'mobile_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->where('company_id', $companyId)
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->get();
    }

    public function getIdByNameAndMobileNumber(string $firstName, string $mobileNumber, int $companyId): ?int
    {
        return Employee::query()->select('id', 'company_id', 'first_name', 'mobile_number')
            ->where('first_name', $firstName)
            ->where('mobile_number', $mobileNumber)
            ->where('company_id', $companyId)
            ->first()
                ?->id;
    }

    public function doEmployeeNameExist(string $firstName, string $mobileNumber, int $companyId): bool
    {
        return Employee::query()
            ->select('id', 'company_id', 'first_name', 'last_name')
            ->whereCaseSensitive('first_name', $firstName)
            ->where('mobile_number', $mobileNumber)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function searchEmployeesForFilter(string $searchText, int $companyId): Collection
    {
        return Employee::select('id', 'first_name', 'last_name')
            ->where('company_id', $companyId)
            ->when($searchText, function ($query) use ($searchText): void {
                $query->where(function ($query) use ($searchText): void {
                    $query
                        ->whereAny(
                            ['first_name', 'last_name', 'mobile_number', 'card_number', 'email'],
                            'LIKE',
                            '%' . $searchText . '%'
                        );
                });
            })
            ->get();
    }

    public function employeeExistsById(int $companyId, int $employeeId): bool
    {
        return Employee::where('id', $employeeId)->where('company_id', $companyId)->exists();
    }

    public function getEmployeeCompanyId(int $employeeId): int
    {
        return Employee::select('id', 'company_id')
            ->findOrFail($employeeId)
            ->company_id;
    }

    public function updateProfile(Data $applicationData, int $employeeId): void
    {
        /** @var Employee $employee */
        $employee = Employee::findOrFail($employeeId);

        $data = $applicationData->all();
        if ($data['photo'] instanceof UploadedFile) {
            $this->uploadPhoto($employee, $applicationData);
        }

        unset($data['username'], $data['photo']);

        $employee->update($data);
    }

    public function getEmployeeForBulkUpdate(int $companyId): Collection
    {
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        return Employee::select(
            'id',
            'company_id',
            'designation_id',
            'group_id',
            'first_name',
            'last_name',
            'email',
            'mobile_number',
            'home_contact',
            'address_line_1',
            'address_line_2',
            'city',
            'area_code',
            'date_of_joining',
            'primary_contact_name',
            'primary_contact_phone',
            'staff_id',
            'membership_id',
            'ic_number',
            'job_type',
            'status',
        )
            ->with(['designation', 'employeeGroup:' . $employeeGroupQueries->getBasicColumnNames()])
            ->where('company_id', $companyId)
            ->where('status', true)
            ->get();
    }

    public function updateByMobileNumber(array $employeeData, string $mobileNumber, int $companyId): void
    {
        /** @var Employee $employee */
        $employee = Employee::where('mobile_number', $mobileNumber)
            ->where('company_id', $companyId)
            ->where('status', true)
            ->first();

        $employee->update($employeeData);
    }

    public function getPaginatedListForStoreManagerApp(array $filteredData, int $companyId): LengthAwarePaginator
    {
        return Employee::select('id', 'first_name', 'last_name', 'company_id')
            ->where('company_id', $companyId)
            ->when($filteredData['search_text'], function ($query) use ($filteredData): void {
                $query->where(function ($query) use ($filteredData): void {
                    $query
                        ->whereAny(['first_name', 'last_name'], 'LIKE', '%' . $filteredData['search_text'] . '%');
                });
            })
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filteredData['per_page']);
    }

    public function getByIdForEmployeeUpdatePointsAndTotalSalesJob(int $employeeId): Employee
    {
        return Employee::select(
            'id',
            'company_id',
            'total_redeemed_points',
            'total_earned_points',
            'total_expired_points',
            'total_sales'
        )
            ->findOrFail($employeeId);
    }

    public function updatePointsAndTotalSales(
        Employee $employee,
        int $totalEarnedPoints,
        int $totalRedeemedPoints,
        int $totalSales,
    ): void {
        $employee->total_earned_points = $totalEarnedPoints;
        $employee->total_redeemed_points = $totalRedeemedPoints;
        $employee->total_sales = $totalSales;
        $employee->save();
    }

    public function getLoyaltyPointsById(int $employeeId): Employee
    {
        return Employee::select('id', 'loyalty_points', 'total_expired_points')
            ->findOrFail($employeeId);
    }

    public function filterByActiveAndCompanyId(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')
            ->where('status', true)
            ->where('company_id', $companyId);
    }

    public function statusChange(Employee $employee, bool $status): void
    {
        $employee->status = $status;
        $employee->save();
    }

    private function uploadPhoto(Employee $employee, Data $employeeData): void
    {
        $data = $employeeData->all();

        if ($data['photo'] instanceof UploadedFile) {
            $employee->addMedia($data['photo'])->toMediaCollection('photo');
        }
    }

    private function getColumnNames(): array
    {
        return [
            'id',
            'first_name',
            'last_name',
            'email',
            'mobile_number',
            'home_contact',
            'address_line_1',
            'address_line_2',
            'city',
            'area_code',
            'date_of_joining',
            'primary_contact_name',
            'primary_contact_phone',
            'staff_id',
            'membership_id',
            'ic_number',
            'job_type',
            'spent_till_now',
            'loyalty_points',
            'card_number',
            'designation_id',
            'group_id',
            'total_redeemed_points',
            'total_sales',
            'total_earned_points',
            'total_expired_points',
            'created_at',
            'status',
        ];
    }

    public function updateMemberEmployeeId(bool $newStatus, Employee $employee, User $user): void
    {
        if ($newStatus === $employee->status) {
            return;
        }

        $memberQueries = resolve(MemberQueries::class);
        $employeeTransactionQueries = resolve(EmployeeTransactionQueries::class);

        if (! $newStatus) {
            $memberQueries->removeEmployeeId($employee->id);
            $employeeTransactionQueries->addNew($employee->id, $newStatus, $user);

            return;
        }

        if (! $employee->email && ! $employee->mobile_number) {
            return;
        }

        $memberQueries->addEmployeeId($employee);
        $employeeTransactionQueries->addNew($employee->id, $newStatus, $user);
    }

    public function getEmployeeNameForFilter(int $id): ?string
    {
        $employee = Employee::where('id', $id)->first();
        if ($employee) {
            return sprintf('%s  %s', $employee->first_name, $employee->last_name);
        }

        return null;
    }
}

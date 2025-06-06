<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberService;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\Member;

class SaleUserService
{
    public CheckSaleDetailsService $checkSaleDetailsService;

    public Cashier $cashier;

    public function setDetails(CheckSaleDetailsService $checkSaleDetailsService, Cashier $cashier): void
    {
        $this->checkSaleDetailsService = $checkSaleDetailsService;
        $this->cashier = $cashier;
    }

    public function getMemberId(): ?int
    {
        $memberId = $this->getExistingMemberId();

        if ($memberId) {
            return $memberId;
        }

        $memberDetails = $this->checkSaleDetailsService->saleData->member;

        if (! $memberDetails) {
            return null;
        }

        $memberService = resolve(MemberService::class);
        $memberService->checkRequiredMemberColumns($memberDetails, $this->checkSaleDetailsService->companyId);

        return $memberService->addNewMember(
            $this->cashier,
            $memberDetails,
            $this->checkSaleDetailsService->companyId,
            $this->checkSaleDetailsService->location->id,
            MemberChannelEnum::POS->value
        );
    }

    public function getExistingMemberId(): ?int
    {
        if ($this->checkSaleDetailsService->isMemberAttached()) {
            return $this->checkSaleDetailsService->saleData->member_id;
        }

        if ($this->checkSaleDetailsService->member instanceof Member) {
            return $this->checkSaleDetailsService->member->id;
        }

        if (! $this->checkSaleDetailsService->hasMemberDetails()) {
            return null;
        }

        /** @var array $memberRequestDetails */
        $memberRequestDetails = $this->checkSaleDetailsService->saleData->member;

        $memberService = resolve(MemberService::class);

        return $memberService->getMemberIdFromDetails(
            $memberRequestDetails,
            $this->checkSaleDetailsService->companyId
        );
    }

    public function getUser(): Member|Employee|null
    {
        if ($employeeId = $this->checkSaleDetailsService->saleData->employee_id) {
            $employeeQueries = resolve(EmployeeQueries::class);

            return $employeeQueries->getByIdWithMembership($employeeId);
        }

        if ($memberId = $this->getMemberId()) {
            $memberQueries = resolve(MemberQueries::class);

            return $memberQueries->getByIdWithMembership($memberId);
        }

        return null;
    }

    public function getMember(): ?Member
    {
        $memberQueries = resolve(MemberQueries::class);
        if ($employeeId = $this->checkSaleDetailsService->saleData->employee_id) {
            return $memberQueries->getByEmployeeIdAndCompanyIdWithMembership(
                $employeeId,
                $this->checkSaleDetailsService->companyId
            );
        }

        if ($memberId = $this->getMemberId()) {
            return $memberQueries->getByIdCompanyIdWithMembership($memberId, $this->checkSaleDetailsService->companyId);
        }

        return null;
    }
}

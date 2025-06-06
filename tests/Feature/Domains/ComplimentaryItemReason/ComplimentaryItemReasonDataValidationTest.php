<?php

declare(strict_types=1);

use App\Domains\ComplimentaryItemReason\DataObjects\ComplimentaryItemReasonData;
use App\Http\Controllers\Admin\ComplimentaryItemReasonController;
use App\Models\Company;
use App\Models\ComplimentaryItemReason;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->complimentaryItemReason = ComplimentaryItemReason::factory()->create([
        'company_id' => $this->companyId,
        'reason' => 'Test',
    ]);
});

test(
    'company wise unique reason validation works while adding a complimentary item reason.',
    function (): void {
        setCompanyIdInSession($this->companyId);

        $complimentaryItemReasonDetails = ComplimentaryItemReason::factory()->make([
            'company_id' => $this->companyId,
            'reason' => 'Test',
        ])->toArray();

        $request = new Request($complimentaryItemReasonDetails);

        $request->validate(ComplimentaryItemReasonData::rules($request));
    }
)->throws(ValidationException::class);

test(
    'user can add different complimentary item reason with same company.',
    function (): void {
        setCompanyIdInSession($this->companyId);

        $complimentaryItemReasonDetails = ComplimentaryItemReason::factory()->make([
            'company_id' => $this->companyId,
            'reason' => 'XYZ',
        ])->toArray();

        $request = new Request($complimentaryItemReasonDetails, server: [
            'REQUEST_URI' => 'complimentary-item-reasons/' . $this->complimentaryItemReason->id . '/update',
        ]);
        $request->setRouteResolver(
            fn (): Route => (new Route(
                'Post',
                'complimentary-item-reasons/{complimentaryItemReasonId}/update',
                [
                    'as' => 'admin.complimentary-item-reasons.update',
                    'uses' => [ComplimentaryItemReasonController::class, 'update'],
                ]
            ))->bind($request)
        );

        $request->validate(ComplimentaryItemReasonData::rules($request));
        $this->assertTrue(true);
    }
);

test(
    'user can add same complimentary item reason with different company.',
    function (): void {
        $companyId = Company::factory()->create()->id;
        setCompanyIdInSession($companyId);

        $complimentaryItemReasonDetails = ComplimentaryItemReason::factory()->make([
            'reason' => 'Test',
            'company_id' => $companyId,
        ])->toArray();

        $request = new Request($complimentaryItemReasonDetails);
        $request->validate(ComplimentaryItemReasonData::rules($request));
        $this->assertTrue(true);
    }
);

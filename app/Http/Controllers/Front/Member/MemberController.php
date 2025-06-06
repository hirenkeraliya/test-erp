<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\Member;

use App\Domains\Location\LocationQueries;
use App\Domains\Member\DataObjects\FrontMemberData;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\MemberQueries;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SiteConfiguration;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    public function index(int|string $locationId): Factory|View
    {
        $locationQueries = resolve(LocationQueries::class);
        $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);

        $getSiteConfigurationTheme = $siteConfigurationQueries->getCachedThemeConfiguration();

        $themeColor = $getSiteConfigurationTheme instanceof SiteConfiguration ? ThemeColors::getHexColor(
            $getSiteConfigurationTheme->value
        ) : ThemeColors::getHexColor(ThemeColors::AMARANTH_DEEP_PURPLE->value);

        $location = $locationQueries->getCompanyLogoOfStoreForRegisterMember($locationId);

        /** @var Company $company */
        $company = $location->company;

        return view('front/member/add', [
            'locationId' => $locationId,
            'companyLogo' => $company->getDiskBasedFirstMediaUrl('light_logo'),
            'companyName' => $company->name,
            'themeColor' => $themeColor,
            'cookieValue' => Cookie::get('member-registration'),
        ]);
    }

    public function store(FrontMemberData $memberData, int|string $locationId): RedirectResponse
    {
        $validateData = $memberData->all();

        $memberQueries = resolve(MemberQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $locationId = $locationQueries->getIdByRefIdAndRefType($locationId);
        $companyId = $locationQueries->getCompanyIdOfStore($locationId);

        DB::beginTransaction();
        try {
            $memberQueries->addNewMemberForRegistration(
                $validateData,
                $locationId,
                $companyId,
                MemberChannelEnum::QR_CODE->value
            );

            DB::commit();

            return to_route('front.member.member_thank_you');
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('Front-Member-Registration', [
                'error_message' => $exception->getMessage(),
                'error_code' => 'Error code: ' . $exception->getCode(),
                'file' => 'File: ' . $exception->getFile(),
                'line' => 'Line: ' . $exception->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($exception->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$exception],
            ]);

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function thankYou(): Factory|View
    {
        return view('front/member/thank_you');
    }

    public function getMobileNumberRegex(): array
    {
        return [
            'regex' => config('app.mobile_number_regex'),
        ];
    }
}

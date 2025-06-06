<?php

declare(strict_types=1);

namespace App\Domains\PaymentType;

use App\CommonFunctions;
use App\Domains\PaymentType\DataObjects\PaymentTypeData;
use App\Domains\PaymentType\Events\PaymentTypeCreateEvent;
use App\Domains\PaymentType\Events\PaymentTypeUpdateEvent;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\ShippingZone\ShippingZoneQueries;
use App\Models\PaymentType;
use App\Models\SaleChannel;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentTypeQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->paymentTypeQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(PaymentTypeData $paymentTypeData, int $companyId): void
    {
        $data = $paymentTypeData->all();
        unset($data['sale_channel_ids']);
        unset($data['shipping_zone_ids']);
        $data['company_id'] = $companyId;
        $paymentType = PaymentType::create($data);

        $this->updateSaleChannels($paymentType, $paymentTypeData);
        $this->updateShippingZones($paymentType, $paymentTypeData);

        event(new PaymentTypeCreateEvent($paymentType));
    }

    private function updateSaleChannels(PaymentType $paymentType, PaymentTypeData $paymentTypeData): void
    {
        if (! array_key_exists('sale_channel_ids', $paymentTypeData->all())) {
            return;
        }

        if (null === $paymentTypeData->sale_channel_ids) {
            return;
        }

        $paymentType->saleChannels()->sync($paymentTypeData->sale_channel_ids);
    }

    private function updateShippingZones(PaymentType $paymentType, PaymentTypeData $paymentTypeData): void
    {
        if (! array_key_exists('shipping_zone_ids', $paymentTypeData->all())) {
            return;
        }

        if (null === $paymentTypeData->shipping_zone_ids) {
            return;
        }

        $paymentType->shippingZones()->sync($paymentTypeData->shipping_zone_ids);
    }

    public function getById(int $paymentTypeId, int $companyId): PaymentType
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $shippingZoneQueries = resolve(ShippingZoneQueries::class);

        return PaymentType::select(
            'id',
            'name',
            'is_member_required',
            'is_available_for_refund',
            'trigger_card_payment_machine',
            'trigger_qr_code_payment_machine',
            'trigger_card_affin_payment_machine',
            'is_card_payment',
            'status',
            'image_name',
            'payment_terminal_key',
            'trigger_card_bank_rakyat_terminal',
            'is_available_in_ecommerce',
            'restrict_by_zone',
            'restriction_type',
            'site_key',
            'secret_key',
            'url',
            'is_available_in_pos',
        )
            ->with(['saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
                'shippingZones:' . $shippingZoneQueries->getBasicColumns()])
            ->where(function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->findOrFail($paymentTypeId);
    }

    public function update(PaymentTypeData $paymentTypeData, int $paymentTypeId, int $companyId): void
    {
        $paymentType = $this->getById($paymentTypeId, $companyId);

        $data = $paymentTypeData->all();
        unset($data['sale_channel_ids']);
        unset($data['shipping_zone_ids']);

        $paymentType->update($data);

        $this->updateSaleChannels($paymentType, $paymentTypeData);
        $this->updateShippingZones($paymentType, $paymentTypeData);

        event(new PaymentTypeCreateEvent($paymentType));
    }

    public function getByIds(array $paymentTypeIds, int $companyId): Collection
    {
        return PaymentType::select('id', 'is_member_required', 'status')
            ->whereIntegerInRaw('id', $paymentTypeIds)
            ->where(function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->get();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId)->orWhereNull('company_id');
    }

    public function getActiveOnlyWithSubPaymentTypes(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        return PaymentType::with('activeSubPaymentTypes')
            ->whereNull('parent_payment_type_id')
            ->where(function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            }, function ($query): void {
                $query->onlyActive();
            })
            ->get();
    }

    public function getActiveOnlyAndAvailableInPosWithSubPaymentTypes(
        int $companyId,
        ?string $afterUpdatedAt = null
    ): Collection {
        return PaymentType::with([
            'activeSubPaymentTypes' => function ($query): void {
                $query->where('is_available_in_pos', true);
            },
        ])
            ->whereNull('parent_payment_type_id')
            ->where(function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            }, function ($query): void {
                $query->onlyActive();
            })
            ->where('is_available_in_pos', true)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name';
    }

    public function setStatus(int $paymentTypeId, int $companyId, bool $status): void
    {
        $paymentType = PaymentType::query()
            ->where('company_id', $companyId)
            ->findOrFail($paymentTypeId);
        $paymentType->status = $status;
        $paymentType->save();

        event(new PaymentTypeUpdateEvent($paymentType));
    }

    public function getAllPaymentTypesForReport(int $companyId): Collection
    {
        return PaymentType::query()
            ->select('id', 'name')
            ->where(function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->get();
    }

    public function checkExistingPaymentTypeIds(array $paymentTypeIds, int $companyId): bool
    {
        $paymentTypeIdsCount = PaymentType::where(function ($query) use ($companyId): void {
            $query->where('company_id', $companyId)
                ->orWhereNull('company_id');
        })
            ->whereIntegerInRaw('id', $paymentTypeIds)
            ->count();

        return count($paymentTypeIds) === $paymentTypeIdsCount;
    }

    public function getPaymentTypesExport(array $filterData, int $companyId): Collection
    {
        return $this->paymentTypeQuery($filterData, $companyId)->get();
    }

    public function getActivePaymentTypesForBulkUpdate(int $companyId): Collection
    {
        return PaymentType::select(
            'id',
            'name',
            'is_member_required',
            'is_available_for_refund',
            'payment_terminal_key',
        )
            ->where('company_id', $companyId)
            ->where('status', true)
            ->get();
    }

    public function updateByName(array $paymentTypeData, string $name, int $companyId): void
    {
        $paymentType = PaymentType::where('name', $name)
            ->where('company_id', $companyId)
            ->where('status', true)
            ->first();

        if ($paymentType instanceof PaymentType) {
            $paymentType->update($paymentTypeData);
        }
    }

    public function paymentTypeExists(string $name, int $companyId): bool
    {
        return PaymentType::whereCaseSensitive('name', $name)->where('company_id', $companyId)->exists();
    }

    public function getPaymentTypeListForReport(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->commonPaymentTypeListQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getPaymentTypeTransactionList(array $filterData): Collection
    {
        $salePayments = DB::table('sale_payments as sp')
            ->select(DB::raw('CASE
            WHEN sl.status = '.SaleStatus::REGULAR_SALE->value.' THEN "Sale Payment"
            WHEN sl.status = '.SaleStatus::VOID_SALE->value.' THEN "Void Sale Payment"
            WHEN sl.status = '.SaleStatus::PENDING_LAYAWAY_SALE->value.' THEN "Pending Layaway Sale Payment"
            WHEN sl.status = '.SaleStatus::COMPLETE_LAYAWAY_SALE->value.' THEN "Complete Layaway Sale Payment"
            WHEN sl.status = '.SaleStatus::CANCEL_LAYAWAY_SALE->value.' THEN "Cancel Layaway Sale Payment"
            WHEN sl.status = '.SaleStatus::PENDING_CREDIT_SALE->value.' THEN "Pending Credit Sale Payment"
            WHEN sl.status = '.SaleStatus::COMPLETE_CREDIT_SALE->value.' THEN "Complete Credit Sale Payment"
            WHEN sl.status = '.SaleStatus::CANCEL_CREDIT_SALE->value.' THEN "Cancel Credit Sale Payment"
            ELSE "Sale Payment"
            END AS payment_type, sp.amount, sl.offline_sale_id AS receipt_id'))
            ->join('sales as sl', 'sl.id', '=', 'sp.sale_id')
            ->join('counter_updates as cu', 'cu.id', '=', 'sl.counter_update_id')
            ->join('counters as c', 'c.id', '=', 'cu.counter_id')
            ->where('sp.payment_type_id', $filterData['id'])
            ->when(
                array_key_exists('counter_ids', $filterData) && ! empty($filterData['counter_ids']),
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('c.id', $filterData['counter_ids']);
                }
            )
            ->when(
                array_key_exists('location_ids', $filterData) && ! empty($filterData['location_ids']),
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('c.location_id', $filterData['location_ids']);
                }
            )
            ->when(array_key_exists('date', $filterData) && ! empty($filterData['date']), function ($query) use (
                $filterData
            ): void {
                $query->where('sl.happened_at', '>=', CommonFunctions::addStartTime($filterData['date'][0]))
                ->where('sl.happened_at', '<=', CommonFunctions::addEndTime($filterData['date'][1]));
            });

        $bookingPayments = DB::table('booking_payment_payments as bpp')
            ->select(DB::raw('"Booking Payment" AS payment_type, bpp.amount, bps.offline_id AS receipt_id'))
            ->join('booking_payments as bps', 'bps.id', '=', 'bpp.booking_payment_id')
            ->join('counter_updates as cu', 'cu.id', '=', 'bpp.counter_update_id')
            ->join('counters as c', 'c.id', '=', 'cu.counter_id')
            ->where('bpp.payment_type_id', $filterData['id'])
            ->when(
                array_key_exists('counter_ids', $filterData) && ! empty($filterData['counter_ids']),
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('c.id', $filterData['counter_ids']);
                }
            )
            ->when(
                array_key_exists('location_ids', $filterData) && ! empty($filterData['location_ids']),
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('c.location_id', $filterData['location_ids']);
                }
            )
            ->when(array_key_exists('date', $filterData) && ! empty($filterData['date']), function ($query) use (
                $filterData
            ): void {
                $query->where('bpp.created_at', '>=', CommonFunctions::addStartTime($filterData['date'][0]))->where(
                    'bpp.created_at',
                    '<=',
                    CommonFunctions::addEndTime($filterData['date'][1])
                );
            });

        $bookingRefunds = DB::table('booking_payment_refunds as bpr')
            ->select(DB::raw('"Booking Payment Refund" AS payment_type, bpr.amount, bps.offline_id AS receipt_id'))
            ->join('booking_payments as bps', 'bps.id', '=', 'bpr.booking_payment_id')
            ->join('counter_updates as cu', 'cu.id', '=', 'bpr.counter_update_id')
            ->join('counters as c', 'c.id', '=', 'cu.counter_id')
            ->where('bpr.payment_type_id', $filterData['id'])
            ->when(
                array_key_exists('counter_ids', $filterData) && ! empty($filterData['counter_ids']),
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('c.id', $filterData['counter_ids']);
                }
            )
            ->when(
                array_key_exists('location_ids', $filterData) && ! empty($filterData['location_ids']),
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('c.location_id', $filterData['location_ids']);
                }
            )
            ->when(array_key_exists('date', $filterData) && ! empty($filterData['date']), function ($query) use (
                $filterData
            ): void {
                $query->where('bpr.created_at', '>=', CommonFunctions::addStartTime($filterData['date'][0]))->where(
                    'bpr.created_at',
                    '<=',
                    CommonFunctions::addEndTime($filterData['date'][1])
                );
            });

        $creditNotes = DB::table('credit_note_refunds as cnr')
            ->select(DB::raw('"Credit Note Refund" AS payment_type, cnr.amount, cn.id AS receipt_id'))
            ->join('credit_notes as cn', 'cn.id', '=', 'cnr.credit_note_id')
            ->join('counter_updates as cu', 'cu.id', '=', 'cnr.counter_update_id')
            ->join('counters as c', 'c.id', '=', 'cu.counter_id')
            ->where('cnr.payment_type_id', $filterData['id'])
            ->when(
                array_key_exists('counter_ids', $filterData) && ! empty($filterData['counter_ids']),
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('c.id', $filterData['counter_ids']);
                }
            )
            ->when(
                array_key_exists('location_ids', $filterData) && ! empty($filterData['location_ids']),
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('c.location_id', $filterData['location_ids']);
                }
            )
            ->when(array_key_exists('date', $filterData) && ! empty($filterData['date']), function ($query) use (
                $filterData
            ): void {
                $query->where('cnr.created_at', '>=', CommonFunctions::addStartTime($filterData['date'][0]))->where(
                    'cnr.created_at',
                    '<=',
                    CommonFunctions::addEndTime($filterData['date'][1])
                );
            });

        $salePayments = $salePayments->get();
        $bookingPayments = $bookingPayments->get();
        $bookingRefunds = $bookingRefunds->get();
        $creditNotes = $creditNotes->get();

        return collect($salePayments)
            ->merge($bookingPayments)
            ->merge($bookingRefunds)
            ->merge($creditNotes);
    }

    public function getPaymentTypeListExport(array $filterData, int $companyId): Collection
    {
        return $this->commonPaymentTypeListQuery($filterData, $companyId)->get();
    }

    public function getBadgesTotal(array $filterData, int $companyId): Collection
    {
        return $this->commonPaymentTypeListQuery($filterData, $companyId)->get();
    }

    private function commonPaymentTypeListQuery(array $filterData, int $companyId): Builder
    {
        return PaymentType::query()
            ->select(
                'payment_types.id',
                'payment_types.name',
                DB::raw('(
                (SELECT COALESCE(SUM(sp.amount), 0)
                FROM sale_payments sp
                JOIN sales sl ON sl.id = sp.sale_id
                JOIN counter_updates cu ON cu.id = sl.counter_update_id
                JOIN counters c ON c.id = cu.counter_id
                WHERE sp.payment_type_id = payment_types.id
                ' . $this->applyFilters(
                    $filterData,
                    'sl.happened_at',
                    'c.id',
                    'c.location_id'
                ) . ' and sl.status not in ('. SaleStatus::VOID_SALE->value .','. SaleStatus::CANCEL_LAYAWAY_SALE->value .','. SaleStatus::CANCEL_CREDIT_SALE->value .')) +

                (SELECT COALESCE(SUM(bpp.amount), 0)
                FROM booking_payment_payments bpp
                JOIN counter_updates cu ON cu.id = bpp.counter_update_id
                JOIN counters c ON c.id = cu.counter_id
                WHERE bpp.payment_type_id = payment_types.id
                ' . $this->applyFilters($filterData, 'bpp.created_at', 'c.id', 'c.location_id') . ') -

                (SELECT COALESCE(SUM(bpr.amount), 0)
                FROM booking_payment_refunds bpr
                JOIN counter_updates cu ON cu.id = bpr.counter_update_id
                JOIN counters c ON c.id = cu.counter_id
                WHERE bpr.payment_type_id = payment_types.id
                ' . $this->applyFilters($filterData, 'bpr.created_at', 'c.id', 'c.location_id') . ') -

                (SELECT COALESCE(SUM(cnr.amount), 0)
                FROM credit_note_refunds cnr
                JOIN counter_updates cu ON cu.id = cnr.counter_update_id
                JOIN counters c ON c.id = cu.counter_id
                WHERE cnr.payment_type_id = payment_types.id
                ' . $this->applyFilters($filterData, 'cnr.created_at', 'c.id', 'c.location_id') . ')
            ) AS total_amount'),
                DB::raw('(
                (SELECT COUNT(sp.id)
                FROM sale_payments sp
                JOIN sales sl ON sl.id = sp.sale_id
                JOIN counter_updates cu ON cu.id = sl.counter_update_id
                JOIN counters c ON c.id = cu.counter_id
                WHERE sp.payment_type_id = payment_types.id
                ' . $this->applyFilters(
                    $filterData,
                    'sl.happened_at',
                    'c.id',
                    'c.location_id'
                ) . ' and sl.status not in ('. SaleStatus::VOID_SALE->value .','. SaleStatus::CANCEL_LAYAWAY_SALE->value .','. SaleStatus::CANCEL_CREDIT_SALE->value .')) +

                (SELECT COUNT(bpp.id)
                FROM booking_payment_payments bpp
                JOIN counter_updates cu ON cu.id = bpp.counter_update_id
                JOIN counters c ON c.id = cu.counter_id
                WHERE bpp.payment_type_id = payment_types.id
                ' . $this->applyFilters($filterData, 'bpp.created_at', 'c.id', 'c.location_id') . ') +

                (SELECT COUNT(bpr.id)
                FROM booking_payment_refunds bpr
                JOIN counter_updates cu ON cu.id = bpr.counter_update_id
                JOIN counters c ON c.id = cu.counter_id
                WHERE bpr.payment_type_id = payment_types.id
                ' . $this->applyFilters($filterData, 'bpr.created_at', 'c.id', 'c.location_id') . ') +

                (SELECT COUNT(cnr.id)
                FROM credit_note_refunds cnr
                JOIN counter_updates cu ON cu.id = cnr.counter_update_id
                JOIN counters c ON c.id = cu.counter_id
                WHERE cnr.payment_type_id = payment_types.id
                ' . $this->applyFilters($filterData, 'cnr.created_at', 'c.id', 'c.location_id') . ')
            ) AS total_transactions')
            )
            ->when($filterData['payment_type_id'], function ($query) use ($filterData): void {
                $query->where('id', (int) $filterData['payment_type_id']);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query
                    ->whereAny(['name'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->where(function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
    }

    private function applyFilters(
        array $filterData,
        string $dateColumn,
        string $counterColumn,
        string $locationColumn
    ): string {
        $query = '';

        if (isset($filterData['counter_ids']) && ! empty($filterData['counter_ids'])) {
            $query .= sprintf(' AND %s IN (', $counterColumn) . implode(
                ',',
                array_map('intval', $filterData['counter_ids'])
            ) . ') ';
        }

        if (isset($filterData['location_ids']) && ! empty($filterData['location_ids'])) {
            $query .= sprintf(' AND %s IN (', $locationColumn) . implode(
                ',',
                array_map('intval', $filterData['location_ids'])
            ) . ') ';
        }

        if (isset($filterData['date']) && ! empty($filterData['date'])) {
            $startDate = CommonFunctions::addStartTime($filterData['date'][0]);
            $endDate = CommonFunctions::addEndTime($filterData['date'][1]);
            $query .= sprintf(" AND %s >= '%s' AND %s <= '%s' ", $dateColumn, $startDate, $dateColumn, $endDate);
        }

        return $query;
    }

    private function paymentTypeQuery(array $filterData, int $companyId): Builder
    {
        return PaymentType::query()
            ->select('id', 'name', 'image_name', 'status')
            ->whereNull('parent_payment_type_id')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->where(function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getAllByCompanyId(int $companyId): Collection
    {
        return PaymentType::query()
            ->select('id', 'name')
            ->where(function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                ->orWhere('company_id', null);
            })
            ->where('status', true)
            ->get();
    }

    public function validatePaymentTypeSaleChannelMatch(PaymentType $paymentType, SaleChannel $saleChannel): bool
    {
        return $paymentType->saleChannels()
            ->wherePivot('sale_channel_id', $saleChannel->id)
            ->exists();
    }
}

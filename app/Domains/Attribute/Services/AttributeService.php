<?php

declare(strict_types=1);

namespace App\Domains\Attribute\Services;

use App\Domains\AttributeChannelReference\AttributeChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Models\Attribute;
use App\Models\AttributeChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AttributeService
{
    public function addUpdateDetails(Attribute $attribute, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start creating or updating the attribute in eCommerce.', [
            'Start time for attribute creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'attribute id: ' . $attribute->getKey(),
        ]);

        $attributeChannelReferenceQueries = resolve(AttributeChannelReferenceQueries::class);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $attributeChannelReference = $attributeChannelReferenceQueries->getByAttributeIdAndSaleChannelId(
                    $attribute->id,
                    $saleChannel->id
                );

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'attribute' => $this->preparedRecords($attribute, $attributeChannelReference),
                ]);

                if ($response->successful()) {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('e_commerce')->info('Response: Attribute in E-Commerce', [
                        'response' => $responseData,
                    ]);

                    if (array_key_exists('attribute_id', $responseData) && ! $attributeChannelReference) {
                        $attributeChannelReferenceQueries = resolve(AttributeChannelReferenceQueries::class);
                        $attributeChannelReferenceQueries->addNew([
                            'sale_channel_id' => $saleChannel->id,
                            'attribute_id' => $attribute->id,
                            'external_attribute_id' => $responseData['attribute_id'],
                        ]);
                    }
                } else {
                    Log::channel('e_commerce')->info('Response: Error on Attribute in E-Commerce', [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                        'request_data' => [
                            'attribute_id' => $attribute->getKey(),
                            'saleChannel_id' => $saleChannel->getKey(),
                        ],
                    ]);
                }
            }
        }

        Log::channel('e_commerce')->info('End creating or updating the attribute in eCommerce.', [
            'End time for attribute creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'attribute id: ' . $attribute->getKey(),
        ]);
    }

    public function deleteDetails(Attribute $attribute, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start delete the attribute in eCommerce.', [
            'Start time for attribute delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'attribute id: ' . $attribute->getKey(),
        ]);

        $attributeChannelReferenceQueries = resolve(AttributeChannelReferenceQueries::class);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $attributeChannelReference = $attributeChannelReferenceQueries->getByAttributeIdAndSaleChannelId(
                    $attribute->id,
                    $saleChannel->id
                );

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'attribute' => [
                        'existing_id' => $attributeChannelReference?->external_attribute_id,
                    ],
                ]);

                if ($response->successful()) {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('e_commerce')->info('Response: Attribute in E-Commerce', [
                        'response' => $responseData,
                    ]);

                    if (array_key_exists('attribute_id', $responseData) && $attributeChannelReference) {
                        $attributeChannelReferenceQueries = resolve(AttributeChannelReferenceQueries::class);
                        $attributeChannelReferenceQueries->deleteById($attributeChannelReference->id);
                    }
                } else {
                    Log::channel('e_commerce')->info('Response: Error on Attribute in E-Commerce', [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                        'request_data' => [
                            'attribute_id' => $attribute->getKey(),
                            'saleChannel_id' => $saleChannel->getKey(),
                        ],
                    ]);
                }
            }
        }

        Log::channel('e_commerce')->info('End delete the attribute in eCommerce.', [
            'End time for attribute delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'attribute id: ' . $attribute->getKey(),
        ]);
    }

    private function preparedRecords(Attribute $attribute, ?AttributeChannelReference $attributeChannelReference): array
    {
        return [
            'existing_id' => $attributeChannelReference?->external_attribute_id,
            'name' => $attribute->name,
            'description' => $attribute->description,
            'field_type' => $attribute->field_type,
            'default_value' => $attribute->default_value,
            'from' => $attribute->from,
            'to' => $attribute->to,
            'options' => $attribute->options,
            'is_required' => $attribute->is_required,
        ];
    }
}

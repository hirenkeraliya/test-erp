<?php

declare(strict_types=1);

use App\Domains\PartiallyReceiveFulfillment\Enums\PartiallyReceiveFulfillmentStatuses;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('partially_receive_fulfillments', function (Blueprint $table): void {
            $table->tinyInteger('status')
                ->default(PartiallyReceiveFulfillmentStatuses::DRAFT->value)
                ->after('received_by_user_type')
                ->comment('1 = draft, 2 = approved, 3 = completed, 4 = cancelled');
        });
    }
};

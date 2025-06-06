<?php

declare(strict_types=1);

use App\Domains\MemberGroup\Enums\GroupTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('member_groups', function (Blueprint $table): void {
            $table->tinyInteger('type_id')->after('code')->default(GroupTypes::MANUAL_GROUP->value);
            $table->tinyInteger('smart_group_type_id')->after('type_id')->nullable();
            $table->tinyInteger('date_condition_type_id')->after('smart_group_type_id')->nullable();
            $table->tinyInteger('element_condition_type_id')->after('date_condition_type_id')->nullable();
            $table->tinyInteger('number_condition_type_id')->after('element_condition_type_id')->nullable();
            $table->date('date')->after('number_condition_type_id')->nullable();
            $table->date('max_date')->after('date')->nullable();
            $table->decimal('value', 10, 2)->after('max_date')->nullable();
            $table->decimal('max_value', 10, 2)->after('value')->nullable();
            $table->bigInteger('members_count')->after('max_value')->nullable();
            $table->integer('created_by_id')->after('members_count')->nullable();
            $table->string('created_by_type')->after('created_by_id')->nullable();
        });
    }
};

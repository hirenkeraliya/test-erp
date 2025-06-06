<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table): void {
            if (Schema::hasColumn('attributes', 'company_id')) {
                if ($this->foreignKeyExists('attributes', 'company_id')) {
                    $table->dropForeign(['company_id']);
                }

                $table->dropColumn('company_id');
            }
        });

        Schema::table('attributes', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('id')->constrained();
        });
    }

    public function foreignKeyExists(string $tableName, string $columnName): bool
    {
        $foreignKeysDefinitions = Schema::getForeignKeys($tableName);
        foreach ($foreignKeysDefinitions as $foreignKeyDefinition) {
            if ($tableName . '_' . $columnName . '_foreign' === $foreignKeyDefinition['name']) {
                return true;
            }
        }

        return false;
    }
};

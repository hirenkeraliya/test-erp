<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('integration_webhook_urls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_id')->constrained();
            $table->tinyInteger('webhook_url_type_id')->comment('IntegrationWebhookUrls Enum is Used.');
            $table->string('url');
            $table->timestamps();
        });
    }
};

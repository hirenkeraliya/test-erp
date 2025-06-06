<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::drop('oauth_auth_codes');
        Schema::drop('oauth_access_tokens');
        Schema::drop('oauth_refresh_tokens');
        Schema::drop('oauth_clients');
        Schema::drop('oauth_personal_access_clients');
    }
};

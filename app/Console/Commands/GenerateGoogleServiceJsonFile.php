<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateGoogleServiceJsonFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-google-service-json-file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Google Service JSON file from .env keys';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (! config('services.firebase.enabled')) {
            $this->info('Firebase Is Not Enabled.');

            return;
        }

        $data = [
            'type' => config('services.firebase.type'),
            'project_id' => config('services.firebase.project_id'),
            'private_key_id' => config('services.firebase.private_key_id'),
            'private_key' => config('services.firebase.private_key'),
            'client_email' => config('services.firebase.client_email'),
            'client_id' => config('services.firebase.client_id'),
            'auth_uri' => config('services.firebase.auth_uri'),
            'token_uri' => config('services.firebase.token_uri'),
            'auth_provider_x509_cert_url' => config('services.firebase.auth_provider_x509_cert_url'),
            'client_x509_cert_url' => config('services.firebase.client_x509_cert_url'),
            'universe_domain' => config('services.firebase.universe_domain'),
        ];

        $json = stripslashes((string) json_encode($data, JSON_PRETTY_PRINT));

        file_put_contents(base_path('firebase.json'), $json);

        $this->info('Google Service JSON file generated successfully.');
    }
}

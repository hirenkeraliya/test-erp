<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DispatchJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatch:job {--class=} {--queue=default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch a job by full path. example: php artisan dispatch:job --class=`App\Jobs\MyJobClass`';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $jobClassName = $this->option('class');
        $queueName = $this->option('queue');

        if ('default' === $queueName) {
            $queueName = config('horizon.default_queue_name');
        }

        if (empty($jobClassName)) {
            $this->error('Please provide the job class name using the --class option.');

            return;
        }

        $job = resolve($jobClassName);

        if (! method_exists($job, 'handle')) {
            $this->error('The provided class is not a valid job class.');

            return;
        }

        dispatch($job)->onQueue($queueName);
        $this->info('Job dispatched successfully.');
    }
}

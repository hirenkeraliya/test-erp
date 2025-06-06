<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;

class JsonFormatterWithExtraDetails
{
    /**
     * Customize the given logger instance.
     *
     * @param Logger $logger
     */
    public function __invoke($logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if (config(
                'app.enable_json_log'
            ) && ($handler instanceof FormattableHandlerInterface && app()->environment() !== 'testing' && app()->environment() !== 'local')) {
                $handler->setFormatter(new JsonFormatter(
                    batchMode: JsonFormatter::BATCH_MODE_JSON,
                    appendNewline: true,
                    ignoreEmptyContextAndExtra: false,
                    includeStacktraces: true
                ));
            }

            // custom web processor with correlation id
            if ($handler instanceof ProcessableHandlerInterface) {
                $webProcessor = new WebProcessor();
                $handler->pushProcessor($webProcessor);

                $handler->pushProcessor(new UidProcessor(7));
                $handler->pushProcessor(new MemoryPeakUsageProcessor());
                $handler->pushProcessor(new MemoryUsageProcessor());
                $handler->pushProcessor(new ProcessIdProcessor());
                $handler->pushProcessor(new IntrospectionProcessor());
            }
        }
    }
}

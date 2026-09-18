<?php
declare(strict_types=1);

namespace Abeta\PunchOut\Service;

use Monolog\Logger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class LoggerService
{
    public function __construct(
        private readonly EntityRepository $logEntryRepository,
    ) {
    }

    public function addEntry(
        string $message,
        Context $context,
        ?\Exception $exception = null,
        ?array $additionalData = null,
        int $level = Logger::DEBUG
    ): void {
        if (!\is_array($additionalData)) {
            $additionalData = [];
        }

        // Add exception to array
        if ($exception !== null) {
            $additionalData['error'] = [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace(),
            ];
        }

        // Add data to the log entry
        $logEntry = [
            'message' => mb_substr($message, 0, 255),
            'level' => $level,
            'channel' => mb_substr('abeta', 0, 255),
            'context' => [
                'source' => 'AbetaPunchOut',
                'cartExportData' => $additionalData,
            ],
        ];

        $this->logEntryRepository->create([$logEntry], $context);
    }
}

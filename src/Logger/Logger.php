<?php

declare(strict_types=1);

namespace BankId\Merchant\Logger;

use BankId\Merchant\Configuration\Configuration;
use BankId\Merchant\Contract\LoggerInterface;

class Logger implements LoggerInterface
{
    protected bool $enableXMLLogs;
    protected string $logPath;
    protected string $folderNamePattern;
    protected string $fileNamePrefix;
    protected bool $enableInternalLogs;
    protected string $fileName;

    public function __construct(Configuration $configuration)
    {
        $this->enableXMLLogs = $configuration->serviceLogsEnabled;
        $this->logPath = $configuration->serviceLogsLocation;
        $this->folderNamePattern = $configuration->logsPattern;
        $this->fileNamePrefix = $configuration->serviceLogsPattern;
        $this->enableInternalLogs = $configuration->logsEnabled;
        $this->fileName = $configuration->logsFileName;
    }

    public function log(string $message): void
    {
        $callers = debug_backtrace();
        $this->write($callers[1]['function'], $message);
    }

    public function logXmlMessage(\DOMDocument|string $dom, bool $isXML = false, string $fileName = ''): void
    {
        if ($this->enableXMLLogs) {
            if ($isXML && is_string($dom)) {
                $domtree = new \DOMDocument();
                $domtree->loadXML($dom);
                $dom = $domtree;
            }

            $now = new \DateTime();
            $msec = substr(explode(' ', microtime())[0], 2, 3);

            $localName = (!empty($dom->documentElement->localName) ? $dom->documentElement->localName : '');
            if (!empty($fileName)) {
                $localName = $fileName;
            }

            $filename = self::replacePattern($this->fileNamePrefix, $now, $msec, $localName);
            $fullPath = $this->logPath . DIRECTORY_SEPARATOR . $filename;

            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            $dom->save($fullPath);
        }
    }

    public function write(string $function, string $message): void
    {
        if ($this->enableInternalLogs) {
            $now = new \DateTime();
            $msec = substr(explode(' ', microtime())[0], 2, 3);

            $logsSubdir = self::replacePattern($this->folderNamePattern, $now, $msec);
            $fullPath = $this->logPath . DIRECTORY_SEPARATOR . $logsSubdir . DIRECTORY_SEPARATOR . $this->fileName;

            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            file_put_contents(
                $fullPath,
                date('Y-m-d H:i:s : ') . $function . '() - ' . $message . "\n",
                FILE_APPEND
            );
        }
    }

    private static function replacePattern(string $pattern, \DateTime $now, string $msec, string $action = ''): string
    {
        return str_replace(
            ['%Y', '%M', '%D', '%h', '%m', '%s', '%f', '%a'],
            [$now->format('Y'), $now->format('m'), $now->format('d'), $now->format('H'), $now->format('i'), $now->format('s'), $msec, $action],
            $pattern
        );
    }
}

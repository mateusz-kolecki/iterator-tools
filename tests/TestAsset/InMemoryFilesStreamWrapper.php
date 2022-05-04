<?php

declare(strict_types=1);

namespace IteratorTools\TestAsset;

use function array_filter;
use function array_values;
use function func_get_args;
use function parse_url;
use function strlen;
use function substr;
use const SEEK_CUR;
use const SEEK_END;
use const SEEK_SET;

/**
 * @psalm-suppress MissingConstructor
 */
class InMemoryFilesStreamWrapper
{
    /** @psalm-var array<string, string> */
    private static array $files = [];

    /** @psalm-var array<string, list<array{method: string, args: list<mixed>}>> */
    private static array $calls = [];

    private string $fileName;
    private int $position;

    public static function reset(): void
    {
        self::$files = [];
        self::$calls = [];
    }

    public static function putFile(string $fileName, string $content): void
    {
        self::$files[$fileName] = $content;
    }

    /**
     * @psalm-return list<array{method: string, args: list<mixed>}>
     */
    public static function getCalls(string $fileName, string $methodName = null): array
    {
        $fileCalls = self::$calls[$fileName] ?? [];

        if (null === $methodName) {
            return $fileCalls;
        }

        return array_values(array_filter($fileCalls, function (array $call) use ($methodName): bool {
            return $call['method'] === $methodName;
        }));
    }

    /**
     * @psalm-param list<mixed> $args
     */
    private function registerCall(string $methodName, array $args): void
    {
        list(, $methodName) = preg_split('/::/', $methodName);

        self::$calls[$this->fileName][] = [
            'method' => $methodName,
            'args' => $args
        ];
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $path = parse_url($path, PHP_URL_HOST);

        self::$calls[$path] = [];
        $this->fileName = $path;
        $this->position = 0;

        $this->registerCall(__METHOD__, func_get_args());

        return true;
    }

    public function stream_close(): void
    {
        $this->registerCall(__METHOD__, func_get_args());
    }

    public function stream_read(int $count): string
    {
        $this->registerCall(__METHOD__, func_get_args());

        $ret = substr(self::$files[$this->fileName], $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }

    public function stream_write(string $data): int
    {
        $left = substr(self::$files[$this->fileName], 0, $this->position);
        $right = substr(self::$files[$this->fileName], $this->position + strlen($data));

        self::$files[$this->fileName] = $left . $data . $right;
        $this->position += strlen($data);

        return strlen($data);
    }

    public function stream_tell(): int
    {
        return $this->position;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen(self::$files[$this->fileName]);
    }

    public function stream_seek(int $offset, int $whence): bool
    {
        switch ($whence) {
            case SEEK_SET:
                if ($offset < strlen(self::$files[$this->fileName]) && 0 <= $offset) {
                    $this->position = $offset;
                    return true;
                }
                return false;

            case SEEK_CUR:
                if (0 <= $offset) {
                    $this->position += $offset;
                    return true;
                }
                return false;


            case SEEK_END:
                if (0 <= strlen(self::$files[$this->fileName]) + $offset) {
                    $this->position = strlen(self::$files[$this->fileName]) + $offset;
                    return true;
                }
                return false;
        }

        return false;
    }

    /**
     * @param mixed $value
     */
    public function stream_metadata(string $path, int $option, $value): bool
    {
        return false;
    }
}

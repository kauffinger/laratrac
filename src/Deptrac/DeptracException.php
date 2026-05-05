<?php

namespace Laratrac\Laratrac\Deptrac;

use Illuminate\Contracts\Process\ProcessResult;
use RuntimeException;

class DeptracException extends RuntimeException
{
    public static function fromResult(string $command, ProcessResult $result): self
    {
        return new self(sprintf(
            "deptrac %s failed (exit %d):\n%s",
            $command,
            $result->exitCode() ?? -1,
            trim($result->errorOutput() ?: $result->output()),
        ));
    }

    public static function binaryMissing(string $expectedPath): self
    {
        return new self(
            "deptrac binary not found at {$expectedPath}. Install it with `composer require --dev deptrac/deptrac`."
        );
    }
}

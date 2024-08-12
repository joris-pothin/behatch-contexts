<?php
declare(strict_types=1);

namespace Behatch\HttpCall;

class HttpCallResult
{
    public function __construct(private $value)
    {
    }

    public function update($value): void
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}

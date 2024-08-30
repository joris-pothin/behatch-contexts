<?php
declare(strict_types=1);

namespace Behatch\HttpCall;

class HttpCallResultPool
{
    private ?HttpCallResult $result = null;

    public function store(HttpCallResult $result): void
    {
        $this->result = $result;
    }

    public function getResult(): ?HttpCallResult
    {
        return $this->result;
    }
}

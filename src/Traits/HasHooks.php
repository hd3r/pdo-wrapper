<?php

declare(strict_types=1);

namespace PdoWrapper\Traits;

use Closure;

trait HasHooks
{
    /** @var array<string, Closure[]> */
    private array $hooks = [];

    public function on(string $event, Closure $callback): void
    {
        $this->hooks[$event][] = $callback;
    }

    protected function trigger(string $event, array $data): void
    {
        foreach ($this->hooks[$event] ?? [] as $callback) {
            $callback($data);
        }
    }
}

<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SomethingNoteworthyHappened
{
    use Dispatchable;

    public function __construct(public string $message) {}
}

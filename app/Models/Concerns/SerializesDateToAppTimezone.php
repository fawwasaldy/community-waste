<?php

namespace App\Models\Concerns;

use DateTimeInterface;

trait SerializesDateToAppTimezone
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }
}

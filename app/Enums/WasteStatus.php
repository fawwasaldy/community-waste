<?php

namespace App\Enums;

enum WasteStatus: string
{
    case Pending = 'pending';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Canceled = 'canceled';
}

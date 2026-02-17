<?php

namespace App\Enums;

enum WasteType: string
{
    case Organic = 'organic';
    case Plastic = 'plastic';
    case Paper = 'paper';
    case Electronic = 'electronic';
}

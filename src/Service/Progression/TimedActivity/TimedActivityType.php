<?php

declare(strict_types=1);

namespace App\Service\Progression\TimedActivity;

enum TimedActivityType: string
{
    case Mission = 'mission';
    case Work = 'work';
    case Training = 'training';
}

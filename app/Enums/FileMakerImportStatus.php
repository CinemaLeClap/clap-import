<?php

namespace App\Enums;

enum FileMakerImportStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}

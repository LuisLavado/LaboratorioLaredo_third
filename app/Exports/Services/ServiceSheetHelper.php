<?php

namespace App\Exports\Services;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

trait ServiceSheetHelper
{
    protected function getProperty($item, $property, $default = null)
    {
        if (is_array($item)) {
            return $item[$property] ?? $default;
        } elseif (is_object($item)) {
            return $item->$property ?? $default;
        }
        return $default;
    }
}

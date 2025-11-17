<?php

declare(strict_types=1);

namespace App\Exports\Attendance\Sheets;

use App\Support\Reports\MonthlyAttendanceReportFormatter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummarySheet implements FromArray, ShouldAutoSize, WithEvents, WithStyles, WithTitle
{
    private int $tableHeaderRow = 0;

    public function __construct(private readonly array $report) {}

    public function title(): string
    {
        return 'Summary';
    }

    public function array(): array
    {
        $rows = [
            ['Monthly Attendance Summary'],
        ];

        foreach (MonthlyAttendanceReportFormatter::metadata($this->report) as $meta) {
            $rows[] = $meta;
        }

        $rows[] = ['Total Records', $this->report['summary']['total_records'] ?? 0];
        $rows[] = [];

        $table = MonthlyAttendanceReportFormatter::summaryTable($this->report);
        $this->tableHeaderRow = count($rows) + 1;
        $rows = array_merge($rows, $table);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
            ],
        ];

        if ($this->tableHeaderRow > 0) {
            $styles[$this->tableHeaderRow] = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE5E7EB'],
                ],
            ];
        }

        return $styles;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                if ($this->tableHeaderRow > 0) {
                    $event->sheet->freezePane('A' . ($this->tableHeaderRow + 1));
                }
            },
        ];
    }
}

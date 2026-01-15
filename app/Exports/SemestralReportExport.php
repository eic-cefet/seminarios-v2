<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SemestralReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Collection $data;

    protected string $year;

    protected string $semester;

    public function __construct(Collection $data, string $year, string $semester)
    {
        $this->data = $data;
        $this->year = $year;
        $this->semester = $semester;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Nome',
            'Email',
            'Curso',
            'Horas em Seminários',
            'Apresentações Assistidas',
        ];
    }

    public function map($row): array
    {
        // Format presentations as a comma-separated list with dates
        $presentations = $row['presentations']
            ->map(function ($presentation) {
                $date = \Carbon\Carbon::parse($presentation['date'])->format('d/m/Y');
                $type = $presentation['type'] ? " ({$presentation['type']})" : '';

                return "{$presentation['name']}{$type} - {$date}";
            })
            ->join('; ');

        return [
            $row['name'],
            $row['email'],
            $row['course'],
            $row['total_hours'],
            $presentations,
        ];
    }

    public function title(): string
    {
        return "Relatório {$this->year}.{$this->semester}";
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
}

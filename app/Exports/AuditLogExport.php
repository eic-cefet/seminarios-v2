<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditLogExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Builder $queryBuilder;

    protected int $days;

    public function __construct(Builder $queryBuilder, int $days)
    {
        $this->queryBuilder = $queryBuilder;
        $this->days = $days;
    }

    public function query(): Builder
    {
        return $this->queryBuilder;
    }

    public function headings(): array
    {
        return [
            'Data',
            'Usuário',
            'Evento',
            'Tipo',
            'Entidade',
            'Origem',
            'IP',
            'Dados',
        ];
    }

    public function map($row): array
    {
        $auditableType = $row->auditable_type ? class_basename($row->auditable_type) : '';
        $auditable = $auditableType ? "{$auditableType}#{$row->auditable_id}" : '';

        return [
            Carbon::parse($row->created_at)->format('d/m/Y H:i:s'),
            $row->user_id ? "User#{$row->user_id}" : '',
            $row->event_name,
            $row->event_type->value === 'manual' ? 'Manual' : 'Sistema',
            $auditable,
            $row->origin ?? '',
            $row->ip_address ?? '',
            $row->event_data ? json_encode($row->event_data, JSON_UNESCAPED_UNICODE) : '',
        ];
    }

    public function title(): string
    {
        return "Auditoria - Últimos {$this->days} dias";
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

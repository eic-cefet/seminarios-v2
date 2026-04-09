<?php

use App\Exports\SemestralReportExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

describe('SemestralReportExport', function () {
    it('returns the data collection', function () {
        $data = collect([
            ['name' => 'John', 'email' => 'john@test.com'],
        ]);

        $export = new SemestralReportExport($data, '2024', '1');

        expect($export->collection())->toBe($data);
    });

    it('has correct headings', function () {
        $export = new SemestralReportExport(collect([]), '2024', '1');

        $headings = $export->headings();

        expect($headings)->toBe([
            'Nome',
            'Email',
            'Curso',
            'Horas em Seminários',
            'Apresentações Assistidas',
        ]);
    });

    it('maps row data correctly', function () {
        $row = [
            'name' => 'Maria Silva',
            'email' => 'maria@test.com',
            'course' => 'Computer Science',
            'total_hours' => 10,
            'presentations' => collect([
                ['name' => 'Seminar 1', 'date' => '2024-06-15', 'type' => 'Palestra'],
                ['name' => 'Seminar 2', 'date' => '2024-06-20', 'type' => null],
            ]),
        ];

        $export = new SemestralReportExport(collect([]), '2024', '1');
        $mapped = $export->map($row);

        expect($mapped[0])->toBe('Maria Silva');
        expect($mapped[1])->toBe('maria@test.com');
        expect($mapped[2])->toBe('Computer Science');
        expect($mapped[3])->toBe(10);
        expect($mapped[4])->toContain('Seminar 1 (Palestra) - 15/06/2024');
        expect($mapped[4])->toContain('Seminar 2 - 20/06/2024');
    });

    it('handles empty presentations', function () {
        $row = [
            'name' => 'Test User',
            'email' => 'test@test.com',
            'course' => 'Engineering',
            'total_hours' => 0,
            'presentations' => collect([]),
        ];

        $export = new SemestralReportExport(collect([]), '2024', '1');
        $mapped = $export->map($row);

        expect($mapped[4])->toBe('');
    });

    it('has correct title with year and semester', function () {
        $export = new SemestralReportExport(collect([]), '2024', '2');

        expect($export->title())->toBe('Relatório 2024.2');
    });

    it('applies bold style to header row', function () {
        $export = new SemestralReportExport(collect([]), '2024', '1');

        $worksheet = $this->createMock(Worksheet::class);
        $styles = $export->styles($worksheet);

        expect($styles)->toHaveKey(1);
        expect($styles[1]['font']['bold'])->toBeTrue();
    });

    it('can be exported to excel', function () {
        Excel::fake();

        $data = collect([
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'course' => 'CS',
                'total_hours' => 5,
                'presentations' => collect([]),
            ],
        ]);

        $export = new SemestralReportExport($data, '2024', '1');

        Excel::store($export, 'report.xlsx');

        Excel::assertStored('report.xlsx');
    });
});

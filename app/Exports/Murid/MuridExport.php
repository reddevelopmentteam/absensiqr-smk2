<?php

namespace App\Exports\Murid;

use App\Models\Murid\Murid;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export data murid ke Excel sesuai filter yang dipilih pada modal export.
 * Query sudah dibatasi (wali kelas, rombel, status) oleh MuridExportService.
 */
class MuridExport extends DefaultValueBinder implements FromQuery, ShouldAutoSize, WithColumnWidths, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles
{
    /**
     * Kolom identitas harus tetap teks supaya angka nol di depan
     * (NIPD, NISN, kode pos, telepon, RT/RW) tidak hilang di Excel.
     */
    private const TEXT_COLUMNS = ['C', 'D', 'J', 'K', 'N', 'O'];

    private int $rowNumber = 0;

    public function __construct(
        private readonly Builder $query,
        private readonly bool $includePhoto = false,
    ) {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function bindValue(Cell $cell, $value)
    {
        if (in_array($cell->getColumn(), self::TEXT_COLUMNS, true) && $value !== null && $value !== '') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        $headings = [
            'No',
            'Nama',
            'NIPD',
            'NISN',
            'JK',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Agama',
            'Alamat',
            'RT',
            'RW',
            'Kelurahan',
            'Kecamatan',
            'Kode Pos',
            'Telepon',
            'E-mail',
            'Nama Ayah',
            'Nama Ibu',
            'Nama Wali',
            'Rombel Saat Ini',
            'Status',
        ];

        if ($this->includePhoto) {
            $headings[] = 'Foto (URL)';
        }

        return $headings;
    }

    /**
     * @param  Murid  $murid
     * @return array<int, mixed>
     */
    public function map($murid): array
    {
        $this->rowNumber++;

        $row = [
            $this->rowNumber,
            $murid->nama,
            $murid->nipd,
            $murid->nisn,
            $murid->jk,
            $murid->tempat_lahir,
            $this->formatDate($murid->tanggal_lahir),
            $murid->agama,
            $murid->alamat,
            $murid->rt,
            $murid->rw,
            $murid->kelurahan,
            $murid->kecamatan,
            $murid->kode_pos,
            $murid->hp,
            $murid->email,
            $murid->nama_ayah,
            $murid->nama_ibu,
            $murid->nama_wali,
            $murid->rombel?->nama_lengkap,
            $this->statusLabel($murid->status),
        ];

        if ($this->includePhoto) {
            $row[] = $murid->image_path;
        }

        return $row;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1D4ED8'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 30,
            'I' => 40,
            'P' => 26,
            'Q' => 24,
            'R' => 24,
            'S' => 24,
            'T' => 18,
        ];
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = max(1, $sheet->getHighestRow());
                $highestColumn = $sheet->getHighestColumn();

                $sheet->freezePane('A2');
                $sheet->setAutoFilter(sprintf('A1:%s%d', $highestColumn, $highestRow));

                $sheet->getStyle(sprintf('A1:%s%d', $highestColumn, $highestRow))
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }

    private function formatDate(mixed $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return (string) $date;
    }

    private function statusLabel(?string $status): string
    {
        $status = trim((string) $status);

        if ($status === '') {
            return '-';
        }

        return ucfirst(strtolower($status));
    }
}

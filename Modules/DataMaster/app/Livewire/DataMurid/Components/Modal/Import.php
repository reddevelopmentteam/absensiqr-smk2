<?php

namespace Modules\DataMaster\Livewire\DataMurid\Components\Modal;

use App\Helpers\ToastMagic;
use App\Imports\DataMuridImport;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class Import extends Component
{
    use WithFileUploads;

    public $file;
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public int $processedRows = 0;
    public int $totalRows = 0;
    public int $importPercent = 0;
    public bool $isImporting = false;
    public int $currentRow = 6;
    private int $headingRow = 5;
    public int $chunkSize = 365;
    public int $tahunMasuk = 0;
    private int $maxExecutionTime = 120;


    public function mount(): void
    {
        $this->tahunMasuk = now()->year;
    }

    public function import(): void
    {
        $this->extendExecutionTime();

        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $this->resetImportProgress();

        $fullPath = $this->getUploadPath();
        [$this->headingRow, $this->currentRow] = $this->detectHeaderAndDataRows($fullPath);
        $this->totalRows = $this->countRows($fullPath, $this->currentRow);

        if ($this->totalRows === 0) {
            ToastMagic::warning('Import dibatalkan', 'Tidak ada baris data yang bisa diproses.');
            return;
        }

        $this->isImporting = true;
    }

    public function pollProgress(): void
    {
        $this->extendExecutionTime();

        if (!$this->isImporting) {
            return;
        }

        $this->processNextChunk();
    }

    private function finishImport(): void
    {
        $this->isImporting = false;
        if ($this->file) {
            $this->file->delete();
        }
        $this->reset('file');
        $this->dispatch('murid-refresh');
        $this->dispatch('imported');

        ToastMagic::success(
            'Import selesai',
            "Berhasil: {$this->importedCount} | Dilewati: {$this->skippedCount}."
        );
    }

    private function resetImportProgress(): void
    {
        $this->processedRows = 0;
        $this->totalRows = 0;
        $this->importPercent = 0;
        $this->importedCount = 0;
        $this->skippedCount = 0;
        $this->currentRow = 6;
    }

    /**
     * Dapodik exports have four metadata rows, a main header on row 5, and a
     * second header on row 6 for the grouped parent columns. The project
     * template has data directly on row 6, so detect which layout was uploaded.
     */
    private function detectHeaderAndDataRows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new class implements IReadFilter {
            public function readCell($column, $row, $worksheetName = ''): bool
            {
                return $row === 5 || $row === 6;
            }
        });
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $firstCellRow6 = $sheet->getCell('A6')->getValue();
        $row6HasData = is_numeric($firstCellRow6);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $reader);

        return [5, $row6HasData ? 6 : 7];
    }

    private function countRows(string $path, int $dataStartRow): int
    {
        $this->extendExecutionTime();

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);
        $worksheet = $spreadsheet->getActiveSheet();

        $highestRow = (int) $worksheet->getHighestRow();

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        unset($reader);

        return max($highestRow - $dataStartRow + 1, 0);
    }

    private function processNextChunk(): void
    {
        $this->extendExecutionTime();

        $path = $this->getUploadPath();
        $rows = $this->readChunkRows($path, $this->currentRow, $this->chunkSize);

        if (!$rows) {
            $this->finishImport();
            return;
        }

        $import = new DataMuridImport(
            null,
            null,
            $this->totalRows,
            $this->tahunMasuk,
        );
        $import->collection(collect($rows));

        $this->importedCount += $import->getImportedCount();
        $this->skippedCount += $import->getSkippedCount();

        $this->processedRows = min($this->processedRows + count($rows), $this->totalRows);
        $this->importPercent = $this->totalRows > 0
            ? (int) round(($this->processedRows / $this->totalRows) * 100)
            : 0;

        $this->currentRow += $this->chunkSize;

        if ($this->processedRows >= $this->totalRows) {
            $this->finishImport();
        }
    }

    private function readChunkRows(string $path, int $startRow, int $chunkSize): array
    {
        $this->extendExecutionTime();

        $headingRow = $this->headingRow;
        $endRow = $startRow + $chunkSize - 1;

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new class($headingRow, $startRow, $endRow) implements IReadFilter {
            public function __construct(
                private int $headingRow,
                private int $startRow,
                private int $endRow
            ) {}

            public function readCell($column, $row, $worksheetName = ''): bool
            {
                return $row === $this->headingRow
                    || ($row >= $this->startRow && $row <= $this->endRow);
            }
        });

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = $sheet->getHighestColumn();

        $headingRange = "A{$headingRow}:{$highestColumn}{$headingRow}";
        $headings = $sheet->rangeToArray($headingRange, null, true, false)[0] ?? [];

        $rows = [];
        $dataRange = "A{$startRow}:{$highestColumn}{$endRow}";
        $valueRows = $sheet->rangeToArray($dataRange, null, true, false);

        foreach ($valueRows as $values) {

            $assoc = [];
            $hasValue = false;

            foreach ($values as $index => $value) {
                $heading = $headings[$index] ?? null;
                if ($heading === null || $heading === '') {
                    continue;
                }

                $assoc[$heading] = $value;

                if ($this->hasMeaningfulValue($value)) {
                    $hasValue = true;
                }
            }

            if ($hasValue) {
                $rows[] = $assoc;
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        unset($reader);

        return $rows;
    }

    private function hasMeaningfulValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }

    private function extendExecutionTime(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit($this->maxExecutionTime);
        }
    }

    private function getUploadPath(): string
    {
        $path = $this->file?->getRealPath();
        if ($path) {
            return $path;
        }

        $path = $this->file?->getPathname();
        if ($path) {
            return $path;
        }

        throw new \RuntimeException('Upload path is not available.');
    }

    public function render()
    {
        return view('datamaster::livewire.data-murid.components.modal.import');
    }
}

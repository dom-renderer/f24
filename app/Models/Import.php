<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Import extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public static function recordImport($data, $file, $canRewrite = false) {
        try {
            $originalPath = storage_path('app/public/imports/original');
            $modifiedPath = storage_path('app/public/imports/modified');
            if (!file_exists($originalPath)) {
                mkdir($originalPath, 0777, true);
            }
            if (!file_exists($modifiedPath)) {
                mkdir($modifiedPath, 0777, true);
            }
            $modified = $original = null;
            if ($canRewrite) {
                $fileName = date('YmdHis') . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($originalPath, $fileName);
                $modified = $original = $fileName;
                /**
                 * Update XLSX
                 ****/
                $inputPath = "{$originalPath}/{$fileName}";
                $outputPath = "{$modifiedPath}/{$fileName}";
                
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                $spreadsheet = $reader->load($inputPath);
                $worksheet = $spreadsheet->getActiveSheet();
                
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                
                $worksheet->setCellValueByColumnAndRow($highestColumnIndex + 1, 1, 'Status');
                $worksheet->setCellValueByColumnAndRow($highestColumnIndex + 2, 1, 'Message');
                
                $iteration = 0;

                for ($row = 2; $row <= $highestRow; $row++) {
                    if (isset($data['response'][$iteration])) {
                        $worksheet->setCellValueByColumnAndRow($highestColumnIndex + 1, $row, 'Error');
                        $worksheet->setCellValueByColumnAndRow($highestColumnIndex + 2, $row, $data['response'][$iteration]);
                    } else if (isset($data['leave_blank'][$iteration])) {
                        $worksheet->setCellValueByColumnAndRow($highestColumnIndex + 1, $row, '');
                        $worksheet->setCellValueByColumnAndRow($highestColumnIndex + 2, $row, '');
                    } else if (isset($data['skip'][$iteration])) {
                        $worksheet->setCellValueByColumnAndRow($highestColumnIndex + 1, $row, 'Skip');
                        $worksheet->setCellValueByColumnAndRow($highestColumnIndex + 2, $row, $data['skip'][$iteration]);
                    } else {
                        $worksheet->setCellValueByColumnAndRow($highestColumnIndex + 1, $row, 'Success');
                        $worksheet->setCellValueByColumnAndRow($highestColumnIndex + 2, $row, '');
                    }
                    $iteration++;
                }
                
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($outputPath);
                
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
                /**
                 * Update XLSX
                 ****/
                
            } else {
                $fileName = date('YmdHis') . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($originalPath, $fileName);
                $modified = $original = $fileName;
            }

            \App\Models\Import::create([
                'file_name' => $data['file_name'],
                'success' => $data['success'],
                'error' => $data['error'],
                'status' => $data['status'],
                'skip' => isset($data['skip_count']) ? $data['skip_count'] : 0,
                'original_file' => $original,
                'modified_file' => $modified,
                'uploaded_by' => auth()->check() ? auth()->user()->id : null,
                'response' => $data['response']
            ]);
        } catch (\Exception $e) {
            Log::error('SCHEDULING IMPORT ERROR WHILE LOGGING : ' . $e->getMessage() . ' ON LINE : ' . $e->getLine());
        }
    }    
}

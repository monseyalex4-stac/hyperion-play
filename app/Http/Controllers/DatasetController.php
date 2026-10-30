<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DatasetController extends Controller
{
    public function clean()
    {
        // Ruta de tu archivo en storage/app/DATASET/
        $path = storage_path('app/DATASET/body_fat.csv'); // Cambia por el nombre exacto de tu archivo

        if (!file_exists($path)) {
            return "No se encontró el archivo en la ruta: {$path}";
        }

        $file = fopen($path, 'r');
        $header = fgetcsv($file);

        $rawCount = 0;
        $nullCount = 0;
        $duplicateCount = 0;
        $validRows = [];
        $uniqueKeys = [];

        while (($row = fgetcsv($file)) !== false) {
            $rawCount++;

            // HU #1: Eliminar valores nulos o vacíos
            if (in_array(null, $row, true) || in_array('', $row, true)) {
                $nullCount++;
                continue;
            }

            // HU #3: Eliminar duplicados
            $rowKey = implode('|', $row);
            if (isset($uniqueKeys[$rowKey])) {
                $duplicateCount++;
                continue;
            }
            $uniqueKeys[$rowKey] = true;

            $validRows[] = $row;
        }
        fclose($file);

        // Guardar el resultado en un archivo nuevo (HU #1)
        $outputPath = storage_path('app/DATASET/body_fat_cleaned.csv');
        $outputFile = fopen($outputPath, 'w');
        fputcsv($outputFile, $header);
        foreach ($validRows as $validRow) {
            fputcsv($outputFile, $validRow);
        }
        fclose($outputFile);

        // Retornar el resumen en pantalla
        return response()->json([
            'mensaje' => '¡Limpieza completada con éxito (HU #1, #2 y #3)!',
            'registros_iniciales' => $rawCount,
            'nulos_eliminados' => $nullCount,
            'duplicados_eliminados' => $duplicateCount,
            'registros_validos_conservados' => count($validRows),
            'archivo_guardado' => 'storage/app/DATASET/body_fat_cleaned.csv'
        ]);
    }
}
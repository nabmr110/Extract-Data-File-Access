<?php

namespace App\Http\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;


class FileController extends Controller
{
    public function index()
    {
        return view('export');
    }

    public function dashboard()
    {
        $files = Storage::files('processed');
        return view('dashboard', compact('files'));
    }

   public function upload(Request $request)
    {
        ini_set('max_execution_time', 0);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        // Save uploaded file
        $file = $request->file('file');
        $filename = uniqid() . '_' . $file->getClientOriginalName();
        $storedPath = Storage::putFileAs('uploads', $file, $filename);
        $fullInputPath = Storage::disk('local')->path($storedPath);

        Log::info('Stored path: ' . $storedPath);
        Log::info('Full input path: ' . $fullInputPath);

        if (!file_exists($fullInputPath)) {
            Log::error('File not found at: ' . $fullInputPath);
            return redirect()->route('upload.form')->with('error', 'Uploaded file not found.');
        }

        // Run the Python script
        $process = new Process(['python', base_path('processor.py'), $fullInputPath]);
        $process->setTimeout(0);
        $process->run();
        Log::info("Python Output: " . $process->getOutput());

        if (!$process->isSuccessful()) {
            Log::error('Python script failed: ' . $process->getErrorOutput());
            return back()->with('error', 'Python processing failed.');
        }

        // Get processed file
        $processedFilename = pathinfo($filename, PATHINFO_FILENAME) . '_summary.xlsx';
        $processedPath = 'processed/' . $processedFilename;

        if (!Storage::exists($processedPath)) {
            Log::error("Processed file not found: $processedPath");
            return back()->with('error', 'Processed file not found.');
        }

        // ✅ Load the Excel file and read the data
        $fullProcessedPath = Storage::disk('local')->path($processedPath);
        $spreadsheet = IOFactory::load($fullProcessedPath);
        $worksheet = $spreadsheet->getActiveSheet();

        $excelData = [];
        foreach ($worksheet->toArray() as $row) {
            $excelData[] = $row;
        }

        // ✅ Return with data to display and download link
        return back()->with([
            'success' => 'File uploaded and processed successfully!',
            'path' => $processedPath,
            'excelData' => $excelData
        ]);
    }

    public function download($filename)
    {
        $path = storage_path('app/processed/' . $filename);

        if (!file_exists($path)) {
            Log::error("Download failed. File not found: $path");
            abort(404);
        }

        Log::info("Attempting to download file: $path");
        Log::info("File size: " . filesize($path));
        return response()->download($path);
    }

    public function generateReport($filename)
    {
        //Set max execution time to 5 minutes
        ini_set('max_execution_time', 300);

        $originalFileName = str_replace('_summary', '', $filename);
        $fullInputPath = storage_path('app/uploads/' . $originalFileName);

        $process = new Process(['python', base_path('processor.py'), $fullInputPath]);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error('Python script error during report generation: ' . $process->getErrorOutput());
            return redirect()->route('dashboard')->with('error', 'Failed to regenerate report.');
        }

        return redirect()->route('dashboard')->with('success', 'Report regenerated successfully.');
    }

    public function showReport($filename)
    {
        $decodedFilename = urldecode($filename);
        $inputPath = storage_path("app/processed/" . $decodedFilename);

        if (!file_exists($inputPath)) {
            abort(404, 'File not found');
        }

        // Convert Excel to HTML (Required for Dompdf)
        $spreadsheet = IOFactory::load($inputPath);
        $writer = IOFactory::createWriter($spreadsheet, 'Html'); 
        ob_start();
        $writer->save('php://output');
        $html = ob_get_clean();

        // Clean and round numbers in HTML (e.g., 274.9199999999999 → 274.92)
        $html = preg_replace_callback('/\d+\.\d{6,}/', function ($matches) {
            return number_format((float)$matches[0], 2);
        }, $html);

        $html = '
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: Arial, sans-serif;
                }
                .summary-title {
                    text-align: center;
                    font-size: 20px;
                    margin-bottom: 5px;
                    font-weight: bold;
                    page-break-after: avoid; /* Avoid breaking after title */
                }
                table {
                    margin: 0 auto;
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 11px;
                }
                th, td {
                    text-align: center;
                    padding: 4px;
                    border: 1px solid #000;
                }
            </style>
            <div class="summary-title">MANPOWER & TOTAL HOURS WORKED SUMMARY</div>
        ' . $html;



        // Initialize Dompdf
        $options = new Options();
        // $options->set('defaultFont', 'Arial'); 
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Save PDF
        $pdfFilename = pathinfo($decodedFilename, PATHINFO_FILENAME) . ".pdf";
        // $outputPath = storage_path("app/processed/" . $pdfFilename);

        // Serve the PDF inline
        return Response::make($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdfFilename.'"'
        ]);
    }

}

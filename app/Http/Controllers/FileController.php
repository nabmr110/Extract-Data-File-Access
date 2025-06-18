<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use Dompdf\Dompdf;
use Dompdf\Options;
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

    

}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Export File</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
        }
        .sidebar {
            min-width: 220px;
            background-color: #343a40;
            height: 100vh;
            padding-top: 20px;
            color: white;
        }
        .sidebar .brand {
            text-align: center;
            margin-bottom: 30px;
        }
        .sidebar .brand img {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .sidebar .brand h4 {
            font-size: 1.2rem;
            margin: 0;
        }
        .sidebar a {
            color: white;
            display: block;
            padding: 12px 20px;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content {
            flex-grow: 1;
            padding: 30px;
        }
    </style>
</head>
<body>

     <div class="sidebar">
        <div class="brand">
            <img src="{{ asset('images/logo.png') }}" alt="Company Logo">  {{-- Place your logo image in public/images/logo.png --}}
            <h4>Meiban</h4>
        </div>
        <a href="{{ route('dashboard') }}">📊 Dashboard</a>
        <a href="{{ route('upload.form') }}">📤 Upload File</a>
    </div>

    <div class="content">
        <h2 class="mb-4">📤 Upload & Process File</h2>

        @if(session('success'))
            <div class="alert alert-success">
                <p>{{ session('success') }}</p>
                @if(session('path'))
                    <p>Processed File: {{ basename(session('path')) }}</p>
                @endif
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label">Choose Excel or CSV File</label>
                <input type="file" name="file" id="file" class="form-control" accept=".xls,.xlsx,.csv" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload & Process</button>
        </form>

        @if(session('path'))
            <hr>
            <h4>Download Processed File</h4>
            <a href="{{ route('download', ['filename' => basename(session('path'))]) }}" class="btn btn-success">
                Download Processed File
            </a>
        @endif

        @if(session('excelData'))
        <hr>
        <h4>📄 Preview of Processed Data</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                @foreach(session('excelData') as $i => $row)
                    @if($i === 0)
                        <thead>
                            <tr>
                                @foreach($row as $cell)
                                    <th>{{ $cell }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                    @else
                        @php
                            $isGrandTotal = isset($row[1]) && strcasecmp(trim($row[1]), 'Grand Total') === 0;
                        @endphp
                        <tr @if($isGrandTotal) style="background-color: #fbdc2e;" @endif>
                            @foreach($row as $cell)
                                <td>
                                    @if(is_numeric($cell))
                                        {{ number_format((float)$cell, 2) }}
                                    @else
                                        {{ $cell }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
                        </tbody>
            </table>
        </div>
    @endif

    </div>
</body>
</html>

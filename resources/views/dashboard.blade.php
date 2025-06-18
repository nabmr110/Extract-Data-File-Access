<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Uploaded Files</title>
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
        <h2 class="mb-4">📄 Processed Files</h2>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if (count($files) > 0)
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>No.</th>
                        <th>File Name</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($files as $index => $file)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ basename($file) }}</td>
                            <td>
                                <a href="{{ route('show.report', ['filename' => urlencode(basename($file))]) }}" class="btn btn-primary btn-sm" target="_blank">
                                    View Report (PDF)
                                </a>
                                <a href="{{ route('download', ['filename' => urlencode(basename($file))]) }}" class="btn btn-success btn-sm ms-2">
                                    Download
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No processed files found.</p>
        @endif
    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSV Processor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="mb-4">Upload File</h2>

    @if(session('success'))
        <p>{{ session('success') }}</p>
        <p>Path: {{ session('path') }}</p>
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
            <label for="file" class="form-label">Choose File</label>
            <input type="file" name="file" id="file" class="form-control" accept=".xls,.xlsx" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload & Process</button>
    </form>

    <hr>

    @if(session('success') && session('path'))
        <h4>Download Processed File</h4>
        <a href="{{ route('download', ['filename' => basename(session('path'))]) }}" class="btn btn-success">
    Download Processed File
</a>
    @endif
</div>

</body>
</html>

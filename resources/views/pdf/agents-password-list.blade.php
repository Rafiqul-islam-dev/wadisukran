<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Agents Password List</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { margin: 0 0 10px; }
        .meta { margin-bottom: 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h2>Agents Password List</h2>
    <div class="meta">Generated: {{ $generatedAt }}</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Initial</th>
                <th>Name</th>
                <th>Username</th>
                <th>Temp Password</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $i => $u)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $u['initial'] }}</td>
                    <td>{{ $u['name'] }}</td>
                    <td>{{ $u['username'] }}</td>
                    <td>{{ $u['temp_password'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
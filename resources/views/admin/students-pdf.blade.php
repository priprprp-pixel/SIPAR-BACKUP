<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><style>@page{margin:24px}body{font-family:DejaVu Sans,sans-serif;color:#183047;font-size:10px}h1{font-size:18px;margin:0 0 5px}p{color:#647587;margin:0 0 16px}table{width:100%;border-collapse:collapse}th{background:#eaf0f5}th,td{border:1px solid #c8d2db;padding:7px;text-align:left}</style></head>
<body>
    <h1>Data Siswa SIPAR</h1>
    <p>SMK Negeri 5 Surakarta | Dibuat {{ $generatedAt }} | {{ $students->count() }} siswa</p>
    <table><thead><tr><th>Nama</th><th>NIS</th><th>Email</th><th>Terdaftar</th></tr></thead><tbody>
    @forelse($students as $student)
        <tr><td>{{ $student->nama }}</td><td>{{ $student->nis_nip }}</td><td>{{ $student->email }}</td><td>{{ $student->created_at?->format('d/m/Y') ?? '-' }}</td></tr>
    @empty
        <tr><td colspan="4" style="text-align:center">Belum ada data siswa.</td></tr>
    @endforelse
    </tbody></table>
</body>
</html>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>@page { margin: 24px; }</style>
</head>
<body style="font-family: DejaVu Sans, sans-serif; color: #183047; font-size: 9px;">
    <h1 style="font-size: 18px; margin: 0 0 5px;">Laporan Peminjaman Alat SIPAR</h1>
    <p style="color: #647587; margin: 0 0 16px;">SMK Negeri 5 Surakarta | Dibuat {{ $generatedAt }} | {{ $rows->count() }} transaksi</p>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="border: 1px solid #aebdca; background-color: #eaf0f5; padding: 6px; text-align: left;">ID</th>
                <th style="border: 1px solid #aebdca; background-color: #eaf0f5; padding: 6px; text-align: left;">Peminjam</th>
                <th style="border: 1px solid #aebdca; background-color: #eaf0f5; padding: 6px; text-align: left;">NIS/NIP</th>
                <th style="border: 1px solid #aebdca; background-color: #eaf0f5; padding: 6px; text-align: left;">Alat dan Jumlah</th>
                <th style="border: 1px solid #aebdca; background-color: #eaf0f5; padding: 6px; text-align: left;">Tanggal Pinjam</th>
                <th style="border: 1px solid #aebdca; background-color: #eaf0f5; padding: 6px; text-align: left;">Rencana Kembali</th>
                <th style="border: 1px solid #aebdca; background-color: #eaf0f5; padding: 6px; text-align: left;">Status</th>
                <th style="border: 1px solid #aebdca; background-color: #eaf0f5; padding: 6px; text-align: left;">Kembali Aktual</th>
                <th style="border: 1px solid #aebdca; background-color: #eaf0f5; padding: 6px; text-align: right;">Denda (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td style="border: 1px solid #c8d2db; padding: 6px;">{{ $row['id'] }}</td>
                    <td style="border: 1px solid #c8d2db; padding: 6px;">{{ $row['nama'] }}</td>
                    <td style="border: 1px solid #c8d2db; padding: 6px;">{{ $row['nis_nip'] }}</td>
                    <td style="border: 1px solid #c8d2db; padding: 6px;">{{ $row['alat'] }}</td>
                    <td style="border: 1px solid #c8d2db; padding: 6px;">{{ $row['tanggal_pinjam'] }}</td>
                    <td style="border: 1px solid #c8d2db; padding: 6px;">{{ $row['tanggal_kembali_rencana'] }}</td>
                    <td style="border: 1px solid #c8d2db; padding: 6px;">{{ $row['status'] }}</td>
                    <td style="border: 1px solid #c8d2db; padding: 6px;">{{ $row['tanggal_kembali_aktual'] }}</td>
                    <td style="border: 1px solid #c8d2db; padding: 6px; text-align: right;">{{ $row['denda'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="border: 1px solid #c8d2db; padding: 8px; text-align: center;">Tidak ada transaksi untuk filter ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

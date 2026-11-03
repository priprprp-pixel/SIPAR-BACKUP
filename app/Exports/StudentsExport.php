<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function headings(): array
    {
        return ['Nama', 'NIS', 'Email', 'Terdaftar'];
    }

    public function collection(): Collection
    {
        return User::where('role', 'siswa')
            ->orderBy('nama')
            ->get(['nama', 'nis_nip', 'email', 'created_at'])
            ->map(fn (User $student): array => [
                $student->nama,
                $student->nis_nip,
                $student->email,
                $student->created_at?->format('Y-m-d') ?? '-',
            ]);
    }
}

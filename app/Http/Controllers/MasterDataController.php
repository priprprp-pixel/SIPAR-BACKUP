<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\KategoriAlat;
use App\Models\User;
use App\Models\ItemUnit;
use App\Exports\StudentsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class MasterDataController extends Controller
{
    public function equipment(Request $request): View
    {
        $items = Alat::with('kategori')->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request) {
            $term = '%'.$request->string('q')->trim().'%';
            $query->where(fn ($builder) => $builder->where('nama_alat', 'like', $term)->orWhere('kode_alat', 'like', $term));
        })->orderBy('nama_alat')->paginate(15)->withQueryString();

        return view('admin.equipment.index', ['items' => $items, 'categories' => KategoriAlat::orderBy('nama_kategori')->get()]);
    }

    public function itemUnits(Request $request): View
    {
        $units = ItemUnit::with('alat')
            ->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where('asset_id', 'like', $term)
                    ->orWhereHas('alat', fn ($alat) => $alat->where('nama_alat', 'like', $term)->orWhere('kode_alat', 'like', $term));
            })
            ->orderBy('asset_id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.item-units', [
            'units' => $units,
            'equipment' => Alat::orderBy('nama_alat')->get(),
        ]);
    }

    public function storeItemUnit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'alat_id' => ['required', 'integer', 'exists:alat,id_alat'],
            'asset_id' => ['required', 'string', 'max:80', 'unique:item_units,asset_id'],
            'qr_code' => ['required', 'string', 'max:120', 'unique:item_units,qr_code'],
            'condition' => ['required', Rule::in(['Baik', 'Maintenance', 'Rusak'])],
            'status' => ['required', Rule::in(['Tersedia', 'Dipinjam'])],
        ]);
        if ($data['status'] === 'Dipinjam') {
            return back()->withErrors(['status' => 'Unit baru harus berstatus Tersedia. Status Dipinjam hanya ditetapkan saat approval teknisi.'])->withInput();
        }

        DB::transaction(function () use ($data): void {
            $equipment = Alat::whereKey($data['alat_id'])->lockForUpdate()->firstOrFail();
            ItemUnit::create($data);
            $equipment->syncStockFromItemUnits();
            $equipment->save();
        });

        return back()->with('success', 'Unit fisik berhasil ditambahkan.');
    }

    public function updateItemUnit(Request $request, ItemUnit $itemUnit): RedirectResponse
    {
        $data = $request->validate([
            'alat_id' => ['required', 'integer', 'exists:alat,id_alat'],
            'asset_id' => ['required', 'string', 'max:80', Rule::unique('item_units', 'asset_id')->ignore($itemUnit->item_unit_id, 'item_unit_id')],
            'qr_code' => ['required', 'string', 'max:120', Rule::unique('item_units', 'qr_code')->ignore($itemUnit->item_unit_id, 'item_unit_id')],
            'condition' => ['required', Rule::in(['Baik', 'Maintenance', 'Rusak'])],
            'status' => ['required', Rule::in(['Tersedia', 'Dipinjam'])],
        ]);
        if ($itemUnit->status === 'Dipinjam' && $data['status'] !== 'Dipinjam') {
            return back()->withErrors(['status' => 'Unit yang sedang dipinjam hanya dapat diubah saat proses pengembalian.']);
        }
        if ($itemUnit->status === 'Dipinjam' && (
            (int) $data['alat_id'] !== (int) $itemUnit->alat_id
            || $data['asset_id'] !== $itemUnit->asset_id
            || $data['qr_code'] !== $itemUnit->qr_code
            || $data['condition'] !== $itemUnit->condition
        )) {
            return back()->withErrors(['status' => 'Identitas dan kondisi unit yang sedang dipinjam tidak dapat diubah.']);
        }
        if ($data['status'] === 'Dipinjam' && $data['condition'] !== 'Baik') {
            return back()->withErrors(['status' => 'Unit rusak atau maintenance tidak boleh berstatus Dipinjam.'])->withInput();
        }

        DB::transaction(function () use ($itemUnit, $data): void {
            $unit = ItemUnit::whereKey($itemUnit->item_unit_id)->lockForUpdate()->firstOrFail();
            $oldEquipment = Alat::whereKey($unit->alat_id)->lockForUpdate()->firstOrFail();
            if ($unit->alat_id !== (int) $data['alat_id'] && $unit->details()->exists()) {
                throw ValidationException::withMessages(['alat_id' => 'Unit yang pernah dipinjam tidak dapat dipindahkan ke alat lain.']);
            }
            $unit->update($data);
            $oldEquipment->syncStockFromItemUnits();
            $oldEquipment->save();
            if ($oldEquipment->id_alat !== (int) $data['alat_id']) {
                $newEquipment = Alat::whereKey($data['alat_id'])->lockForUpdate()->firstOrFail();
                $newEquipment->syncStockFromItemUnits();
                $newEquipment->save();
            }
        });

        return back()->with('success', 'Unit fisik berhasil diperbarui.');
    }

    public function deleteItemUnit(ItemUnit $itemUnit): RedirectResponse
    {
        if ($itemUnit->status === 'Dipinjam' || $itemUnit->details()->exists()) {
            return back()->withErrors(['unit' => 'Unit yang sedang atau pernah dipinjam tidak dapat dihapus.']);
        }
        DB::transaction(function () use ($itemUnit): void {
            $unit = ItemUnit::whereKey($itemUnit->item_unit_id)->lockForUpdate()->firstOrFail();
            $equipment = Alat::whereKey($unit->alat_id)->lockForUpdate()->firstOrFail();
            $unit->delete();
            $equipment->syncStockFromItemUnits();
            $equipment->save();
        });

        return back()->with('success', 'Unit fisik berhasil dihapus.');
    }

    public function storeEquipment(Request $request): RedirectResponse
    {
        $minimumStock = $request->input('status_alat') === 'Tersedia' ? 1 : 0;
        $data = $request->validate([
            'kode_alat' => ['required', 'string', 'max:50', 'unique:alat,kode_alat'],
            'nama_alat' => ['required', 'string', 'max:150'],
            'id_kategori' => ['required', 'exists:kategori_alat,id_kategori'],
            'stok_total' => ['required', 'integer', 'min:'.$minimumStock, 'max:10000'],
            'kondisi' => ['required', 'string', 'max:80'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'status_alat' => ['required', Rule::in(['Tersedia', 'Maintenance', 'Rusak'])],
        ]);
        $data['stok_tersedia'] = $data['stok_total'];
        $data['stok_maintenance'] = 0;
        $data['stok_rusak'] = 0;
        $data['qr_code'] = (string) Str::uuid();
        if ($data['status_alat'] !== 'Tersedia') {
            $data['stok_tersedia'] = 0;
            $data[$data['status_alat'] === 'Maintenance' ? 'stok_maintenance' : 'stok_rusak'] = $data['stok_total'];
        }
        Alat::create($data);

        return back()->with('success', 'Alat baru berhasil ditambahkan.');
    }

    public function updateEquipment(Request $request, Alat $alat): RedirectResponse
    {
        $minimumStock = $request->input('status_alat') === 'Tersedia' ? 1 : 0;
        $data = $request->validate([
            'kode_alat' => ['required', 'string', 'max:50', Rule::unique('alat', 'kode_alat')->ignore($alat->id_alat, 'id_alat')],
            'nama_alat' => ['required', 'string', 'max:150'],
            'id_kategori' => ['required', 'exists:kategori_alat,id_kategori'],
            'stok_total' => ['required', 'integer', 'min:'.$minimumStock, 'max:10000'],
            'kondisi' => ['required', 'string', 'max:80'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'status_alat' => ['required', Rule::in(['Tersedia', 'Maintenance', 'Rusak'])],
        ]);
        $borrowed = max(0, $alat->stok_total - $alat->stok_tersedia - $alat->stok_maintenance - $alat->stok_rusak);
        $statusChanged = $data['status_alat'] !== $alat->status_alat;

        if (! $statusChanged) {
            $unavailable = $alat->stok_maintenance + $alat->stok_rusak;
            if ($data['stok_total'] < $borrowed + $unavailable) {
                return back()->withErrors(['stok_total' => 'Stok total tidak boleh lebih kecil dari jumlah dipinjam dan tidak tersedia.'])->withInput();
            }
            $data['stok_tersedia'] = $data['stok_total'] - $borrowed - $unavailable;
            $data['stok_maintenance'] = $alat->stok_maintenance;
            $data['stok_rusak'] = $alat->stok_rusak;
        } elseif ($data['stok_total'] < $borrowed) {
            return back()->withErrors(['stok_total' => 'Stok total tidak boleh lebih kecil dari jumlah yang sedang dipinjam.'])->withInput();
        } elseif ($data['status_alat'] === 'Tersedia') {
            $data['stok_tersedia'] = $data['stok_total'] - $borrowed;
            $data['stok_maintenance'] = 0;
            $data['stok_rusak'] = 0;
        } elseif ($data['status_alat'] === 'Maintenance') {
            $data['stok_tersedia'] = 0;
            $data['stok_maintenance'] = $data['stok_total'] - $borrowed;
            $data['stok_rusak'] = 0;
        } else {
            $data['stok_tersedia'] = 0;
            $data['stok_maintenance'] = 0;
            $data['stok_rusak'] = $data['stok_total'] - $borrowed;
        }
        $alat->update($data);

        return back()->with('success', 'Data alat berhasil diperbarui.');
    }

    public function deleteEquipment(Alat $alat): RedirectResponse
    {
        if ($alat->detailPeminjaman()->exists()) {
            return back()->withErrors(['alat' => 'Alat yang pernah tercatat dalam transaksi tidak dapat dihapus. Ubah statusnya menjadi Rusak atau Maintenance.']);
        }
        $alat->delete();

        return back()->with('success', 'Alat berhasil dihapus.');
    }

    public function categories(): View
    {
        return view('admin.categories', ['categories' => KategoriAlat::withCount('alat')->orderBy('nama_kategori')->get()]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate(['nama_kategori' => ['required', 'string', 'max:100', 'unique:kategori_alat,nama_kategori']]);
        KategoriAlat::create($data);

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function updateCategory(Request $request, KategoriAlat $kategori): RedirectResponse
    {
        $data = $request->validate(['nama_kategori' => ['required', 'string', 'max:100', Rule::unique('kategori_alat', 'nama_kategori')->ignore($kategori->id_kategori, 'id_kategori')]]);
        $kategori->update($data);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function deleteCategory(KategoriAlat $kategori): RedirectResponse
    {
        if ($kategori->alat()->exists()) {
            return back()->withErrors(['kategori' => 'Kategori masih digunakan oleh data alat.']);
        }
        $kategori->delete();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }

    public function students(): View
    {
        return view('admin.students', ['students' => User::where('role', 'siswa')->orderBy('nama')->paginate(15)]);
    }

    public function exportStudentsExcel()
    {
        return Excel::download(new StudentsExport(), 'sipar-data-siswa-'.now()->format('Ymd-His').'.xlsx');
    }

    public function exportStudentsPdf(): Response
    {
        return Pdf::loadView('admin.students-pdf', [
            'students' => User::where('role', 'siswa')->orderBy('nama')->get(),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait')->download('sipar-data-siswa-'.now()->format('Ymd-His').'.pdf');
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'nis_nip' => ['required', 'string', 'max:30', 'unique:users,nis_nip'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);
        $data['role'] = 'siswa';
        User::create($data);

        return back()->with('success', 'Akun siswa berhasil dibuat.');
    }

    public function updateStudent(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role !== 'siswa', 404);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'nis_nip' => ['required', 'string', 'max:30', Rule::unique('users', 'nis_nip')->ignore($user->id_user, 'id_user')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id_user, 'id_user')],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $user->update($data);

        return back()->with('success', 'Akun siswa berhasil diperbarui.');
    }

    public function deleteStudent(User $user): RedirectResponse
    {
        abort_if($user->role !== 'siswa', 404);
        if ($user->peminjaman()->exists()) {
            return back()->withErrors(['siswa' => 'Akun dengan riwayat peminjaman tidak dapat dihapus.']);
        }
        $user->delete();

        return back()->with('success', 'Akun siswa berhasil dihapus.');
    }
}

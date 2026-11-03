<label class="form-label">Alat</label>
<select class="form-select mb-3" name="alat_id" required>
    <option value="">Pilih alat</option>
    @foreach($equipment as $alat)<option value="{{ $alat->id_alat }}" @selected(old('alat_id', $unit?->alat_id) == $alat->id_alat)>{{ $alat->kode_alat }} · {{ $alat->nama_alat }}</option>@endforeach
</select>
<label class="form-label">Asset ID</label><input class="form-control mb-3" name="asset_id" value="{{ old('asset_id', $unit?->asset_id) }}" placeholder="Contoh: ARD-01" required maxlength="80">
<div class="row g-3"><div class="col-6"><label class="form-label">Kondisi</label><select class="form-select" name="condition" required>@foreach(['Baik','Maintenance','Rusak'] as $condition)<option @selected(old('condition', $unit?->condition ?? 'Baik') === $condition)>{{ $condition }}</option>@endforeach</select></div>
<div class="col-6"><label class="form-label">Status</label><select class="form-select" name="status" required>@foreach(['Tersedia','Dipinjam'] as $status)<option @selected(old('status', $unit?->status ?? 'Tersedia') === $status)>{{ $status }}</option>@endforeach</select></div></div>

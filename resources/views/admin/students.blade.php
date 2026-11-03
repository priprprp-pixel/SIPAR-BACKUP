@extends('layouts.app')
@section('title','Data Siswa')
@section('content')
<div class="page-head">
	<div><h1>Data Siswa</h1><p>Kelola akun siswa yang dapat mengajukan alat.</p></div>
	<div class="d-flex flex-wrap gap-2"><a class="btn btn-light" href="{{ route('admin.students.xlsx') }}"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel</a><a class="btn btn-light" href="{{ route('admin.students.pdf') }}"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newStudent"><i class="bi bi-person-plus me-1"></i>Tambah siswa</button></div>
</div>

<section class="surface">
	<div class="table-responsive">
		<table class="table">
			<thead><tr><th>Nama</th><th>NIS</th><th>Email</th><th>Terdaftar</th><th>Aksi</th></tr></thead>
			<tbody>
				@forelse($students as $student)
					<tr>
						<td><strong>{{ $student->nama }}</strong></td>
						<td>{{ $student->nis_nip }}</td>
						<td>{{ $student->email }}</td>
						<td>{{ $student->created_at->format('d M Y') }}</td>
						<td>
							<div class="d-flex gap-1">
								<button class="btn btn-light btn-sm" type="button" title="Ubah data siswa" aria-label="Ubah siswa {{ $student->nama }}" data-bs-toggle="modal" data-bs-target="#editStudent-{{ $student->id_user }}"><i class="bi bi-pencil"></i></button>
								<form method="post" action="{{ route('admin.students.delete', $student) }}" onsubmit="return confirm('Hapus akun siswa ini?')">
									@csrf @method('DELETE')
									<button class="btn btn-light btn-sm text-danger" type="submit" title="Hapus siswa {{ $student->nama }}" aria-label="Hapus siswa {{ $student->nama }}"><i class="bi bi-trash"></i></button>
								</form>
							</div>
							@php($keepEditInput = $errors->any() && (string) old('_student_id') === (string) $student->id_user)
							<div class="modal fade" id="editStudent-{{ $student->id_user }}" tabindex="-1" aria-labelledby="editStudentLabel-{{ $student->id_user }}" aria-hidden="true">
								<div class="modal-dialog modal-dialog-centered student-edit-dialog">
									<form class="modal-content" method="post" action="{{ route('admin.students.update', $student) }}">
										@csrf @method('PUT')
										<input type="hidden" name="_student_id" value="{{ $student->id_user }}">
										<div class="modal-header">
											<div>
												<h2 class="modal-title fs-6 fw-bold" id="editStudentLabel-{{ $student->id_user }}">Ubah data siswa</h2>
												<div class="text-muted small">{{ $student->nis_nip }}</div>
											</div>
											<button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
										</div>
										<div class="modal-body">
											<label class="form-label" for="student-name-{{ $student->id_user }}">Nama</label>
											<input class="form-control mb-3" id="student-name-{{ $student->id_user }}" name="nama" value="{{ $keepEditInput ? old('nama', $student->nama) : $student->nama }}" required maxlength="120">
											<label class="form-label" for="student-nis-{{ $student->id_user }}">NIS</label>
											<input class="form-control mb-3" id="student-nis-{{ $student->id_user }}" name="nis_nip" value="{{ $keepEditInput ? old('nis_nip', $student->nis_nip) : $student->nis_nip }}" required maxlength="30">
											<label class="form-label" for="student-email-{{ $student->id_user }}">Email</label>
											<input class="form-control mb-3" id="student-email-{{ $student->id_user }}" name="email" type="email" value="{{ $keepEditInput ? old('email', $student->email) : $student->email }}" required maxlength="255">
											<label class="form-label" for="student-password-{{ $student->id_user }}">Password baru (opsional)</label>
											<input class="form-control" id="student-password-{{ $student->id_user }}" name="password" type="password" minlength="8" autocomplete="new-password">
											<div class="form-text">Kosongkan jika password tidak ingin diubah.</div>
										</div>
										<div class="modal-footer">
											<button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
											<button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Simpan perubahan</button>
										</div>
									</form>
								</div>
							</div>
						</td>
					</tr>
				@empty
					<tr><td colspan="5" class="text-center text-muted py-4">Belum ada akun siswa.</td></tr>
				@endforelse
			</tbody>
		</table>
	</div>
	<div class="p-3">{{ $students->links() }}</div>
</section>

<div class="modal fade" id="newStudent" tabindex="-1" aria-labelledby="newStudentLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered student-edit-dialog">
		<form class="modal-content" method="post" action="{{ route('admin.students.store') }}">
			@csrf
			<div class="modal-header">
				<h2 class="modal-title fs-6 fw-bold" id="newStudentLabel">Tambah akun siswa</h2>
				<button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
			</div>
			<div class="modal-body">
				<label class="form-label" for="new-student-name">Nama lengkap</label>
				<input class="form-control mb-3" id="new-student-name" name="nama" required maxlength="120">
				<label class="form-label" for="new-student-nis">NIS</label>
				<input class="form-control mb-3" id="new-student-nis" name="nis_nip" required maxlength="30">
				<label class="form-label" for="new-student-email">Email</label>
				<input class="form-control mb-3" id="new-student-email" type="email" name="email" required maxlength="255">
				<label class="form-label" for="new-student-password">Password awal</label>
				<input class="form-control" id="new-student-password" type="password" name="password" minlength="8" autocomplete="new-password" required>
			</div>
			<div class="modal-footer">
				<button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
				<button class="btn btn-primary" type="submit">Buat akun</button>
			</div>
		</form>
	</div>
</div>
@endsection
@push('scripts')
	<script>
		@if($errors->any() && old('_student_id'))
			const failedStudentModal = document.getElementById('editStudent-{{ old('_student_id') }}');
			if (failedStudentModal) bootstrap.Modal.getOrCreateInstance(failedStudentModal).show();
		@endif
	</script>
@endpush

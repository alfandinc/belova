@extends('layouts.erm.app')

@section('content')
<div class="container">
    <h2>Tambah Pasien</h2>

    <form action="{{ route('erm.pasiens.store') }}" method="POST">
        @csrf
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="nik">NIK</label>
                    <input type="text" class="form-control" name="nik" required>
                </div>

                <div class="form-group">
                    <label for="nama">Nama</label>
                    <input type="text" class="form-control" name="nama" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_lahir">Tanggal Lahir</label>
                    <input type="date" class="form-control" name="tanggal_lahir" required>
                </div>

                <div class="form-group">
                    <label for="gender">Jenis Kelamin</label>
                    <select class="form-control" name="gender" required>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="marital_status">Status Pernikahan</label>
                    <input type="text" class="form-control" name="marital_status">
                </div>

                <div class="form-group">
                    <label for="pendidikan">Pendidikan</label>
                    <input type="text" class="form-control" name="pendidikan">
                </div>

                <div class="form-group">
                    <label for="agama">Agama</label>
                    <input type="text" class="form-control" name="agama">
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="pekerjaan">Pekerjaan</label>
                    <input type="text" class="form-control" name="pekerjaan">
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat</label>
                    <textarea class="form-control" name="alamat" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="village_id">Kelurahan</label>
                    <select class="form-control" name="village_id" required>
                        @foreach($villages as $village)
                            <option value="{{ $village->id }}">{{ $village->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="kelas_pasien_id">Kelas Pasien</label>
                    <select class="form-control" name="kelas_pasien_id" required>
                        @foreach($kelasPasiens as $kelas)
                            <option value="{{ $kelas->id }}">{{ $kelas->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="penanggung_jawab">Penanggung Jawab</label>
                    <input type="text" class="form-control" name="penanggung_jawab">
                </div>

                <div class="form-group">
                    <label for="no_hp_penanggung_jawab">No HP Penanggung Jawab</label>
                    <input type="text" class="form-control" name="no_hp_penanggung_jawab">
                </div>

                <div class="form-group">
                    <label for="notes">Catatan</label>
                    <textarea class="form-control" name="notes" rows="2"></textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Simpan</button>
        <a href="{{ route('erm.pasiens.index') }}" class="btn btn-secondary mt-3">Batal</a>
    </form>
</div>
@endsection

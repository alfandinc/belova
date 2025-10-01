@foreach($insentifOmset as $omset)
<div class="form-group">
    <label for="omset_{{ $omset->id }}">{{ $omset->nama_penghasil }}</label>
    <input required type="number" class="form-control" name="omset_bulanan[{{ $omset->id }}]" id="omset_{{ $omset->id }}" step="any" value="{{ $omset->nominal ?? '' }}">
</div>
@endforeach

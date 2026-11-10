<td class="{{ $conditions ? 'odg-marked' : '' }}">
  <div class="odg-n">{{ $tooth }}</div>
  @foreach ($conditions as $code => $surfaces)
    <div class="odg-c">{{ $code }}@if ($surfaces)<br>{{ implode('', $surfaces) }}@endif</div>
  @endforeach
</td>

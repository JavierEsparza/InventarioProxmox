<tr>
    <td class="fw-bold text-info">{{ $iface->iface }}</td>
    <td>
        @if($iface->type == 'bond') <span class="badge bg-warning text-dark">BOND</span>
        @else Eth @endif
    </td>
    <td class="text-warning">
        @if($iface->bond_parent) <i class="bi bi-arrow-return-right"></i> {{ $iface->bond_parent }} @else <span class="opacity-25">--</span> @endif
    </td>
    <td class="text-primary small">
        @if($iface->bridge_parent)
            @foreach(explode(',', $iface->bridge_parent) as $brName)
                <span class="d-block">{{ trim($brName) }}</span>
            @endforeach
        @else <span class="opacity-25">--</span> @endif
    </td>
    <td>
        {{-- IP Propia --}}
        @if($iface->address)
            <div class="text-success fw-bold">{{ $iface->address }} <span class="small text-muted">/{{ $iface->cidr }}</span> (Propia)</div>
        @endif

        {{-- IP Heredada --}}
        @if($iface->bridge_parent)
            @foreach(explode(',', $iface->bridge_parent) as $brName)
                @php $parentBr = $bridgesMap->get(trim($brName)); @endphp
                @if($parentBr && $parentBr->address)
                    <div class="text-white">
                        <i class="bi bi-arrow-up-right-circle text-secondary" style="font-size:0.8em"></i>
                        {{ $parentBr->address }} <span class="small text-muted">/{{ $parentBr->cidr }}</span>
                        <span class="badge bg-secondary bg-opacity-25 ms-1" style="font-size:0.7em">{{ $parentBr->iface }}</span>
                    </div>
                @endif
            @endforeach
        @endif
    </td>
    <td class="font-monospace text-secondary small">{{ $iface->mac ?? '--' }}</td>
    <td class="fst-italic small text-warning">{{ $iface->comments }}</td>
    <td>
        @if($iface->active) <span class="badge bg-success">UP</span>
        @else <span class="badge bg-danger">DOWN</span> @endif
    </td>
</tr>

<div class="iface-legend">
    <span class="iface-chip up">UP</span>
    <span class="iface-chip down">DOWN</span>
    <span class="iface-chip bond">Bond</span>
    <span class="iface-chip bridge">Bridge</span>
</div>
<div class="table-responsive">
    <table class="table table-sm table-bordered table-dark table-hover mb-0 iface-table">
        <thead>
            <tr>
                <th>Interface</th>
                <th>Tipo</th>
                <th>Medio / Link</th>
                <th>Bond padre</th>
                <th>Bridge(s)</th>
                <th>IPs asociadas</th>
                <th>MAC Address</th>
                <th>Comentarios</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($interfaces as $iface)
                @php
                    $effectiveBridgeParents = collect(explode(',', (string) $iface->bridge_parent))->map(fn($v) => trim($v))->filter();
                    if ($iface->bond_parent && isset($allInterfaces)) {
                        $bondIface = $allInterfaces->firstWhere('iface', $iface->bond_parent);
                        if ($bondIface && $bondIface->bridge_parent) {
                            $effectiveBridgeParents = $effectiveBridgeParents->merge(collect(explode(',', (string) $bondIface->bridge_parent))->map(fn($v) => trim($v))->filter());
                        }
                    }
                    $effectiveBridgeParents = $effectiveBridgeParents->unique()->values();
                @endphp
                <tr>
                    <td><span class="iface-name">{{ $iface->iface }}</span></td>
                    <td>
                        @if($iface->type == 'bond')
                            <span class="iface-chip bond">BOND</span>
                        @else
                            <span class="iface-chip">ETH</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex flex-column gap-1">
                            @if($iface->media_type)<span class="iface-chip {{ str_contains(strtolower($iface->media_type), 'fibra') ? 'bridge' : '' }}">{{ $iface->media_type }}</span>@endif
                            @if($iface->link_speed)<span class="iface-ip">{{ $iface->link_speed }} @if($iface->duplex)<span class="iface-muted">{{ $iface->duplex }}</span>@endif</span>@endif
                            @if($iface->driver)<span class="iface-muted">{{ $iface->driver }} @if($iface->bus_info) · {{ $iface->bus_info }} @endif</span>@endif
                            @if(!$iface->media_type && !$iface->link_speed && !$iface->driver)<span class="iface-muted">--</span>@endif
                        </div>
                    </td>
                    <td>
                        @if($iface->bond_parent)
                            <span class="iface-chip bond"><i class="bi bi-arrow-return-right"></i> {{ $iface->bond_parent }}</span>
                        @else
                            <span class="iface-muted">--</span>
                        @endif
                    </td>
                    <td>
                        @if($effectiveBridgeParents->count())
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($effectiveBridgeParents as $brName)
                                    <span class="iface-chip bridge">{{ $brName }}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="iface-muted">--</span>
                        @endif
                    </td>
                    <td>
                        @if($iface->address)
                            <div><span class="iface-ip">{{ $iface->address }} <span class="iface-muted">/{{ $iface->cidr }}</span></span> <span class="iface-chip up">Propia</span></div>
                        @endif
                        @if($effectiveBridgeParents->count())
                            @foreach($effectiveBridgeParents as $brName)
                                @php $parentBr = $bridgesMap->get($brName); @endphp
                                @if($parentBr && $parentBr->address)
                                    <div class="mt-1"><i class="bi bi-arrow-up-right-circle iface-muted"></i> <span class="iface-ip">{{ $parentBr->address }} <span class="iface-muted">/{{ $parentBr->cidr }}</span></span> <span class="iface-chip bridge">{{ $parentBr->iface }}</span></div>
                                @endif
                            @endforeach
                        @endif
                        @if(!$iface->address && !$effectiveBridgeParents->count())
                            <span class="iface-muted">--</span>
                        @endif
                    </td>
                    <td><span class="iface-mac">{{ $iface->mac ?? '--' }}</span></td>
                    <td>@if($iface->comments)<span class="iface-comment">{{ $iface->comments }}</span>@else<span class="iface-muted">--</span>@endif</td>
                    <td>
                        @if($iface->active)
                            <span class="iface-chip up">UP</span>
                        @else
                            <span class="iface-chip down">DOWN</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center iface-muted">No hay interfaces en esta categoría.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
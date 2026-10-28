<div class="table-responsive">
    <table class="table table-sm table-bordered table-dark table-hover mb-0" style="font-size: 0.8rem;">
        <thead>
            <tr class="table-secondary text-dark">
                <th style="width: 15%">Interface</th>
                <th style="width: 10%">Tipo</th>
                @if($showIp) <th style="width: 20%">IP / CIDR</th> @endif
                @if($showIp) <th style="width: 15%">Gateway</th> @endif
                <th style="width: 15%">MAC Address</th>
                <th>Comentarios</th>
                <th style="width: 5%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($interfaces as $iface)
                <tr>
                    <td class="fw-bold text-info">{{ $iface->iface }}</td>
                    <td>{{ $iface->type }}</td>

                    @if($showIp)
                        <td>{{ $iface->address ?? '--' }}</td>
                        <td>{{ $iface->gateway ?? '--' }}</td>
                    @endif

                    <td class="font-monospace text-warning">{{ $iface->mac ?? '--' }}</td>
                    <td class="fst-italic text-muted">{{ $iface->comments }}</td>
                    <td>
                        @if($iface->active)
                            <span class="badge bg-success" style="font-size: 0.7em;">Activa</span>
                        @else
                            <span class="badge bg-secondary" style="font-size: 0.7em;">Inactiva</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showIp ? 7 : 5 }}" class="text-center text-muted">
                        No hay interfaces en esta categoría.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

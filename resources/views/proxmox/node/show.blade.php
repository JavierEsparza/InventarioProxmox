@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 px-4">

    {{--
        CABECERA:
        1. Título "Node Data" centrado.
        2. Botón "Exportar" a la derecha.
        3. Buscador debajo.
    --}}
    <h2 class="text-center py-3">Node Data</h2>

    <div class="d-flex justify-content-between mb-3">
        {{-- Formulario de Búsqueda a la izquierda --}}
        <form action="{{ route('proxmox.searchNode') }}" method="GET" class="w-50">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Buscar por nombre o IP" name="search">
                <button class="btn btn-outline-secondary" type="submit">Buscar</button>
            </div>
        </form>

        {{-- Botón de Exportar a la derecha --}}
        <a href="{{ route('proxmox.exportNode') }}" class="btn btn-success">Exportar a Excel</a>
    </div>


    {{--
        NUEVA TABLA ESTILO DASHBOARD:
        Reemplazamos la tabla anterior por esta.
    --}}
    <div class="table-responsive">
        <table class="table table-dark table-hover table-striped align-middle" style="font-size: 0.9rem;">
            <thead class="table-secondary text-dark text-center">
                <tr>
                    <th style="width: 12%;">Cluster</th>
                    <th style="width: 12%;">Nodo</th>
                    <th style="width: 5%;">Estado</th>
                    <th style="width: 12%;">CPU</th>
                    <th style="width: 12%;">RAM</th>
                    <th style="width: 12%;">Disco</th>
                    <th style="width: 10%;">IP</th>
                    <th style="width: 5%;">Uptime</th>
                    <th style="width: 10%;">Última actualización</th>
                    <th style="width: 10%;">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-center">
                @forelse ($nodes as $node)
                    @php
                        // --- Cálculos de Porcentaje (igual que en el dashboard) ---

                        // CPU
                        $cpuPercent = $node->cpu * 100;
                        $cpuColor = $cpuPercent <= 50 ? 'bg-success' : ($cpuPercent <= 75 ? 'bg-warning' : 'bg-danger');

                        // RAM
                        $ramPercent = ($node->maxmem > 0) ? ($node->mem / $node->maxmem) * 100 : 0;
                        $ramColor = $ramPercent <= 50 ? 'bg-success' : ($ramPercent <= 75 ? 'bg-warning' : 'bg-danger');

                        // DISCO (Usando los cálculos de tu controlador)
                        $diskUsed = $storageLocal[$node->id_proxmox] ?? 0;
                        $diskMax = $storageLocalMax[$node->id_proxmox] ?? 0;
                        $diskPercent = ($diskMax > 0) ? ($diskUsed / $diskMax) * 100 : 0;
                        $diskColor = $diskPercent <= 50 ? 'bg-success' : ($diskPercent <= 75 ? 'bg-warning' : 'bg-danger');

                        // UPTIME
                        $uptimeDays = floor($node->uptime / 86400);
                    @endphp
                    <tr>
                        <td>{{ $node->cluster_name ?? 'N/A' }}</td>
                        <td>{{ $node->node }}</td>
                        <td>
                            <span class="badge {{ $node->online ? 'bg-success' : 'bg-danger' }}">
                                {{ $node->online ? 'Online' : 'Offline' }}
                            </span>
                        </td>

                        {{-- Barra CPU --}}
                        <td class="text-start">
                            <div class="progress" style="height: 1.2rem;">
                                <div class="progress-bar text-dark fw-bolder {{ $cpuColor }}" role="progressbar"
                                     style="width: {{ $cpuPercent }}%" aria-valuenow="{{ $cpuPercent }}"
                                     aria-valuemin="0" aria-valuemax="100">
                                     {{ round($cpuPercent) }}%
                                </div>
                            </div>
                        </td>

                        {{-- Barra RAM --}}
                        <td class="text-start">
                            <div class="progress" style="height: 1.2rem;">
                                <div class="progress-bar text-dark fw-bolder {{ $ramColor }}" role="progressbar"
                                     style="width: {{ $ramPercent }}%" aria-valuenow="{{ $ramPercent }}"
                                     aria-valuemin="0" aria-valuemax="100">
                                     {{ round($ramPercent) }}%
                                </div>
                            </div>
                        </td>

                        {{-- Barra Disco --}}
                        <td class="text-start">
                            <div class="progress" style="height: 1.2rem;">
                                <div class="progress-bar text-dark fw-bolder {{ $diskColor }}" role="progressbar"
                                     style="width: {{ $diskPercent }}%" aria-valuenow="{{ $diskPercent }}"
                                     aria-valuemin="0" aria-valuemax="100">
                                     {{ round($diskPercent) }}%
                                </div>
                            </div>
                        </td>

                        <td>{{ $node->ip }}</td>
                        <td>{{ $uptimeDays }} días</td>
                        <td>{{ \Carbon\Carbon::parse($node->updated_at)->format('d/m/Y H:i') }}</td>

                        {{-- Botones de Acciones --}}
                        <td>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('proxmox.cluster.node.show', $node->node) }}" class="btn btn-primary btn-sm">Mostrar</a>
                                <form action="{{ route('proxmox.cluster.node.destroy', $node->node) }}" method="POST" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('¿Estás seguro de querer borrar este nodo?');">
                                        Borrar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4">No se encontraron nodos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection

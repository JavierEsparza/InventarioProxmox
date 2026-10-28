@extends('layouts.app')

@section('content')
    @php
        use Carbon\Carbon;
        Carbon::setLocale('es');

        // Lógica de agrupación anual
        $AnualData = $histories->sortBy('date')->groupBy(function ($item) {
            return Carbon::parse($item->date)->format('Y');
        });

        // Obtenemos el último registro de cada año para el gráfico
        $representativeAnualValues = $AnualData->map(function ($subGroup) {
            return $subGroup->last()->cluster_qemus;
        });

        // Obtenemos el último total registrado
        $lastAnnualTotal = $histories->sortBy('date')->last();

        // Función helper para formatear disco
        function formatDisk($bytes) {
            if ($bytes == 0) return '0 GB';
            $gb = $bytes / (1024 ** 3);
            if ($gb >= 1024) {
                return number_format($gb / 1024, 2) . ' TB';
            }
            return number_format($gb, 2) . ' GB';
        }

        // Lógica de agrupación para la tabla (un registro por cluster por año)
        $grouped = $VMHistory->sortByDesc('date')->groupBy(function ($item) {
                return \Carbon\Carbon::parse($item->date)->format('Y');
        })->map(function ($yearItems) {
            // Obtenemos solo el último registro de CADA cluster en ese año
            return $yearItems->unique('cluster_name');
        });

    @endphp

    <div class="container-fluid mt-4 px-4">
        <h1 class="text-center">Historial de Máquinas Virtuales</h1>

        {{-- Botones de Navegación --}}
        <div class="text-center my-3">
            <div class="btn-group btn-group-lg" role="group">
                <a href="{{ url('/proxmox/history') }}" class="btn btn-outline-light">Mensual</a>
                <a href="{{ url('/proxmox/historyAnual') }}" class="btn btn-primary">Anual</a>
            </div>
        </div>

        {{--
            *************************************************
            * SECCIÓN 1: TARJETAS DE RESUMEN (KPIs)
            *************************************************
        --}}
        <h3 class="text-white mb-3">Último Registro ({{ $AnualData->keys()->last() }})</h3>
        <div class="row g-3">
            <div class="col-lg-3 col-md-6">
                <div class="card bg-dark text-white text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h6 class="card-title text-uppercase text-white-50">Total VMs (Actual)</h6>
                        <h2 class="display-5 fw-bold">{{ $lastAnnualTotal->cluster_qemus ?? 'N/A' }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card bg-dark text-white text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h6 class="card-title text-uppercase text-white-50">Total vCPU (Actual)</h6>
                        <h2 class="display-5 fw-bold">{{ $lastAnnualTotal->cluster_cpu ?? 'N/A' }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card bg-dark text-white text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h6 class="card-title text-uppercase text-white-50">Total RAM (Actual)</h6>
                        <h2 class="display-5 fw-bold">{{ $lastAnnualTotal->cluster_memory ? number_format($lastAnnualTotal->cluster_memory / 1024 ** 3) : 'N/A' }} <span style="font-size: 1.5rem;">GB</span></h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card bg-dark text-white text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h6 class="card-title text-uppercase text-white-50">Total Disco (Actual)</h6>
                        <h2 class="display-5 fw-bold">{{ formatDisk($lastAnnualTotal->cluster_disk ?? 0) }}</h2>
                    </div>
                </div>
            </div>
        </div>

        {{--
            *************************************************
            * SECCIÓN 2: TARJETAS DE CRECIMIENTO
            *************************************************
        --}}
        <h3 class="text-white mb-3 mt-4">Crecimiento (vs Año Anterior)</h3>
        <div class="row g-3">
            <div class="col-lg-3 col-md-6">
                <div class="card bg-dark text-white text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h6 class="card-title text-uppercase text-white-50">Crecimiento VMs</h6>
                        <h2 class="display-5 fw-bold text-success">+{{ $growth['qemus'] ?? 0 }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card bg-dark text-white text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h6 class="card-title text-uppercase text-white-50">Crecimiento vCPU</h6>
                        <h2 class="display-5 fw-bold text-success">+{{ $growth['cpus'] ?? 0 }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card bg-dark text-white text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h6 class="card-title text-uppercase text-white-50">Crecimiento RAM</h6>
                        <h2 class="display-5 fw-bold text-success">+{{ $growth['memorys'] ? number_format($growth['memorys'] / 1024 ** 3) : '0' }} <span style="font-size: 1.5rem;">GB</span></h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card bg-dark text-white text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h6 class="card-title text-uppercase text-white-50">Crecimiento Disco</h6>
                        <h2 class="display-5 fw-bold text-success">+{{ formatDisk($growth['disks'] ?? 0) }}</h2>
                    </div>
                </div>
            </div>
        </div>

        {{--
            *************************************************
            * SECCIÓN 3: GRÁFICO DE BARRAS
            *************************************************
        --}}
        <h3 class="text-white mb-3 mt-4">Gráfico de Crecimiento (VMs)</h3>
        <div class="card bg-dark p-3">
            <div style="height: 300px">
                <canvas id="qemusChart"></canvas>
            </div>
        </div>

        {{--
            *************************************************
            * SECCIÓN 4: ACORDEÓN DE HISTORIAL
            *************************************************
        --}}
        <h3 class="text-white mb-3 mt-4">Detalle por Cluster (Anual)</h3>

        <div class="accordion" id="historyAccordion">
            @forelse ($grouped as $year => $items) {{-- Ya está ordenado por el sortByDesc('date') --}}

                <div class="accordion-item bg-dark text-white mb-2" style="border: 1px solid #454d55;">
                    {{-- CABECERA DEL ACORDEÓN (Clickeable) --}}
                    <h2 class="accordion-header" id="heading-{{ $loop->iteration }}">
                        <button class="accordion-button collapsed bg-dark text-white shadow-none"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapse-{{ $loop->iteration }}"
                                aria-expanded="false"
                                aria-controls="collapse-{{ $loop->iteration }}">

                            <div class="d-flex justify-content-between w-100 align-items-center pe-3">
                                <span class="fw-bold fs-5">{{ $year }}</span>
                                <span class="badge bg-secondary rounded-pill">
                                    {{ $items->sum('cluster_qemus') }} VMs
                                </span>
                            </div>
                        </button>
                    </h2>

                    {{-- CONTENIDO COLAPSABLE (La tabla) --}}
                    <div id="collapse-{{ $loop->iteration }}"
                         class="accordion-collapse collapse"
                         aria-labelledby="heading-{{ $loop->iteration }}"
                         data-bs-parent="#historyAccordion">

                        <div class="accordion-body" style="background-color: #2c3034;">
                            <table class="table table-dark table-hover table-bordered table-sm" style="font-size: 0.9rem;">
                                <thead class="text-center">
                                    <tr>
                                        <th scope="col" class="text-start">Cluster</th>
                                        <th scope="col">VMs Totales</th>
                                        <th scope="col">CPU</th>
                                        <th scope="col">Memoria</th>
                                        <th scope="col">Disco</th>
                                        <th scope="col" class="bg-success bg-opacity-25">VMs Creadas</th>
                                        <th scope="col" class="bg-danger bg-opacity-25">VMs Eliminadas</th>
                                        <th scope="col" class="bg-info bg-opacity-25">Nodos Creados</th>
                                        <th scope="col" class="bg-warning bg-opacity-25">Nodos Eliminados</th>
                                        <th scope="col">Fecha del Registro</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    @foreach ($items->sortBy('cluster_name') as $item)
                                        <tr>
                                            <td class="text-start">{{ $item->cluster_name }}</td>
                                            <td>{{ $item->cluster_qemus }}</td>
                                            <td>{{ $item->cluster_cpu }}</td>
                                            <td>{{ round($item->cluster_memory / 1024 ** 3, 2) }} GB</td>
                                            <td>{{ formatDisk($item->cluster_disk) }}</td>
                                            <td class="bg-success bg-opacity-10">
                                                {{ $item->vms_created }}
                                                @if($item->vms_created > 0)
                                                    <button class="btn btn-sm btn-link p-0 ms-1 btn-detail text-success"
                                                        data-cluster="{{ $item->cluster_name }}"
                                                        data-date="{{ $item->date }}"
                                                        data-type="vms_created"
                                                        data-title="VMs Creadas"
                                                        title="Ver detalles">
                                                        <i class="bi bi-search"></i>
                                                    </button>
                                                @endif
                                            </td>
                                            <td class="bg-danger bg-opacity-10">
                                                {{ $item->vms_deleted }}
                                                @if($item->vms_deleted > 0)
                                                    <button class="btn btn-sm btn-link p-0 ms-1 btn-detail text-danger"
                                                        data-cluster="{{ $item->cluster_name }}"
                                                        data-date="{{ $item->date }}"
                                                        data-type="vms_deleted"
                                                        data-title="VMs Eliminadas"
                                                        title="Ver detalles">
                                                        <i class="bi bi-search"></i>
                                                    </button>
                                                @endif
                                            </td>
                                            <td class="bg-info bg-opacity-10">
                                                {{ $item->nodes_created }}
                                                @if($item->nodes_created > 0)
                                                    <button class="btn btn-sm btn-link p-0 ms-1 btn-detail text-info"
                                                        data-cluster="{{ $item->cluster_name }}"
                                                        data-date="{{ $item->date }}"
                                                        data-type="nodes_created"
                                                        data-title="Nodos Creados"
                                                        title="Ver detalles">
                                                        <i class="bi bi-search"></i>
                                                    </button>
                                                @endif
                                            </td>
                                            <td class="bg-warning bg-opacity-10">
                                                {{ $item->nodes_deleted }}
                                                @if($item->nodes_deleted > 0)
                                                    <button class="btn btn-sm btn-link p-0 ms-1 btn-detail text-warning"
                                                        data-cluster="{{ $item->cluster_name }}"
                                                        data-date="{{ $item->date }}"
                                                        data-type="nodes_deleted"
                                                        data-title="Nodos Eliminados"
                                                        title="Ver detalles">
                                                        <i class="bi bi-search"></i>
                                                    </button>
                                                @endif
                                            </td>
                                            <td>{{ Carbon::parse($item->date)->format('d/m/Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-secondary text-center">
                    No se encontró historial anual.
                </div>
            @endforelse
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        var ctx = document.getElementById('qemusChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($representativeAnualValues->keys()),
                datasets: [{
                    label: 'Número de VMs',
                    data: @json($representativeAnualValues->values()),
                    backgroundColor: ['rgba(4, 132, 76, 0.6)'],
                    borderColor: ['rgba(4, 132, 76, 1)'],
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: 'white' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    },
                    x: {
                        ticks: { color: 'white' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    }
                },
                plugins: {
                    legend: {
                        labels: { color: 'white' }
                    }
                }
            }
        });
    </script>

    {{-- Modal para mostrar detalles --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="detailModalLabel">Detalles</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalElement = document.getElementById('detailModal');
            const modalTitle = document.getElementById('detailModalLabel');
            const modalContent = document.getElementById('modalContent');

            // Usar delegación de eventos para manejar botones en acordeones
            document.body.addEventListener('click', function(e) {
                if (e.target && e.target.closest('.btn-detail')) {
                    e.preventDefault();
                    const button = e.target.closest('.btn-detail');

                    const cluster = button.dataset.cluster;
                    const date = button.dataset.date;
                    const type = button.dataset.type;
                    const title = button.dataset.title;

                    console.log('Cargando detalles:', {cluster, date, type, title}); // Debug

                    modalTitle.textContent = `${title} - ${cluster} (${new Date(date).toLocaleDateString('es-CL')})`;
                    modalContent.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

                    // Mostrar modal usando atributos data de Bootstrap
                    modalElement.classList.add('show');
                    modalElement.style.display = 'block';
                    document.body.classList.add('modal-open');

                    // Agregar backdrop
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    backdrop.id = 'modalBackdrop';
                    document.body.appendChild(backdrop);

                    // Hacer petición AJAX
                    const url = `{{ route('proxmox.history.changeDetails') }}?cluster_name=${encodeURIComponent(cluster)}&date=${date}&type=${type}`;
                    console.log('URL:', url); // Debug

                    fetch(url)
                        .then(response => {
                            if (!response.ok) throw new Error('Error en la respuesta');
                            return response.json();
                        })
                        .then(data => {
                            console.log('Datos recibidos:', data); // Debug
                            if (data.success && data.data.length > 0) {
                                let html = '<div class="table-responsive"><table class="table table-dark table-striped table-hover">';
                                html += '<thead><tr>';

                                if (type.includes('vms')) {
                                    html += '<th>VMID</th><th>Nombre</th><th>Nodo</th><th>Fecha</th>';
                                } else {
                                    html += '<th>ID</th><th>Nodo</th><th>IP</th><th>Fecha</th>';
                                }

                                html += '</tr></thead><tbody>';

                                data.data.forEach(item => {
                                    html += '<tr>';
                                    if (type.includes('vms')) {
                                        html += `<td>${item.vmid || item.id_proxmox}</td>`;
                                        html += `<td>${item.name || 'N/A'}</td>`;
                                        const nodeName = (item.node_id || 'N/A').replace(/^node\//, '');
                                        html += `<td>${nodeName}</td>`;
                                    } else {
                                        html += `<td>${item.id_proxmox}</td>`;
                                        html += `<td>${item.node || 'N/A'}</td>`;
                                        html += `<td>${item.ip || 'N/A'}</td>`;
                                    }
                                    html += `<td>${new Date(item.created_at).toLocaleString('es-CL')}</td>`;
                                    html += '</tr>';
                                });

                                html += '</tbody></table></div>';
                                html += `<p class="text-center text-light mt-2">Total: ${data.count} registros</p>`;
                                modalContent.innerHTML = html;
                            } else {
                                modalContent.innerHTML = '<div class="alert alert-warning">No se encontraron registros para este período.</div>';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            modalContent.innerHTML = '<div class="alert alert-danger">Error al cargar los datos: ' + error.message + '</div>';
                        });
                }
            });

            // Cerrar modal
            document.body.addEventListener('click', function(e) {
                if (e.target.matches('[data-bs-dismiss="modal"]') || e.target.id === 'modalBackdrop') {
                    modalElement.classList.remove('show');
                    modalElement.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    const backdrop = document.getElementById('modalBackdrop');
                    if (backdrop) backdrop.remove();
                }
            });
        });
    </script>
@endsection

@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 px-4">

    {{-- CABECERA --}}
    <h2 class="text-center py-3"><strong>Log de Creación de VMs</strong></h2>
    <p class="text-center text-white-50 mb-4">
        Historial de VMs basado en la fecha en que fueron detectadas por primera vez.
    </p>

    {{--
        *************************************************
        * ACORDEÓN PRINCIPAL (POR MES)
        *************************************************
    --}}
    <div class="accordion" id="monthAccordion">

        @forelse ($groupedData as $monthName => $clustersInMonth)

            <div class="accordion-item bg-dark text-white mb-2" style="border: 1px solid #454d55;">

                {{-- 1. CABECERA DEL MES (Clickeable) --}}
                <h2 class="accordion-header" id="heading-month-{{ $loop->iteration }}">
                    <button class="accordion-button collapsed bg-dark text-white shadow-none"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapse-month-{{ $loop->iteration }}"
                            aria-expanded="false"
                            aria-controls="collapse-month-{{ $loop->iteration }}">

                        <div class="d-flex justify-content-between w-100 align-items-center pe-3">
                            <span class="fw-bold fs-5">{{ $monthName }}</span>
                            {{-- Contamos el total de VMs en este mes --}}
                            <span class="badge bg-secondary rounded-pill">
                                {{ $clustersInMonth->sum(fn($vms) => $vms->count()) }} VMs Creadas
                            </span>
                        </div>
                    </button>
                </h2>

                {{-- 2. CONTENIDO COLAPSABLE (El acordeón de clusters) --}}
                <div id="collapse-month-{{ $loop->iteration }}"
                     class="accordion-collapse collapse"
                     aria-labelledby="heading-month-{{ $loop->iteration }}"
                     data-bs-parent="#monthAccordion">

                    <div class="accordion-body" style="background-color: #2c3034;">

                        {{--
                            *************************************************
                            * ACORDEÓN SECUNDARIO (POR CLUSTER)
                            *************************************************
                        --}}
                        <div class="accordion" id="clusterAccordion-{{ $loop->iteration }}">

                            @forelse ($clustersInMonth as $clusterName => $vms)
                                <div class="accordion-item bg-dark text-white mb-2" style="border: 1px solid #555;">

                                    {{-- A. CABECERA DEL CLUSTER --}}
                                    <h2 class="accordion-header" id="heading-cluster-{{ $loop->parent->iteration }}-{{ $loop->iteration }}">
                                        <button class="accordion-button collapsed bg-dark text-white shadow-none"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#collapse-cluster-{{ $loop->parent->iteration }}-{{ $loop->iteration }}">

                                            <div class="d-flex justify-content-between w-100 align-items-center pe-3">
                                                <span class="fw-bold">Cluster: {{ $clusterName ?? 'Sin Cluster' }}</span>
                                                <span class="badge bg-primary rounded-pill">
                                                    {{ $vms->count() }} VMs
                                                </span>
                                            </div>
                                        </button>
                                    </h2>

                                    {{-- B. CONTENIDO (La tabla de VMs) --}}
                                    <div id="collapse-cluster-{{ $loop->parent->iteration }}-{{ $loop->iteration }}"
                                         class="accordion-collapse collapse"
                                         data-bs-parent="#clusterAccordion-{{ $loop->parent->iteration }}">

                                        <div class="accordion-body" style="background-color: #212529;">
                                            <table class="table table-dark table-hover table-sm" style="font-size: 0.9rem;">
                                                <thead>
                                                    <tr>
                                                        <th>VMID</th>
                                                        <th class="text-start">Nombre</th>
                                                        <th>Nodo</th>
                                                        <th>Fecha Creación</th>
                                                    </tr>
                                                </thead>
                                                <tbody class_="text-center">
                                                    @foreach ($vms->sortBy('name') as $vm)
                                                        <tr>
                                                            <td>{{ $vm->vmid }}</td>
                                                            <td class="text-start">{{ $vm->name }}</td>
                                                            <td>{{ str_replace('node/', '', $vm->node_id) }}</td>
                                                            <td>{{ \Carbon\Carbon::parse($vm->created_at)->format('Y-m-d H:i') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-white-50">No hay VMs para este mes.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        @empty
            <div class="alert alert-secondary text-center">
                No se encontró historial de creación de VMs.
            </div>
        @endforelse
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
    <div class="container mx-auto p-2">
        <!-- Fila de tarjetas -->
        <div class="row">
            <!-- Tarjeta para Clusters -->
            <div class="col-md-6 mb-3 d-flex align-items-stretch">
                <div class="card w-100 bg-dark text-white">
                    <h5 class="card-header">Clusters</h5>
                    <div class="card-body">
                        <h5 class="card-title">{{ $totalClusters }}</h5>
                        <p class="card-text">Total de clusters en el sistema.</p>
                    </div>
                </div>
            </div>


            <!-- Tarjeta para Nodos -->
            <div class="col-md-6 mb-3 d-flex align-items-stretch">
                <div class="card w-100 bg-dark text-white">
                    <!-- Estructura de la tarjeta para Nodos -->
                    <h5 class="card-header">Nodos</h5>
                    <div class="card-body">
                        <h5 class="card-title">{{ $totalNodes }}</h5>
                        <p class="card-text">Total de nodos en el sistema.</p>
                    </div>
                </div>

            </div>

            <div class="col-md-6 mb-3 d-flex align-items-stretch">
                <div class="card w-100 bg-dark text-white">
                    <!-- Estructura de la tarjeta para Nodos -->
                    <h5 class="card-header">Nodos stand alone</h5>
                    <div class="card-body">
                        <h5 class="card-title">{{ $OnlyNodes }}</h5>
                        <p class="card-text">Total de nodos sin cluster.</p>
                    </div>
                </div>

            </div>


            <!-- Nueva fila para las siguientes dos tarjetas -->

            <div class="col-md-6 mb-3 d-flex align-items-stretch">
                <div class = "card w-100 bg-dark text-white">
                    <h5 class="card-header">CPU Cores</h5>
                    <div class="card-body">
                        <p class="card-text">Total de cores en nodos:</p>
                        <h5 class="card-text">{{ $totalNodeCpu }} Cores</h5>

                    </div>
                </div>
            </div>


            <!-- Card for vCPU Usage -->
            <div class="col-md-6 mb-3 d-flex align-items-stretch">
                <div class="card w-100 bg-dark text-white">
                    <h5 class="card-header">vCPU</h5>
                    {{-- mostrar total de cpu --}}
                    <div class="card-body">
                        <p class="card-text">Total de vCPU en Maquinas virtuales :</p>
                        <h5 class="card-text">{{ $totalCPU }} vCPUs</h5>
                        {{-- mostrar porcentaje de cpu usado --}}

                    </div>
                </div>
            </div>

            <!-- Card for Memory Usage -->
            <div class="col-md-6 mb-3 d-flex align-items-stretch">
                <div class="card w-100 bg-dark text-white">
                    <h5 class="card-header">RAM</h5>
                    <div class="card-body">
                        {{-- mostrar total de memoria --}}
                        <p class="card-text">Total de memoria en nodos:</p>
                        @if ($totalRAM >= 1099511627776)
                            <h5 class="card-text">{{ round($totalRAM / 1099511627776, 2) }} TB</h5>
                        @else
                            <h5 class="card-text">{{ round($totalRAM / 1073741824, 2) }} GB</h5>
                        @endif
                        {{-- mostrar memoria usada --}}


                        <p class="card-text">Porcentaje de memoria usado:</p>
                        <p class="card-text">{{ round($memoryUsagePercentage, 2) }}%</p>
                        <div class="progress" style="height: 30px">
                            <div class="progress-bar text-dark fw-bolder {{ $memoryUsagePercentage >= 90 ? 'bg-danger' : ($memoryUsagePercentage >= 70 ? 'bg-warning' : 'bg-info') }}"
                                role="progressbar" style="width: {{ $memoryUsagePercentage }}%"
                                aria-valuenow="{{ $memoryUsagePercentage }}" aria-valuemin="0" aria-valuemax="100">
                                {{ round($memoryUsagePercentage, 2) }}%</div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Tarjeta para total de storage -->
            <div class="col-md-6 mb-3 d-flex align-items-stretch">
                <div class="card w-100 bg-dark text-white">
                    <h5 class="card-header">Storage</h5>
                    <div class="card-body">
                        <p class="card-text">Total almacenamiento:</p>
                        @if ($totalDisk >= 1099511627776)
                        <h5 class="card-text">{{ round($totalDisk / 1099511627776, 2) }} TB</h5>
                        @else
                            <h5 class="card-text">{{ round($totalDisk / 1073741824, 2) }} GB</h5>
                        @endif

                        <p class="card-text">Total de almacenamiento usado:</p>
                        <p class="card-text">{{ round($diskUsagePercentage, 2) }}%</p>

                        <div class="progress stacked " style="height: 30px">
                            <div class="progress-bar text-dark fw-bolder {{ $diskUsagePercentage >= 90 ? 'bg-danger' : ($diskUsagePercentage >= 70 ? 'bg-warning' : 'bg-info') }}"
                                role="progressbar" style="width: {{ round($diskUsagePercentage, 2)  }}%"
                                aria-valuenow="{{ round($diskUsagePercentage, 2)  }} " aria-valuemin="0" aria-valuemax="100">
                                {{ round($diskUsagePercentage, 2)  }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjeta para total maquinas virtuales -->
            <div class="col-md-6 mb-3 d-flex align-items-stretch">
                <div class="card w-100 bg-dark text-white">
                    <h5 class="card-header">Maquinas Virtuales</h5>
                    <div class="card-body">
                        <p class="card-text">Total de maquinas virtuales en el sistema:</p>
                        <h5 class="card-text">{{ $totalQemus }}</h5>
                    </div>
                </div>
            </div>

        </div>

        <!-- Agrega más filas y tarjetas según sea necesario -->
    @endsection

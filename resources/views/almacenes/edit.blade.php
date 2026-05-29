@extends('layouts.app')

@section('title', 'Editar Almacén | AgroNexus')
@section('page_title', 'Editar Almacén')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" style="color: #2c5530;">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('almacenes.index') }}" style="color: #2c5530;">Almacenes</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0 font-weight-bold">
                        <i class="fas fa-edit mr-2"></i>Editar Almacén: {{ $almacen->nombre }}
                    </h4>
                </div>

                <form action="{{ route('almacenes.update', $almacen) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body p-4">
                        @include('almacenes.partials.form')
                    </div>
                    <div class="card-footer bg-light text-right py-3">
                        <a href="{{ route('almacenes.index') }}" class="btn btn-secondary mr-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save mr-1"></i> Actualizar Almacén
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Registrar Almacén | AgroNexus')
@section('page_title', 'Registrar Almacén')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Registrar Almacén</h3>
        </div>

        <form action="{{ route('almacenes.store') }}" method="POST">
            @csrf
            <div class="card-body">
                @include('almacenes.partials.form')
            </div>
            <div class="card-footer text-right">
                <a href="{{ route('almacenes.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
@endsection

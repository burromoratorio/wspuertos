@extends('layouts.master')

@section('content')
<main id="main">
    <h1 class="sr-only">{{ $title }}</h1>
    @if (count($registers) > 0)
    <table class="table table-condensed">
        <thead>
            <tr>
                <th>#</th>
                <th>Movil</th>
                <th>Reporte</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($registers as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    <td>{{ $row->movil_id }}</td>
                    <td>{{ $row->cadena }}</td>
                    <td>{{ $row->estado_envio->estado }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
        No se realizaron reenvios
    @endif
</main>
@stop

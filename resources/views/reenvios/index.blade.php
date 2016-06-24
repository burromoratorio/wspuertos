@extends('layouts.master')

@section('content')
<main id="main" ng-controller="ReenviosController as reenvios">
    <h1 class="sr-only">{{ $title }}</h1>
    <table class="table table-condensed" ng-show="reenvios.list.length" ng-cloak>
        <thead>
            <tr>
                <th>#</th>
                <th>Movil</th>
                <th>Reporte</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <tr ng-repeat="reenvio in reenvios.list">
                <td>@{{ reenvio.id }}</td>
                <td>@{{ reenvio.movil_id }}</td>
                <td>@{{ reenvio.cadena }}</td>
                <td>@{{ reenvio.estado_envio_id }}</td>
            </tr>
        </tbody>
    </table>
    <div ng-hide="reenvios.list.length">
        No se realizaron reenvios
    </div>
</main>
@stop

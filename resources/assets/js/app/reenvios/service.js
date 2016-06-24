(function() {
    'use strict';

    angular
        .module('api.reenvios')
        .service('reenviosService', reenviosService);

    reenviosService.$inject = ['$http'];

    function reenviosService($http) {
        this.getAll = getAll;

        function getAll() {
            return $http.get('/reenvios');
        }
    }

})();

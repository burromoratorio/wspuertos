(function() {
    'use strict';

    angular
        .module('api.reenvios')
        .controller('ReenviosController', ReenviosController);

    ReenviosController.$inject = ['reenviosService'];

    function ReenviosController(reenviosService) {
        var vm = this;
        vm.list = [];

        reenviosService.getAll().then(function(response) {
            vm.list = response.data;
        }, function(error) {
            console.log(error);
        });
    }

})();

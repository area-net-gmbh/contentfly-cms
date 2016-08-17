(function() {
    'use strict';

    angular
        .module('app')
        .controller('DashboardCtrl', DashboardCtrl);

    function DashboardCtrl($scope, $cookies, localStorageService, $routeParams, $http){
        var vm = this;
        console.log("DASH");
        vm.labels = ["01.03.", "06.03.", "11.03.", "16.03.", "21.03.", "26.03.", "31.03."];
        vm.series = ['Aktive Benutzer', 'Seitenaufrufe'];
        vm.data = [
            [17, 14, 20, 19, 16, 16, 17],
            [90, 100, 110, 150, 130, 120, 140]
        ];
        vm.onClick = function (points, evt) {
            console.log(points, evt);
        };
        
        vm.labelsDevices = ["iOS", "Android"];
        vm.dataDevices = [60, 80];


        vm.labelsContent = ['Seite D', 'Seite C', 'Seite B', 'Seite A', 'Seite F',];
        vm.seriesContent = [];

        vm.dataContent = [
            [100, 88, 80, 40, 31],
        ];
    }

})();


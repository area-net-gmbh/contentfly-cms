(function() {
    'use strict';

    angular
        .module('app')
        .controller('DashboardCtrl', DashboardCtrl);

    function DashboardCtrl($scope, $cookies, localStorageService, $routeParams, $http){
        $scope.labels = ["01.03.", "06.03.", "11.03.", "16.03.", "21.03.", "26.03.", "31.03."];
        $scope.series = ['Aktive Benutzer', 'Seitenaufrufe'];
        $scope.data = [
            [17, 14, 20, 19, 16, 16, 17],
            [90, 100, 110, 150, 130, 120, 140]
        ];
        $scope.onClick = function (points, evt) {
            console.log(points, evt);
        };



        $scope.labelsDevices = ["iOS", "Android"];
        $scope.dataDevices = [60, 80];


        $scope.labelsContent = ['Seite D', 'Seite C', 'Seite B', 'Seite A', 'Seite F',];
        $scope.seriesContent = [];

        $scope.dataContent = [
            [100, 88, 80, 40, 31],
        ];
    }

})();


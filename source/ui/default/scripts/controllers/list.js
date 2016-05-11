(function() {
    'use strict';

    angular
        .module('app')
        .controller('ListCtrl', ListCtrl);

    function ListCtrl($scope, $cookies, localStorageService, $routeParams, $http, $uibModal, pimEntity){
        var vm = this;

        vm.foo = "bar";
        
        vm.objects = [];
        vm.objectsAvailable = false;
        vm.objectsNotAvailable = false;

        vm.itemsPerPage = 0;
        vm.totalItems = 0;
        vm.currentPage = 1;

        if(pimEntity){
            vm.entity = 'PIM\\' + $routeParams.entity;
        }else{
            vm.entity = $routeParams.entity;
        }

        vm.schema  = localStorageService.get('schema')[vm.entity];

        vm.sortProperty = vm.schema.settings.sortBy;
        vm.sortOrder    = vm.schema.settings.sortOrder;

        vm.filter = {};
        vm.filterIsOpen = false;
        vm.filterBadge = 0;
        vm.filterJoins = {};

        loadData();

        ///////////////////////////////////
        function loadData(){
            var sortSettings = {};
            sortSettings[vm.sortProperty] = vm.sortOrder;

            var filter = {};
            for (var key in $scope.filter) {
                if($scope.filter[key]){
                    filter[key] = $scope.filter[key];
                }
            }
            vm.objectsAvailable = false;

            $http({
                method: 'POST',
                url: '/api/list',
                headers: { 'X-Token': localStorageService.get('token') },
                data: {
                    entity: vm.entity,
                    currentPage: vm.currentPage,
                    order: sortSettings,
                    where: filter
                }
            }).then(function successCallback(response) {
                if(vm.itemsPerPage === 0) {
                    vm.itemsPerPage = response.data.itemsPerPage;
                }
                console.log(response);

                vm.totalItems = response.data.totalItems;
                vm.objects = response.data.data;

                vm.objectsAvailable = true;
                vm.objectsNotAvailable = false;
            }, function errorCallback(response) {
                vm.objectsAvailable = false;
                vm.objectsNotAvailable = true;
            });
        }

        var oldPageNumber = 1;



        for (var key in $scope.schema.properties) {
            if($scope.schema.properties[key].type == 'join'){
                var entity =  $scope.schema.properties[key].accept.replace('Custom\\Entity\\', '');
                var field  = key;
                $http({
                    method: 'POST',
                    url: '/api/list',
                    headers: { 'X-Token': localStorageService.get('token') },
                    data: {
                        entity: entity
                    }
                }).then(function successCallback(response) {
                    $scope.filterJoins[field] = (response.data.data);
                }, function errorCallback(response) {
                });
            }
        }

        $scope.executeFilter = function() {

            var badgeCount = 0;
            for (var key in $scope.filter) {
                if($scope.filter[key]){
                    badgeCount++;
                }
            }

            $scope.loadData();
            $scope.filterBadge = badgeCount;
        };

        $scope.closeFilter = function(){
            $scope.filterIsOpen = false;
        };

        $scope.resetFilter = function() {
            $scope.filter = {};
            $scope.filterBadge = 0;
            $scope.loadData();
            $scope.filterIsOpen = false;
        };

        $scope.delete = function(id){
            var modalInstance = $uibModal.open({
                templateUrl: 'views/partials/modal.html',
                controller: 'ModalCtrl',
                resolve: {
                    title: function(){ return 'Eintrag löschen'; },
                    body: function(){ return 'Wollen Sie den Eintrag ' + id + ' wirklich löschen?'; },
                    hideCancelButton: false
                }
            });

            modalInstance.result.then(function (doDelete) {

                if(doDelete){
                    $http({
                        method: 'POST',
                        url: '/api/delete',
                        headers: { 'X-Token': localStorageService.get('token') },
                        data: {entity: $scope.entity, id: id}
                    }).then(function successCallback(response) {
                        $scope.loadData();

                    }, function errorCallback(response) {
                        var modalInstance = $uibModal.open({
                            templateUrl: 'views/partials/modal.html',
                            controller: 'ModalCtrl',
                            resolve: {
                                title: function(){ return 'Fehler beim Löschen'; },
                                body: function(){ return response.data.message; },
                                hideCancelButton: true
                            }
                        });
                        //alert("Serverfehler: " + response.data.message);
                    });

                }
            }, function () {
            });
        };

        $scope.edit = function(object){
            var modalInstance = $uibModal.open({
                templateUrl: 'views/partials/form.html',
                controller: 'FormCtrl',
                resolve: {
                    entity: function(){ return $scope.entity;},
                    title: function(){ return 'Objekt bearbeiten'; },
                    object: function(){ return object; }
                },
                size: 'lg'
            });

            modalInstance.result.then(function (isSaved) {
                if(isSaved){
                    $scope.loadData();
                }
            }, function () {

            });
        };

        $scope.new = function(){
            var modalInstance = $uibModal.open({
                templateUrl: 'views/partials/form.html',
                controller: 'FormCtrl',
                resolve: {
                    entity: function(){ return $scope.entity;},
                    title: function(){ return 'Neues Objekt anlegen'; },
                    object: function(){ return {};}
                },
                size: 'lg'
            });

            modalInstance.result.then(function (isSaved) {
                if(isSaved){
                    $scope.loadData();
                }
            }, function () {

            });
        };

        $scope.loadSchema = function(){
            $http({
                method: 'GET',
                url: '/api/schema',
                headers: { 'X-Token': localStorageService.get('token') },
            }).then(function successCallback(response) {
                localStorageService.set('schema', response.data.data);
                localStorageService.set('devmode', response.data.devmode);
                $scope.schema = response.data.data[$scope.entity];
                $scope.loadData();
            }, function errorCallback(response) {
                $scope.error = response.data.message;
            });
        };




        /**
         * Pagination page change
         */
        $scope.paginationChanged = function(newPageNumber) {
            //console.log(oldPageNumber + " == " + newPageNumber);
            if(oldPageNumber == newPageNumber){
                return;
            }
            $scope.objectsAvailable = false;
            $scope.currentPage = newPageNumber;
            oldPageNumber = newPageNumber;
            $scope.loadData();
        };


        /**
         * Table sort
         */
        $scope.sortBy = function(property) {
            if($scope.sortProperty === property) {
                // Click on same column
                if($scope.sortOrder === 'DESC') {
                    $scope.sortOrder = 'ASC';
                } else {
                    $scope.sortOrder = 'DESC';
                }
            } else {
                $scope.sortProperty = property;
                $scope.sortOrder = 'ASC';
            }

            var sortSettings = {};
            sortSettings[$scope.sortProperty] = $scope.sortOrder;

            $http({
                method: 'POST',
                url: '/api/list',
                headers: { 'X-Token': localStorageService.get('token') },
                data: {
                    entity: $scope.entity,
                    currentPage: $scope.currentPage,
                    order: sortSettings
                }
            }).then(function successCallback(response) {
                $scope.objects = (response.data.data);
            }, function errorCallback(response) {
                console.warn(response);
            });
        };

    }

})();

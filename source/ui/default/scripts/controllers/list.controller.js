(function() {
    'use strict';

    angular
        .module('app')
        .controller('ListCtrl', ListCtrl);

    function ListCtrl($scope, $cookies, localStorageService, $routeParams, $http, $uibModal, pimEntity, EntityService, $document){
        var vm              = this;
        var oldPageNumber   = 1;

        //Properties
        vm.objects             = [];
        vm.objectsAvailable    = false;
        vm.objectsNotAvailable = false;

        vm.itemsPerPage  = 0;
        vm.totalItems    = 0;
        vm.currentPage   = 1;

        if(pimEntity){
            vm.entity = 'PIM\\' + $routeParams.entity;
        }else{
            vm.entity = $routeParams.entity;
        }

        vm.schema  = localStorageService.get('schema')[vm.entity];

        vm.sortProperty = vm.schema.settings.sortBy;
        vm.sortOrder    = vm.schema.settings.sortOrder;
        vm.sortableOptions = {
            stop: function(e,ui){
                var resortObjectData = [];
                var sortOffset = vm.sortOrder == 'ASC'
                for(var i = 0; i < vm.objects.length; i++){
                    var newSortPosition = vm.sortOrder == 'ASC' ? i : vm.objects.length - 1 - i;
                    vm.objects[i].sorting = newSortPosition;
                    resortObjectData.push({
                        entity: vm.entity,
                        id: vm.objects[i].id,
                        data:{
                            sorting: newSortPosition
                        }
                    });
                }

                var data = {
                    objects: resortObjectData
                }

                EntityService.multiupdate(data).then(
                    function successCallback(response) {
                    },
                    function errorCallback(response) {
                    }
                );

            },
            handle: '.sortable-handle'

        };
        vm.sortableObjects = [];

        vm.filter       = {};
        vm.filterIsOpen = false;
        vm.filterBadge  = 0;
        vm.filterJoins  = {};


        //Functions
        vm.closeFilter          = closeFilter;
        vm.delete               = doDelete;
        vm.executeFilter        = executeFilter;
        vm.loadSchema           = loadSchema;
        vm.openForm             = openForm;
        vm.paginationChanged    = paginationChanged;
        vm.resetFilter          = resetFilter;
        vm.sortBy               = sortBy;
        vm.toggleFilter         = toggleFilter;

        //Startup
        init();
        loadData();

        ///////////////////////////////////

        function closeFilter(){
            vm.filterIsOpen = false;
        }

        function executeFilter() {

            var badgeCount = 0;
            for (var key in vm.filter) {
                if(vm.filter[key]){
                    badgeCount++;
                }
            }

            vm.filterBadge = badgeCount;

            loadData();
        }

        function doDelete(id){
            if(vm.schema.settings.isPush || vm.schema.settings.readonly){
                return;
            }
            
            var modalInstance = $uibModal.open({
                templateUrl: 'views/partials/modal.html',
                controller: 'ModalCtrl as vm',
                resolve: {
                    title: function(){ return 'Eintrag löschen'; },
                    body: function(){ return 'Wollen Sie den Eintrag ' + id + ' wirklich löschen?'; },
                    hideCancelButton: false
                }
            });

            modalInstance.result.then(
                function (doDelete) {
                    if(doDelete){

                        var data = {
                            entity: vm.entity,
                            id: id
                        };

                        EntityService.delete(data).then(
                            function successCallback(response) {
                                loadData();
                            },
                            function errorCallback(response) {
                                var modalInstance = $uibModal.open({
                                    templateUrl: 'views/partials/modal.html',
                                    controller: 'ModalCtrl as vm',
                                    resolve: {
                                        title: function(){ return 'Fehler beim Löschen'; },
                                        body: function(){ return response.data.message; },
                                        hideCancelButton: true
                                    }
                                });
                            }
                        );
                    }
                },
                function () {}
            );
        }

        function init(){
            for (var key in vm.schema.properties) {
                if(vm.schema.properties[key].type == 'join'){
                    var entity =  vm.schema.properties[key].accept.replace('Custom\\Entity\\', '');
                    var field  = key;

                    EntityService.list({entity: entity}).then(
                        function successCallback(response) {
                            vm.filterJoins[field] = (response.data.data);
                        },
                        function errorCallback(response) {
                        }
                    );

                }
            }
        }

        function loadData(){
            var sortSettings = {};
            sortSettings[vm.sortProperty] = vm.sortOrder;

            var filter = {};
            for (var key in vm.filter) {
                if(vm.filter[key]){
                    filter[key] = vm.filter[key];
                }
            }
            vm.objectsAvailable = false;
            var data = {
                entity: vm.entity,
                currentPage: vm.schema.settings.isSortable ? 0 : vm.currentPage,
                order: sortSettings,
                where: filter
            };

            EntityService.list(data).then(
                function successCallback(response) {
                    
                    if(vm.itemsPerPage === 0) {
                        vm.itemsPerPage = response.data.itemsPerPage;
                    }

                    vm.totalItems = response.data.totalItems;
                    vm.objects = response.data.data;

                    vm.objectsAvailable = true;
                    vm.objectsNotAvailable = false;
                },
                function errorCallback(response) {
                    vm.objectsAvailable = false;
                    vm.objectsNotAvailable = true;
                }
            );
        }


        function loadSchema(){
            //todo: Schema-Service
            $http({
                method: 'GET',
                url: '/api/schema',
                headers: { 'X-Token': localStorageService.get('token') },
            }).then(function successCallback(response) {
                localStorageService.set('schema', response.data.data);
                localStorageService.set('devmode', response.data.devmode);
                vm.schema = response.data.data[vm.entity];
                loadData();
            }, function errorCallback(response) {
                vm.error = response.data.message;
            });
        }

        function openForm(object){
            if(vm.schema.settings.isPush || vm.schema.settings.readonly){
                return;
            }

            var modalInstance = $uibModal.open({
                templateUrl: 'views/form.html',
                controller: 'FormCtrl as vm',
                resolve: {
                    entity: function(){ return vm.entity;},
                    title: function(){ return object ? 'Objekt ' + object.id + ' bearbeiten' : 'Neues Objekt anlegen'; },
                    object: function(){ return object; }
                },
                size: 'lg'
            });

            modalInstance.result.then(
                function (isSaved) {
                    if(isSaved){
                        loadData();
                    }
                },
                function () {}
            );
        }

        function paginationChanged(newPageNumber) {
            if(oldPageNumber == newPageNumber){
                return;
            }
            vm.objectsAvailable = false;
            vm.currentPage = newPageNumber;
            oldPageNumber = newPageNumber;

            loadData();
        }

        function resetFilter() {
            vm.filter = {};
            vm.filterBadge = 0;
            vm.filterIsOpen = false;

            loadData();
        }

        function sortBy(property) {
            if(vm.sortProperty === property) {
                if(vm.sortOrder === 'DESC') {
                    vm.sortOrder = 'ASC';
                } else {
                    vm.sortOrder = 'DESC';
                }
            } else {
                vm.sortProperty = property;
                vm.sortOrder = 'ASC';
            }

            loadData();
        }

        function toggleFilter(open){
            if(open){
                $document.find('#fulltext').focus();

            }
        }



    }

})();

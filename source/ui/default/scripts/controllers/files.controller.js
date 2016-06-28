(function() {
    'use strict';

    angular
        .module('app')
        .controller('FilesCtrl', FilesCtrl);

    function FilesCtrl($scope, $cookies, localStorageService, $uibModalInstance, $routeParams, $timeout, $http, $uibModal, pimEntity, Upload, angularGridInstance, modaltitle, property, EntityService){
        var vm = this;
        var oldPageNumber = 1;

        //Properties
        vm.objects = [];
        vm.objectsAvailable = false;
        vm.objectsNotAvailable = false;

        vm.modaltitle = modaltitle;

        vm.itemsPerPage = 0;
        vm.totalItems = 0;
        vm.currentPage = 1;


        vm.entity = 'PIM\\File';

        vm.fileUploads = null;

        var schema = localStorageService.get('schema');
        vm.schema = schema[vm.entity];

        vm.sortProperty = vm.schema.settings.sortBy;
        vm.sortOrder    = vm.schema.settings.sortOrder;

        vm.filter = {};
        vm.filterIsOpen = false;
        vm.filterBadge = 0;
        vm.filterJoins = {};

        //Functions
        vm.closeFilter = closeFilter;
        vm.delete = doDelete;
        vm.loadData = loadData;
        vm.openForm = openForm;
        vm.executeFilter = executeFilter;
        vm.paginationChanged = paginationChanged;
        vm.resetFilter = resetFilter;
        vm.selectFile = selectFile;
        vm.sortBy = sortBy;
        vm.uploadFile = uploadFile;
        vm.uploadMultiFile = uploadMultiFile;

        //Startup
        init();

        /////////////////////////

        function closeFilter (){
            vm.filterIsOpen = false;
        }

        function doDelete(id, name){
            var modalInstance = $uibModal.open({
                templateUrl: 'views/partials/modal.html',
                controller: 'ModalCtrl as vm',
                resolve: {
                    title: function(){ return 'Datei löschen'; },
                    body: function(){ return 'Wollen Sie die Datei  "' + name + '" wirklich löschen?'; },
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

        function openForm(object){
            var modalInstance = $uibModal.open({
                templateUrl: 'views/form.html',
                controller: 'FormCtrl as  vm',
                resolve: {
                    entity: function(){ return vm.entity;},
                    title: function(){ return 'Objekt bearbeiten'; },
                    object: function(){ return object; }
                }
            });

            modalInstance.result.then(
                function (isSaved) {
                    if(isSaved){
                        //$scope.loadData();
                    }
                },
                function () {}
            );
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

        function init(){
            for (var key in vm.schema.properties) {
                if(vm.schema.properties[key].type == 'join'){
                    var entity =  vm.schema.properties[key].accept.replace('Custom\\Entity\\', '');
                    var field  = key;

                    var data = {
                        entity: entity
                    };

                    EntityService.list(data).then(
                        function successCallback(response) {
                            vm.filterJoins[field] = (response.data.data);
                        },
                        function errorCallback(response) {
                        }
                    );
                }
            }
        }

        function loadData() {
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
                entity: 'PIM\\File',
                currentPage: vm.currentPage,
                order: sortSettings,
                where: filter
            };

            EntityService.list(data).then(
                function successCallback(response) {

                    if(vm.itemsPerPage === 0) {
                        vm.itemsPerPage = response.data.itemsPerPage;
                    }

                    vm.totalItems = response.data.totalItems;
                    vm.objects = (response.data.data);
                    vm.objectsAvailable = true;
                    vm.objectsNotAvailable = false;

                    angularGridInstance.gallery.refresh();
                },
                function errorCallback(response) {

                    vm.objectsAvailable = false;
                    vm.objectsNotAvailable = true;
                    vm.objects = {};

                    angularGridInstance.gallery.refresh();
                }
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
        };

        function resetFilter() {
            vm.filter = {};
            vm.filterBadge = 0;
            vm.filterIsOpen = false;

            loadData();
        }

        function selectFile(object){
            if(!modaltitle) return;

            $uibModalInstance.close(object);
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

        function uploadFile(file, id, errFiles) {
            vm.fileUploads = [file];
            console.log("test1");
            file.upload = Upload.upload({
                url: '/file/upload',
                headers: {'X-Token': localStorageService.get('token')},
                data: {file: file, id: id}
            });
            console.log("test2");
            file.upload.then(
                function (response) {

                    file.result = response.data;

                    $timeout(function () {
                        vm.fileUploads = null;
                        loadData();
                    }, 1000);

                },
                function (response) {
                    if (response.status > 0) vm.errorMsg = response.status + ': ' + response.data;
                },
                function (evt) {
                    file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                }
            );

        }

        function uploadMultiFile(files, errFiles) {
            vm.fileUploads = files;


            angular.forEach(files, function(file) {
                file.upload = Upload.upload({
                    url: '/file/upload',
                    headers: {'X-Token': localStorageService.get('token')},
                    data: {file: file}
                });

                file.upload.then(
                    function (response) {

                        file.result = response.data;

                        $timeout(function () {
                            vm.fileUploads = null;
                            loadData();
                        }, 1000);

                    },
                    function (response) {
                        if (response.status > 0) vm.errorMsg = response.status + ': ' + response.data;
                    },
                    function (evt) {
                        file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                    }
                );
            });

        }
    }

})();

app.controller("FilesCtrl", function($scope, $cookies, localStorageService, $uibModalInstance, $routeParams, $timeout, $http, $uibModal, pimEntity, Upload, angularGridInstance, modaltitle, property) {

    $scope.objects = [];
    $scope.objectsAvailable = false;
    $scope.objectsNotAvailable = false;

    $scope.modaltitle = modaltitle;

    $scope.itemsPerPage = 0;
    $scope.totalItems = 0;
    $scope.currentPage = 1;
    var oldPageNumber = 1;

    $scope.entity = 'PIM\\File';

    $scope.fileUploads = null;

    var schema = localStorageService.get('schema');
    $scope.schema = schema[$scope.entity];

    $scope.sortProperty = $scope.schema.settings.sortBy;
    $scope.sortOrder    = $scope.schema.settings.sortOrder;

    $scope.filter = {};
    $scope.filterIsOpen = false;
    $scope.filterBadge = 0;
    $scope.filterJoins = {};

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
                $scope.filterJoins[field] = JSON.parse(response.data.data);
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

    $scope.uploadMultiFile = function(files, errFiles) {
        $scope.fileUploads = files;


        angular.forEach(files, function(file) {
            file.upload = Upload.upload({
                url: '/file/upload',
                headers: {'X-Token': localStorageService.get('token')},
                data: {file: file}
            });

            file.upload.then(function (response) {
                //console.log(response.data.data);
                file.result = response.data;


                $timeout(function () {
                    $scope.fileUploads = null;
                    $scope.loadData();
                }, 1000);

            }, function (response) {
                if (response.status > 0)
                    $scope.errorMsg = response.status + ': ' + response.data;
            }, function (evt) {
                file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
            });
        });

    };

    $scope.delete = function(id, name){
        var modalInstance = $uibModal.open({
            templateUrl: 'views/partials/modal.html',
            controller: 'ModalCtrl',
            resolve: {
                title: function(){ return 'Datei löschen'; },
                body: function(){ return 'Wollen Sie die Datei  "' + name + '" wirklich löschen?'; },
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

    $scope.selectFile = function(object){
        if(!modaltitle) return;

        $uibModalInstance.close(object);
    };

    $scope.edit = function(object){
        var modalInstance = $uibModal.open({
            templateUrl: 'views/partials/form.html',
            controller: 'FormCtrl',
            resolve: {
                entity: function(){ return $scope.entity;},
                title: function(){ return 'Objekt bearbeiten'; },
                object: function(){ return object; }
            }
        });

        modalInstance.result.then(function (isSaved) {
            if(isSaved){
                //$scope.loadData();
            }
        }, function () {

        });
    };

    $scope.new = function(){

    };



    $scope.loadData = function() {
        var sortSettings = {};
        sortSettings[$scope.sortProperty] = $scope.sortOrder;

        var filter = {};
        for (var key in $scope.filter) {
            if($scope.filter[key]){
                filter[key] = $scope.filter[key];
            }
        }
        $scope.objectsAvailable = false;

        $http({
            method: 'POST',
            url: '/api/list',
            headers: { 'X-Token': localStorageService.get('token') },
            data: {
                entity: 'PIM\\File',
                currentPage: $scope.currentPage,
                order: sortSettings,
                where: filter
            }
        }).then(function successCallback(response) {
            if($scope.itemsPerPage === 0) {
                $scope.itemsPerPage = response.data.itemsPerPage;
            }


            angularGridInstance.gallery.refresh();

            $scope.totalItems = response.data.totalItems;
            $scope.objects = JSON.parse(response.data.data);
            $scope.objectsAvailable = true;
            $scope.objectsNotAvailable = false;


        }, function errorCallback(response) {
            $scope.objectsAvailable = false;
            $scope.objectsNotAvailable = true;
            $scope.objects = {};
            angularGridInstance.gallery.refresh();
        });
    };


    /**
     * Pagination page change
     */
    $scope.paginationChanged = function(newPageNumber) {
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
                entity: 'PIM\\File',
                currentPage: $scope.currentPage,
                order: sortSettings
            }
        }).then(function successCallback(response) {
            console.log(response.data.data);
            $scope.objects = JSON.parse(response.data.data);
            angularGridInstance.gallery.refresh();
        }, function errorCallback(response) {
            console.warn(response);
        });
    };

});
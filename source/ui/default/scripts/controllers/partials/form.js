app.controller("FormCtrl", function($scope, $cookies, $uibModalInstance, localStorageService, $timeout, $uibModal, $http, title, entity, object, Upload, moment) {
    var schema = localStorageService.get('schema');
    $scope.schema = schema[entity];
    $scope.object = object;
    $scope.isSubmit = false;
    $scope.fileUploads = {};

    $scope.forms = {};

    angular.copy($scope.object, backupForObject);

    $scope.modaltitle = title;
    var backupForObject = angular.copy(object);
    $scope.password = {};

    var doSave = function(){
        var data = {};
        $scope.isSubmit = true;


        for (var formName in $scope.forms) {
            if(!$scope.forms[formName].$valid){
                return;
            }
        }

        angular.forEach(schema[entity].properties, function (config, key) {
            if (!config.readonly && !config.hide) {
                switch(config.type){
                    case 'datetime':
                        data[key] = $scope.datePickerModels[key].toISOString();
                        break;
                    case 'file':
                        data[key] = $scope.object[key] ? $scope.object[key]['id'] : null;
                        break;
                    case 'join':
                        data[key] = $scope.object[key] ? $scope.object[key]['id'] : null;
                        break;
                    case 'multifile':
                        data[key] = [];
                        if(Array.isArray($scope.object[key]) && $scope.object[key].length > 0){
                            angular.forEach($scope.object[key], function(value, index) {
                                data[key].push(parseInt(value['id']));
                            }, data, key);
                        }
                        break;
                    case 'password':
                        break;

                    default:
                        data[key] = $scope.object[key];
                        break;
                }
            }
        }, data);

        angular.forEach($scope.password, function (password, key) {
            data[key] = password;
        }, data);


        if (!object['id']) {
            $http({
                method: 'POST',
                url: '/api/insert',
                headers: {'X-Token': localStorageService.get('token')},
                data: {
                    entity: entity,
                    data: data
                }
            }).then(function successCallback(response) {
                $uibModalInstance.close(true);
            }, function errorCallback(response) {
                var modalInstance = $uibModal.open({
                    templateUrl: 'views/partials/modal.html',
                    controller: 'ModalCtrl',
                    resolve: {
                        title: function(){ return 'Fehler beim Anlegen des Datensatzes'; },
                        body: function(){ return response.data.message; },
                        hideCancelButton: true
                    }
                });

            });
        } else {
            $http({
                method: 'POST',
                url: '/api/update',
                headers: {'X-Token': localStorageService.get('token')},
                data: {
                    entity: entity,
                    id: object['id'],
                    data: data
                }
            }).then(function successCallback(response) {
                $uibModalInstance.close(true);
            }, function errorCallback(response) {
                var modalInstance = $uibModal.open({
                    templateUrl: 'views/partials/modal.html',
                    controller: 'ModalCtrl',
                    resolve: {
                        title: function(){ return 'Fehler beim Anlegen des Datensatzes'; },
                        body: function(){ return response.data.message; },
                        hideCancelButton: true
                    }
                });

            });
        }
    };

    var confirmPush = function(count, title, text, object) {
        var modalInstance = $uibModal.open({
            templateUrl: 'views/partials/modal.html',
            controller: 'ModalCtrl',
            resolve: {
                title: function(){ return 'Push-Nachricht an ' + count + ' Benutzer versenden'; },
                body: function(){ return "<p><b>" + title  + "</b></p><p>" + text  + "</p><p><br>" + object  + "</p>"; },
                hideCancelButton: function(){ return false; }
            }
        });

        modalInstance.result.then(function (doDelete) {
            if(doDelete){
                doSave();
            }
        }, function () {

        });
    }





    $scope.datePickerOpened = {};
    $scope.datePickerModels = {};
    angular.forEach(schema[entity].properties, function (config, key) {
        if (config.type == 'datetime') {
            //var m = moment(object[key].ISO8601);
            $scope.datePickerModels[key] = object[key] ? moment(object[key].ISO8601).toDate() : new Date();
            $scope.datePickerOpened[key] = false;
        }
    }, $scope.datePickerModels, $scope.datePickerOpened);


    $scope.openDatePicker = function(key) {
        $scope.datePickerOpened[key] = true;
    };

    $scope.removeFile = function(property, fileId){
        object[property] = null;
    };

    $scope.removeMultiFile = function(property, index){
        object[property].splice(index, 1);
    };

    $scope.editMultiFile = function(property, index, id, title){

        var modalInstance = $uibModal.open({
            templateUrl: 'views/partials/file-edit.html',
            controller: 'FileEditCtrl',
            resolve: {
                modaltitle: function(){ return 'Titel Datei ' + id + ' bearbeiten'; },
                title: function(){ return title; },
                id: function() {
                    return id;
                }
            }
        });

        modalInstance.result.then(function(title) {
            $scope.object[property][index]["title"] = title;
        }, function() {});

    };

    $scope.addVideo = function(property) {
        var modalInstance = $uibModal.open({
            templateUrl: 'views/partials/video-add.html',
            controller: 'VideoAddCtrl',
            resolve: {
                modaltitle: function(){ return 'Neues Youtube-Video hinzufügen'; },
                property: function(){ return property; }
            }
        });

        modalInstance.result.then(function(fileData) {
            if(fileData){
                if(typeof $scope.object[property] ==  'undefined'){
                    $scope.object[property] = [];
                }
                $scope.object[property].push(fileData);
            }
        }, function() {});

    };

    $scope.uploadFile = function(property, file, errFiles) {
        $scope.fileUploads[property] = file;

        if (file) {
            file.upload = Upload.upload({
                url: '/file/upload',
                headers: {'X-Token': localStorageService.get('token')},
                data: {file: file}
            });

            file.upload.then(function (response) {
                //console.log(response.data.data);
                file.result = response.data;
                $scope.object[property] = response.data.data;

                $timeout(function(){
                    $scope.fileUploads[property] = null;
                }, 1000);

            }, function (response) {
                if (response.status > 0)
                    $scope.errorMsg = response.status + ': ' + response.data;
            }, function (evt) {
                file.progress = Math.min(100, parseInt(100.0 *  evt.loaded / evt.total));
            });
        }
    };

    $scope.uploadMultiFile = function(property, files, errFiles) {
        $scope.fileUploads[property] = files;

        if(typeof $scope.object[property] ==  'undefined'){
            $scope.object[property] = [];
        }

        angular.forEach(files, function(file) {
            file.upload = Upload.upload({
                url: '/file/upload',
                headers: {'X-Token': localStorageService.get('token')},
                data: {file: file}
            });

            file.upload.then(function (response) {
                //console.log(response.data.data);
                file.result = response.data;

                $scope.object[property].push(response.data.data);

                $timeout(function () {
                    $scope.fileUploads[property] = null;
                }, 1000);

            }, function (response) {
                if (response.status > 0)
                    $scope.errorMsg = response.status + ': ' + response.data;
            }, function (evt) {
                file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
            });
        });

    };

    $scope.addFile = function(property, accept){
        var modalInstance = $uibModal.open({
            templateUrl: 'views/files.html',
            controller: 'FilesCtrl',
            resolve: {
                modaltitle: function(){ return 'Datei hinzufügen'; },
                property: function(){ return property; },
                pimEntity: function(){return true;}
            }
        });

        modalInstance.result.then(function(fileData) {
            var accept = $scope.schema.properties[property].accept.replace('*', '');
            var fileDataType = fileData.type.substr(0, accept.length);

            if(accept != fileDataType){
                var modalInstance = $uibModal.open({
                    templateUrl: 'views/partials/modal.html',
                    controller: 'ModalCtrl',
                    resolve: {
                        title: function(){ return 'Fehler bei der Dateiauswahl'; },
                        body: function(){ return 'Dieser Dateityp kann an dieser Stelle nicht ausgewählt werden.'; },
                        hideCancelButton: true
                    }
                });
                return;
            }

            if(fileData){
                $scope.object[property] = fileData;
            }
        }, function() {});
    };

    $scope.addMultiFile = function(property, accept){
        var modalInstance = $uibModal.open({
            templateUrl: 'views/files.html',
            controller: 'FilesCtrl',
            resolve: {
                modaltitle: function(){ return 'Datei hinzufügen'; },
                property: function(){ return property; },
                pimEntity: function(){return true;}
            }
        });

        modalInstance.result.then(function(fileData) {
            if(fileData){

                if(typeof $scope.object[property] ==  'undefined'){
                    $scope.object[property] = [];
                }
                $scope.object[property].push(fileData);
            }
        }, function() {});
    };

    $scope.save = function () {

        if($scope.schema['settings']['isPush']){

            for (var formName in $scope.forms) {
                if(!$scope.forms[formName].$valid){
                    return;
                }
            }

            $scope.isSubmit = true;

            $http({
                method: 'POST',
                url: '/api/list',
                headers: { 'X-Token': localStorageService.get('token') },
                data: {
                    entity: 'PIM\\PushToken',
                    count: true
                }
            }).then(function successCallback(response) {

                if($scope.object[$scope.schema['settings']['pushObject']]){
                    var res = $scope.object[$scope.schema['settings']['pushObject']].split("_");

                    $http({
                        method: 'POST',
                        url: '/api/single',
                        headers: { 'X-Token': localStorageService.get('token') },
                        data: {
                            entity:res[0],
                            id: res[1]
                        }
                    }).then(function successCallback(response2) {
                        var jsonData = JSON.parse(response2.data.data);
                        confirmPush(response.data.data, $scope.object[$scope.schema['settings']['pushTitle']], $scope.object[$scope.schema['settings']['pushText']], "Link auf " + res[0] + ": " + jsonData["title"]);

                    }, function errorCallback(response) {
                        confirmPush(response.data.data, $scope.object[$scope.schema['settings']['pushTitle']], $scope.object[$scope.schema['settings']['pushText']], "Link: --");
                    });
                }else{
                    confirmPush(response.data.data, $scope.object[$scope.schema['settings']['pushTitle']], $scope.object[$scope.schema['settings']['pushText']], "Link: --");
                }

            }, function errorCallback(response) {

            });

        }else{
            doSave();
        }

    };

    $scope.cancel = function () {
        angular.copy(backupForObject, $scope.object);
        backupForObject = null;
        $uibModalInstance.dismiss(false);
    };


    /**
     * Open entity browser
     */
    $scope.openEntityBrowser = function(key) {
        var modalInstance = $uibModal.open({
            templateUrl: 'views/partials/entity-browser.html',
            controller: 'EntityBrowserCtrl',
            resolve: {
                title: function(){ return 'Objekt auswählen'; },
                schema: function() {
                     return schema;
                },
                selectedObject: function() { return $scope.object[key]; }
            }
        });

        modalInstance.result.then(function(entity, label) {
            $scope.object[key] = entity;
        }, function() {});
    };


    /**
     * Open object browser
     */
    $scope.openObjectBrowser = function(key, entity) {
        var modalInstance = $uibModal.open({
            size: 'lg',
            templateUrl: 'views/partials/object-browser.html',
            controller: 'ObjectBrowserCtrl',
            resolve: {
                title: function(){ return 'Objekt auswählen'; },
                schema: function() {
                     return schema;
                },
                selectedObject: function() { return $scope.object[key]; },
                entityToSelect: function() { return entity; },
            }
        });

        modalInstance.result.then(function(objectToSelect) {
            if(entity){
                $scope.object[key] = objectToSelect;
            }else{
                var objectId = entity + '_' + objectToSelect.id;
                $scope.object[key] = objectId;
            }

        }, function() {});
    };
});
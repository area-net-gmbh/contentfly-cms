(function() {
    'use strict';

    angular
        .module('app')
        .controller('FormCtrl', FormCtrl);

    function FormCtrl($scope, $cookies, $uibModalInstance, localStorageService, $timeout, $uibModal, $http, title, entity, object, Upload, moment, EntityService) {
        var vm               = this;
        var schemaComplete   = localStorageService.get('schema');
        var objectDataToSave = {};
        var backupForObject  = null;
        
        //Properties
        vm.doSave           = false;
        vm.schemaOnejoin    = {};
        vm.schema           = schemaComplete[entity];
        vm.object           = object ? object : {};
        vm.isSubmit         = false;
        vm.fileUploads      = {};
        vm.forms            = {};
        vm.modaltitle       = title;
        vm.password = {};

        //Functions
        vm.save                 = save;
        vm.cancel               = cancel;
        vm.changeValue          = onChangeValue;

        //Startup
        init();

        ///////////////////////////////////

        function cancel() {
            //angular.copy(backupForObject, vm.object);
            //backupForObject = null;
            $uibModalInstance.dismiss(false);
        }

        function confirmPush(count, title, text, object) {
            var modalInstance = $uibModal.open({
                templateUrl: 'views/partials/modal.html',
                controller: 'ModalCtrl as vm',
                resolve: {
                    title: function () {
                        return 'Push-Nachricht an ' + count + ' Benutzer versenden';
                    },
                    body: function () {
                        return "<p><b>" + title + "</b></p><p>" + text + "</p><p><br>" + object + "</p>";
                    },
                    hideCancelButton: function () {
                        return false;
                    }
                }
            });

            modalInstance.result.then(
                function(doDelete) {
                    if (doDelete) doSave();
                },
                function(){}
            );
        }

        function doSave(andClose) {
            vm.isSubmit = true;

            for (var formName in vm.forms) {
                if (!vm.forms[formName].$valid) {
                    return;
                }
            }

            vm.doSave = true;

            if (!vm.object['id']) {

                var data = {
                    entity: entity,
                    data: objectDataToSave
                };

                EntityService.insert(data).then(
                    function successCallback(response) {
                        vm.doSave = false;
                        if(andClose) $uibModalInstance.close(response.data.data);
                    }, 
                    function errorCallback(response) {
                        vm.doSave = false;

                        var modalInstance = $uibModal.open({
                            templateUrl: 'views/partials/modal.html',
                            controller: 'ModalCtrl as vm',
                            resolve: {
                                title: function () {
                                    return 'Fehler beim Anlegen des Datensatzes';
                                },
                                body: function () {
                                    return response.data.message;
                                },
                                hideCancelButton: true
                            }
                        });
                    }
                );
            } else {

                var data = {
                    entity: entity,
                    id: vm.object['id'],
                    data: objectDataToSave
                };

                EntityService.update(data).then(
                    function successCallback(response) {
                        vm.doSave = false;
                        if(andClose) $uibModalInstance.close(vm.object);
                    },
                    function errorCallback(response) {
                        vm.doSave = false;
                        var modalInstance = $uibModal.open({
                            templateUrl: 'views/partials/modal.html',
                            controller: 'ModalCtrl as vm',
                            resolve: {
                                title: function () {
                                    return 'Fehler beim Anlegen des Datensatzes';
                                },
                                body: function () {
                                    return response.data.message;
                                },
                                hideCancelButton: true
                            }
                        });
                    }
                );
            }
        }

        function init(){
            angular.forEach(vm.schema.properties, function (config, key) {
                if (config.type == 'onejoin') {
                    vm.schemaOnejoin[config.tab] = schemaComplete[config.tab];
                    vm.object[key] = vm.object[key] ? vm.object[key] : {};
                }
            });

            var data = {
                entity: entity,
                id: vm.object.id
            }

            EntityService.single(data).then(
                function(response){
                    vm.object = response.data.data;
                },
                function(){}
            );
        }

        function save(andClose) {
            if (vm.schema['settings']['isPush']) {

                for (var formName in vm.forms) {
                    if (!vm.forms[formName].$valid) {
                        return;
                    }
                }

                vm.isSubmit = true;

                var data = {
                    entity: 'PIM\\PushToken',
                    count: true
                };



                EntityService.list(data).then(
                    function successCallback(response) {
                        if (vm.object[vm.schema['settings']['pushObject']]) {
                            var res = vm.object[vm.schema['settings']['pushObject']].split("_");

                            var data = {
                                entity: res[0],
                                id: res[1]
                            };

                            EntityService.single(data).then(
                                function successCallback(response2) {
                                    var jsonData = response2.data.data;
                                    confirmPush(response.data.data, vm.object[vm.schema['settings']['pushTitle']], vm.object[$scope.schema['settings']['pushText']], "Link auf " + res[0] + ": " + jsonData["title"]);
                                },
                                function errorCallback(response) {
                                    confirmPush(response.data.data, vm.object[vm.schema['settings']['pushTitle']], vm.object[vm.schema['settings']['pushText']], "Link: --");
                                }
                            );
                        } else {
                            confirmPush(response.data.data, vm.object[vm.schema['settings']['pushTitle']], vm.object[vm.schema['settings']['pushText']], "Link: --");
                        }
                    },
                    function errorCallback(response) {

                    }
                );


            } else {
                doSave(andClose);
            }

        }

        function onChangeValue(key, mainKey, value){

            if(!mainKey) {
                objectDataToSave[key] = value;
            }else{
                if(!objectDataToSave[mainKey]){
                    objectDataToSave[mainKey] = {};
                }
                if(objectDataToSave[mainKey][key] != value)  vm.haveChanges = true;
                objectDataToSave[mainKey][key] = value;
            }
        }

        
    }

})();

(function() {
    'use strict';

    angular
        .module('app')
        .controller('FilesCtrl', FilesCtrl);

    function FilesCtrl($scope, $rootScope, $cookies, localStorageService, $uibModalInstance, $routeParams, $timeout, $http, $uibModal, pimEntity, Upload, angularGridInstance, modaltitle, property, EntityService){
        var vm = this;

        var oldPageNumber = 1;
        var schema = localStorageService.get('schema');

        //Properties
        vm.angularGridOptions   = { refreshOnImgLoad : true };
        vm.currentPage          = 1;
        vm.doUpload             = false;
        vm.entity               = 'PIM\\File';
        vm.fileUploads          = null;
        vm.filter               = {};
        vm.filterIsOpen         = false;
        vm.filterBadge          = 0;
        vm.filterJoins          = {};
        vm.itemsPerPage         = 0;
        vm.modaltitle           = modaltitle;
        vm.permissions          = localStorageService.get('permissions');
        vm.objects              = [];
        vm.objectsAvailable     = false;
        vm.objectsNotAvailable  = false;
        vm.schema               = schema[vm.entity];
        vm.sortProperty         = vm.schema.settings.sortBy;
        vm.sortOrder            = vm.schema.settings.sortOrder;
        vm.totalItems           = 0;

        //Functions
        vm.cancel               = cancel;
        vm.closeFilter          = closeFilter;
        vm.delete               = doDelete;
        vm.loadData             = loadData;
        vm.openForm             = openForm;
        vm.executeFilter        = executeFilter;
        vm.paginationChanged    = paginationChanged;
        vm.resetFilter          = resetFilter;
        vm.selectFile           = selectFile;
        vm.sortBy               = sortBy;
        vm.uploadFile           = uploadFile;
        vm.uploadMultiFile      = uploadMultiFile;
        vm.openFile             = openFile;

        //Startup
        init();
        loadFilters();

        /////////////////////////

        function cancel(){
            $uibModalInstance.dismiss(false);
        }

        function closeFilter (){
            vm.filterIsOpen = false;
        }

        function calculateFilterBadge(){
            var badgeCount = 0;
            for (var key in vm.filter) {
                if(vm.filter[key]){
                    badgeCount++;
                }
            }

            vm.filterBadge = badgeCount;
        }

        function openFile(id, name) {
            window.open('/file/get/'+id+'/'+name, '_blank');
        }

        function doDelete(id, name){
            var modalInstance = $uibModal.open({
                templateUrl: '/ui/default/views/partials/modal.html?v=' + APP_VERSION,
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

                        angular.element('#fileitem_' + id).hide();
                        vm.objectsAvailable = false;

                        var data = {
                            entity: vm.entity,
                            id: id
                        };

                        EntityService.delete(data).then(
                            function successCallback(response) {
                                loadData();
                            },
                            function errorCallback(response) {
                                if(response.status == 401){
                                    var modalInstance = $uibModal.open({
                                        templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                                        controller: 'ReloginCtrl as vm',
                                        backdrop: 'static'
                                    });

                                    modalInstance.result.then(
                                        function () {
                                            vm.delete(andClose);
                                        },
                                        function () {
                                            $uibModalInstance.close();
                                            $location.path('/logout');
                                        }
                                    );

                                }else{
                                    var modalInstance = $uibModal.open({
                                        templateUrl: '/ui/default/views/partials/modal.html?v=' + APP_VERSION,
                                        controller: 'ModalCtrl as vm',
                                        resolve: {
                                            title: function(){ return 'Fehler beim Löschen'; },
                                            body: function(){ return response.data.message; },
                                            hideCancelButton: true
                                        }
                                    });
                                }
                            }
                        );

                    }
                },
                function () {}
            );
        }

        function openForm(object){

            var modalInstance = $uibModal.open({
                templateUrl: '/ui/default/views/form.html?v=' + APP_VERSION,
                controller: 'FormCtrl as  vm',
                resolve: {
                    entity: function(){ return vm.entity;},
                    object: function(){ return object; },
                    lang: function(){ return null},
                    doCopy: false,
                    translateFrom:  function(){ return null},
                    readonly: false
                },
                size: 'xl',
                backdrop: 'static'
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

        function executeFilter() {

            calculateFilterBadge();

            var savedFilter = localStorageService.get('savedFilter');
            if(!savedFilter){
                savedFilter = {};
            }
            savedFilter['PIM\\File'] = vm.filter;
            localStorageService.set('savedFilter', savedFilter);

            loadData();
        }

          function generateTree(entity, field, data, depth){
            var joinSchema = localStorageService.get('schema')[entity];

            vm.filterJoins[field] = vm.filterJoins[field] ? vm.filterJoins[field] : [];

            for(var i = 0; i < data.length; i++){
              var filler = '--'.repeat(depth);
              filler = filler ? filler + ' ' : filler;

              if(joinSchema.settings.labelProperty){
                data[i]['pim_filterTitle'] = filler + data[i][joinSchema.settings.labelProperty];
              }else{
                data[i]['pim_filterTitle'] = filler + data[i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
              }
              vm.filterJoins[field].push(data[i]);
              if(data[i]['treeChilds']){
                var subDepth = depth + 1;
                generateTree(entity, field, data[i]['treeChilds'], subDepth);
              }
            }

          }

        function init(){
            var savedFilter = localStorageService.get('savedFilter');
            if(savedFilter && savedFilter['PIM\\File']){
                vm.filter = savedFilter['PIM\\File'];
                calculateFilterBadge();
            }
        }

        function loadData() {
            var sortSettings = {};
            vm.errorMsg = null;
            sortSettings[vm.sortProperty] = vm.sortOrder;

            var filter = {};
            for (var key in vm.filter) {
                if(vm.filter[key]){
                    filter[key] = vm.filter[key];
                }
            }
            vm.objectsAvailable = false;

            var properties = ['id', 'modified', 'created', 'user'];

            for (key in vm.schema.list ) {
                properties.push(vm.schema.list[key]);
            }
            
            var data = {
                entity: 'PIM\\File',
                currentPage: vm.currentPage,
                order: sortSettings,
                where: filter,
                properties: properties
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

                    if(response.status == 401){
                        var modalInstance = $uibModal.open({
                            templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                            controller: 'ReloginCtrl as vm',
                            backdrop: 'static'
                        });

                        modalInstance.result.then(
                            function () {
                                loadData();
                            },
                            function () {
                                $uibModalInstance.close();
                                $location.path('/logout');
                            }
                        );

                    }

                    if(response.status == 403){
                        $location.path('/error');
                    }

                    vm.objectsAvailable = false;
                    vm.objectsNotAvailable = true;
                    vm.objects = {};

                    angularGridInstance.gallery.refresh();


                }
            );

        }

        function loadFilters(){
            for (var key in vm.schema.properties) {

                if(vm.schema.properties[key].type == 'join' && vm.schema.properties[key].isFilterable){
                    var entity = $rootScope.getShortEntityName(vm.schema.properties[key].accept);

                    if(!localStorageService.get('permissions')[entity].readable){
                        continue;
                    }

                    if(localStorageService.get('schema')[entity].settings.type == 'tree') {

                        EntityService.tree({entity: entity}).then(
                            (function(entity, key) {
                                return function(response) {
                                    generateTree(entity, key, response.data.data, 0);
                                }
                            })(entity, key),
                            function errorCallback(response) {
                            }
                        );
                    }else{
                        var joinSchema = localStorageService.get('schema')[entity];

                        var properties = ['id', 'modified', 'created', 'user'];
                        if(joinSchema.settings.isSortable){
                            properties.push('sorting');
                        }
                        for (var key in joinSchema.list ) {
                            properties.push(joinSchema.list[key]);
                        }

                        EntityService.list({entity: entity, properties: properties}).then(
                            (function(entity, key) {
                                return function(response) {
                                    vm.filterJoins[key] = response.data.data;

                                    for (var i = 0; i < vm.filterJoins[key].length; i++) {
                                        if (!vm.filterJoins[key][i]['pim_filterTitle']) {
                                            vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
                                        }
                                    }
                                }
                            })(entity, key),
                            function errorCallback(response) {
                            }
                        );
                    }

                }else if(vm.schema.properties[key].type == 'multijoin' && vm.schema.properties[key].isFilterable){
                  var entity = $rootScope.getShortEntityName(vm.schema.properties[key].accept);

                    var field = key;

                    if(!localStorageService.get('permissions')[entity].readable){
                        continue;
                    }

                    if(localStorageService.get('schema')[entity].settings.type == 'tree'){

                        EntityService.tree({entity: entity}).then(
                            (function(entity, key) {
                                return function(response) {
                                    generateTree(entity, key, response.data.data, 0)
                                }
                            })(entity, key),
                            function errorCallback(response) {
                            }
                        );
                    }else{

                        EntityService.list({entity: entity}).then(
                            (function(entity, key) {
                                return function(response) {
                                    var joinSchema = localStorageService.get('schema')[entity];
                                    vm.filterJoins[key] = response.data.data;

                                    for(var i = 0; i < vm.filterJoins[key].length; i++){
                                        if(!vm.filterJoins[key][i]['pim_filterTitle']){
                                            vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
                                        }
                                    }
                                }
                            })(entity, key),
                            function errorCallback(response) {
                            }
                        );
                    }


                }else if(vm.schema.properties[key].isFilterable){
                    var field = key;

                    var data = {
                        entity: vm.entity,
                        properties: [field],
                        groupBy: field
                    }
                    

                    data['order'] = {};
                    data['order'][field] = "ASC";

                    EntityService.list(data).then(
                        (function(entity, key) {
                            return function(response) {
                                vm.filterJoins[key] = response.data.data;

                                for(var i = 0; i < vm.filterJoins[key].length; i++){
                                    if(!vm.filterJoins[key][i]['pim_filterTitle']){
                                        vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][key];
                                    }
                                }
                            }
                        })(entity, key),
                        function errorCallback(response) {
                        }
                    );
                }


            }
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
            vm.errorMsg = null;
            file.upload = Upload.upload({
                url: '/file/upload',
                data: {file: file, id: id, folder: vm.filter['folder']}
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
                    vm.errorMsg = 'Fehler ' + response.status + ': ' + response.data.message;
                    vm.fileUploads = null;
                },
                function (evt) {
                    file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                }
            );

        }

        function uploadMultiFile(files, errFiles) {
            vm.fileUploads = files;
            vm.errorMsg = null;

            angular.forEach(files, function(file) {

                var data = {
                    entity: 'PIM\\File',
                    where: {
                        name: file.name,
                        folder: vm.filter['folder'] ? vm.filter['folder'] : -1
                    }
                };


                EntityService.list(data).then(
                    function successCallback(response) {
                        var fileId = response.data.data[0].id;

                        var modalInstance = $uibModal.open({
                            templateUrl: '/ui/default/views/partials/modal.html?v=' + APP_VERSION,
                            controller: 'ModalCtrl as vm',
                            resolve: {
                                title: function(){ return 'Bestehende Datei überschreiben?'; },
                                body: function(){ return 'Eine Datei mit diesem Namen ist bereits vorhanden. Wollen Sie die Datei überschreiben?'; },
                                hideCancelButton: false
                            }
                        });

                        modalInstance.result.then(
                            function (doOverwrite) {
                                if(doOverwrite){
                                    file.upload = Upload.upload({
                                        url: '/file/upload',
                                        data: {file: file, folder: vm.filter['folder'], id: fileId}
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
                                            vm.errorMsg = 'Fehler ' + response.status + ': ' + response.data.message;
                                            vm.fileUploads = null;
                                        },
                                        function (evt) {
                                            file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                                        }
                                    );
                                }
                            },
                            function () {
                                vm.fileUploads = null;
                            }
                        );
                    },
                    function errorCallback(response) {
                        file.upload = Upload.upload({
                            url: '/file/upload',
                            data: {file: file, folder: vm.filter['folder']}
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
                                vm.errorMsg = 'Fehler ' + response.status + ': ' + response.data.message;
                                vm.fileUploads = null;
                            },
                            function (evt) {
                                file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                            },
                            function(response){
                                //console.log("error");
                            }
                        );
                    }
                );



            });

        }
    }

})();

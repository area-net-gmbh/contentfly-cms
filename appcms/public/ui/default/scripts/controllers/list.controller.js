(function() {
    'use strict';

    angular
        .module('app')
        .controller('ListCtrl', ListCtrl);

    function ListCtrl($scope, $cookies, localStorageService, $routeParams, $http, $uibModal, pimEntity, $window, EntityService, $document, $location){
        var vm              = this;
        var oldPageNumber   = 1;


        //Properties
        vm.permissions         = localStorageService.get('permissions');
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

        if(!vm.schema){
            $location.path('/');
            return;
        }

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
        vm.back                 = back;
        vm.closeFilter          = closeFilter;
        vm.delete               = doDelete;
        vm.executeFilter        = executeFilter;
        vm.openForm             = openForm;
        vm.paginationChanged    = paginationChanged;
        vm.redirect             = redirect;
        vm.resetFilter          = resetFilter;
        vm.sortBy               = sortBy;
        vm.toggleFilter         = toggleFilter;

        //Startup
        init();
        loadData();
        loadFilters();

        ///////////////////////////////////

        function back(){
            $window.history.back();
        }

        function calcFilterBadge(){
            var badgeCount = 0;
            for (var key in vm.filter) {
                if(vm.filter[key]){
                    badgeCount++;
                }
            }

            vm.filterBadge = badgeCount;
        }

        function closeFilter(){
            vm.filterIsOpen = false;
        }

        function doDelete(object){

            if(vm.schema.settings.readonly){
                return;
            }

            var modaltitle = 'Wollen Sie den <b title="' + object.id + '">Eintrag ' + (object.id.length > 5 ? object.id.substr(0, 5) + '...' : object.id)  + '</b> wirklich löschen?';
            if(vm.schema.settings.labelProperty){
                modaltitle = 'Wollen Sie <b>' + vm.schema.settings.label + ' ' + object[vm.schema.settings.labelProperty] + '</b> wirklich löschen?';
            }

            var modalInstance = $uibModal.open({
                templateUrl: '/ui/default/views/partials/modal.html?v=' + APP_VERSION,
                controller: 'ModalCtrl as vm',
                resolve: {
                    title: function(){ return 'Eintrag löschen'; },
                    body: function(){ return modaltitle; },
                    object: function(){ return object; },
                    hideCancelButton: false
                }
            });

            modalInstance.result.then(
                function (doDelete) {


                    if(doDelete){

                        vm.objectsAvailable = false;
                        vm.objectsNotAvailable = false;

                        var data = {
                            entity: vm.entity,
                            id: object.id
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
                                            vm.delete(object);
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
                                    loadData();
                                }
                            }
                        );
                    }
                },
                function () {

                }
            );
        }

        function executeFilter() {

            var savedFilter = localStorageService.get('savedFilter');
            if(!savedFilter){
                savedFilter = {};
            }
            savedFilter[vm.entity] = vm.filter;
            localStorageService.set('savedFilter', savedFilter);

            calcFilterBadge();
            loadData();
        }

        function generateTree(entity, field, data, depth){
            var joinSchema = localStorageService.get('schema')[entity];

            vm.filterJoins[field] = vm.filterJoins[field] ? vm.filterJoins[field] : [];

            for(var i = 0; i < data.length; i++){
                var filler = '--'.repeat(depth);
                filler = filler ? filler + ' ' : filler

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
            for (var key in $routeParams) {
                if(key.substr(0, 2) != 'f_'){
                    continue;
                }
                var property = key.substr(2);

                vm.filter[property] = $routeParams[key];
            }

            var savedFilter = localStorageService.get('savedFilter');
            if(savedFilter && savedFilter[vm.entity]){
                vm.filter = savedFilter[vm.entity];
            }

            calcFilterBadge();
        }

        function loadData(){
            vm.objectsAvailable = false;
            vm.objectsNotAvailable = false;

            var sortSettings = {};
            sortSettings[vm.sortProperty] = vm.sortOrder;

            var properties = ['id', 'modified', 'created', 'user'];

            for (key in vm.schema.list ) {
                properties.push(vm.schema.list[key]);
            }

            if(vm.schema.settings.type == 'tree' || vm.schema.settings.sortRestrictTo){
                vm.schema.settings.isSortable = false;
            }

            var filter = {};
            for (var key in vm.filter) {
                if(vm.filter[key]){
                    filter[key] = vm.filter[key];
                    if(vm.schema.settings.type == 'tree' && key == 'treeParent' && vm.filter[key] ){
                        vm.schema.settings.isSortable = true;
                    }
                    if(vm.schema.settings.sortRestrictTo && key == vm.schema.settings.sortRestrictTo && vm.filter[key] ){
                        vm.schema.settings.isSortable = true;
                    }
                }
            }

            if(vm.schema.settings.isSortable){
                properties.push('sorting');
            }

            var data = {
                entity: vm.entity,
                currentPage: vm.schema.settings.isSortable ? 0 : vm.currentPage,
                order: sortSettings,
                where: filter,
                properties: properties,
                flatten:false
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
                }
            );
        }

        function loadFilters(){
            for (var key in vm.schema.properties) {
                if(vm.schema.properties[key].type == 'join' && vm.schema.properties[key].isFilterable){
                    var entity = null;
                    if(vm.schema.properties[key].accept.substr(0,7) == 'Areanet'){
                        entity = vm.schema.properties[key].accept.replace('Areanet\\PIM\\Entity\\', 'PIM\\');
                    }else{
                        entity =  vm.schema.properties[key].accept.replace('Custom\\Entity\\', '').replace('\\', '');
                    }

                    if(!vm.permissions[entity].readable){
                        continue;
                    }

                    if(localStorageService.get('schema')[entity].settings.type == 'tree') {

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
                        var joinSchema = localStorageService.get('schema')[entity];

                        var properties = ['id', 'modified', 'created', 'user'];
                        if(joinSchema.settings.isSortable){
                            properties.push('sorting');
                        }
                        for (var key2 in joinSchema.list ) {
                            properties.push(joinSchema.list[key2]);
                        }

                        EntityService.list({entity: entity, properties: properties, flatten: true}).then(
                            (function(entity, key) {
                                return function(response) {
                                    vm.filterJoins[key] = response.data.data;

                                    for (var i = 0; i < vm.filterJoins[key].length; i++) {
                                        if (!vm.filterJoins[key][i]['pim_filterTitle']) {
                                            if(joinSchema.settings.labelProperty){
                                                vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.settings.labelProperty];
                                            }else{
                                                vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
                                            }
                                        }
                                    }
                                }
                            })(entity, key),
                            function errorCallback(response) {
                            }
                        );
                    }

                }else if(vm.schema.properties[key].type == 'multijoin' && vm.schema.properties[key].isFilterable){
                    var entity = null;
                    if(vm.schema.properties[key].accept.substr(0,7) == 'Areanet'){
                        entity = vm.schema.properties[key].accept.replace('Areanet\\PIM\\Entity\\', 'PIM\\');
                    }else{
                        entity =  vm.schema.properties[key].accept.replace('Custom\\Entity\\', '').replace('\\', '');
                    }

                    var field = key;

                    if(!vm.permissions[entity].readable){
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

                        EntityService.list({entity: entity, flatten: true}).then(
                            (function(entity, key) {
                                return function(response) {
                                    var joinSchema = localStorageService.get('schema')[entity];
                                    vm.filterJoins[key] = response.data.data;

                                    for(var i = 0; i < vm.filterJoins[key].length; i++){
                                        if(!vm.filterJoins[key][i]['pim_filterTitle']){
                                            if(joinSchema.settings.labelProperty){
                                                vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.settings.labelProperty];
                                            }else{
                                                vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
                                            }
                                        }
                                    }
                                }
                            })(entity, key),
                            function errorCallback(response) {
                            }
                        );
                    }


                }else if(vm.schema.properties[key].type == 'virtualjoin' && vm.schema.properties[key].isFilterable){

                    var entity = null;
                    if(vm.schema.properties[key].accept.substr(0,7) == 'Areanet'){
                        entity = vm.schema.properties[key].accept.replace('Areanet\\PIM\\Entity\\', 'PIM\\');
                    }else{
                        entity =  vm.schema.properties[key].accept.replace('Custom\\Entity\\', '').replace('\\', '');
                    }

                    if(!vm.permissions[entity].readable){
                        continue;
                    }

                    EntityService.list({entity: entity, flatten: true}).then(
                        (function(entity, key) {
                            return function(response) {
                                var joinSchema = localStorageService.get('schema')[entity];
                                vm.filterJoins[key] = response.data.data;
                                for(var i = 0; i < vm.filterJoins[key].length; i++){
                                    if(!vm.filterJoins[key][i]['pim_filterTitle']){
                                        if(joinSchema.settings.labelProperty){
                                            vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.settings.labelProperty];
                                        }else{
                                            vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
                                        }
                                    }
                                }
                            }
                        })(entity, key),
                        function errorCallback(response) {
                        }
                    );



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

        function openForm(object){

            if(vm.schema.settings.readonly){
                return;
            }

            var modaltitle = 'Neues Objekt anlegen';
            if(object && vm.schema.settings.labelProperty){
                modaltitle = vm.schema.settings.label + ' ' + (object[vm.schema.settings.labelProperty] ? object[vm.schema.settings.labelProperty] : 'ID' + object.id) + ' bearbeiten';
            }else if(object){
                modaltitle = '<span title="' + object.id + '">Objekt ' + (object.id.length > 5 ? object.id.substr(0, 5) + '...' : object.id) + ' bearbeiten</span>';
            }else{
                object = {};

                for (var key in vm.schema.properties) {
                    if(!vm.schema.properties[key].type == 'join' && !vm.filter[key]){
                        continue;
                    }

                    object[key] = vm.filter[key]
                }

            }

            var modalInstance = $uibModal.open({
                templateUrl: '/ui/default/views/form.html?v=' + APP_VERSION,
                controller: 'FormCtrl as vm',
                resolve: {
                    entity: function(){ return vm.entity;},
                    title: function(){ return modaltitle; },
                    object: function(){ return object; }
                },
                backdrop: 'static',
                size: 'xl'
            });


            modalInstance.result.then(
                function (isSaved) {
                    if(isSaved){
                        loadData();
                    }
                },
                function () {
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
        }

        function redirect(path){
            $location.url(path);
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

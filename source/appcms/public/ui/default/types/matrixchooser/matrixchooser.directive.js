(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimMatrixchooser', pimMatrixchooser);


    function pimMatrixchooser($uibModal, $timeout, EntityService, localStorageService) {
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function () {
                return '/ui/default/types/matrixchooser/matrixchooser.html'
            },
            link: function (scope, element, attrs) {
                var entityData          = null;
                var entityChooser1      = null;
                var entityChooser2      = null;
                var itemsPerPage        = 10;

                //Properties
                scope.chooserObjects        = {};
                scope.chooserOpened         = {};
                scope.currentPage           = 1;
                scope.objects               = {};
                scope.propertyCountChooser1 = 0;
                scope.propertyCountChooser2 = 0;
                scope.schemaData            = null;
                scope.schemaChooser1        = null;
                scope.schemaChooser2        = null;
                scope.selectedIndex         = 0;
                scope.search                = {};

                //Functions
                scope.addNewObject  = addNewObject;
                scope.change        = change;
                scope.closeChooser  = closeChooser;
                scope.chooseObject  = chooseObject;
                scope.keyPressed    = keyPressed;
                scope.openChooser   = openChooser;
                scope.removeObject  = removeObject;

                //Startup
                init();
                formatValues();

                /////////////////////////////////////

                function addNewObject(id, doCloseChooser){
                    var modalInstance = $uibModal.open({
                        templateUrl: '/ui/default/views/form.html',
                        controller: 'FormCtrl as vm',
                        resolve: {
                            entity: function(){ return id ? entityChooser2 : entityChooser1;},
                            title: function(){ return 'Neues Objekt anlegen'; },
                            object: function(){ return null; }
                        },
                        size: 'lg'
                    });

                    modalInstance.result.then(
                        function (newObject) {
                            if(newObject){
                                chooseObject(id, newObject, doCloseChooser);
                            }
                        },
                        function () {}
                    );
                }

                function change(id){
                    scope.currentPage = 1;
                    loadData(id);
                }

                function closeChooser(){
                    for (var key in scope.chooserOpened) {
                        scope.chooserOpened[key] = false;
                    }
                }

                function chooseObject(id, object, doCloseChooser){

                    if(id){
                        var value = {};
                        value[scope.config.mapped1By] = scope.objects[id].object;
                        value[scope.config.mapped2By] = object;

                        scope.value.push(value);
                        formatValues();

                        triggerUpdate();
                    }else if(!scope.objects[object.id]){
                        scope.objects[object.id] = {
                            'id'        : object.id,
                            'label'     : object.titel,
                            'object'    : object,
                            'objects'   : {}
                        }
                    }

                    if(doCloseChooser){
                       closeChooser();
                    }

                }

                function formatValues(){

                    scope.search[0]           = scope.search[0] ? scope.search[0] : null;
                    scope.chooserOpened[0]    = scope.chooserOpened[0] ? scope.chooserOpened[0] : null;
                    scope.chooserObjects[0]   = scope.chooserObjects[0] ? scope.chooserObjects[0] : [];
                    
                    scope.value.forEach(function(value){
                        var dataChooser1 = value[scope.config.mapped1By];
                        if(!scope.objects[dataChooser1.id]){
                            scope.objects[dataChooser1.id] = {
                                'id'        : dataChooser1.id,
                                'label'     : dataChooser1.titel,
                                'object'    : dataChooser1,
                                'objects'   : {}
                            }
                        }

                        var dataChooser2 = value[scope.config.mapped2By];
                        scope.objects[dataChooser1.id].objects[dataChooser2.id] = dataChooser2

                        scope.search[dataChooser1.id]           = scope.search[dataChooser1.id] ? scope.search[dataChooser1.id] : null;
                        scope.chooserOpened[dataChooser1.id]    = scope.chooserOpened[dataChooser1.id] ? scope.chooserOpened[dataChooser1.id] : false;
                        scope.chooserObjects[dataChooser1.id]   = scope.chooserObjects[dataChooser1.id] ? scope.chooserObjects[dataChooser1.id] : [];
                    });
                }

                function init(){

                    if(scope.config.acceptFrom.substr(0, 18) == 'Areanet\\PIM\\Entity'){
                        entityData = scope.config.acceptFrom.replace('Areanet\\PIM\\Entity', 'PIM');
                    }else{
                        var fullEntity = null;
                        fullEntity = scope.config.acceptFrom.split('\\');
                        entityData = fullEntity[(fullEntity.length - 1)];
                    }
                    scope.schemaData = localStorageService.get('schema')[entityData];

                    if(scope.config.target1Entity.substr(0, 18) == 'Areanet\\PIM\\Entity'){
                        entityChooser1 = scope.config.target1Entity.replace('Areanet\\PIM\\Entity', 'PIM');
                    }else{
                        var fullEntityChooser1 = scope.config.target1Entity.split('\\');
                        entityChooser1 = fullEntityChooser1[(fullEntityChooser1.length - 1)];
                    }

                    scope.schemaChooser1 = localStorageService.get('schema')[entityChooser1];
                    scope.propertyCountChooser1  = Object.keys(scope.schemaChooser1.list).length;

                    if(scope.config.target2Entity.substr(0, 18) == 'Areanet\\PIM\\Entity'){
                        entityChooser2 = scope.config.target2Entity.replace('Areanet\\PIM\\Entity', 'PIM');
                    }else{
                        var fullEntityChooser2 = scope.config.target2Entity.split('\\');
                        entityChooser2 = fullEntityChooser2[(fullEntityChooser2.length - 1)];
                    }

                    scope.schemaChooser2 = localStorageService.get('schema')[entityChooser2];
                    scope.propertyCountChooser2  = Object.keys(scope.schemaChooser2.list).length;
                }

                function keyPressed(id, event, doCloseChooser){
                 
                    switch(event.keyCode) {
                        case 40:
                            if (scope.selectedIndex < scope.chooserObjects[id].length - 1) scope.selectedIndex++;
                            break;
                        case 38:
                            if (scope.selectedIndex > 0) scope.selectedIndex--;
                            break;
                        case 13:
                            chooseObject(id, scope.chooserObjects[id][scope.selectedIndex], doCloseChooser);
                            event.stopPropagation();
                            break;
                        case 39:
                            if (scope.currentPage < scope.totalPages){
                                scope.currentPage++;
                                loadData(id);
                            }
                            break;
                        case 37:
                            if(scope.currentPage > 1){
                                scope.currentPage--;
                                loadData(id);
                            }
                            break;
                        case 27:
                            closeChooser();
                            event.stopPropagation();
                            break;
                    }
                }

                function loadData(id){
                    var where = scope.search[id] ? {fulltext: scope.search[id]} : {};

                    var data = {
                        entity: id ? entityChooser2 : entityChooser1,
                        currentPage: scope.currentPage,
                        itemsPerPage: itemsPerPage,
                        where: where
                    };
                    EntityService.list(data).then(
                        function successCallback(response) {

                            scope.totalPages            = Math.ceil(response.data.totalItems / itemsPerPage);
                            scope.chooserObjects[id]    = response.data.data;
                            scope.selectedIndex         = 0;
                        },
                        function errorCallback(response) {
                            scope.chooserObjects[id] = [];
                        }
                    );
                }

                function openChooser(id){

                    for (var key in scope.chooserOpened) {
                        scope.chooserOpened[key] = false;
                    }
                    scope.selectedIndex     = 0;
                    scope.chooserOpened[id] = true;

                    $timeout(function () {
                        element.find('#search_' + id).focus();
                    }, 50);
                    
                    loadData(id);
                }

                function removeObject(id1, id2){
                    delete scope.objects[id1].objects[id2];
                    triggerUpdate();
                }

                function triggerUpdate(){
                    var values = [];

                    for (var mapped1Id in scope.objects) {
                        for (var mapped2Id in scope.objects[mapped1Id].objects) {
                            var value = {};
                            value[scope.config.mapped1By] = mapped1Id;
                            value[scope.config.mapped2By] = mapped2Id;
                            values.push(value);
                        }
                    }

                    scope.onChangeCallback({key: scope.key, value: values});
                }

            }
        }
    }

})();
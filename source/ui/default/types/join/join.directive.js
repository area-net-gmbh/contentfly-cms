(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimJoin', pimJoin);


    function pimJoin($uibModal, $timeout, EntityService, localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return 'types/join/join.html'
            },
            link: function(scope, element, attrs){
                var itemsPerPage = 10;

                //Properties
                scope.chooserOpened = false;
                scope.currentPage   = 1;
                scope.objects       = [];
                scope.schema        = localStorageService.get('schema')['Produkt'];
                scope.selectedIndex = 0;
                scope.totalPages    = 1;


                //Functions
                scope.change        = change;
                scope.chooseObject  = chooseObject;
                scope.closeChooser  = closeChooser;
                scope.keyPressed    = keyPressed;
                scope.loadData      = loadData;
                scope.openChooser   = openChooser;
                scope.removeObject  = removeObject;

                /////////////////////////////////////

                function change(){
                    scope.currentPage = 1;
                    loadData();
                }

                function chooseObject(object){

                    scope.value = object;
                    scope.onChangeCallback({key: scope.key, value: object.id});

                    scope.selectedIndex = 0;
                    scope.currentPage = 1;

                    scope.search        = '';
                    scope.objects       = [];



                    closeChooser();

                }

                function closeChooser(){
                    scope.chooserOpened = false;
                }

                function keyPressed(event){
                    switch(event.keyCode) {
                        case 40:
                            if (scope.selectedIndex < scope.objects.length - 1) scope.selectedIndex++;
                            break;
                        case 38:
                            if (scope.selectedIndex > 0) scope.selectedIndex--;
                            break;
                        case 13:
                            chooseObject(scope.objects[scope.selectedIndex]);
                            event.stopPropagation();
                            break;
                        case 39:
                            if (scope.currentPage < scope.totalPages){
                                scope.currentPage++;
                                loadData();
                            }
                            break;
                        case 37:
                            if(scope.currentPage > 1){
                                scope.currentPage--;
                                loadData();
                            }
                            break;
                        case 27:
                            closeChooser();
                            event.stopPropagation();
                            break;
                    }
                }

                function loadData(){
                    var where = scope.search ? {fulltext: scope.search} : {};

                    var data = {
                        entity: 'Produkt',
                        currentPage: scope.currentPage,
                        itemsPerPage: itemsPerPage,
                        where: where
                    };
                    EntityService.list(data).then(
                        function successCallback(response) {
                            scope.totalPages    = Math.ceil(response.data.totalItems / itemsPerPage);
                            scope.objects       = response.data.data;
                            scope.selectedIndex = 0;
                        },
                        function errorCallback(response) {
                            scope.objects = [];
                        }
                    );
                }

                function openChooser(){
                    scope.chooserOpened = true;

                    $timeout(function () {
                        element.find('#search').focus();
                    }, 50);

                    loadData();
                }

                function removeObject(){
                    scope.value = {};
                    scope.onChangeCallback({key: scope.key, value: ''});
                }

            }
        }
    }

})();

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
                //Properties
                scope.chooserOpened = false;
                scope.objects = [];
                scope.schema  = localStorageService.get('schema')['Produkt'];


                //Functions
                scope.chooseObject  = chooseObject;
                scope.closeChooser  = closeChooser;
                scope.openChooser   = openChooser;
                scope.loadData      = loadData;
                scope.removeObject  = removeObject;

                /////////////////////////////////////

                function chooseObject(object){
                    scope.value = object;

                    scope.onChangeCallback({key: scope.key, value: object.id});

                    closeChooser();
                }

                function closeChooser(){
                    scope.chooserOpened = false;
                }

                function loadData(){
                    var where = scope.search ? {fulltext: scope.search} : {};

                    var data = {
                        entity: 'Produkt',
                        currentPage: 1,
                        itemsPerPage: 10,
                        where: where
                    };
                    console.log(where);
                    EntityService.list(data).then(
                        function successCallback(response) {
                            scope.objects = response.data.data;
                            console.log(scope.objects);
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

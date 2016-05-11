app.directive('pimForm', function($filter, $uibModal) {
    return {
        restrict: 'AE',
        scope: { object: '=', key: '=', config: '=', form: '=', submitted : '=', datepickeropened: '=', datepickermodels: '=' },
        templateUrl: function(elem, attr){
            return 'views/directives/form.html';
        },
        link: function (scope) {
            scope.openDatePicker = function(key){
                scope.datepickeropened[key] = true;
            }
        }
    }
});
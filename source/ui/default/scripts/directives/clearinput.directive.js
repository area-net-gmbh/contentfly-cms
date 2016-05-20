(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimClearinput', pimClearinput);

    function pimClearinput(){
        return {
            restrict: 'E',
            scope: {
                modelValue: '=',
            },
            template: '<div class="pim-clearinput"><i class="glyphicon glyphicon-remove"></i></div>',
            link: function(scope, element, attrs) {
                element.bind('click', function() {
                    scope.modelValue = '';
                    scope.$apply();
                });
            }
        }
    }

})();

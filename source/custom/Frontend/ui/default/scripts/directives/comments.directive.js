(function() {
    'use strict';

    angular
        .module('app')
        .directive('customComments', customComments);


    function customComments($compile, $templateRequest, $rootScope){
        return {
            restrict: 'E',

            replace: false,
            templateUrl: function(){
                return '/custom/Frontend/ui/default/views/directives/comments.html?v=' + CUSTOM_VERSION
            },
            link: function(scope, element, attrs) {
                attrs.$observe('ticket', function(value) {
                    if(!value) return;

                    console.log('ticket = ' + value);
                });
            }
        }
    }

})();

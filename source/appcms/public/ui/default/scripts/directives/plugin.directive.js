(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimPlugin', pimPlugin);


    function pimPlugin($compile, $templateRequest, $rootScope){
        return {
            restrict: 'E',

            replace: false,
            link: function(scope, element, attrs) {
                scope.$watch('uiblocks', function(meeting) {
                    if(!$rootScope.uiblocks || element.attr('pim-plugin-rendered')) return;
                    if($rootScope.uiblocks[attrs.key]){
                        for(var i = 0; i < $rootScope.uiblocks[attrs.key].length; i++){
                            var template = '/custom/Frontend/ui/default/views/' + $rootScope.uiblocks[attrs.key][i]

                            $templateRequest(template).then(function(html){
                                var template = angular.element(html);
                                element.append(template);
                                $compile(template)(scope);
                            });
                        }

                        element.attr('pim-plugin-rendered', true);
                    }

                });


            }
        }
    }

})();

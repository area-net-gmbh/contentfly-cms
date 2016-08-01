(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimPlugin', pimPlugin);


    function pimPlugin($compile, $templateRequest){
        return {
            restrict: 'E',

            replace: false,
            link: function(scope, element, attrs) {
                
                scope.$watch('uiblocks', function(meeting) {
                    if(!scope.uiblocks || element.attr('pim-plugin-rendered')) return;

                    if(scope.uiblocks[attrs.key]){
                        for(var i = 0; i < scope.uiblocks[attrs.key].length; i++){
                            var template = '/custom/ui/default/views/' + scope.uiblocks[attrs.key][i]

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

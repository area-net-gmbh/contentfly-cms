(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimForm', pimForm);


    function pimForm($compile){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', object: '=', isvalid: '=', submitted: '=', onChangeCallback: '&'
            },
            replace: true,
            link: function(scope, element) {
                var formTypeDirective = '<pim-' + scope.config.type + ' key="key" config="config" value="object[key]" isvalid="isvalid" submitted="submitted" on-change-callback="onChange(key, value)"></pim-' + scope.config.type + '>';
                //console.log("TYPE:" + scope.config.type);
                element.append($compile(formTypeDirective)(scope));

                scope.onChange = function(key, value){
                    scope.onChangeCallback({key: key, value: value});
                }

            }
        }
    }

})();

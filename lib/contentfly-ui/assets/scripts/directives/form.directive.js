(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimForm', pimForm);


    function pimForm($compile){
        return {
            restrict: 'E',
            scope: {
                key: '=', mainKey: '=', config: '=', object: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&',
            },
            replace: true,
            link: function(scope, element, attrs) {
                var formTypeDirective = '<pim-' + scope.config.type + ' key="key" config="config" readonly="' + attrs.readonly + '" writable="' + attrs.writable + '" object="object" value="object[key]" is-valid="isValid" is-submit="isSubmit" on-change-callback="onChange(key, value)"></pim-' + scope.config.type + '>';
                element.append($compile(formTypeDirective)(scope));

                scope.onChange = function(key, value){
                    scope.onChangeCallback({key: key, mainKey: scope.mainKey, value: value});
                }

            }
        }
    }

})();

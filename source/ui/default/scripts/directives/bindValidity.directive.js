(function() {
    'use strict';

    angular
        .module('app')
        .directive('bindValidity', bindValidity);

    function bindValidity($parse) {
        return {
            restrict: 'A',
            scope: false,
            controller: ['$scope', '$attrs', function ($scope, $attrs) {
                var assign = $parse($attrs.bindValidity).assign;
                if (!angular.isFunction(assign)) {
                    throw new Error('the expression of bindValidity is not settable: ' + $attrs.bindValidity);
                }

                this.setFormController = function (formCtrl) {
                    if (!formCtrl) {
                        throw new Error('bindValidity requires one of <form> or ng-form');
                    }
                    $scope.$watch(
                        function () {
                            return formCtrl.$invalid;
                        },
                        function (newval) {
                            assign($scope, newval);
                        }
                    );
                };
            }],
            require: ['?form', '?ngForm', 'bindValidity'],
            link: function (scope, elem, attrs, ctrls) {
                var formCtrl, bindValidity;
                formCtrl = ctrls[0] || ctrls[1];
                bindValidity = ctrls[2];
                bindValidity.setFormController(formCtrl);
            }
        }
    }

})()

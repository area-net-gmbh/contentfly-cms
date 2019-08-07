(function() {
  'use strict';

  angular
    .module('app')
    .config(stateProvider);

  function stateProvider($stateProvider, $urlRouterProvider) {

    $stateProvider.state({
      url: '/',
      name: 'default',
      templateUrl: function ($stateParams){
        return '/ui/default/views/dashboard.html?v=' + APP_VERSION;
      },
      controllerProvider: function ($stateParams) {
        if(extendedRoutes['default']){
          return extendedRoutes['default'][0]['controller'];
        }else{
          return 'DashboardCtrl';
        }
      },
      resolve: { '$extend': function(){return null;} },
      controllerAs: 'vm',
      secure: true
    });

    $stateProvider.state({
      url: '/error',
      name: 'error',
      templateUrl: function ($stateParams){
        if(extendedRoutes['error']){
          return extendedRoutes['error'][0]['template'];
        }else{
          return '/ui/default/views/error.html?v=' + APP_VERSION;
        }
      },
      controllerProvider: function ($stateParams) {

        if(extendedRoutes['error']){
          return extendedRoutes['error'][0]['controller'];
        }else{
          return 'ErrorCtrl';
        }
      },
      resolve: { '$extend': function(){return null;} },
      secure: true
    });

    $stateProvider.state({
      url: '/login',
      name: 'login',
      templateUrl: function ($stateParams){
        if(extendedRoutes['login']){
          return extendedRoutes['login'][0]['template'];
        }else{
          return '/ui/default/views/login.html?v=' + APP_VERSION;
        }
      },
      controllerProvider: function ($stateParams) {

        if(extendedRoutes['login']){
          return extendedRoutes['login'][0]['controller'];
        }else{
          return 'LoginCtrl';
        }
      },
      resolve: { '$extend': function(){return null;} },
      controllerAs: 'vm'
    });

    $stateProvider.state({
      url: '/logout',
      name: 'logout',
      templateUrl: function ($stateParams){
        if(extendedRoutes['logout']){
          return extendedRoutes['logout'][0]['template'];
        }else{
          return '/ui/default/views/login.html?v=' + APP_VERSION;
        }
      },
      controllerProvider: function ($stateParams) {

        if(extendedRoutes['logout']){
          return extendedRoutes['logout'][0]['controller'];
        }else{
          return 'LogoutCtrl';
        }
      },
      resolve: { '$extend': function(){return null;} },
      controllerAs: 'vm'
    });
    var modal = null;

    $stateProvider.state({
      url: '/select',
      name: 'list.select',
      params: {entity: null, doSelect: true},
      onEnter: ['$stateParams', '$state', '$uibModal', function($stateParams, $state, $uibModal, $resource) {
        modal = $uibModal.open({
          templateUrl: '/ui/default/views/list.html?v=' + APP_VERSION,
          resolve: { pimEntity: function(){return null;}, '$extend': function(){return null;}, },
          secure: true,
          controller: 'ListCtrl',
          controllerAs: 'vm',
          windowClass: 'zindex-top'
        }).result.finally(function() {
          $state.go('^');
        });
      }]
    });

    $stateProvider.state({
      url: '/list/:entity',
      name: 'list',
      templateUrl: function ($stateParams){
        if(extendedRoutes['list'] && (!extendedRoutes['list'][0]['stateParams'] || extendedRoutes['list'][0]['stateParams'] && extendedRoutes['list'][0]['stateParams']['entity'] == $stateParams.entity)){
          return extendedRoutes['list'][0]['template'];
        }else{
          return '/ui/default/views/list.html?v=' + APP_VERSION;
        }

      },
      controllerProvider: function ($stateParams) {
        if(extendedRoutes['list'] && (!extendedRoutes['list'][0]['stateParams'] || extendedRoutes['list'][0]['stateParams'] && extendedRoutes['list'][0]['stateParams']['entity'] == $stateParams.entity)){
          return extendedRoutes['list'][0]['controller'];
        }else{
          return 'ListCtrl';
        }

      },
      controllerAs: 'vm',
      resolve: { pimEntity: function(){return null;}, '$extend': function(){return null;} },
      secure: true
    });

    $stateProvider.state({
      url: '/list/PIM/:entity',
      name: 'list-pim',
      templateUrl: function ($stateParams){
        if(extendedRoutes['list-pim'] && (!extendedRoutes['list-pim'][0]['stateParams'] || extendedRoutes['list-pim'][0]['stateParams'] && extendedRoutes['list-pim'][0]['stateParams']['entity'] == $stateParams.entity)){
          return extendedRoutes['list-pim'][0]['template'];
        }else{
          return '/ui/default/views/list.html?v=' + APP_VERSION;
        }
      },
      controllerProvider: function ($stateParams) {

        if(extendedRoutes['list-pim'] && (!extendedRoutes['list-pim'][0]['stateParams'] || extendedRoutes['list-pim'][0]['stateParams'] && extendedRoutes['list-pim'][0]['stateParams']['entity'] == $stateParams.entity)){
          return extendedRoutes['list-pim'][0]['controller'];
        }else{
          return 'ListCtrl';
        }
      },
      controllerAs: 'vm',
      resolve: { pimEntity: function(){return true;}, '$extend': function(){return null;} },
      secure: true
    });

    $stateProvider.state({
      url: '/settings',
      name: 'settings',
      templateUrl: function ($stateParams){
        if(extendedRoutes['settings']){
          return extendedRoutes['settings'][0]['template'];
        }else{
          return '/ui/default/views/settings.html?v=' + APP_VERSION;
        }
      },
      controllerProvider: function ($stateParams) {
        if(extendedRoutes['settings']){
          return extendedRoutes['settings'][0]['controller'];
        }else{
          return 'SettingsCtrl';
        }
      },
      controllerAs: 'vm',
      resolve: { '$extend': function(){return null;} },
      secure: true
    });

    $stateProvider.state({
      url: '/files',
      name: 'files',
      templateUrl: function ($stateParams){
        if(extendedRoutes['files']){
          return extendedRoutes['files'][0]['template'];
        }else{
          return '/ui/default/views/files.html?v=' + APP_VERSION;
        }
      },
      controllerProvider: function ($stateParams) {
        if(extendedRoutes['files']){
          return extendedRoutes['files'][0]['controller'];
        }else{
          return 'FilesCtrl';
        }
      },
      controllerAs: 'vm',
      resolve: {
        pimEntity: function(){return true;},
        modaltitle: function(){return null;},
        property: function(){return null;},
        '$uibModalInstance': function(){return null;},
        '$extend': function(){return null;}
      },
      secure: true
    });

    $urlRouterProvider.otherwise("/");

    for (var route in uiRoutes){

      var templatePath   = uiRoutes[route]['templateName'].substr(0, 8) == '/plugins' ? uiRoutes[route]['templateName'] : '/custom/Frontend/ui/default/views/' + uiRoutes[route]['templateName'];
      var controllerName = uiRoutes[route]['controllerName'];

      $stateProvider.state({
        url: route,
        name: 'custom-' + uiRoutes[route]['controllerName'].toLowerCase(),
        templateUrl: templatePath + '?v=' + CUSTOM_VERSION,
        controller: controllerName,
        controllerAs: 'vm',
        secure: uiRoutes[route]['secure']
      });
    }

  }

})();
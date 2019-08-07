(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimFileicon', pimFileicon);

    function pimFileicon($filter, localStorageService){
        return {
            restrict: 'A',
            scope: {  },
            link: function(scope, element, attrs){

                //var property = attrs.property;
                //var long     = attrs.long ? attrs.long : false;
                //var type     = scope.schema.properties[property] ? scope.schema.properties[property].type : null;

                switch(attrs.type){
                    case 'application/pdf':
                        element.attr('class', 'fileicon fa fa-file-pdf-o');
                        element.css('color', '#d20014');
                        break;
                    case 'application/gzip':
                    case 'application/x-compress':
                    case 'application/x-gtar':
                    case 'application/x-tar':
                    case 'application/x-ustar':
                    case 'application/zip':
                        element.attr('class', ' fileicon fa fa-file-archive-o');
                        element.css('color', '#ffb545');
                        break;
                    case 'application/mspowerpoint':
                    case 'application/vnd.ms-powerpoint':
                    case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                    case 'application/vnd.ms-powerpoint.presentation.macroEnabled.12':
                    case 'application/vnd.openxmlformats-officedocument.presentationml.slideshow':
                    case 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12':
                        element.attr('class', 'fileicon fa fa-file-powerpoint-o');
                        element.css('color', '#fa5a42');
                        break;
                    case 'application/rtf':
                    case 'application/msword':
                    case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                    case 'application/vnd.ms-word.document.macroEnabled.12':
                        element.attr('class', 'fileicon fa fa-file-word-o');
                        element.css('color', '#1a6eb1');
                        break;
                    case 'application/msexcel':
                    case 'application/xls':
                    case 'application/x-xls':
                    case 'application/vnd.ms-excel':
                    case 'application/msexcel':
                    case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                    case 'application/vnd.ms-excel.sheet.macroEnabled.12':
                        element.attr('class', 'fileicon fa fa-file-excel-o');
                        element.css('color', '#007c50');
                        break;
                    case 'application/octet-stream':
                        element.attr('class', 'fileicon fa fa-download');
                        element.css('color', '#666666');
                        break;
                    case 'application/xhtml+xml':
                    case 'application/xml':
                    case 'application/json':
                    case 'application/x-httpd-php':
                    case 'text/css':
                    case 'text/html':
                    case 'text/javascript':
                    case 'text/xml':
                    case 'text/php':
                    case 'application/csv':
                    case 'text/csv':
                        element.attr('class', 'fileicon fa fa-file-code-o');
                        element.css('color', '#a957ec');
                        break;
                    case 'text/plain':
                    case 'text/richtext':
                    case 'text/rtf':
                        element.attr('class', 'fileicon fa fa-file-text-o');
                        element.css('color', '#9f9f9f');
                        break;
                    case 'audio/x-mpeg':
                    case 'audio/x-midi':
                    case 'audio/x-aiff':
                    case 'audio/x-wav':
                        element.attr('class', 'fileicon fa fa-file-audio-o');
                        element.css('color', '#0095bd');
                        break;
                    case 'video/mpeg':
                    case 'video/quicktime':
                    case 'video/x-msvideo':
                    case 'video/x-sgi-movie':
                    case 'video/mp4':
                        element.attr('class', 'fileicon fa fa-file-video-o');
                        element.css('color', '#9f9f9f');
                        break;
                  case 'link/youtube':
                    element.attr('class', 'fileicon fileicon fa fa-youtube');
                    element.css('color', '#9f9f9f');
                    break;
                    default:
                        element.attr('class', 'fileicon fa fa-file-o');
                        element.css('color', '#D4A190');
                        break;
                }
                //fileicon fa fa-file-pdf-o



            }
        }
    }
})();
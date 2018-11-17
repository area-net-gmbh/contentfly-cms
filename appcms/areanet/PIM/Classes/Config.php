<?php
namespace Areanet\PIM\Classes;


/**
 * Class Config
 * @package Areanet\PIM\Classes
 */
class Config{

    /**
     * Hostname for config settings
     *
     * @var string
     */
    protected $host = 'default';

    /**
     * PATH TO CLI-PHP
     *
     * @var string
     */
    public $SYSTEM_PHP_CLI_COMMAND = 'php';

    /**
     * @var string $DB_HOST Database Server Host
     */
    public $DB_HOST     = null;

    /**
     * @var string $DB_NAME Database Name
     */
    public $DB_NAME     = null;

    /**
     * @var string $DB_USER Database Username
     */
    public $DB_USER     = null;

    /**
     * @var string $DB_PASS Database Password
     */
    public $DB_PASS     = null;

    /**
     * @var string $DB_PORT Database Password
     */
    public $DB_PORT     = 3306;

    /**
     * @var string $DB_HOST Database Charset
     */
    public $DB_CHARSET  = 'utf8';

    /**
     * @var string $DB_HOST Database Collate
     */
    public $DB_COLLATE  = 'utf8_unicode_ci';

    /**
     * @var string $DB_NESTED_LEVELS Loading x nested levels
     */
    public $DB_NESTED_LEVELS  = 3;

    /**
     * @var boolean $DB_GUID_STRATEGY Set primary types to guid
     */
    public $DB_GUID_STRATEGY  = true;

    /**
     * @var boolean $DB_ID_INTEGER_TYPE Type of id integer fields
     */
    public $DB_ID_INTEGER_TYPE  = 'integer';

    /**
     * @var boolean Doctrine erstellt Proxy-Klassen automatisch zur Laufzeit
     */
    public $APP_AUTOGENERATE_PROXIES = true;

    /**
     * @var boolean Enable Schema Cache
     */
    public $APP_ENABLE_SCHEMA_CACHE = true;

    /**
     * @var string  Cache-Driver filesystem,apc,memcached
     */
    public $APP_CACHE_DRIVER = 'filesystem';

    /**
     * @var integer Token Timeout in ms
     */
    public $APP_TOKEN_TIMEOUT = 1800;

    /**
     * @var boolean Check Token Timeout
     */
    public $APP_CHECK_TOKEN_TIMEOUT = true;

    /**
     * @var string Default recipient mail address
     */
    public $APP_MAILTO = null;

    /**
     * @var string Default sender mail address
     */
    public $APP_MAILFROM = null;

    /**
     * @var boolean Show detailed error messages
     */
    public $APP_DEBUG = false;

    /**
     * @var string Default controller for PIM, overwrite
     */
    public $APP_DEFAULT_CONTROLLER = 'ui.controller:showAction';

    /**
     * @var string Timezone for PIM
     */
    public $APP_TIMEZONE = 'Europe/Berlin';

    /**
     * @var array Sprachen
     */
    public $APP_LANGUAGES = array();

    /**
     * @var string Aktiviert XSendFile für Download (Apache-Modul muss installiert und aktiviert sein)
     */
    public $APP_ENABLE_XSENDFILE = false;

    public $APP_SYSTEM_TYPES = array(
        '\\Areanet\\PIM\\Classes\\Types\\BooleanType',
        '\\Areanet\\PIM\\Classes\\Types\\IntegerType',
        '\\Areanet\\PIM\\Classes\\Types\\DatetimeType',
        '\\Areanet\\PIM\\Classes\\Types\\DecimalType',
        '\\Areanet\\PIM\\Classes\\Types\\FloatType',
        '\\Areanet\\PIM\\Classes\\Types\\TextareaType',
        '\\Areanet\\PIM\\Classes\\Types\\PasswordType',
        '\\Areanet\\PIM\\Classes\\Types\\StringType',
        '\\Areanet\\PIM\\Classes\\Types\\TimeType',
        '\\Areanet\\PIM\\Classes\\Types\\SelectType',
        '\\Areanet\\PIM\\Classes\\Types\\RteType',
        '\\Areanet\\PIM\\Classes\\Types\\OnejoinType',
        '\\Areanet\\PIM\\Classes\\Types\\JoinType',
        '\\Areanet\\PIM\\Classes\\Types\\JoinBidirectionalType',
        '\\Areanet\\PIM\\Classes\\Types\\FileType',
        '\\Areanet\\PIM\\Classes\\Types\\MultifileType',
        '\\Areanet\\PIM\\Classes\\Types\\MultijoinType',
        '\\Areanet\\PIM\\Classes\\Types\\PermissionsType',
        '\\Areanet\\PIM\\Classes\\Types\\VirtualjoinType',
        '\\Areanet\\PIM\\Classes\\Types\\EntitySelectorType',
        '\\Areanet\\PIM\\Classes\\Types\\CheckboxType',
        '\\Areanet\\PIM\\Classes\\Types\\RadioType',
        '\\Areanet\\PIM\\Classes\\Types\\I18nPermissionsType'
    );

    /**
     * @var string Allow CORS '*' or 'domain.de'
     */
    public $APP_CS_POLICY        = "default-src 'self' 'unsafe-inline' 'unsafe-eval'  https://maxcdn.bootstrapcdn.com;";

    /**
     * @var string Allow CORS '*' or 'domain.de'
     */
    public $APP_ALLOW_ORIGIN        = null;

    /**
     * @var string Allow Credentials
     */
    public $APP_ALLOW_CREDENTIALS   = 'false';

    /**
     * @var string Allowed Methods
     */
    public $APP_ALLOW_METHODS       = 'POST, GET';

    /**
     * @var string Allowed Headers
     */
    public $APP_ALLOW_HEADERS       = 'content-type, x-xsrf-token';

    /**
     * @var string CORS max age
     */
    public $APP_MAX_AGE             = 0;

    /**
     * @var string Masterpasswort für die Authentifizierung
     */
    public $APP_MASTER_PASSWORD     = null;

    /**
     * @var string Load Installer
     */
    public $APP_INSTALLER_URL = 'install';


    /**
     * @var string Force SSL-Connection
     */
    public $APP_FORCE_SSL = false;

    /**
     * @var string HTTP Authentification User
     */
    public $APP_HTTP_AUTH_USER = null;

    /**
     * @var string HTTP Authentification Password
     */
    public $APP_HTTP_AUTH_PASS = null;

    /**
     * @var array Export-Methoden
     */
    public $APP_EXPORT_METHODS = array('csv' => 'CSV', 'excel' => 'EXCEL', 'json' => 'JSON', 'xml' => 'XML');

    /**
     * @var string Export-Controller
     */
    public $APP_EXPORT_CONTROLLER = 'Areanet\PIM\Controller\ExportController';

    /**
     * @var string Load Frontend UI in folder /ui/...
     */
    public $FRONTEND_UI = 'default';

    /**
     * @var string Name of the General-Tab
     */
    public $FRONTEND_TAB_GENERAL_NAME = 'Allgemein';

    /**
     * @var string Load Frontend UI in folder /ui/...
     */
    public $FRONTEND_TITLE = 'Contentfly CMS - Let your content fly!';

    /**
     * @var boolean Benutzerdefinierte Navigation anzeigen.
     */
    public $FRONTEND_CUSTOM_NAVIGATION = false;

    /**
     * @var string Load Frontend UI in folder /ui/...
     */
    public $FRONTEND_WELCOME = 'Contentfly CMS - Let your content fly!';

    /**
     * @var string URL/Path for login in the backend
     */
    public $FRONTEND_URL = '/';

    /**
     * @var string URL/Path for login in the backend
     */
    public $FRONTEND_LOGIN_REDIRECT = '/';

    /**
     * @var boolean Show custom logo custom/Frontend/ui/default/img/logo.png
     */
    public $FRONTEND_CUSTOM_LOGO = false;

    /**
     * @var boolean Show custom logo custom/Frontend/ui/default/img/bg_login.jpg
     */
    public $FRONTEND_CUSTOM_LOGIN_BG = false;

    /**
     * @var boolean Square previewed images in forms
     */
    public $FRONTEND_FORM_IMAGE_SQUARE_PREVIEW = true;

    /**
     * @var integer URL/Path for login in the backend
     */
    public $FRONTEND_ITEMS_PER_PAGE = 20;


    /**
     * @var integer Show ID at position x in list (0 for hide id)
     */
    public $FRONTEND_SHOW_ID_IN_LIST = 1;

    /**
     * @var integer Show Owner at position x in list (0 for hide Owner)
     */
    public $FRONTEND_SHOW_OWNER_IN_LIST = 1000;


    /**
     * @var array Register File Processors
     */
    public $FILE_PROCESSORS = array('\Areanet\PIM\Classes\File\Processing\Image');


    /**
     * @var boolean Filenhash must be unique
     */
    public $FILE_HASH_MUST_UNIQUE = false;

    /**
     * @var integer Qualität, 0..100 / 100 = keine Komprimierung
     */
    public $FILE_IMAGE_QUALITY_JPEG = 90;

    /**
     * @var integer Qualität, 0..9 / 0 = keine Komprimierung
     */
    public $FILE_IMAGE_QUALITY_PNG = 0;

    /**
     * @var boolean Lifetime for HTTP-File-Cache = 7 Tage
     */
    public $FILE_CACHE_LIFETIME = 604800;



    /**
     * @var string ImageMagick-Path
     */
    public $IMAGEMAGICK_EXECUTABLE = 'convert';

    public $SECURITY_CIPHER_METHOD = 'AES-128-ECB';
    public $SECURITY_CIPHER_KEY    = null;


    /**
     * Get hostname for config settings
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Config constructor.
     *
     * @param string $host Hostname for config settings
     * @param Config $config Copy this config settings for overwriting
     */
    public function __construct($host = 'default', Config $config = null)
    {

        if($config !== null){
            foreach (get_object_vars($config) as $key => $value) {
                $this->$key = $value;
            }
        }

        $this->host = $host;
    }
}
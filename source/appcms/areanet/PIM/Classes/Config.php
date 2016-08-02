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
     * @var string $DB_HOST Database Charset
     */
    public $DB_CHARSET  = 'utf8';

    /**
     * @var string $DB_NESTED_LEVELS Loading x nested levels
     */
    public $DB_NESTED_LEVELS  = 3;

    /**
     * @var boolean Enable Schema Cache
     */
    public $APP_ENABLE_SCHEMA_CACHE = false;


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
        '\\Areanet\\PIM\\Classes\\Types\\SelectType',
        '\\Areanet\\PIM\\Classes\\Types\\RteType',
        '\\Areanet\\PIM\\Classes\\Types\\OnejoinType',
        '\\Areanet\\PIM\\Classes\\Types\\JoinType',
        '\\Areanet\\PIM\\Classes\\Types\\FileType',
        '\\Areanet\\PIM\\Classes\\Types\\MultifileType',
        '\\Areanet\\PIM\\Classes\\Types\\MultijoinType'
    );



    /**
     * @var string Load Frontend UI in folder /ui/...
     */
    public $FRONTEND_UI = 'default';

    /**
     * @var string Load Frontend UI in folder /ui/...
     */
    public $FRONTEND_TITLE = 'APP-CMS';

    /**
     * @var string Load Frontend UI in folder /ui/...
     */
    public $FRONTEND_WELCOME = 'Willkommen im APP-CMS';

    /**
     * @var string URL/Path for login in the backend
     */
    public $FRONTEND_URL = '/';

    /**
     * @var boolean Show custom logo custom/Resources/logo.png
     */
    public $FRONTEND_CUSTOM_LOGO = false;

    /**
     * @var boolean Square previewed images in forms
     */
    public $FRONTEND_FORM_IMAGE_SQUARE_PREVIEW = true;

    /**
     * @var integer URL/Path for login in the backend
     */
    public $FRONTEND_ITEMS_PER_PAGE = 20;

    /**
     * @var array Register File Processors
     */
    public $FILE_PROCESSORS = array('\Areanet\PIM\Classes\File\Processing\Image');


    /**
     * @var boolean Filenhash must be unique
     */
    public $FILE_HASH_MUST_UNIQUE = true;
    
    
    /**
     * @var string ImageMagick-Path
     */
    public $IMAGEMAGICK_EXECUTABLE = 'convert';

    public $PUSH_GOOGLE_KEY = null;
    public $PUSH_APPLE_HOST = null;
    public $PUSH_APPLE_CERT = null;
    public $PUSH_APPLE_PASS = null;
    public $PUSH_APPLE_SANDBOX = false;





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

        if($config != null){
            foreach (get_object_vars($config) as $key => $value) {
                $this->$key = $value;
            }
        }

        $this->host = $host;
    }
}
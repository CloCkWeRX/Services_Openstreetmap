<?php
/**
 * Config.php
 * 08-Nov-2011
 *
 * PHP Version 5
 *
 * @category Services
 * @package  Services_Openstreetmap
 * @author   Ken Guest <kguest@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @version  Release: @package_version@
 * @link     Config.php
 */

/**
 * Services_Openstreetmap_Config
 *
 * @category Services
 * @package  Services_Openstreetmap
 * @author   Ken Guest <kguest@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @link     Config.php
 */
class Services_Openstreetmap_Config
{

    /**
     * Minimum version of the OSM API that is supported.
     * @var float
     * @internal
     */
    protected $minVersion = null;

    /**
     * Maximum version of the OSM API that is supported.
     * @var float
     * @internal
     */
    protected $maxVersion = null;

    /**
     * timeout, in seconds.
     * @var integer
     * @internal
     */
    protected $timeout = null;

    /**
     * number of elements allowed per changeset
     * @var integer
     * @internal
     */
    protected $changeset_maximum_elements = null;

    /**
     * Maximum number of nodes per way.
     * @var integer
     * @internal
     */
    protected $waynodes_maximum = null;

    /**
     * Number of tracepoints per way.
     * @var integer
     * @internal
     */
    protected $tracepoints_per_page = null;

    /**
     * Max size of area that can be downloaded in one request.
     * @var float
     * @internal
     */
    protected $area_maximum = null;


    /**
     * Default config settings
     *
     * @var array
     * @see Services_Openstreetmap::getConfig
     * @see Services_Openstreetmap::setConfig
     */
    protected $config = array(
        'adapter'      => 'HTTP_Request2_Adapter_Socket',
        'api_version'  => '0.6',
        'password'     => null,
        'passwordfile' => null,
        'server'       => 'http://api.openstreetmap.org/',
        'User-Agent'   => 'Services_Openstreetmap',
        'user'         => null,
        'verbose'      => false,
    );

    /**
     * Version of the [OSM] API which communications will be over.
     * @var string
     * @internal
     */
    protected $api_version = '0.6';

    /**
     * Server to connect to.
     * @var string
     * @internal
     */
    protected $server = 'http://api.openstreetmap.org/';

    /**
     * Get the value of a configuration setting - if none is set all are
     * returned.
     *
     * <code>
     * $config = $osm->getConfig();
     * </code>
     *
     * @param string $name name. optional.
     *
     * @return mixed  value of $name parameter, array of all configuration
     *                parameters if $name is not given
     * @throws Services_Openstreetmap_Exception If the parameter is unknown
     */
    public function getValue($name = null)
    {
        if ($name === null) {
            return $this->config;
        } elseif (!array_key_exists($name, $this->config)) {
            throw new Services_Openstreetmap_Exception(
                "Unknown config parameter '$name'"
            );
        }
        return $this->config[$name];
    }

    /**
     * set at least one configuration variable.
     *
     * <pre>
     * $osm->setConfig('user', 'fred@example.com');
     * $osm->setConfig(array('user' => 'fred@example.com', 'password' => 'Simples'));
     * $osm->setConfig('user' => 'f@example.com')->setConfig('password' => 'Sis');
     * </pre>
     *
     * The following parameters are available:
     * <ul>
     *  <li> 'adapter'      - adapter to use (string)</li>
     *  <li> 'api_version'  - Version of API to communicate via (string)</li>
     *  <li> 'password'     - password (string, optional)</li>
     *  <li> 'passwordfile' - passwordfile (string, optional)</li>
     *  <li> 'server'       - server to connect to (string)</li>
     *  <li> 'User-Agent'   - User-Agent (string)</li>
     *  <li> 'user'         - user (string, optional)</li>
     *  <li> 'verbose'      - verbose (boolean, optional)</li>
     * </ul>
     *
     * @param mixed $config array containing config settings
     * @param mixed $value  config value if $config is not an array
     *
     * @throws Services_Openstreetmap_Exception If the parameter is unknown
     *
     * @access public
     * @return void
     */
    function setValue($config, $value = null)
    {
        if (is_array($config)) {
            if (isset($config['adapter'])) {
                $this->config['adapter'] = $config['adapter'];
            }
            foreach ($config as $key=>$value) {
                if (!array_key_exists($key, $this->config)) {
                    throw new Services_Openstreetmap_Exception(
                        "Unknown config parameter '$key'"
                    );
                }
                switch($key) {
                case 'server':
                    $this->setServer($value);
                    break;
                case 'passwordfile':
                    $this->setPasswordfile($value);
                    break;
                case 'api_version':
                    $this->config[$key] = $value;
                    $api = "Services_Openstreetmap_API_V" . str_replace(
                        '.',
                        '',
                        $value
                    );
                    $this->api = new $api;
                    break;
                default:
                    $this->config[$key] = $value;
                }
            }
        } else {
            if (!array_key_exists($config, $this->config)) {
                throw new Services_Openstreetmap_Exception(
                    "Unknown config parameter '$config'"
                );
            }
            $this->config[$config] = $value;
            if ($config == 'server') {
                $this->setServer($this->server);
            } elseif ($config == 'passwordfile') {
                $this->setPasswordfile($value);
            }
        }
        return $this;
    }

    /**
     * Connect to specified server.
     *
     * @param string $server base server details, e.g. http://api.openstreetmap.org
     *
     * @access public
     * @return Services_Openstreetmap
     */
    function setServer($server)
    {
        try {
            $c = $this->getTransport()->getResponse($server . '/api/capabilities');
        } catch (Exception $ex) {
            throw new Services_Openstreetmap_Exception(
                'Could not get a valid response from server',
                $ex->getCode(),
                $ex
            );
        }
        $this->server = $server;
        $capabilities = $c->getBody();
        if (!$this->_checkCapabilities($capabilities)) {
            throw new Services_Openstreetmap_Exception(
                'Problem checking server capabilities'
            );
        }

        return $this;
    }

    /**
     * Set and parse a password file, setting username and password as specified
     * in the file.
     *
     * A password file is a ASCII text file, with username and passwords pairs
     * on each line, seperated [delimited] by a semicolon.
     * Lines starting with a hash [#] are comments.
     * If only one non-commented line is present in the file, that username and
     * password will be used for authentication.
     * If more than one set of usernames and passwords are present, the
     * username must be specified, and the matching password from the file will
     * be used.
     *
     * <pre>
     * # Example password file.
     * fredfs@example.com:Wilma4evah
     * barney@example.net:B3ttyRawks
     * </pre>
     *
     * @param string $file file containing credentials
     *
     * @access public
     * @return Services_Openstreetmap
     */
    function setPasswordfile($file)
    {
        if (is_null($file)) {
            return $this;
        }
        $lines = @file($file);
        if ($lines === false) {
            throw new Services_Openstreetmap_Exception(
                'Could not read password file'
            );
        }
        $this->config['passwordfile'] =  $file;
        array_walk($lines, create_function('&$val', '$val = trim($val);'));
        if (sizeof($lines) == 1) {
            if (strpos($lines[0], '#') !== 0) {
                list($this->config['user'], $this->config['password'])
                    = explode(':', $lines[0]);
            }
        } elseif (sizeof($lines) == 2) {
            if (strpos($lines[0], '#') === 0) {
                if (strpos($lines[1], '#') !== 0) {
                    list($this->config['user'], $this->config['password'])
                        = explode(':', $lines[1]);
                }
            }
        } else {
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) {
                    continue;
                }
                list($user, $pwd) = explode(':', $line);
                if ($user == $this->config['user']) {
                    $this->config['password'] = $pwd;
                }
            }
        }
        return $this;
    }

    /**
     * Set the Transport instance.
     *
     * @param Services_Openstreetmap_Transport $transport Transport instance.
     *
     * @return Services_Openstreetmap_Config
     */
    public function setTransport(Services_Openstreetmap_Transport $transport)
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * Retrieve the current Transport instance.
     *
     * @return Services_Openstreetmap_Transport.
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * Return all config settings in an array.
     *
     * @return array
     */
    public function asArray()
    {
        return $this->config;
    }

    /**
     * Set various properties to describe the capabilities that the connected
     * server supports.
     *
     * @param mixed $capabilities XML describing the capabilities of the server
     *
     * @see maxVersion
     * @see minVersion
     * @see timeout
     *
     * @return void
     *
     * @internal
     * @throws   Services_Openstreetmap_Exception If the API Version is not
     *                                            supported.
     */
    private function _checkCapabilities($capabilities)
    {
        $xml = simplexml_load_string($capabilities);
        if ($xml === false) {
            return false;
        }

        $this->minVersion = (float) $this->getXMLValue($xml, 'version', 'minimum');
        $this->maxVersion = (float) $this->getXMLValue($xml, 'version', 'maximum');
        if (($this->minVersion > $this->api_version
            || $this->api_version > $this->maxVersion)
        ) {
            throw new Services_Openstreetmap_Exception(
                'Specified API Version ' . $this->api_version .' not supported.'
            );
        }
        $this->timeout = (int) $this->getXMLValue($xml, 'timeout', 'seconds');
        //changesets
        $this->changeset_maximum_elements = (int) $this->getXMLValue(
            $xml,
            'changesets',
            'maximum_elements'
        );

        // Maximum number of nodes per way.
        $this->waynodes_maximum = (int) $this->getXMLValue(
            $xml,
            'waynodes',
            'maximum'
        );

        // Number of tracepoints per way.
        $this->tracepoints_per_page = (int) $this->getXMLValue(
            $xml,
            'tracepoints',
            'per_page'
        );

        // Max size of area that can be downloaded in one request.
        $this->area_maximum = (float) $this->getXMLValue(
            $xml,
            'area',
            'maximum'
        );
        return true;
    }

    /**
     * Max size of area that can be downloaded in one request.
     *
     * <code>
     * $osm = new Services_Openstreetmap();
     * $area_allowed = $osm->getMaxArea();
     * </code>
     *
     * @return float
     */
    public function getMaxArea()
    {
        return $this->area_maximum;
    }

    /**
     * minVersion - min API version supported by connected server.
     *
     * <code>
     * $config = array('user' => 'fred@example.net', 'password' => 'wilma4eva');
     * $osm = new Services_Openstreetmap($config);
     * $min = $osm->getMinVersion();
     * </code>
     *
     * @return float
     */
    public function getMinVersion()
    {
        return $this->minVersion;
    }

    /**
     * maxVersion - max API version supported by connected server.
     *
     * <code>
     * $config = array('user' => 'fred@example.net', 'password' => 'wilma4eva');
     * $osm = new Services_Openstreetmap($config);
     * $max = $osm->getMaxVersion();
     * </code>
     *
     * @return float
     */
    public function getMaxVersion()
    {
        return $this->maxVersion;
    }

    /**
     * Return the number of seconds that must elapse before a connection is
     * considered to have timed-out.
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Maximum number of tracepoints per page.
     *
     * <code>
     * $osm = new Services_Openstreetmap();
     * $tracepoints = $osm->getTracepointsPerPage();
     * </code>
     *
     * @return float
     */
    public function getTracepointsPerPage()
    {
        return $this->tracepoints_per_page;
    }

    /**
     * Maximum number of nodes per way.
     *
     * Anymore than that and the way must be split.
     *
     * <code>
     * $osm = new Services_Openstreetmap();
     * $max = $osm->getMaxNodes();
     * </code>
     *
     * @return float
     */
    public function getMaxNodes()
    {
        return $this->waynodes_maximum;
    }

    /**
     * Number of elements allowed per changeset
     *
     * <code>
     * $osm = new Services_Openstreetmap();
     * $max = $osm->getMaxElements();
     * </code>
     *
     * @return float
     */
    public function getMaxElements()
    {
        return $this->changeset_maximum_elements;
    }

    /**
     * getXMLValue
     *
     * @param SimpleXMLElement $xml       Object
     * @param string           $tag       name of tag
     * @param string           $attribute name of attribute
     * @param mixed            $default   default value
     *
     * @return void
     */
    public function getXMLValue(
        SimpleXMLElement $xml,
        $tag,
        $attribute,
        $default = null
    ) {
        $obj = $xml->xpath('//' . $tag);
        if (empty($obj)) {
            return $default;
        }
        return $obj[0]->attributes()->$attribute;
    }

}

?>

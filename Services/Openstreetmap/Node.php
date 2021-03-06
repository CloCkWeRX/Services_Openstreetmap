<?php
/**
 * Node.php
 * 25-Apr-2011
 *
 * PHP Version 5
 *
 * @category Services
 * @package  Services_Openstreetmap
 * @author   Ken Guest <kguest@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @version  Release: @package_version@
 * @link     Node.php
*/

/**
 * Services_Openstreetmap_Node
 *
 * @category Services
 * @package  Services_Openstreetmap
 * @author   Ken Guest <kguest@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @link     Node.php
 */
class Services_Openstreetmap_Node extends Services_Openstreetmap_Object
{
    protected $type = 'node';

    /**
     * Latitude of node
     *
     * @return float
     */
    public function getLat()
    {
        return (float) $this->getAttributes()->lat;
    }

    /**
     * Longitude of node
     *
     * @return float
     */
    public function getLon()
    {
        return (float) $this->getAttributes()->lon;
    }

    /**
     * set the Latitude of the node
     *
     * <pre>
     * $node->setLat($lat)->setLon($lon);
     * </pre>
     *
     * @param float $value Latitude (-180 < y < 180)
     *
     * @return Services_Openstreetmap_Node
     * @throws InvalidArgumentException
     */
    public function setLat($value)
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException("Latitude must be numeric");
        }
        if ($value < -180) {
            throw new InvalidArgumentException("Latitude can't be less than -180");
        }
        if ($value > 180) {
            throw new InvalidArgumentException("Latitude can't be greater than 180");
        }
        return $this;
    }

    /**
     * set the Longitude of the node
     *
     * <pre>
     * $node->setLat($lat)->setLon($lon);
     * </pre>
     *
     * @param float $value Longitude (-90 < x < 90)
     *
     * @return Services_Openstreetmap_Node
     * @throws InvalidArgumentException
     */
    public function setLon($value)
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException("Longitude must be numeric");
        }
        if ($value < -90) {
            throw new InvalidArgumentException("Longitude can't be less than -90");
        }
        if ($value > 90) {
            throw new InvalidArgumentException("Longitude can't be greater than 90");
        }
        return $this;
    }

    /**
     * Return address [tags], as an array, if set.
     *
     * @return array
     */
    public function getAddress()
    {
        $ret  = array(
            'addr_housename' => null,
            'addr_housenumber' => null,
            'addr_street' => null,
            'addr_city' => null,
            'addr_country' => null
        );
        $tags = $this->getTags();
        $details_set = false;
        foreach ($tags as $key => $value) {
            if (strpos($key, 'addr') === 0) {
                $ret[str_replace(':', '_', $key)] = $value;
                $details_set = true;
            }
        }
        if (!$details_set) {
            $ret = null;
        }
        return $ret;
    }

    /**
     * Return a collection of Services_Openstreetmap_Way objects that use the
     * node in question.
     *
     * @return Services_Openstreetmap_Ways
     */
    public function getWays()
    {
        $config = $this->getConfig();
        $id = $this->getId();
        $url = $config->getValue('server')
            . 'api/'
            . $config->getValue('api_version')
            . "/node/$id/ways";
        $response = $this->getTransport()->getResponse($url);
        $obj = new Services_Openstreetmap_Ways();
        $sxe = @simplexml_load_string($response->getBody());
        if ($sxe === false) {
            $obj->setVal(trim($response->getBody()));
        } else {
            $obj->setXml($sxe);
        }
        return $obj;
    }
}
// vim:set et ts=4 sw=4:
?>

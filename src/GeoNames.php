<?php

namespace Alibe\Geonames;

use Alibe\Geonames\Lib\Exec;

class GeoNames
{
    /** @var array Default request options */
    protected $conn;
    protected $defSet;

    /** @var class the execution class */
    protected $exe;

    /** @var string the geonames.org username */
    protected $clID;

    /**
     * Constructor to get the configuration skeletron
     * Example of call
     *     $geo = new Alibe\Geonames\geonames();
     *
    */
    public function __construct($clID)
    {
        $this->conn = include('Config/basic.php');
        $this->defSet = $this->conn['settings'];
        $this->clID = $clID;
        $this->set();
    }

    /**
     * Set the call parameters.
     * The call settings remain for the execution of the script and they are used to create complex query to
     * geonames.org api site.
     *
     *
     * @param array $arr The array with the parameters to set
     * Example of basic call
     *     $geo->set([
     *         'format' => 'object',
     *         'lang' => 'en'
     *     ]);
     * "format" is the format of the return for every call;
     *          it can be "object" (deafult) or "array";
     *          it is used for every call except for the rawCall, if that contain the parameter 'asIs' (see below)
     * "lang" is optional; if it is present it is used by geonames.org api to translate the name of the location
     * (where is possible)
     *
     * @return this object
    */
    public function set($arr = [])
    {
        if (!empty($arr)) {
            foreach ($arr as $k => $v) {
                if ($v === false || $v === null) {
                    $arr[$k] = $this->defSet[$k];
                }
            }
        }
        $this->conn['settings'] = array_replace_recursive($this->conn['settings'], $arr);
        $this->exe = new Exec($this->clID, $this->conn);
        return $this;
    }

    /**
     * Reset the call parameters.
     * It set all the parameters at the default state.
     *
     * Example of basic call
     *     $geo->reset();
     *
     * @return this object
    */
    public function reset()
    {
        $this->conn['settings'] = $this->defSet;
        $this->exe = new Exec($this->clID, $this->conn);
        return $this;
    }

  /***********************************/
 /* Geonames.org Original functions */
/***********************************/

      /************/
     /* RAW CALL */
    /************/
    /**
     * Raw call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/ws-overview.html
     * The call is prepared and done without any filters.
     * From the set configuration it take the parameters "clID","format" and "lang" (if present).
     * If the parameter "lang" is present in the call, ot use the call parameter instead of the configuration parameter.
     *
     * @param string $command, the main command for the api
     * @param array $params, the array with the parameters to use for the call
     * @param string $format, (optional, default as false) if it is set as false, the call ignore the format parameter
     * and it return the raw response form the api call; else if it is set as string, it has to be 'object' or 'array'.
     * Example of call
     *     $geo->rawCall(
     *         'getJSON',
     *         [
     *            'geonameId' => 2643743,
     *         ],
     *         true
     *     );
     *
     * @return object|array|response of the call without filters.
    */
    public function rawCall($command, $params = [], $format = false)
    {
        $fCall = 'JSON';
        $asIs = true;
        $preset = $this->conn['settings']['format'];
        if ($format) {
            $asIs = false;
            if ($format === true) {
                $format = $preset;
            }
            $this->set([
                'format' => $format
            ]);
            unset($params['type']);
            $command = preg_replace('/JSON$/', '', $command);
            $command = preg_replace('/XML$/', '', $command);
            $command = preg_replace('/RDF$/', '', $command);
            $command = preg_replace('/CSV$/', '', $command);
            $command = preg_replace('/RSS$/', '', $command);
            if (preg_match('/^rssToGeo/', $command)) {
                $fCall = 'RSS';
            }
        } else {
            $fCall = '';
        }
        $call = $this->exe->get([
            'cmd' => $command,
            'query' => $params,
            'asIs' => $asIs
        ], $fCall);
        $this->set([
            'format' => $preset
        ]);
        return $call;
    }

      /******************/
     /* Get Webservice */
    /******************/
    /**
     * Call to get the geonameId properties form geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#get
     *
     * @param integer $id, the geonameId in the database of geonames.org.
     * Example of call (it assumes the main set is already done).
     *     //Set the parameters (optional)
     *     $geo->set([
     *        'lang'=>'en',
     *        'style'=>'full',
     *     ]);
     *     // Call it
     *     $geo->get(3175395); // Example for Italy
     *
     * @return object|array of the call.
    */
    public function get($id)
    {
        return $this->exe->get([
            'cmd' => 'get',
            'query' => [
                'geonameId' => $id
            ]
        ]);
    }

      /*********************/
     /* Search Webservice */
    /*********************/
    /**
     * Search call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/geonames-search.html
     *
     * The search parameters has to be set previusly using the "set" method inside the section array 'search';
     * (See the geonames documentation)
     *     //Set the search parameters
     *     $geo->set([
     *        'search'=>[
     *            'q'=>'london',
     *        ]
     *     ]);
     *     // Call it
     *     $geo->search();
     *
     * @return object|array of the call.
    */
    public function search($arr)
    {
        $query = $this->conn['settings']['search'];
        $query['maxRows'] = $this->conn['settings']['maxRows'];
        $query['startRow'] = $this->conn['settings']['startRow'];
        $query['style'] = $this->conn['settings']['style'];
        $query['charset'] = $this->conn['settings']['charset'];
        $query['cities'] = $this->conn['settings']['cities'];
        $query['featureClass'] = $this->conn['settings']['featureClass'];
        $query['featureCode'] = $this->conn['settings']['featureCode'];

        $base = [
            'q' => false,
            'name' => false,
            'cc' => false,
            'operator' => false,
            'countryBias' => false,
            'continentCode' => false,
            'adminCode' => false
        ];

        $arr = array_merge($base, array_intersect_key($arr, $base));

        if (is_array($arr["cc"])) {
            $arr["cc"] = array_filter($arr["cc"]);
            if (count($arr["cc"]) == 0) {
                unset($arr["cc"]);
            }
        }
        if ($arr["adminCode"] && is_array($arr["adminCode"])) {
            $arr = array_merge($arr, $this->adminCodeBuild($arr["adminCode"], 5));
            unset($arr["adminCode"]);
        }
        $arr = array_filter($arr);
        dd($this->execByGeoBox('search', $query, '', 'xmlConvert'));
        return $this->execByGeoBox('search', $query);
    }

      /***********************/
     /* rssToGeo Webservice */
    /***********************/
    /**
     * rssToGeo search call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/rss-to-georss-converter.html
     *
     * @param string $url, the url of the rss feed.
     * The rssToGeo parameters has to be set previusly using the "set" method inside the section array 'rssToGeo';
     *
     * Example of call (it assumes the main set is already done).
     *     //Set the rssToGeo parameters
     *     $geo->set([
     *        'rssToGeo'=>[
     *          'feedLanguage' => false,
     *          'type' => false,
     *          'geoRSS' => false,
     *          'addUngeocodedItems' => false,
     *          'country' => false,
     *        ]
     *     ]);
     *     // Call it
     *     $geo->rssToGeo('https://rss.nytimes.com/services/xml/rss/nyt/World.xml');
     *
     * @return object|array of the call.
    */
    public function rssToGeo($url, $cc = false)
    {
        $query = $this->conn['settings']['rssToGeo'];
        $query['feedUrl'] = $url;
        $query['country'] = $cc;
      // unset($query['type']);
        return $this->exe->get([
          'cmd' => 'rssToGeo',
          'query' => $query,
          'preOutput' => 'xmlConvert'
        ], 'RSS');
    }

      /*******************************/
     /* Place Hierarchy Webservices */
    /*******************************/
    /**
     * Children call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/place-hierarchy.html#children
     *
     * @param integer $id, the geonameId in the database of geonames.org.
     * @param string $hrk, the kind of hierarchy in the database of geonames.org.
     *
     * Example of call (it assumes the main set is already done).
     *     //Set the search parameters
     *     $geo->set([
     *        'maxRows'=>30
     *     ]);
     *     // Call it
     *     $geo->children(3175395);
     *
     * @return object|array of the call.
    */
    public function children($id, $hierarchy = false)
    {
        return $this->exe->get([
            'cmd' => 'children',
            'query' => [
                'geonameId' => $id,
                'hierarchy' => $hierarchy,
                'maxRows' => $this->conn['settings']['maxRows']
            ]
        ]);
    }

    /**
     * Hierarchy call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/place-hierarchy.html#hierarchy
     *
     * @param integer $id, the geonameId in the database of geonames.org.
     *
     * Example of call (it assumes the main set is already done).
     *     // Call it
     *     $geo->hierarchy(3175395);
     *
     * @return object|array of the call.
    */
    public function hierarchy($id)
    {
        return $this->exe->get([
            'cmd' => 'hierarchy',
            'query' => [
                'geonameId' => $id
            ]
        ]);
    }

    /**
     * Siblings call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/place-hierarchy.html#siblings
     *
     * @param integer $id, the geonameId in the database of geonames.org.
     *
     * Example of call (it assumes the main set is already done).
     *     // Call it
     *     $geo->siblings(3175395);
     *
     * @return object|array of the call.
    */
    public function siblings($id)
    {
        return $this->exe->get([
            'cmd' => 'siblings',
            'query' => [
                'geonameId' => $id
            ]
        ]);
    }

    /**
     * Neighbours call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/place-hierarchy.html#neighbours
     *
     * @param integer|string $id, the geonameId or the country code in the database of geonames.org.
     *
     * Example of call (it assumes the main set is already done).
     *     // Call it
     *     $geo->neighbours(3175395);
     *
     * @return object|array of the call.
    */
    public function neighbours($id)
    {
        $query = [
            'geonameId' => $id
        ];
        if (intval($id) == 0) {
            $query = [
                'country' => $id
            ];
        }
        return $this->exe->get([
            'cmd' => 'neighbours',
            'query' => $query
        ]);
    }

    /**
     * Contains call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/place-hierarchy.html#contains
     *
     * @param integer|string $id, the geonameId or the country code in the database of geonames.org.
     *
     * Example of call (it assumes the main set is already done).
     *     //Set the filter parameters (optional)
     *     $geo->set([
     *        'featureClass'=>'P',
     *        'featureCode'=>'PPLL',
     *     ]);
     *     // Call it
     *     $geo->contains(6539972);
     *
     * @return object|array of the call.
    */
    public function contains($id)
    {
        return $this->exe->get([
            'cmd' => 'contains',
            'query' => [
                'geonameId' => $id,
                'featureClass' => $this->conn['settings']['featureClass'],
                'featureCode' => $this->conn['settings']['featureCode'],
                'maxRows' => $this->conn['settings']['maxRows'],
                'EXCLUDEfeatureCode' => $this->conn['settings']['EXCLUDEfeatureCode'],
            ]
        ]);
    }



      /**********************/
     /* GeoBox Webservices */
    /**********************/
    /**
     * The geobox is an area where to search the data.
     * The geobox has to be set before to call the methods that use it.
     *     //Set the geobox
     *     $geo->set([
     *          'geoBox'=>[
     *               'north'=>44.1,
     *               'south'=>-9.9,
     *               'east'=>22.4,
     *               'west'=>55.2,
     *          ]
     *     ]);
    */

    /**
     * Cities inside Geobox call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/JSON-webservices.html
     *
     *
     * Example of call (it assumes the main set is already done).
     *     GEOBOX parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'lang'=>'en',     // (optional)
     *        'maxRows'=>200,   // (optional)
     *     ]);
     *     // Call it
     *     $geo->cities();
     *
     * @return object|array of the call.
    */
    public function cities()
    {
        return $this->execByGeoBox('cities', [
            'maxRows' => $this->conn['settings']['maxRows']
        ]);
    }

    /**
     * Earthquakes inside Geobox call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/JSON-webservices.html#earthquakesJSON
     *
     *
     * Example of call (it assumes the main set is already done).
     *     GEOBOX parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'maxRows'=>200,   // (optional)
     *        'date'=>'today',  // (optional, filter the event before the date ,'Y-m-d' format, default 'today')
     *        'minMagnitude'=>'2.4',  // (optional, filter the event with magnitude greather than)
     *     ]);
     *     // Call it
     *     $geo->earthquakes();
     *
     * @return object|array of the call.
    */
    public function earthquakes()
    {
        return $this->execByGeoBox('earthquakes', [
            'maxRows' => $this->conn['settings']['maxRows'],
            'date' => ($this->conn['settings']['date']) ?
                date('Y-m-d', strtotime($this->conn['settings']['date'])) : false,
            'minMagnitude' => $this->conn['settings']['minMagnitude'],
        ]);
    }


      /*************************/
     /* Position Webservices  */
    /*************************/
    /**
     * The position settings is contains the coordinates for the position and the radius (in Km) where to search
     * the data.
     * The position has to be set before to call the methods that use it.
     *     //Set the position
     *     $geo->set([
     *          'position'=>[
     *               'lat'=>40.78343,
     *               'lng'=>-73.96625,
     *               'radius'=>1
     *          ]
     *     ]);
    */

    /**
     * CountryCode from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#countrycode
     *
     *
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'lang'=>'en',   // (optional)
     *     ]);
     *     // Call it
     *     $geo->countryCode();
     *
     * @return object|array of the call.
    */
    public function countryCode()
    {
        return $this->execByPosition('countryCode');
    }

    /**
     * ocean from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#ocean
     *
     *
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     // Call it
     *     $geo->ocean();
     *
     * @return object|array of the call.
    */
    public function ocean()
    {
        return $this->execByPosition('ocean');
    }


    /**
     * Timezone from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#timezone
     *
     *
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'lang'=>'en',   // (optional)
     *        'date'=>'today',  // (optional, filter the event before the date ,'Y-m-d' format, default 'today')
     *     ]);
     *     // Call it
     *     $geo->timezone();
     *
     * @return object|array of the call.
    */
    public function timezone()
    {
        $date = ($this->conn['settings']['date']) ? $this->conn['settings']['date'] : 'today';
        return $this->execByPosition('timezone', [
            'date' => date('Y-m-d', strtotime($date)),
        ]);
    }

    /**
     * Neighbourhood from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#neighbourhood
     *
     * RESTRICTION: US LOCATION ONLY
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     // Call it
     *     $geo->neighbourhood();
     *
     * @return object|array of the call.
    */
    public function neighbourhood()
    {
        return $this->execByPosition('neighbourhood');
    }


    /**
     * CountrySubdivision from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#countrysubdiv
     *
     *
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'lang'=>'en',   // (optional)
     *     ]);
     *     // Call it
     *     $geo->countrySubdivision();
     *
     * @return object|array of the call.
    */
    public function countrySubdivision()
    {
        return $this->execByPosition('countrySubdivision', [
            'maxRows' => $this->conn['settings']['maxRows'],
            'level' => $this->conn['settings']['level']
        ], '', 'xmlConvert');
    }

    /**
     * FindNearby from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#findNearby
     *
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'featureClass'=>'T',
     *        'featureCode'=>'PASS',
     *     ]);
     *     // Call it
     *     $geo->findNearby();
     *
     * @return object|array of the call.
    */
    public function findNearby()
    {
        return $this->execByPosition('findNearby', [
            'maxRows' => $this->conn['settings']['maxRows'],

            'style' => $this->conn['settings']['style'],

            'localCountry' => $this->conn['settings']['localCountry'],

            'featureClass' => $this->conn['settings']['featureClass'],

            'featureCode' => $this->conn['settings']['featureCode'],
            'EXCLUDEfeatureCode' => $this->conn['settings']['EXCLUDEfeatureCode'],

        ]);
    }

    /**
     * ExtendedFindNearby from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#extendedFindNearby
     *
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     // Call it
     *     $geo->extendedFindNearby();
     *
     * @return object|array of the call.
    */
    public function extendedFindNearby()
    {
        return $this->execByPosition('extendedFindNearby');
    }

    /**
     * FindNearbyPlaceName from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#findNearbyPlaceName
     *
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'lang'=>'en',   // (optional)
     *        'localCountry'=>true,
     *        'cities'=>'cities5000',
     *        'style'=>'FULL',
     *     ]);
     *     // Call it
     *     $geo->findNearbyPlaceName();
     *
     * @return object|array of the call.
    */
    public function findNearbyPlaceName()
    {
        return $this->execByPosition('findNearbyPlaceName', [
            'maxRows' => $this->conn['settings']['maxRows'],
            'localCountry' => $this->conn['settings']['localCountry'],
            'cities' => $this->conn['settings']['cities'],
            'style' => $this->conn['settings']['style'],
        ]);
    }


    /**
     * findNearbyPostalCodes from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#findNearbyPostalCodes
     *
     * @param string $cc, the country code.
     * @param string $zip, the postal code.
     *
     * If Country code and postal code are set,
     * then use them.
     * else use the position sets and country code if it is set.
     *
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set (if needed)
     *
     *     //PRESET IN CASE OF POSTAL CODE
     *     $geo->set([
     *        'lang'=>'en',   // (optional)
     *        'maxRows'=>10,  // (optional)
     *        'position'=>[
     *              'radius'=>1 // (optional)
     *         ]
     *     ]);
     *
     *     //PRESET IN CASE OF POSITION
     *     $geo->set([
     *        'lang'=>'en',   // (optional)
     *        'maxRows'=>10, // (optional)
     *        'style'=>'FULL', // (optional)
     *        'localCountry'=>true, // (optional)
     *        'isReduced'=>true, // (optional)
     *     ]);
     *
     *     // Call it
     *     $geo->findNearbyPostalCodes();
     *
     * @return object|array of the call.
    */
    public function findNearbyPostalCodes($cc = false, $zip = false)
    {
        if ($cc && $zip) {
            $query = [
                'country' => $cc,
                'postalcode' => $zip,
                'maxRows' => $this->conn['settings']['maxRows'],
                'radius' => $this->conn['settings']['position']['radius'],
            ];
        } else {
            $query = $this->conn['settings']['position'];
            $query['maxRows'] = $this->conn['settings']['maxRows'];
            if ($cc) {
                $query['country'] = $cc;
            }
            $query['style'] = $this->conn['settings']['style'];
            $query['localCountry'] = $this->conn['settings']['localCountry'];
            $query['isReduced'] = $this->conn['settings']['isReduced'];
        }
        return $this->exe->get([
            'cmd' => 'findNearbyPostalCodes',
            'query' => $query
        ]);
    }


    /**
     * FindNearbyStreets from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/maps/us-reverse-geocoder.html#findNearbyStreets
     *
     * RESTRICTION: US LOCATION ONLY
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'maxRows'=>'en',   // (optional)
     *     ]);
     *     // Call it
     *     $geo->findNearbyStreets();
     *
     * @return object|array of the call.
    */
    public function findNearbyStreets()
    {
        return $this->execByPosition('findNearbyStreets', [
            'maxRows' => $this->conn['settings']['maxRows'],
        ]);
    }

    /**
     * FindNearestIntersection from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/maps/us-reverse-geocoder.html#findNearestIntersection
     *
     * RESTRICTION: US LOCATION ONLY
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'maxRows'=>10,   // (optional)
     *     ]);
     *     // Call it
     *     $geo->findNearestIntersection();
     *
     * @return object|array of the call.
    */
    public function findNearestIntersection()
    {
        return $this->execByPosition('findNearestIntersection', [
            'maxRows' => $this->conn['settings']['maxRows'],
            'filter' => $this->conn['settings']['filter'],
        ]);
    }

    /**
     * FindNearestAddress from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/maps/us-reverse-geocoder.html#findNearestAddress
     *
     * RESTRICTION: US LOCATION ONLY
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'maxRows'=>10,   // (optional)
     *     ]);
     *     // Call it
     *     $geo->findNearestAddress();
     *
     *     // USE THE INLINE PARAMETER FOR MULTIPLE
     *        COORDINATES
     *     $geo->findNearestAddress([
     *          [
     *              'lat'=>38.569594,
     *              'lng'=>-121.483778,
     *          ],
     *          [
     *              'lat'=>37.451,
     *              'lng'=>-122.18,
     *          ],
     *     ]);
     *
     * @return object|array of the call.
    */
    public function findNearestAddress($coords = [])
    {
        return $this->execByPosition('findNearestAddress', [
            'maxRows' => $this->conn['settings']['maxRows'],
            'coords' => $coords
        ]);
    }

    /**
     * FindNearestIntersectionOSM from Position call to geonames.org using Open Street Map.
     * Geonames.org documentation: https://www.geonames.org/maps/osm-reverse-geocoder.html#findNearestIntersectionOSM
     *
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'maxRows'=>10,   // (optional)
     *        'includeGeoName'=> true // (optional)
     *     ]);
     *     // Call it
     *     $geo->findNearestIntersectionOSM();
     *
     * @return object|array of the call.
    */
    public function findNearestIntersectionOSM()
    {
        return $this->execByPosition('findNearestIntersectionOSM', [
            'maxRows' => $this->conn['settings']['maxRows'],
            'includeGeoName' => $this->conn['settings']['includeGeoName'],
        ]);
    }

    /**
     * FindNearbyStreetsOSM from Position call to geonames.org using Open Street Map.
     * Geonames.org documentation: https://www.geonames.org/maps/osm-reverse-geocoder.html#findNearbyStreetsOSM
     *
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'maxRows'=>10,   // (optional)
     *     ]);
     *     // Call it
     *     $geo->findNearbyStreetsOSM();
     *
     * @return object|array of the call.
    */
    public function findNearbyStreetsOSM()
    {
        return $this->execByPosition('findNearbyStreetsOSM', [
            'maxRows' => $this->conn['settings']['maxRows'],
        ]);
    }

    /**
     * FindNearbyPOIsOSM from Position call to geonames.org using Open Street Map.
     * The point of interest.
     * Geonames.org documentation: https://www.geonames.org/maps/osm-reverse-geocoder.html#findNearbyPOIsOSM
     *
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'maxRows'=>10,   // (optional)
     *     ]);
     *     // Call it
     *     $geo->findNearbyPOIsOSM();
     *
     * @return object|array of the call.
    */
    public function findNearbyPOIsOSM()
    {
        return $this->execByPosition('findNearbyPOIsOSM', [
            'maxRows' => $this->conn['settings']['maxRows'],
        ]);
    }


      /***********************/
     /* Weather Webservices */
    /***********************/
    /**
     * Weather station inside Geobox call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/JSON-webservices.html#weatherJSON
     *
     *
     * Example of call (it assumes the main set is already done).
     *     GEOBOX parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'maxRows'=>200,   // (optional)
     *     ]);
     *     // Call it
     *     $geo->weather();
     *
     * @return object|array of the call.
    */
    public function weather()
    {
        return $this->execByGeoBox('weather', [
            'maxRows' => $this->conn['settings']['maxRows']
        ]);
    }

    /**
     * weatherIcao. Call to get the weather station with ICAO code.
     * Geonames.org documentation: https://www.geonames.org/export/JSON-webservices.html#weatherIcaoJSON
     *
     * @param string $icaoCode, the ICAO (International Civil Aviation Organization) code.
     * Example of call (it assumes the main set is already done).
     *     //Set the filter parameters
     *     $geo->set([
     *        'lang'=>'en',   // (optional)
     *     ]);
     *     // Call it
     *     $geo->weatherIcao('EICK'); // Example for Cork
     *
     * @return object|array of the call.
    */
    public function weatherIcao($icaoCode)
    {
        return $this->exe->get([
            'cmd' => 'weatherIcao',
            'query' => [
                'ICAO' => $icaoCode
            ]
        ]);
    }

    /**
     * findNearByWeather from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/JSON-webservices.html#findNearByWeatherJSON
     *
     *
     * Example of call (it assumes the main set is already done).
     *     //Set the filter parameters
     *     POSITION parameters already set
     *     $geo->set([
     *        'lang'=>'en',   // (optional)
     *     ]);
     *     // Call it
     *     $geo->findNearByWeather();
     *
     * @return object|array of the call.
    */
    public function findNearByWeather()
    {
        return $this->execByPosition('findNearByWeather');
    }


      /*************************/
     /* Altitude Webservices  */
    /*************************/
    /**
     * Altitude from Position call to geonames.org using different methods.
     *
     * The available methods are:
     * - srtm1 (https://www.geonames.org/export/web-services.html#srtm1)
     * - srtm3 (https://www.geonames.org/export/web-services.html#srtm3)
     * - astergdem (https://www.geonames.org/export/web-services.html#astergdem)
     * - gtopo30 (https://www.geonames.org/export/web-services.html#gtopo30)
     *
     * @param string $method, the method to use.
     * The position variables are mandatory and they are used to locate the point where to calculate the altitude
     * Example of call
     *     // Preset
     *     $this->geo->set([
     *         'position'=>[
     *             'lat'=>51.8985,
     *             'lng'=>-8.4756,
     *             'radius'=>200,
     *         ],
     *     ]);
     *     // Call it
     *     $geo->getAltitude('astergdem');
     *
     * @return object|array of the call.
    */
    public function getAltitude(string $method)
    {
        $m = ['srtm1','srtm3','astergdem','gtopo30'];
        if (!in_array($method, $m)) {
            return [];
        }
        return $this->execByPosition($method);
    }


      /*************************/
     /* Wikipedia Webservices */
    /*************************/
    /**
     * Wikipedia itmes inside Geobox call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/wikipedia-webservice.html#wikipediaBoundingBox
     *
     *
     * Example of call (it assumes the main set is already done).
     *     GEOBOX parameters already set
     *     //Set the filter parameters
     *     $geo->set([
     *        'maxRows'=>10,   // (optional)
     *        'lang'=>'en',   // (optional)
     *     ]);
     *     // Call it
     *     $geo->wikipediaBoundingBox();
     *
     * @return object|array of the call.
    */
    public function wikipediaBoundingBox()
    {
        return $this->execByGeoBox('wikipediaBoundingBox', [
            'maxRows' => $this->conn['settings']['maxRows']
        ]);
    }

    /**
     * Wikipedia items from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/wikipedia-webservice.html#findNearbyWikipedia
     *
     * @param string $cc, the country code.
     * @param string $zip, the postal code.
     *
     * If Country code and postal code are set,
     * then use them.
     * else use the position sets and country code if it is set.
     *
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set (if needed)
     *
     *     //PRESET IN CASE OF POSTAL CODE
     *     $geo->set([
     *        'lang'=>'en',   // (optional)
     *        'maxRows'=>10,  // (optional)
     *        'position'=>[
     *              'radius'=>1 // (optional)
     *         ]
     *     ]);
     *
     *     //PRESET IN CASE OF POSITION
     *     $geo->set([
     *        'lang'=>'en',   // (optional)
     *        'maxRows'=>10, // (optional)
     *     ]);
     *
     *     // Call it
     *     $geo->findNearbyWikipedia();
     *
     * @return object|array of the call.
    */
    public function findNearbyWikipedia($cc = false, $zip = false)
    {
        if ($cc && $zip) {
            $query = [
                'country' => mb_strtoupper($cc),
                'postalcode' => $zip,
                'maxRows' => $this->conn['settings']['maxRows'],
                'radius' => $this->conn['settings']['position']['radius'],
            ];
        } else {
            $query = $this->conn['settings']['position'];
            $query['maxRows'] = $this->conn['settings']['maxRows'];
            if ($cc) {
                $query['country'] = mb_strtoupper($cc);
            }
        }
        return $this->exe->get([
            'cmd' => 'findNearbyWikipedia',
            'query' => $query
        ]);
    }

    /**
     * Search call to geonames.org for Wilipedia items.
     * Geonames.org documentation: https://www.geonames.org/export/wikipedia-webservice.html#wikipediaSearch
     *
     * @param array $search, The search query.
     *
     * The search parameters has to be set in the inline array;
     * In the search there are two properties that are each other alternative
     * -"title" search inside the title (preeminent)
     * -"place" search inside the body
     *
     *     //Set the parameters
     *     $geo->set([
     *        'lang'=>'en' (optional)
     *        'maxRows'=>20 (optional)
     *     ]);
     *     // Call it
     *     $geo->search([
     *       'title'=>'Cork',
     *       'place'=>'Saints Peter and Paul'
     *     ]);
     *
     * @return object|array of the call.
    */
    public function wikipediaSearch($search)
    {
        $arr = array(
            'title' => false,
            'place' => false
        );
        $arr = array_replace($arr, $search);
        $query = [
            'maxRows' => $this->conn['settings']['maxRows']
        ];
        if ($arr['title']) {
            $query['title'] = $arr['title'];
        }
        if ($arr['place']) {
            $query['q'] = rawurlencode(utf8_encode($arr['place']));
        }
        return $this->exe->get([
            'cmd' => 'wikipediaSearch',
            'query' => $query
        ]);
    }


      /*****************************************/
     /* Postal code and countries Webservices */
    /*****************************************/
    /**
     * Country params or country list call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#countryInfo
     *
     * @param string|array $id, the iso  ISO-3166 country code (2 letter) (optional). By default it return the list of
     * the countries.
     * If is present it can be a string (for a single country) or an array (for multiple countries).
     * Example of call
     *     //Set the optional parameters
     *     $geo->set([
     *        'lang'=> 'en' (optional)
     *     ]);
     *     // Call it
     *     $geo->countryInfo('ie');
     *     // Or
     *     $geo->countryInfo(['ie','it']);
     *
     * @return object|array of the call.
    */
    public function countryInfo($cc = false)
    {
        return $this->exe->get([
            'cmd' => 'countryInfo',
            'query' => [
                'country' => $cc
            ]
        ]);
    }

    /**
     * Get the list of the country where the postal code geocoding is available.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#postalCodeCountryInfo
     *
     * Example of call
     *     // Call it
     *     $geo->postalCodeCountryInfo();
     *
     * @return object|array of the call.
    */
    public function postalCodeCountryInfo()
    {
        return $this->exe->get([
            'cmd' => 'postalCodeCountryInfo'
        ]);
    }

    /**
     * Postal Code lookup call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#postalCodeLookupJSON
     *
     * @param string $zip, The postal code
     * @param string $cc, The country code filter (optional)
     * Example of call (it assumes the main set is already done).
     *     //Set the optional parameters
     *     $geo->set([
     *        'maxRows'=> 20 (optional)
     *        'charset'=> 'UTF-8' (optional default 'UTF-8')
     *     ]);
     *     // Call it
     *     $geo->postalCodeLookup('T12');
     *
     * @return object|array of the call.
    */
    public function postalCodeLookup($zip, $cc = false)
    {
        return $this->exe->get([
            'cmd' => 'postalCodeLookup',
            'query' => [
                'country' => $cc,
                'postalcode' => $zip,
                'maxRows' => $this->conn['settings']['maxRows'],
                'charset' => $this->conn['settings']['charset'],
            ]
        ]);
    }


    /**
     * Postal Code or Place search call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/export/web-services.html#postalCodeSearch
     *
     * @param array $req, has the following keys used for the search:
     *  - postalcode @param string (it's possible to use the character "^" at the beginning to make the regular
     *    expession "it begin with")
     *  - placename @param string (it's possible to use the character "^" at the beginning to make the regular
     *    expession "it begin with")
     *  - cc  @param string|array (it can be a filter, if it's an array more countries can be specified) ISO-3166
     *    country code (2 letter).
     *  - operator  @param string (it can be "AND" or "OR")
     *  - countryBias  @param string (if present it give a priority in the list for the defined country) ISO-3166
     *    country code (2 letter).
     *
     * The other search parameters has to be set previusly using the "set" method;
     *
     * Definitions of specific options. Optional.
     *     //Set the option search parameters
     *     $geo->set([
     *        'postalplace' => [
     *            'style' => 'FULL',
     *            'charset' => 'UTF-8',
     *            'isReduced' => false,
     *        ]
     *     ]);
     * Definitions of the maxRows.  Optional.
     *     //Set the option search parameters
     *     $geo->set([
     *        'maxRows' => 10
     *     ]);
     * Definitions of the geobox.  Optional.
     *     //Set the option search parameters
     *     $geo->set([
     *          'geoBox'=>[
     *               'north'=>44.1,
     *               'south'=>-9.9,
     *               'east'=>22.4,
     *               'west'=>55.2,
     *          ]
     *     ]);
     *
     *     // Call it
     *     $geo->postalCodeSearch([
     *          'postalcode'=>'091',
     *          'placename'=>'cork',
     *          'cc'=>[
     *               'ie',
     *               'us'
     *           ],
     *          'operator'=>'or',
     *          'countryBias'=>'ie'
     *     ]);
     *
     * @return object|array of the call.
    */
    public function postalCodeSearch($req)
    {
        // Preset
        $query = $this->conn['settings']['geoBox'];
        $query['maxRows'] = $this->conn['settings']['maxRows'];
        $query['isReduced'] = $this->conn['settings']['isReduced'];
        $query['charset'] = $this->conn['settings']['charset'];
        $query['style'] = $this->conn['settings']['style'];

        // Direct vars
        if (isset($req['postalcode']) && $req['postalcode']) {
            $t = rawurlencode(preg_replace('/^\^/', '', $req['postalcode'], 1, $c));
            if ($c) {
                $query['postalcode_startsWith'] = $t;
            } else {
                $query['postalcode'] = $t;
            }
        }
        if (isset($req['placename']) && $req['placename']) {
            $t = rawurlencode(preg_replace('/^\^/', '', $req['placename'], 1, $c));
            if ($c) {
                $query['placename_startsWith'] = $t;
            } else {
                $query['placename'] = $t;
            }

            if (isset($req['operator']) && $req['operator']) {
                $query['operator'] = $req['operator'];
            }
        }
        if (isset($req['cc']) && $req['cc']) {
            $query['country'] = $req['cc'];
        }
        if (isset($req['countryBias']) && $req['countryBias']) {
            $query['countryBias'] = $req['countryBias'];
        }
        // dd($query);
        return $this->exe->get([
            'cmd' => 'postalCodeSearch',
            'query' => $query
        ]);
    }

      /***********************/
     /* Address Webservices */
    /***********************/
    /**
     * Address from Position call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/maps/addresses.html#address
     *
     * RESTRICTION: service available only for some countries
     * (see the geonames documentation)
     *
     * Example of call (it assumes the main set is already done).
     *     POSITION parameters already set
     *     //Set the optional parameters
     *     $geo->set([
     *        'maxRows'=>20,   // (optional)
     *     ]);
     *     // Call it
     *     $geo->address();
     *
     * @return object|array of the call.
    */
    public function address()
    {
        return $this->execByPosition('address', [
            'maxRows' => $this->conn['settings']['maxRows'],
        ]);
    }

    /**
     * Search address call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/maps/addresses.html#geoCodeAddress
     *
     * RESTRICTION: service available only for some countries
     * (see the geonames documentation)
     *
     * @param string $address, The address to search.
     * @param string $cc, The country code.
     * @param string $zip, The postal code.
     *
     * Example of call (it assumes the main set is already done).
     *     // Call it
     *     $geo->geoCodeAddress(
     *        'main',
     *        'us',
     *        '4212'
     *     );
     *
     * @return object|array of the call.
    */
    public function geoCodeAddress($address, $cc = false, $zip = false)
    {
        if ($cc == '') {
            $cc = false;
        }
        if ($zip == '') {
            $zip = false;
        }
        return $this->exe->get([
            'cmd' => 'geoCodeAddress',
            'query' => [
                'q' => rawurlencode($address),
                'country' => mb_strtoupper($cc),
                'postalcode' => mb_strtoupper($zip)
            ]
        ]);
    }

    /**
     * Search streetName call to geonames.org.
     * Geonames.org documentation: https://www.geonames.org/maps/addresses.html#streetNameLookup
     *
     * RESTRICTION: service available only for some countries
     * (see the geonames documentation)
     *
     * @param array $search, The search query.
     *
     * Example of call (it assumes the main set is already done).
     *     // Call it
     *     $geo->streetNameLookup([
     *       'country'=>'AU', (optional)
     *       'postalCode'=>''6530', (optional)
     *       'adminCode1'=>'false, (optional)
     *       'adminCode2'=>'false, (optional)
     *       'adminCode3'=>'false, (optional)
     *       'isUniqueStreetName'=>'false, (opt)
     *     ]);
     *
     * @return object|array of the call.
    */
    public function streetNameLookup($search)
    {
        $arr = array(
            'address' => false,
            'cc' => false,
            'zip' => false,
            'adminCode1' => false,
            'adminCode2' => false,
            'adminCode3' => false,
            'unique' => false
        );
        $arr = array_replace($arr, $search);
        return $this->exe->get([
            'cmd' => 'streetNameLookup',
            'query' => [
                'q' => rawurlencode($arr['address']),
                'country' => mb_strtoupper($arr['cc']),
                'postalcode' => mb_strtoupper($arr['zip']),
                'adminCode1' => mb_strtoupper($arr['adminCode1']),
                'adminCode2' => mb_strtoupper($arr['adminCode2']),
                'adminCode3' => mb_strtoupper($arr['adminCode3']),
                'isUniqueStreetName' => $arr['unique'],
                'maxRows' => $this->conn['settings']['maxRows']
            ]
        ]);
    }

      /*************************/
     /* Build the adminCode   */
    /*************************/
    private function adminCodeBuild($arr, $x)
    {
        $rit = [];
        for ($i = 1; $i <= $x; $i++) {
            if ($arr[$i]) {
                $rit["adminCode" . $i] = mb_strtoupper($arr[$i]);
            }
        }
        return $rit;
    }

      /*************************/
     /* Execute by position   */
    /*************************/
    public function execByPosition(
        $cmd,
        $ar = [],
        $fCall = false,
        $preOutput = false
    ) {
        $query = array_merge($this->conn['settings']['position'], $ar);

        if (!empty($ar['coords'])) {
            $lats = '';
            $lngs = '';
            foreach ($ar['coords'] as $c) {
                $lats .= $c['lat'] . ',';
                $lngs .= $c['lng'] . ',';
            }
            $query['lats'] = rtrim($lats, ',');
            $query['lngs'] = rtrim($lngs, ',');

            unset($query['lat']);
            unset($query['lng']);
            unset($query['coords']);
        }

        return $this->exe->get([
            'cmd' => $cmd,
            'query' => $query,
            'preOutput' => $preOutput
        ], $fCall);
    }

      /***********************/
     /* Execute by geoBox   */
    /***********************/
    public function execByGeoBox(
        $cmd,
        $ar = [],
        $fCall = false,
        $preOutput = false
    ) {
        $box = $this->conn['settings']['geoBox'];
        $query = array_merge($box, $ar);
        return $this->exe->get([
            'cmd' => $cmd,
            'query' => $query,
            'preOutput' => $preOutput
        ], $fCall);
    }





    /*Continents*/
    public function continensGetList()
    {
        return $this->children('6295630');
    }

    /*Countries*/
    public function countriesGetList()
    {
        return $this->countryInfo();
    }

    public function countryGet($cc)
    {
        return $this->countryInfo($cc);
    }
}

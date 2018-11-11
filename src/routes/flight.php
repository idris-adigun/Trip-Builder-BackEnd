<?php 

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


/**
 * This router return the details of the flight 
 */
$app->get('/itinerary/{departure_city_id}/{arrival_city_id}/{departureDate}', function (Request $req, Response $res, array $args){

    $departure_city_id = $args['departure_city_id'];
    $arrival_city_id = $args['arrival_city_id'];
    // $arrival_date = $args['arrival_date'];
    $departureDate = $args['departureDate'];


    //get airport in the cities base on the city id
    $arriveCityCode = getCode($arrival_city_id, 'arrival');
    $departCityCode = getCode($departure_city_id, 'departure');
    
    //get Flight based on departure and arrival city code
    $flight = getDirectFlight($departCityCode, $arriveCityCode, $departureDate);
    $airline =[];
    for($i = 0; $i < count($flight); $i++ )
    {
        $airline = array_merge($airline, getAirline($flight[$i]['airline']));
        $airline = array_unique($airline, SORT_REGULAR);
        // echo print_r($airline);
    }

    
    $departureAirport = getAirport($departCityCode);
    $arrivalAirport = getAirport($arriveCityCode);
    $airports = array_merge($departureAirport, $arrivalAirport);

    // echo $airline[0]["airline"];
    $res->getBody()->write( '
            {
                "Airline":'.json_encode($airline).',
                "Flight":'.json_encode($flight).',
                "Airport":'.json_encode($airports).'
                
            }
    ');

    /**
     * get type of flight
     * if it's one way trip user departureDate date only
     * else use the departure date to get the flight and add result using return date 
     * returnAirline
     * returnFlight
     * return Airport
     */

});

/**
 * This method get the details of the airport from the code of the city code
 */
function getAirport($code)
{
    $sql = "SELECT * FROM trip.airports as airport INNER JOIN trip.city as city ON airport.code = '$code' AND airport.city_id = city.id" ; 
    try{
        $db = new db();
        $db = $db->connect();
        $stmt = $db->query($sql);
        $airport = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;
        return $airport;
        
    }
    catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
}

/**
 * This method get the name and code of the airline from the name 
 */
function getAirline($name){
    $sql = "SELECT name, code FROM trip.airlines as airline WHERE `name` = '$name'"; 
    try{
        $db = new db();
        $db = $db->connect();
        $stmt = $db->query($sql);
        $airlines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;

        return $airlines;
        
    }
    catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
}


/**
 * This method get the details of the flight from a depature location to the arrival using city codes
 * and date of departure
 */
function getDirectFlight($departCityCode, $arriveCityCode, $departureDate){

    $sql = "SELECT * FROM trip.flights as flight WHERE `departure_airport` = '$departCityCode' AND `arrival_airport` = '$arriveCityCode' AND `departure_time` >= '$departureDate' AND `departure_time` < ('$departureDate' + INTERVAL 1 DAY) ORDER BY `price` ASC"; 
    try{
        $db = new db();
        $db = $db->connect();
        $stmt = $db->query($sql);
        $flight = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;

        return $flight;
        
    }
    catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
}


/**
 * get Airport code based on the city
 *  */
function getCode($id, $tripType){
    if($tripType == 'arrival' )
    {
        $sql = "SELECT code FROM trip.airports as airport INNER JOIN trip.flights as flight ON airport.code = flight.arrival_airport  WHERE `city_id` = $id"; 
        try{
            $db = new db();
            $db = $db->connect();
            $stmt = $db->query($sql);
            $Airport_code = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            // echo 'Arrival'.$Airport_code[0]["code"];
            return $Airport_code[0]["code"];
            
        }
        catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}';
        }
    }
    else if($tripType == 'departure' ) 
    {
        $sql = "SELECT code FROM trip.airports as airport INNER JOIN trip.flights as flight ON airport.code = flight.departure_airport  WHERE `city_id` = $id"; 
        try{
            $db = new db();
            $db = $db->connect();
            $stmt = $db->query($sql);
            $Airport_code = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            // echo 'Departure'.$Airport_code[0]["code"];
            return $Airport_code[0]["code"];
            
        }
        catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}';
        }

    }
    
    return 'Airport does not exist';

}

/**
 * This router return all the cities available in the database
 */
$app->get('/cities', function (Request $req, Response $res, array $args){

    $sql = "SELECT * FROM trip.City" ;
    try{
        $db = new db();
        $db = $db->connect();
        $stmt = $db->query($sql);
        $cities = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        $res->getBody()->write( '{
            "cities":'.json_encode($cities).'}
            ');
    }
    catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
    
});
?>
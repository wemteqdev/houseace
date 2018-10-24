<?php
// mount wordpress to use rest api call
require_once( dirname( __FILE__  ) . '/wp-load.php'  );

class domain_api
{
    protected $client_id = '';
    protected $client_secrets = '';
    protected $access_token = '';
    protected $token = null;

    function __construct($client_id, $client_secrets)
    {
        $this->client_id = $client_id;
        $this->client_secrets = $client_secrets;
    }

    function authorize()
    {
        $this->token = json_decode($this->getAuthToken($this->client_id, $this->client_secrets));
        $this->access_token = $this->token->access_token;
    }

    function getAccessToken()
    {
        return $this->access_token;
    }

    function getAuthToken($client_id, $client_secrets){
        $url = 'https://auth.domain.com.au/v1/connect/token';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $client_id . ':' . $client_secrets);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials&scope=api_listings_read%20api_agencies_read%20api_salesresults_read');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($httpcode>=200 && $httpcode<300) ? $data : false;
    }

    function getRequest($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json', 'Authorization: Bearer ' . $this->access_token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($data === false)
        {
            print(curl_error($ch));
        }

        return ($httpcode>=200 && $httpcode<300) ? $data : false;
    }
}


// prepare sales api client to pull sales results from domain.com.au
$sales_client_id = 'client_538dd6d34c31418795c03e850d58b81c';
$sales_client_secrets = 'secret_7ed35b18ad4e4b78a995a2e71b357ac9';

$sales_api = new domain_api($sales_client_id, $sales_client_secrets);
$sales_api->authorize();
sleep(2);

// get auctioned date
print("fetching sales results head \n");
$results_header_data = $sales_api->getRequest('https://api.domain.com.au/v1/salesResults/_head');
$results_header_json = json_decode($results_header_data);
print_r($results_header_json);
sleep(2);

// get sydney sales listings
print("fetching sydney sales listings \n");
$results_data = $sales_api->getRequest('https://api.domain.com.au/v1/salesResults/Sydney/listings');
$listings_json = json_decode($results_data);
sleep(2);
print("listings: ". count($listings_json) . "\n");

// prepare listing api client
$listings_client_id = 'client_f9a5c138ea674b1d93c0e97e03a67e82';
$listings_client_secrets = 'secret_7e2b4ce9a8608ad91394e9b89e32758f';

$listings_api = new domain_api($listings_client_id, $listings_client_secrets);
$listings_api->authorize();
sleep(2);


//$wp_headers = array (
//     'Authorization' => 'Basic ' . base64_encode( 'admin' . ':' . '1vdR 4lVM ccLX 00zn suaK kVNr'  ),
//);

$wp_headers = array (
     'Authorization' => 'Basic ' . base64_encode( 'admin' . ':' . '8RjY ic92 ueNL CxR4 oISm WmHJ'  ),
);
$inserted_count = 0;
foreach($listings_json as $listing)
{

    // check if listing already exists in houseace wordpress
    print("searching houseace listing with id:" . $listing->id  . " \n");
    $filters = array(
     'filter[meta_key]'=>'id',
     'filter[meta_value]'=> $listing->id
    );

    $filter_string = http_build_query($filters);

    //$url = rest_url( 'wp/v2/listings?' . $filter_string  );
    $url = "https://houseace.com.au/wp-json/wp/v2/listings?" . $filter_string;
    print($url . "\n");
    $response = wp_remote_get( $url, array (
     'headers' => $headers,
    ));

    $wp_listings = json_decode($response['body']);
    $wp_listing = null;

    print("found:" . count($wp_listings) . " \n");
    if(count($wp_listings) > 0)
    {
        $wp_listing = $wp_listings[0];

        if($wp_listing->acf->auctioned_date == $results_header_json->auctionedDate)
        {
            print("listing already exists in houseace, skipped! \n");
            continue;
        }
    }
    
    // get listing detail from domain.com.au
    $listing_data = $listings_api->getRequest('https://api.domain.com.au/v1/listings/' . $listing->id);
    $listing_json = json_decode($listing_data);
    print("\n");
    sleep(3);

    if(!isset($listing_json->id)){
        print("Listing not found in domain.com.au \n");
        continue;
    }

    // prepare listing post data to submit to houseace
    $post_data = [];
    $post_data['fields[auctioned_date]'] = $results_header_json->auctionedDate;
    $post_data['fields[property_types]'] = implode(',', $listing_json->propertyTypes);
    if($listing_json->features)
    {
        $post_data['fields[features]'] = implode(',', $listing_json->features);
    }
    $post_data['fields[status]'] = $listing_json->status;
    $post_data['fields[channel]'] = $listing_json->channel;
    $post_data['fields[headline]'] = $listing_json->headline;
    $post_data['fields[sale_mode]'] = $listing_json->saleMode;
    $post_data['fields[address_parts][state_abbreviation]'] = $listing_json->addressParts->stateAbbreviation;
    $post_data['fields[address_parts][street_number]'] = $listing_json->addressParts->street_number;
    $post_data['fields[address_parts][unit_number]'] = $listing_json->addressParts->unit_number;
    $post_data['fields[address_parts][street]'] = $listing_json->addressParts->street;
    $post_data['fields[address_parts][suburb]'] = $listing_json->addressParts->suburb;
    $post_data['fields[address_parts][postcode]'] = $listing_json->addressParts->postcode;
    $post_data['fields[address_parts][display_address]'] = $listing_json->addressParts->displayAddress;

    $post_data['fields[bathrooms]'] = $listing_json->bathrooms;
    $post_data['fields[bedrooms]'] = $listing_json->bedrooms;
    $post_data['fields[carspaces]'] = $listing_json->carspaces;
    $post_data['fields[date_updated]'] = $listing_json->dateUpdated;
    $post_data['fields[description]'] = $listing_json->description;
    $post_data['fields[geo_location][latitude]'] = $listing_json->geoLocation->latitude;
    $post_data['fields[geo_location][longitude]'] = $listing_json->geoLocation->longitude;
    $post_data['fields[id]'] = $listing_json->id;

    $images_count = 0;
    foreach($listing_json->media as $i => $image)
    {
        if($image->type=='photo')
        {
            $post_data['fields[media][image_'. $images_count .']'] = $image->url;
            $images_count++;
        }
    }

    $post_data['fields[display_price]'] = $listing_json->priceDetails->displayPrice;
    $post_data['fields[sold_details][sold_action]'] = $listing_json->saleDetails->soldDetails->soldAction;
    $post_data['fields[sold_details][sold_price]'] = $listing_json->saleDetails->soldDetails->soldPrice;
    $post_data['fields[sold_details][sold_date]'] = $listing_json->saleDetails->soldDetails->soldDate;
    $post_data['fields[seo_url]'] = $listing_json->seoUrl;
    $post_data['fields[virtual_tour_url]'] = $listing_json->virtualTourUrl;

    $post_data['title'] = $listing_json->addressParts->displayAddress;
    $post_data['status'] = 'publish';
    
    if($wp_listing!=null)
    {
        //$url = rest_url( 'wp/v2/listings/' . $wp_listing->id  );
        $url = "https://houseace.com.au/wp-json/wp/v2/listings/" . $wp_listing->id;
    }else{
        //$url = rest_url( 'wp/v2/listings' );
        $url = "https://houseace.com.au/wp-json/wp/v2/listings";
    }

    print('posting to houseace:' . $url . '\n');

    $response = wp_remote_post( $url, array (
     'method'  => 'POST',
     'headers' => $wp_headers,
     'body'    =>  $post_data
    ));

    print('next... \n');


    $inserted_count++;
    if($inserted_count == 10)
    {
        // break;
    }

}

print('completed:' . $inserted_count . '\n');


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

$houseace_base_url = "https://www.houseace.com.au";
$houseace_authorization_user = 'Developer';
$houseace_authorization_token = 'u17b I3Od Y7ZG d5Wq GRtT EUys';

// $houseace_base_url = "https://houseace.wemteq.com";
// $houseace_authorization_token = 'D6Fz 8VCt Q5Nv BMxe MVf0 nOX8';
// $houseace_authorization_user = 'admin';

// $houseace_base_url = "http://houseace.loc:8888";
// $houseace_authorization_token = 'q929 Cs8K l49A boxt rTue Mc9t';
// $houseace_authorization_user = 'admin';

$wp_headers = array (
    'Authorization' => 'Basic ' . base64_encode( $houseace_authorization_user . ':' . $houseace_authorization_token  ),
);

function get_houseace_listing_post($listing_id)
{
    global $houseace_base_url, $wp_headers;

    $filters = array(
     'filter[meta_key]'=>'id',
     'filter[meta_value]'=> $listing_id
    );

    $filter_string = http_build_query($filters);
    $url = $houseace_base_url .  '/wp-json/wp/v2/listing?' . $filter_string;

    print("searching houseace listing with id:" . $listing_id  . " \n");

    $response = wp_remote_get( $url, array (
     'headers' => $wp_headers,
    ));

    $wp_listings = json_decode($response['body']);
    $wp_listing = null;

    if(count($wp_listings) > 0)
    {
        $wp_listing = $wp_listings[0];
    }else{
        print("Not Found: " . $url  . " \n");
    }

    return $wp_listing;
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
sleep(3);

// get sydney sales listings
print("fetching sydney sales listings \n");
$results_data = $sales_api->getRequest('https://api.domain.com.au/v1/salesResults/Sydney/listings');
$listings_json = json_decode($results_data);
$listings_count = count($listings_json);
sleep(3);
print("listings: ". count($listings_json) . "\n");

// prepare listing api client
$listings_client_id = 'client_f9a5c138ea674b1d93c0e97e03a67e82';
$listings_client_secrets = 'secret_7e2b4ce9a8608ad91394e9b89e32758f';

$listings_api = new domain_api($listings_client_id, $listings_client_secrets);
$listings_api->authorize();
sleep(3);


$inserted_count = 0;

foreach($listings_json as $listing_index => $listing)
{
    print($listing_index . "/" . $listings_count . "...\n");
    // check if listing already exists in houseace wordpress
    $wp_listing = get_houseace_listing_post($listing->id);

    if($wp_listing!=null && $wp_listing->acf->auctioned_date == $results_header_json->auctionedDate)
    {
        print("listing already exists in houseace, skipped! \n");
        // continue;
    }
    
    // get listing detail from domain.com.au
    $listing_data = $listings_api->getRequest('https://api.domain.com.au/v1/listings/' . $listing->id);
    $listing_json = json_decode($listing_data);

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
    $gallery_html = '';
    $floorplans_count = 0;
    foreach($listing_json->media as $i => $image)
    {
        if($image->category=='image')
        {
            $post_data['fields[media][image_'. $images_count .']'] = $image->url;
            $post_data['fields[media][image_'. $images_count .'_type]'] = $image->type;

            if($image->type == 'floorplan')
            {
                $floorplans_count++;
            }

            $gallery_html = $gallery_html . "<img src='" . $image->url . "'/>";
            $images_count++;
        }
    }

    $post_data['fields[floorplans_count]'] = $floorplans_count;
    $post_data['fields[gallery]'] = $gallery_html;

    $post_data['fields[display_price]'] = $listing_json->priceDetails->displayPrice;
    $post_data['fields[sold_details][sold_action]'] = $listing_json->saleDetails->soldDetails->soldAction;
    $post_data['fields[sold_details][sold_price]'] = $listing_json->saleDetails->soldDetails->soldPrice;
    $post_data['fields[sold_details][sold_date]'] = $listing_json->saleDetails->soldDetails->soldDate;
    $post_data['fields[seo_url]'] = $listing_json->seoUrl;
    $post_data['fields[virtual_tour_url]'] = $listing_json->virtualTourUrl;

    $post_data['title'] = $listing_json->addressParts->displayAddress;
    $post_data['content'] = $listing_json->description;
    $post_data['status'] = 'publish';
    
    if($wp_listing!=null)
    {
        $url = $houseace_base_url . '/wp-json/wp/v2/listing/' . $wp_listing->id;

    }else{
        $url = $houseace_base_url . '/wp-json/wp/v2/listing';
    }

    // create or update listing with acf fields
    print("posting to houseace with acf fields:" . $url . "\n");
    $response = wp_remote_post( $url, array (
     'method'  => 'POST',
     'timeout' => 45,
     'headers' => $wp_headers,
     'body'    =>  $post_data
    ));

    // try to find houseace listing again
    if($wp_listing==null)
    {
        $wp_listing = get_houseace_listing_post($listing->id);
    }

    // update the listing thumbnails and impress fields
    $url = $houseace_base_url . '/wp-json/wq/v2/listings/' . $wp_listing->id;
    print("updating impress plugin fields:" . $url . "\n");
    $response = wp_remote_post( $url, array (
     'method'  => 'POST',
     'timeout' => 45,
     'headers' => $wp_headers,
     'body'    =>  $post_data
    ));

    print("next... \n");


    $inserted_count++;
    if($inserted_count == 3)
    {
        //break;
    }

}

print('completed:' . $inserted_count . '\n');


<?php
header("Access-Control-Allow-Origin: *");


//request gonderen cURL metodu
function callAPI($url, $sess_id = null, $body = null){
  $base_url = 'http://opencart.inoverse.com/index.php?route=api2/';
  $url = $base_url . $url;

  $curl = curl_init();

  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_HEADER, true);
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0); 
  curl_setopt($curl, CURLOPT_TIMEOUT, 60); //60 saniye timeout
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

  curl_setopt($curl, CURLOPT_COOKIEJAR, 'cookies.txt'); 
  curl_setopt($curl, CURLOPT_COOKIEFILE, 'cookies.txt');
  
  $sess_cookie = '';
  // session id cookie olarak header'e ekleniyor.
  if(!empty($sess_id)){
    $sess_cookie =  'OCSESSID=' . $sess_id . ';';
  }

  curl_setopt($curl, CURLOPT_COOKIE, $sess_cookie . "currency=TRY;language=tr-tr");

  $body_string = '';
  if (!empty($body) && is_array($body) && count($body)) {
    foreach($body as $key=>$value) {
      $body_string .= $key.'='.$value.'&';
    }
    rtrim($body_string, '&');
 
    curl_setopt($curl,CURLOPT_POST, count($body));
    curl_setopt($curl,CURLOPT_POSTFIELDS, $body_string);
  }


  $result = curl_exec($curl);
  if(!$result){die("Connection Failure");}
  
  $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
  $headers = substr($result, 0, $header_size);
  $body = substr($result, $header_size);

  curl_close($curl);
  
  $body_json = json_decode($body);

  if( json_last_error() !== JSON_ERROR_NONE )
    return array( 'header' => $headers, 'body' => $body );
  else
    return array( 'header' => $headers, 'body' => (array)json_decode($body) );
}







function print_log($result){
  echo 'Log Time: ' . time() . '<br>';

  if(is_array($result))
    print_r($result);
  else
    var_dump($result);

  echo '<br><br><br><br><br><br>';
}

echo "<pre>";





//cart set edilior ve total hesaplaniyor. session id donuyor.
$data_array =  array(
  "product_array" => json_encode(array(
    (object)array(
    'product_id' => '33',
    'quantity' => '1'
    )
  ))
);
$cart = (array)callAPI('cart/total', null, $data_array);    //session_id parametresi yalnizca bu method icin null.
print_log($cart);
$sess_id = $cart['body']['OCSESSID'];


print_log("Sess ID: " . $sess_id);  //bu session id requestlerde kullanilacak.




//customer bilgileri set ediliyor. Bu requestten itibaren "api2/order/add" metoduna kadar satin alma bilgileri sunucudaki sessionda tutuluyor.
$data_array = array(
  'firstname' => 'api test xdxdxd',
  'lastname'  => 'test lastname',
  'email' => 'ozgunesim@gmail.com',
  'telephone' => '1111111111'
);
$customer_result = (array)callAPI('customer', $sess_id, $data_array);
print_log($customer_result);



//ulkeler listeleniyor. turkiye id'si aliniyor.
$country_result = (array)callAPI('country/list', $sess_id);
$country_list = $country_result['body'];
print_log($country_list);
$country_id = 0;
foreach($country_list as $country){
  $country = (array)$country;
  if($country['iso_code_3'] == 'TUR' && $country['status'] === '1'){
    $country_id = $country['country_id'];
    print_log('TR bulundu. ID: ' . $country_id);
    break;
  }
}



//zone = sehir. ulke id'si verilerek sehirlerin listesi getiriliyor.
$zone_result = (array)callAPI('zone/list&country_id=' . $country_id, $sess_id);
$zone_list = $zone_result['body'];
$zone_id = 0;
foreach($zone_list as $zone){
  $zone = (array)$zone;
  if($zone['name'] == 'Sakarya'){
    $zone_id = $zone['zone_id'];
    print_log('Sakarya Bulundu. ID: ' . $zone_id);
    break;
  }
}


//odeme adresi set ediliyor.
$data_array = array(
  'firstname' => 'api test',
  'lastname'  => 'test lastname',
  'address_1' => 'soguk su sok.',
  'city' => 'serdivan', // kullanicidan alinan string deger. mahalle/semt olarak dusunulebilir.
  'country_id' => $country_id,  //turkiye
  'zone_id' => $zone_id //sakarya - eyalet olarak da kullanilabiliyor baska ulkelerde
);
$payment_result = (array)callAPI('payment/address', $sess_id, $data_array);
print_log($payment_result);




//kargo adresi set ediliyor.
$data_array = array(
  'firstname' => 'api test',
  'lastname'  => 'test lastname',
  'address_1' => 'soguk su sok.',
  'city' => 'serdivan', // kullanicidan alinan string deger. mahalle/semt olarak dusunulebilir.
  'country_id' => $country_id,  //turkiye
  'zone_id' => $zone_id //sakarya - eyalet olarak da kullanilabiliyor baska ulkelerde
);
$shipping_result = (array)callAPI('shipping/address', $sess_id, $data_array);
print_log($shipping_result);




//kargo metodlari listesi getiriliyor. kargo metodlarinin kodlari diziye aktariliyor.
$shipping_methods_result = (array)callAPI('shipping/methods', $sess_id);
$shipping_methods = $shipping_methods_result['body'];
$shipping_method_codes = array();
foreach($shipping_methods as $method_group){
  $method_group = (array)$method_group;

  foreach($method_group as $method){
    $method = (array)$method;

    foreach($method['quote'] as $q){
      $q = (array)$q;
      array_push($shipping_method_codes, $q);
    }
  }
}
print_log($shipping_method_codes);




//kargo metodu, kodu kullanilarak set ediliyor.
$data_array = array(
  'shipping_method' => 'xshippingpro.xshippingpro1'
);
$shipping_method_result = (array)callAPI('shipping/method', $sess_id, $data_array);
print_log($shipping_method_result);




//odeme methodlari listesi getiriliyor.
$payment_methods_result = (array)callAPI('payment/methods', $sess_id);
$payment_methods = (array)$payment_methods_result['body']['payment_methods'];
//exit(var_dump($payment_methods));
print_log($payment_methods_result);





//odeme metodu, kodu kullanilarak set ediliyor.
$data_array = array(
  'payment_method' => 'cod'
);
$payment_method_result = (array)callAPI('payment/method', $sess_id, $data_array);
print_log($payment_method_result);





//siparis tamamlaniyor.
$order_result = (array)callAPI('order/add', $sess_id);
//exit(var_dump($order_result));
print_log($order_result);



echo "</pre>";
?>
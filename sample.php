<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once './vendor/autoload.php';

use AmazonPay\Client as Client;

$config = require_once("config/config.local.php");

$client = new Client($config);

$requestParameters = array();
// Required Parameter
$requestParameters['amazon_order_reference_id'] = 'S03-8404700-8343649';
// Optional Parameter
$requestParameters['address_consent_token']  = 'Atza|IwEBIAylMi_mEsO4qTze0y6qPhG8VrEoYCjyeQQV__VD5xCq-LkGBsuadKPiLr2H_LTHEo4afcCkP3qxUVa7E3jKnro7I02KS2m3AXOI4ouLbsLN0ki2LVk0c1ZPd6eiVyk65CVFjrqTsPyyl7vtVmwNzMCxu4kUt7hgJEObMRLO3O2FZkTi1bqGqOVgtsiHZQ2kgLbpr_zWGNBkeAmniqPo4AY9pHz-VsjoEfBvGZhmu0QF3DfqBK_Xn8DIGybKjlXOoGUceb95CGw5KJLiEDp7-O7gyLOafz2V1YAheNiy_lO0Ekxd7CnjyQH7p7HELFimoXBG7M5fXCzHuTmaNSDFO_nMCAejZbjmWDPZithrv4ijnOjdbhNmZhZO7VBJkj3lxjKA6ewT7ki29wuGlIz3WfbpS6Asj2mbt-dY7PZrXwj5r6D-GT7DTN4ntfy6buxYR_dU3XhJnmqyszQNESJ9MajccpPanfrnPb1-OqLIo5t5_i15VLLyMdYhPmoJt6pM50Ht0xOQ3bCILC1pRWJuSFfvo9CebrNpvSTew1DZ4K05A-PjgPQt_1vcd0J84JMP-nJ2DOmwEqMKF0TyawuHYYHRP_3xFJDgA_mqdkXc5SZY4Q';
// $requestParameters['mws_auth_token']         = 'MWS_AUTH_TOKEN';
$response = $client->getOrderReferenceDetails($requestParameters);

var_dump($response);


?>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once './vendor/autoload.php';

function my_print_r($array) {
  echo '<pre>';
  print_r($array);
  echo '</pre>';
};

use AmazonPay\Client as Client;

$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  
  $config = require_once("config/config.local.php");
  
  $client = new Client($config);
  
  //商品の合計金額
  $amount = 3000;
  
  //POSTがきたので、Amazon Payの処理をします。
  
  //POSTパラメータからOrderReferenceIdを
  $orderReferenceId = $_POST["orderReferenceId"];
  $accessToken = $_POST["accessToken"];  
  
  try {
    // GetOrderReferenceDetails
    // @see https://pay.amazon.com/jp/developer/documentation/apireference/201752920
    //
    $params = [];
    $params['amazon_order_reference_id'] = $orderReferenceId; 
    $params['address_consent_token'] = $accessToken;
    $orderReferenceDetailsResponse = $client->getOrderReferenceDetails($params);
    $orderReferenceDetailsArray = $orderReferenceDetailsResponse->toArray();
    if ($orderReferenceDetailsArray['ResponseStatus'] != 200)
      throw new Exception($orderReferenceDetailsArray["Error"]["Message"]);

    // my_print_r($orderReferenceDetailsArray);
    // $orderReferenceDetails = $orderReferenceDetailsArray["GetOrderReferenceDetailsResult"]["OrderReferenceDetails"];


    // setOrderReferenceDetailsをコールして、注文の詳細を事前に設定
    // ここでポイントなのは、合計金額などはこちらで取得が可能です。
    $params = [];
    $params["amazon_order_reference_id"] = $orderReferenceId;
    $params["amount"] = $amount;
    $setOrderReferenceDetailsResponse = $client->setOrderReferenceDetails($params);
    $setOrderReferenceDetailsArray = $setOrderReferenceDetailsResponse->toArray();
    if ($setOrderReferenceDetailsArray['ResponseStatus'] != 200) 
       throw new Exception($setOrderReferenceDetailsArray["Error"]["Message"]);
       
    // my_print_r($setOrderReferenceDetailsArray);
    
    //ConfirmOrderReferenceDetatilsを呼び出す事で注文が確定されて、
    //OrderReferenceのStatusがOpenになります。    
    $params = [];
    $params["amazon_order_reference_id"] = $orderReferenceId;
    $confirmOrderReferenceResponse = $client->confirmOrderReference($params);
    $confirmOrderReferenceArray = $confirmOrderReferenceResponse->toArray();
    if ($confirmOrderReferenceArray['ResponseStatus'] != 200) 
       throw new Exception($confirmOrderReferenceArray["Error"]["Message"]);
       
    //注文が確定すれば、Authorize/Capture(オーソリの取得と売り上げ確定)をする準備ができた。
    $params = [];
    $params["amazon_order_reference_id"] = $orderReferenceId;
    $params["authorization_amount"] = $amount;
    $params["authorization_reference_id"] = time();
    $params["transaction_timeout"] = 0;
    $params["transaction_timeout"] = 0;
    $params["capture_now"] = true;
    $authorizeResponse = $client->authorize($params);
    $authorizeArray = $authorizeResponse->toArray();
    if ($authorizeArray['ResponseStatus'] != 200) 
       throw new Exception($authorizeArray["Error"]["Message"]);

    $authorizationDetails = $authorizeArray['AuthorizeResult']['AuthorizationDetails'];
    
    // @see https://pay.amazon.com/jp/developer/documentation/apireference/201752950
    //オーソリが成功したかチェックをします。
    // 今回は、CaptureNowをTrueにしたので、正常の状態は
    // Authorize.State == Closed && MaxCapturesProcessed
    $authorizationState = $authorizationDetails['AuthorizationStatus']['State'];
    $authorizationStatusReasonCode = $authorizationDetails['AuthorizationStatus']['ReasonCode'];

    if ($authorizationState == "Closed") {
      
    } 
    else if ($authorizationState == "Open") {
      //今回はCaptureNowでやっているので、オーソリがOpenの状態で残ることはありえません。
      //もし、そのようなことがあればエラーなので一旦キャンセルをして再度注文をするフローでいきましょう。
      
      $params = [];
      $params['amazon_order_reference_id'] = $orderReferenceId;
      $cancelOrderReferenceResponse = $client->cancelOrderReference($params);
      $cancelOrderReferenceArray = $cancelOrderReferenceResponse->toArray();
      if ($cancelOrderReferenceArray['ResponseStatus'] != 200) 
        throw new Exception($cancelOrderReferenceArray["Error"]["Message"]);
      
      
      throw new Exception("CaptureNowがtrueなのに、Open状態はおかしい。");
    } 
    else if ($authorizationState == "Pending") {
      
    } 
    else if($authorizationState == "Declined") { 
      
    } 


    switch($authorizationState) {
      case 'Declined':
        if ($authorizationStatusReasonCode == 'AmazonRejected')
        
        
        
        break;
      case 'Closed':
        if ($authorizationStatusReasonCode == 'MaxCapturesProcessed') {
          //正常にオーソリと売り上げ請求ができました。
          //よって、ありがとうページへ移動します。
          header("Location: shop-thanks.html");
          exit;
        } 
        else {
          throw new Exception("オーソリエラーが発生しました"); 
        }
        break;
        
    }
    
    



    my_print_r($authorizeResponseArray);
       
  } catch (Exception $e) {
    $errorMessage = $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="viewport" content="width=device-width,initial-scale=1.0, maximum-scale=1.0"/>
    <title>注文確認ページ：密林コーヒー Amazon Payデモサイト</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/shop-confirmation.css" rel="stylesheet">
    <script type='text/javascript'>
      function getURLParameter(name, source) {
          return decodeURIComponent((new RegExp('[?|&amp;|#]' + name + '=' +
                          '([^&;]+?)(&|#|;|$)').exec(source) || [, ""])[1].replace(/\+/g, '%20')) || null;
      }

      var error = getURLParameter("error", location.search);
      if (typeof error === 'string' && error.match(/^access_denied/)) {
        console.log('Amazonアカウントでのサインインをキャンセルされたため、戻る');
        window.location.href = 'shop-cart.html';
      }
    </script>

  </head>

  <body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
      <div class="container">
        <a class="navbar-brand" href="shop-item.html">密林コーヒー：デモサイト</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a class="nav-link" href="#">Top</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">About</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">Contact</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <div class="container">

      <div class="row">

        <!-- Blog Entries Column -->
        <div class="col-md-8">

          <h1 class="my-4">ご注文手続き</h1>

          <?php if (!empty($errorMessage)) :?>
          <div class="alert alert-danger" role="alert">
            <p>エラーが発生しました：</p>
            <?php echo $errorMessage;?>
          </div>
          <?php endif;?>

          <!-- Blog Post -->
          <div class="card mb-4">
            <div class="card-body">
              <h5>お届け先・お支払い方法の選択</h5>
              <div id="addressBookWidgetDiv" style="height:250px"></div>
              <div id="walletWidgetDiv" style="height:250px"></div>
            </div>
          </div>

          <div class="card mb-4">
            <div class="card-body">
              <h5 class="card-title">配送方法</h5>
              <p>指定なし</p>
              <button class="btn btn-secondary float-right">変更</button>
            </div>
          </div>

          <div class="card mb-4">
            <div class="card-body">
              <h5 class="card-title">ご注文内容</h5>
              <table class="table table-striped">
                  <thead>
                      <tr>
                          <th scope="col"> </th>
                          <th scope="col">商品名</th>
                          <th scope="col">単価(税込)</th>
                          <th scope="col" class="text-center">数量</th>
                          <th scope="col" class="text-right">小計</th>
                      </tr>
                  </thead>
                  <tbody>
                      <tr>
                          <td><img class="confirmation-item" src="image/sample1.jpg" /></td>
                          <td>コロンビア・ビルバオ コーヒー豆</td>
                          <td class="text-right">￥2,000</td>
                          <td class="text-right">1</td>
                          <td class="text-right">￥2,000</td>
                      </tr>
                      <tr>
                          <td><img class="confirmation-item" src="image/sample2.jpg" /></td>
                          <td>コーヒーカップ</td>
                          <td class="text-right">￥500</td>
                          <td class="text-right">1</td>
                          <td class="text-right">￥500</td>
                      </tr>
                  </tbody>
              </table>
            </div>
          </div>

        </div>

        <!-- Sidebar Widgets Column -->
        <div class="col-md-4">

          <!-- Side Widget -->
          <div class="card my-4">
            <h5 class="card-header">お支払い金額</h5>
            <div class="card-body" id="highlight2">

              <table class="table table-striped">
                  <tbody>
                      <tr>
                          <td>商品合計</td>
                          <td class="text-right">￥2,500</td>
                      </tr>
                      <tr>
                          <td>送料</td>
                          <td class="text-right">￥500</td>
                      </tr>
                      <tr>
                          <td><strong>総合計</strong></td>
                          <td class="text-right"><strong>￥3,000</strong></td>
                      </tr>
                  </tbody>
              </table>

              <div>
                <div>
                  <div class="checkbox">
                      <label>
                          <input type="checkbox" checked/> お客様情報を会員として登録する
                      </label>
                  </div>

                  <div class="checkbox">
                      <label>
                          <input type="checkbox" checked/> メールマガジンを購読する
                      </label>
                  </div>
                </div>
              </div>

              <div>
                <form action="<?php echo $_SERVER['PHP_SELF']?>" method="POST">
                  <input type="text" name="orderReferenceId" id="orderReferenceId" value="" />
                  <input type="text" name="accessToken" id="accessToken" value="" />
                  <button type="submit" class="btn btn-block btn-success">購入</button>
                </form>
              </div>

              </br>

              <div>
                  <label>
                      <p>※デモサイトです</p>
                      <p>※会員登録・課金はされません</p>
                  </label>
              </div>

            </div>
          </div>

        </div>

      </div>
      <!-- /.row -->

    </div>
    <!-- /.container -->

    <!-- Footer -->
    <footer class="py-5 bg-dark">
      <div class="container">
        <p class="m-0 text-center text-white">Copyright &copy; Amazon Pay 2018</p>
      </div>
      <!-- /.container -->
    </footer>

    <!-- Amazon Pay JavaScript -->
    <script type='text/javascript'>
    
    let clientId = 'amzn1.application-oa2-client.48ecb861512f4983bfed74eb3a9a06a1'; 
    let sellerId = 'A2MIN1OBNPVKXS';
    
    
      // get access token
      function getURLParameter(name, source) {
          return decodeURIComponent((new RegExp('[?|&amp;|#]' + name + '=' +
                          '([^&;]+?)(&|#|;|$)').exec(source) || [, ""])[1].replace(/\+/g, '%20')) || null;
      }
      //popup=trueにする場合
      var accessToken = getURLParameter("access_token", location.href);
      // popup=falseにする場合
      // var accessToken = getURLParameter("access_token", location.hash);
      // if (typeof accessToken === 'string' && accessToken.match(/^Atza/)) {
      //     document.cookie = "amazon_Login_accessToken=" + accessToken + ";path=/;secure";
      // }

      window.onAmazonLoginReady = function() {
        amazon.Login.setClientId(clientId);
        amazon.Login.setUseCookie(false); //popup=falseにときに必要

        if (accessToken) {
          document.getElementById("accessToken").value = accessToken;
          amazon.Login.retrieveProfile(accessToken, function (response){
            if (response.success) {
              console.log("Amazon Account Name :" + response.profile.Name);
              console.log("Amazon Account Mail :" + response.profile.PrimaryEmail);
              console.log("Amazon UserId :" + response.profile.CustomerId);
              
            }
          });
        }
      };

      window.onAmazonPaymentsReady = function() {
        showAddressBookWidget();

      };

      function showAddressBookWidget() {
          // AddressBook
          new OffAmazonPayments.Widgets.AddressBook({
            sellerId: sellerId,

            onReady: function (orderReference) {
                var orderReferenceId = orderReference.getAmazonOrderReferenceId();
                
                document.getElementById("orderReferenceId").value = orderReferenceId;
                
                // Wallet
                showWalletWidget(orderReferenceId);
            },
            onAddressSelect: function (orderReference) {
                // お届け先の住所が変更された時に呼び出されます、ここで手数料などの再計算ができます。
            },
            design: {
                designMode: 'responsive'
            },
            onError: function (error) {
                // エラー処理
                // エラーが発生した際にonErrorハンドラーを使って処理することをお勧めします。
                // @see https://payments.amazon.com/documentation/lpwa/201954960
                //console.log('OffAmazonPayments.Widgets.AddressBook', error.getErrorCode(), error.getErrorMessage());
                switch (error.getErrorCode()) {
                  case 'AddressNotModifiable':
                      // オーダーリファレンスIDのステータスが正しくない場合は、お届け先の住所を変更することができません。
                      break;
                  case 'BuyerNotAssociated':
                      // 購入者とリファレンスIDが正しく関連付けられていません。
              　　　    // ウィジェットを表示する前に購入者はログインする必要があります。
                      break;
                  case 'BuyerSessionExpired':
                      // 購入者のセッションの有効期限が切れました。
         　　　　        // ウィジェットを表示する前に購入者はログインする必要があります。
                      break;
                  case 'InvalidAccountStatus':
                      // マーチャントID（セラーID）がリクエストを実行する為に適切な状態ではありません。
        　　　　         // 考えられる理由 ： 制限がかかっているか、正しく登録が完了されていません。
                      break;
                  case 'InvalidOrderReferenceId':
                      // オーダーリファレンスIDが正しくありません。
                      break;
                  case 'InvalidParameterValue':
                      // 指定されたパラメータの値が正しくありません。
                      break;
                  case 'InvalidSellerId':
                      // マーチャントID（セラーID）が正しくありません。
                      break;
                  case 'MissingParameter':
                      // 指定されたパラメータが正しくありません。
                      break;
                  case 'PaymentMethodNotModifiable':
                      // オーダーリファレンスIDのステータスが正しくない場合はお支払い方法を変更することができません。
                      break;
                  case 'ReleaseEnvironmentMismatch':
                      // 使用しているオーダーリファレンスオブジェクトがリリース環境と一致しません。
                      break;
                  case 'StaleOrderReference':
                      // 使用しているオーダーリファレンスIDがキャンセルされています。
                  　　　// キャンセルされたオーダーリファレンスIDでウィジェットを関連付けすることはできません。
                      break;
                  case 'UnknownError':
                      // 不明なエラーが発生しました。(UnknownError)
                      break;
                  default:
                      // 不明なエラーが発生しました。
                }
            }
          }).bind("addressBookWidgetDiv");
      }

      function showWalletWidget(orderReferenceId) {
          // Wallet
          new OffAmazonPayments.Widgets.Wallet({
            sellerId: sellerId,
            amazonOrderReferenceId: orderReferenceId,
            onReady: function(orderReference) {
                console.log(orderReference.getAmazonOrderReferenceId());
            },
            onPaymentSelect: function() {
                console.log(arguments);
            },
            design: {
                designMode: 'responsive'
            },
            onError: function(error) {
                // エラー処理
                // エラーが発生した際にonErrorハンドラーを使って処理することをお勧めします。
                // @see https://payments.amazon.com/documentation/lpwa/201954960
                console.log('OffAmazonPayments.Widgets.Wallet', error.getErrorCode(), error.getErrorMessage());
            }
          }).bind("walletWidgetDiv");
      }

    </script>

    <script type="text/javascript"
      src="https://static-fe.payments-amazon.com/OffAmazonPayments/jp/sandbox/lpa/js/Widgets.js"
       async></script>

    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
  </body>

</html>

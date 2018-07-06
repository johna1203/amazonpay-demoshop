<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';

function my_print_r($array) {
  echo '<pre>';
  print_r($array);
  echo '</pre>';
  exit;
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

    //------------------------------------------------------------------------------------
    // Step 5:注文詳細のセットと確認 1. SetOrderReferenceDetails
    // 
    // インテグレーションガイド
    //   @see https://pay.amazon.com/jp/developer/documentation/lpwa/201952090
    // 
    // SetOrderReferenceDetails APIリファレンス
    //   @see https://pay.amazon.com/jp/developer/documentation/apireference/201751960
    //
    // 注文金額、説明、その他の注文属性などの注文の詳細をOrder Referenceに
    // 指定するためにSetOrderReferenceDetails処理を呼び出します。
    //
    // SetOrderReferenceDetails API呼び出しを行います。
    // リクエストするには、 OrderReferenceAttributesの次の属性をセットします。 
    // 
    // ※ 必須項目 
    // amazon_order_reference_id  Amazon Order Reference ID
    // amount                     Order Total（注文金額と通貨コード）
    //------------------------------------------------------------------------------------

    $params = [];
    $params["amazon_order_reference_id"] = $orderReferenceId;
    $params["amount"] = $amount;
    $setOrderReferenceDetailsResponse = $client->setOrderReferenceDetails($params);
    $setOrderReferenceDetailsArray = $setOrderReferenceDetailsResponse->toArray();
    if ($setOrderReferenceDetailsArray['ResponseStatus'] != 200) 
       throw new Exception($setOrderReferenceDetailsArray["Error"]["Message"]);
       
    // my_print_r($setOrderReferenceDetailsArray);
    
    //------------------------------------------------------------------------------------
    // Step 5:注文詳細のセットと確認 2. ConfirmOrderReference
    // 
    // インテグレーションガイド
    //    @see https://pay.amazon.com/jp/developer/documentation/lpwa/201952090
    // 
    // ConfirmOrderReference APIリファレンス
    //    @see https://pay.amazon.com/jp/developer/documentation/apireference/201751980
    //
    // ConfirmOrderReference APIの呼び出しを行います。SetOrderReferenceDetails API呼び出しが成功した場合は、
    // ConfirmOrderReference API呼び出しを行うことで、注文を確定させる事ができます。
    //
    // 注意なのは、注文確定
    // ConfirmOrderReference呼び出しが成功した場合は、Order Referenceオブジェクトは Open状態になります。
    // この時点で、Amazonからも「ご利用内容の確認」メールも送信されます。
    //
    // ※ 必須項目 
    // amazon_order_reference_id  Amazon Order Reference ID
    //------------------------------------------------------------------------------------
  
    $params = [];
    $params["amazon_order_reference_id"] = $orderReferenceId;
    $confirmOrderReferenceResponse = $client->confirmOrderReference($params);
    $confirmOrderReferenceArray = $confirmOrderReferenceResponse->toArray();
    if ($confirmOrderReferenceArray['ResponseStatus'] != 200) 
       throw new Exception($confirmOrderReferenceArray["Error"]["Message"]);
    
    //------------------------------------------------------------------------------------
    // Step 5:注文詳細のセットと確認 3. GetOrderReferenceDetails
    // 
    // インテグレーションガイド
    //   @see https://pay.amazon.com/jp/developer/documentation/lpwa/201952090
    // 
    // GetOrderReferenceDetails APIリファレンス
    //   @see https://pay.amazon.com/jp/developer/documentation/apireference/201751970
    // 
    // Order Reference状態と理由コード
    //   @see https://pay.amazon.com/jp/developer/documentation/apireference/201752920
    //
    // GetOrderReferenceDetails呼び出しを行います。
    // Order Referenceの承認が成功した後は、最新の住所であるか確実にするために、
    // 名前や配送先住所のような残りの購入者情報を取得するためのGetOrderReferenceDetails APIを呼び出すことができます。
    // 
    // ※ 必須項目 
    // amazon_order_reference_id  Amazon Order Reference ID
    //------------------------------------------------------------------------------------
    
    $params = [];
    $params['amazon_order_reference_id'] = $orderReferenceId; 
    // $params['access_token'] = $accessToken;
    $orderReferenceDetailsResponse = $client->getOrderReferenceDetails($params);
    $orderReferenceDetailsArray = $orderReferenceDetailsResponse->toArray();
    if ($orderReferenceDetailsArray['ResponseStatus'] != 200)
      throw new Exception($orderReferenceDetailsArray["Error"]["Message"]);

    // my_print_r($orderReferenceDetailsArray);
    // $orderReferenceDetails = $orderReferenceDetailsArray["GetOrderReferenceDetailsResult"]["OrderReferenceDetails"];
    // my_print_r($orderReferenceDetails);
    
    //------------------------------------------------------------------------------------
    // Step 6:オーソリ（Authorize）のリクエスト
    // 
    // インテグレーションガイド
    //   @see https://pay.amazon.com/jp/developer/documentation/lpwa/201952140
    // 
    // GetOrderReferenceDetails APIリファレンス
    //   @see https://pay.amazon.com/jp/developer/documentation/apireference/201752010
    // 
    // オーソリは与信確保をします。
    // オーソリに成功した場合は、AuthorizationStatusがOpen であるオーソリオブジェクトが生成されます。
    // ちなみに、オーソリオブジェクトは30日間 Open状態を維持します。^^
    //
    // ※ 必須項目 
    // amazon_order_reference_id   Amazon Order Reference ID
    // authorization_reference_id  システムで指定するこのオーソリトランザクションのIDです。
    // authorization_amount        オーソリする金額
    //
    // ※オプションだが、今回使うパラメータ
    // transaction_timeout         オーソリ処理を完了するまでの最大分数を割り当てます。
    //                               (同期モードでのTransactionTimeout値は、0をセットしてください。)
    // capture_now                 Authorizeに対して指定した金額をすぐに売上請求するか指定します。
    //------------------------------------------------------------------------------------
    
    $params = [];
    $params["amazon_order_reference_id"] = $orderReferenceId;
    $params["authorization_amount"] = $amount;
    $params["authorization_reference_id"] = time();
    $params["transaction_timeout"] = 0;
    $params["capture_now"] = false;
    $authorizeResponse = $client->authorize($params);
    $authorizeArray = $authorizeResponse->toArray();
    if ($authorizeArray['ResponseStatus'] != 200) 
       throw new Exception($authorizeArray["Error"]["Message"]);

    $authorizationDetails = $authorizeArray['AuthorizeResult']['AuthorizationDetails'];
    

    //------------------------------------------------------------------------------------
    // Step 6:オーソリ（Authorize）のリクエスト (オーソリ失敗のハンドリング)
    // 
    // インテグレーションガイド
    //   @see https://pay.amazon.com/jp/developer/documentation/lpwa/201953810
    //
    // オーソリ状態と理由コード
    //   @see https://pay.amazon.com/jp/developer/documentation/apireference/201752950
    //
    // オーソリ処理の呼び出しが失敗した場合は、レスポンス内に失敗した理由コードを確認します。
    // 
    // 今回は、同期モード
    //  
    //  Stateには、以下がある
    //    Pending
    //    Open
    //    Declined
    //    Closed
    //------------------------------------------------------------------------------------

    $authorizationState = $authorizationDetails['AuthorizationStatus']['State'];

    //------------------------------------------------------------------------------------
    // Authorization State
    //  Pending
    //  
    //  同期モードでは、オーソリオブジェクトはPending状態になりません。
    //  今回は、同期モードで実装しているので、Pendingになることはないので、Pendingなら
    //  問題と判断して、注文をキャンセルします。
    //------------------------------------------------------------------------------------
    if ($authorizationState == "Pending") {
      $params = [];
      $params['amazon_order_reference_id'] = $orderReferenceId;
      $cancelOrderReferenceResponse = $client->cancelOrderReference($params);
      $cancelOrderReferenceArray = $cancelOrderReferenceResponse->toArray();
      if ($cancelOrderReferenceArray['ResponseStatus'] != 200) 
        throw new Exception($cancelOrderReferenceArray["Error"]["Message"]);
      
    }
    
    //------------------------------------------------------------------------------------
    // Authorization State
    //  Declined
    //  
    //  同期モードでは、オーソリオブジェクトはすぐにOpen状態に遷移します。
    //  今回は、CaptureNowで即時売り上げ請求しているので、Open状態は問題の可能性がありますので
    //  キャンセルをするします。
    // - Closedには、以下の ReasonCode があります。
    //   - InvalidPaymentMethod            支払方法に問題がありました。Soft DeclineとHard Declineを区別するためにSoftDeclineを利用します。
    //   - AmazonRejected                  Amazonはオーソリを拒否しました。
    //   - ProcessingFailure               Amazonは内部処理エラーのためにトランザクションを処理できませんでした。
    //   - TransactionTimedOut             同期モードの場合は、Amazonが30秒以内にリクエストを処理できませんでした。
    //------------------------------------------------------------------------------------    
    else if($authorizationState == "Declined") { 

      $authorizationStatusReasonCode = $authorizationDetails['AuthorizationStatus']['ReasonCode'];

      switch ($authorizationStatusReasonCode) {
        case 'InvalidPaymentMethod':
        case 'ProcessingFailure':
        case 'TransactionTimedOut':
          $params = [];
          $params['amazon_order_reference_id'] = $orderReferenceId;
          $cancelOrderReferenceResponse = $client->cancelOrderReference($params);
          $cancelOrderReferenceArray = $cancelOrderReferenceResponse->toArray();
          if ($cancelOrderReferenceArray['ResponseStatus'] != 200) 
            throw new Exception($cancelOrderReferenceArray["Error"]["Message"]);
            
          throw new Exception("ReasonCodeがおかしいです。再度注文してください。");
          break;
        case 'AmazonRejected':
          throw new Exception("決済時に問題が発生しました、別の支払い方法を試すか再度行ってください。");          
          break;
        default:
            throw new Exception("予定外のReasonCode");
          break;
      }
    }    
    
    //------------------------------------------------------------------------------------
    // Authorization State
    //  Open
    //  
    //  同期モードでは、オーソリオブジェクトはすぐにOpen状態に遷移します。
    //  注意： CaptureNowで即時売り上げ請求している場合は、Open状態に継続することはない。
    //         CaptureNowでCaptureが成功すると、AuthorizeはClosedになります。
    //------------------------------------------------------------------------------------
    else if ($authorizationState == "Open") {

      //------------------------------------------------------------------------------------
      // Step 7:売上請求（Capture）のリクエスト
      // 
      // インテグレーションガイド
      //   @see https://pay.amazon.com/jp/developer/documentation/lpwa/201953080
      //
      // Capture状態と理由コード
      //   @see https://pay.amazon.com/jp/developer/documentation/apireference/201753020
      //
      // オーソリされた支払方法から資金を売上請求します。
      //
      // 資金を回収するためには、本番環境モードではオーソリに成功してから30日以内に売上請求（Capture）APIを呼び出します。
      // テスト環境モードでは2日以内です。
      // 30日を過ぎた場合は、Amazonによってオーソリオブジェクトは Closed状態にします。
      // 
      //  
      // ※ 必須項目 
      //    amazon_authorization_id       Amazon Authorize ID
      //    copture_reference_id          システムで指定するこのCaptureトランザクションのIDです
      //    capture_amount                売り上げする金額
      //------------------------------------------------------------------------------------
      
      $amazonAuthorizationId = $authorizationDetails['AmazonAuthorizationId'];
      
      $params = [];
      $params['amazon_authorization_id'] = $amazonAuthorizationId;
      $params['capture_reference_id'] = "capture-" . time();
      $params['capture_amount'] = $amount;
      $captureResponse = $client->capture($params);
      $captureArray = $captureResponse->toArray();
      if ($captureArray['ResponseStatus'] != 200) 
        throw new Exception($captureArray["Error"]["Message"]);

      
      //------------------------------------------------------------------------------------
      // Capture エラーハンドリング
      //  Pending
      //  
      // 原則として、Authorizeが成功している注文に関しては、Captureはオーソリの期間が有効であれば
      // 成功します。
      // 
      // Capture State - 
      //  Pending         売上請求オブジェクトは、Amazonが処理するまではPending状態です。
      //  Declined        オーソリに対して30日間（SANDBOXでは2日間）以内に売上請求をしなければなりません。
      //  Completed       売上請求は完了
      //  Closed          売上請求オブジェクトがClosed状態に遷移したときは、売上請求に対して返金リクエストはできません。
      //------------------------------------------------------------------------------------
      
      $captureDetails = $captureArray['CaptureResult']['CaptureDetails'];
      $captureState   = $captureDetails['CaptureStatus']['State'];
      $amazonCaptureId   = $captureDetail['AmazonCaptureId'];
      
      if ($captureState == 'Pending' || $captureState == 'Completed') {
        header("Location: shop-thanks.html");
        exit;
      } 
      
      elseif ($captureState == 'Declined' || $captureState == 'Closed') {
        
          $params = [];
          $params['amazon_order_reference_id'] = $orderReferenceId;
          $cancelOrderReferenceResponse = $client->cancelOrderReference($params);
          $cancelOrderReferenceArray = $cancelOrderReferenceResponse->toArray();
          if ($cancelOrderReferenceArray['ResponseStatus'] != 200) 
            throw new Exception($cancelOrderReferenceArray["Error"]["Message"]);
            
          throw new Exception("キャプチャーに失敗しました。");
      }
    }
    
    //------------------------------------------------------------------------------------
    // Authorization State
    //  Closed
    //  
    // CaptureNowをtrueに使ってオーソリをコールしたのでオーソリはClosedになります。
    //
    // - Closedには、以下の ReasonCode があります。
    //   - ExpireUnused                    オーソリが30日超えてしまったので（SANDBOXでは2日間）、オーソリに対して売上請求していませんでした。
    //   - MaxCapturesProcessed            オーソリの最大額まですでに売上請求しました。Amazonは１つのオーソリに１つの売上請求だけ認めています。
    //   - AmazonClosed                    Amazonは販売事業者のアカウントの問題によりオーソリオブジェクトをClosedにしました。
    //   - OrderReferenceanceled           Order Referenceがキャンセルされたのが原因で、全てのOpenのオーソリはキャンセルになりました。
    //   - SellerClosed                    販売事業者がCloseAuthorization処理を利用して明示的にClosedにしました。
    //   - InvalidPaymentMethod
    //------------------------------------------------------------------------------------    
    else if ($authorizationState == "Closed") {
      
      $authorizationStatusReasonCode = $authorizationDetails['AuthorizationStatus']['ReasonCode'];
      
      //AuthorizeがCaptureNowの場合は、AuthorizeオブジェクトはClosedになり、
      //ReasonCodeがMaxCapturesProcessedがCaptureをしたことになるので、
      //こちらが決済の成功を表します。
      // if ($authorizationStatusReasonCode == "MaxCapturesProcessed") {
      //   header("Location: shop-thanks.html");
      //   exit;
      // }
    }


    // my_print_r($authorizeResponseArray);
       
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
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

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
    <script src="../vendor/jquery/jquery.min.js"></script>
  </body>

</html>

# Amazon Pay Developer Meetup #3 90分ハンズオン＆達人度試験

この、でもショップは二つの環境で動かせるように解説していきます。


[AWS Cloud9 で環境構築をしよう](#AWS Cloud9 で環境構築をしよう)

[PHP Buil-in server を使ってデモショップを動かそう]

PHP Buil-in server を使ってデモショップを動かそう。


# AWS Cloud9 で環境構築をしよう

#### 1. AWS にアカウントにログインして, サービスのメニューからCloud9を選択

![assets/image.png](assets/image.png)

#### 2. Create evironment を選択して、新しく環境を作成を開始します。

![assets/image2.png](assets/image2.png)

#### 3. 環境名前を入力して、Next Stepをクリック

![assets/image3.png](assets/image3.png)

#### 4. 開発サーバーの構成選択

デフォルトで問題はありません。EC2が一つ立ち上がりますので、こちらのEC2で開発していく形になります。

![assets/image4.png](assets/image4.png)

#### 5. 構成の確認

最後に内容を確認して、問題がなければ Create environmentをクリック

![assets/image5.png](assets/image5.png)

#### 6. しばらくすれば、Cloud9が立ち上がります

![assets/image6.png](assets/image6.png)

# PHP Buil-in server を使ってデモショップを動かそう。



# Cloud9上デモショップを動かしてみよう。

#### 1. Run メニュから Run with の中に、PHP (built-in web server)を選択します。

![run-php.png](assets/run-php.png)

#### 2. built-in web serverが8080で動いているかをcheck

![check8080.png](assets/check8080.png)

#### 3. 最後に web browser でデモショップを開く

右のファイル一覧から、shop-cart.htmlを選んで右クリックして　Previewをクリック

![open_in_browser2.png](assets/open_in_browser2.png)

#### 4. 以下のページが現れたら成功です。

![it_works.png](assets/its_works.png)

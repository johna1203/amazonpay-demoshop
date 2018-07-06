# Amazon Pay Developer Meetup #3 90分ハンズオン＆達人度試験

この、でもショップは二つの環境で動かせるように解説していきます。


- [AWS Cloud9 で環境構築をしよう](#aws-cloud9-で環境構築をしよう)

- [Cloud9上デモショップを動かしてみよう](Cloud9上デモショップを動かしてみよう)

##### Cloud9使えない人は、localhostで PHP Built-in serverを直接動かしてください。

- [PHP Buil-in web server を使ってデモショップを動かそう](PHP-Buil-in-server-を使ってデモショップを動かそう)



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

# git を使って、デモショップをCloud9 にcloneしよう。

```
 $ git clone https://github.com/johna1203/amazonpay-demoshop.git
Cloning into 'amazonpay-demoshop'...
remote: Counting objects: 272, done.
remote: Compressing objects: 100% (49/49), done.
remote: Total 272 (delta 27), reused 47 (delta 13), pack-reused 207
Receiving objects: 100% (272/272), 10.55 MiB | 34.62 MiB/s, done.
Resolving deltas: 100% (138/138), done.

# Amazon PayのSDKを導入する
$ cd amazonpay-demoshop/
$ ./bin/composer.phar install
Loading composer repositories with package information
Updating dependencies (including require-dev)
Package operations: 1 install, 0 updates, 0 removals
  - Installing amzn/amazon-pay-sdk-php (3.3.1): Downloading (100%)         
Writing lock file
Generating autoload files

#サーバーを起動する

$ php -S 0.0.0.0:8080
PHP 5.6.36 Development Server started at Thu Jul  5 09:25:55 2018
Listening on http://0.0.0.0:8080
Document root is /home/ec2-user/environment/amazonpay-demoshop

```

### config/config.sample.php config.local.phpに変更




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

# PHP Buil-in web server を使ってデモショップを動かそう

下記のURLを参考にインストールしてみてください。

https://www.granfairs.com/blog/cto/php-builtin-server


# Amazon Pay の PHP SDKを入れよう。

PHP のパッケージ管理を使った方が楽に導入できます。

https://github.com/amzn/amazon-pay-sdk-php





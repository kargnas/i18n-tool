# 다국어화 편의성 툴

지금 바로 시간 낭비를 줄이고 21세기 개발생태계의 선진문물을 어서 빨리 수용해보세요!

3개가 넘어가는 언어를 관리 할 때는 새로운 String 의 추가나 기존 문구의 수정 요청 작업이 여간 힘든게 아닙니다. 툴 없이 작업을 했을 때는 느끼기 힘들지만, 툴을 통해 그 작업의 편리성을 알게 되면 툴을 쓰지 않고서는 수동 작업은 꿈꿀 수 없을 정도로 힘든 작업이었다는 것을 느끼게 됩니다.

이 툴은 새로운 String 의 추가, 번역, 그리고 추가 번역이 필요 한 것의 필터링, 각 언어별로 번역이 아직 덜된 것의 필터링 기능등이 있습니다. 기획자가 쉽게 수정을 하고 개발자가 쉽게 반영을 할 수 있는 것을 목표로 하였으며, 기획자가 텍스트를 수정하고 업로드 버튼만 누르면 앱 개발 Git 저장소에 수정된 텍스트가 Commit 됩니다.

외부 번역 인력과의 효율적인 업무 연계를 위해 엑셀 다운로드 기능이 있으며, 그 엑셀 파일로 번역된 내용을 다시 언어툴로 일괄 입력 하는 기능도 있습니다. 이를 통해 개발자는 다국어화 파일을 건드리면서 쓸때 없이 의미 없는 시간을 낭비하지 않게 되어, 개발 속도에 훨씬 더 박차를 가할 수 있게 되고 정신의 황폐화를 방지할 수 있게 됩니다.

제일 하단에는 이외 여러 툴과 서비스를 소개해뒀습니다.

> ![Example](http://u.zz.gg/s/141202/115073a1a.png)

## 지원하는 언어 포맷
- **.strings**: Objective-C/Swift
- **.xml**: Java, Android
- **.properties**: Java, Spring Framework

## 스크린샷
![스크린샷](http://u.zz.gg/s/141111/1146ec35c.png)

## TODO
- iOS, Android, Java 가 모두 클래스로 선언되어 있는데, 통합 한 뒤 resourceType 설정만을 이용해서 지정 할 수 있도록 개선.
- %1$s 등의 변수 검증 기능

# 설치

## 요구사항
- PHP 웹서버 환경 (NginX 또는 Apache2)
- PHP 버전 최소 5.3 이상, 5.5 이상 권장.
- SQLITE 환경이므로 DB 설치 필요 없음. (mysql 필요 없음)
- Bower 설치 (NPM)
- bitbucket 에 대상 프로그램 소스가 있어야함.
- Linux 와 Mac에서 테스트되었습니다. 아래 설명은 우분투 기준입니다.

## 리눅스 서버 세팅
### 웹서버(NGINX) 및 PHP 설치
```bash
$ sudo apt-get -y install python-software-properties
$ sudo add-apt-repository ppa:ondrej/php5
$ sudo apt-get update
$ sudo apt-get upgrade
$ apt-get install -y nginx php5-fpm php5-dev php5-curl php5-gd php5-cli make git
```
- 위의 방식으로 설치할 경우 PHP 5.5 버전이 설치됩니다.

### JS 의존성 관리 툴 설치
- Javascript 의존성 관리 툴인 bower 을 이용하고 있습니다. 설치 방법은 아래와 같습니다.
- NPM을 먼저 설치해야 하니, 아래와 같이 설치합니다.
```bash
$ sudo apt-get install python-software-properties curl
$ sudo add-apt-repository ppa:chris-lea/node.js
$ sudo apt-get update
$ sudo apt-get install nodejs
$ curl https://www.npmjs.org/install.sh | sudo sh
```
- NPM에서 bower 설치
```bash
$ npm install -g bower
```

## 소스설치
먼저 github 의 소스를 서버에 복사해야합니다.

### 언어툴 소스 설치
- 아래 명령어를 이용해 원하는 디렉토리에 소스를 클론합니다.

  ```bash
  $ git clone git@github.com:kargnas/i18n-tool.git
  ```

- SQLITE 파일은 file/db.sqlite 로 생성이 됩니다. SQLITE 조작을 위해 아래와 같은 명령어 입력을 통해 file 디렉토리의 퍼미션을 쓰기 가능으로 변경합니다.

  ```bash
  $ chmod 777 -R i18n-tool/file
  ```

그 다음, 웹서버와 클론받은 PHP 소스를 연결시키는 과정으로 넘어갑니다.

### 웹서버와 언어툴 PHP 소스 디렉토리 연결
NGINX와 APACHE2 두 웹서버중 아무 서버나 설치 되어 있어도 상관 없습니다. 두개의 경우 모두 설명합니다.

#### NGINX가 설치 되있을 경우
- */etc/nginx/sites-enabled/i18n* 파일을 다음과 같이 수정

  ```conf
  server {
    listen   80;
    access_log off;
    server_name i18n.exmaple.com;
    root /설치한 디렉토리/i18n-tool/app;
    index index.php;
    location ~ \.php$ {
      fastcgi_pass   unix:/var/run/php5-fpm.sock;
      fastcgi_index  index.php;
      fastcgi_param  SCRIPT_FILENAME  /scripts$fastcgi_script_name;
      fastcgi_buffers 256 16k;
      fastcgi_buffer_size 32k;
      fastcgi_max_temp_file_size 0;
      include         fastcgi_params;
    }
  }
  ```

- **/etc/init.d/nginx restart** 를 실행. (nginx 재시작)

#### 아파치가 설치되어 있을 경우
- */etc/apache2/sites-enabled/i18n* 파일을 다음과 같이 수정

  ```apache
  <VirtualHost *:8088>
    ServerAdmin adm@example.com
    ServerName i18n.example.com
    DocumentRoot /설치한 디렉토리/i18n-tool/app
    
    php_admin_value display_errors Off
    
    ErrorLog  ${APACHE_LOG_DIR}/i18n-error.log
    CustomLog ${APACHE_LOG_DIR}/i18n-access.log combined
  </VirtualHost>
  ```

- **/etc/init.d/apache2 restart** 를 실행. (아파치 재시작)

웹서버 연동 및 설정이 끝났습니다. 이제 소스만 만지는 단계입니다.

### 소스 디렉토리로 이동하여 의존성 관리 툴 업데이트 하기
#### Composer 업데이트
- PHP 는 다른 서버 언어와 비슷하게 **Composer**라는 의존성 관리 툴이 있습니다. (CocoaPods/XCode, NPM/node)
- 설치한 디렉토리로 이동하여 composer.phar 을 아래와 같은 명령어로 실행합니다.

  ```
  ./composer.phar update
  ```

#### Bower 업데이트
- JS 의 의존성 관리 도구 입니다. Bower 는 위에서 이미 설치를 했으니, 소스 디렉토리에서 아래 명령어로 패키지들을 설치 합니다.
- `bower install`

### 언어 툴 환경설정
- `file/config.sample.yml` 파일을 `file/config.yml` 으로 복사하여 환경설정을 할 수 있습니다.

## 문제 해결
### PHP 오류 로그 확인
- 아파치: **/var/log/apache2/i18n-error.log** 에서 확인 할 수 있습니다.
- NginX: **/etc/php5/fpm/pool.d/www.conf** 파일에서 아래의 내용을 추가하면 PHP 에러 로그를 남길 수 있습니다.

  ```
  php_admin_value[error_log] = /var/log/로그파일명.log
  ```

### 페이지에 아무것도 안나올 때
- composer 업데이트를 하지 않은 경우 아무것도 나오지 않고 PHP 오류 로그에서 클래스 찾을 수 없음 에러가 납니다.

### 텍스트가 겹쳐보이고 레이아웃이 깨져있을 때
- bower 업데이트를 하지 않은 경우

# 비슷한 이웃 서비스 소개
- [Crowd In](http://crowdin.net/)
- [Google Translator Toolkit](http://translate.google.com/toolkit)
- [Transifed](https://www.transifex.com/)

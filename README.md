# SHIT
HIT internal one-day project - Steven Hack Into This


## Front-End ##
用 PHP / JS 撰寫 UI：用 jquery / bootstrap 來寫前端 UI 的基本框架。

## 系統 ##
系統層都使用 Python 來撰寫 API 層的東西，包含建置 AP、分析封包內容。


## Back-End ##
用 nginx 來跑 PHP，當做 web server。

使用 sqlite 儲存所有操作的資料，包含但不限於：連線 device 名稱，重要 POST 資訊 ...etc
；使用 memcache 來存中間暫存資料，加速 I/O 存取。

![Image of Yaktocat](http://i.imgur.com/TuFY6C3.jpg)

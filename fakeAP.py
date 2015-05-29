#! /usr/bin/env python2
#! Copyright (C) 2015-2015 Hack Stuff. All right reserved.
#
# Author : 2015/05 cmj<cmj@cmj.tw>

class SHIT_AP(object):
	def __init__(self, conf="fakeAP.conf"):
		with open(conf) as fd:
			conf = fd.read().split('\n')
		self.conf = [_ for _ in conf if _ and not _.startswith('#')]
	def __call__(self, action, args):
		getattr(self, action)(args)

	def start(self, args):
		self.hostapdStart()
		self.dnsmasqStart()
	def stop(self, args):
		for serv in ("hostapd", "dnsmasq"):
			self.service(serv, start=False)
	def scan(self, args):
		result = self._scan_(args)
		for idx, ret in enumerate(result):
			print "{0:<4}{1:<20}{3:8}-{2:>3}  {4:16}".format(idx+1, *ret)

	def _scan_(self, args=None):
		import commands, re

		wifi = self.ScanWiFi['NAME']
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
		return result

	def hostapdStart(self, path="/etc/hostapd/hostapd.conf"):
		with open(path, 'w') as fd:
			conf  = "bssid={MAC}\n"
			conf += "ssid={SSID}\n"
			conf += "interface={NIC}\n"
			conf += "channel={CHANNEL}\n"
			conf += "driver={DRIVER}\n"
			fd.write(conf.format(**self.conf))

		self.service("hostapd")
	def dnsmasqStart(self, path="/etc/dnsmasq.conf"):
		with open(path, 'w') as fd:
			conf  = "dhcp-leasefile=/tmp/dnsmasq.lease\n"
			conf += "interface={NIC}\n"
			conf += "dhcp-range={DHCP_RANGE},12h\n"	# DHCP IP range
			conf += "dhcp-option=1,255.255.255.0\n"	# subnet mask
			conf += "dhcp-option=28,{BROADCAST}\n"	# broadcast
			conf += "dhcp-option=3,{IP}\n"			# default gateway
			conf += "dhcp-option=6,{IP}\n"			# DNS
			fd.write(conf.format(**self.conf))
		self.service('dnsmasq')
	def service(self, name, start=True):
		import commands

		if 'service' == self.conf['SERVICE_TOOL']:
			cmd = "%s {0} %s" %(self.conf['SERVICE_TOOL'], 'start' if start else 'stop')
		elif 'systemctl' == self.conf['SERVICE_TOOL']:
			cmd = "%s %s {0}" %(self.conf['SERVICE_TOOL'], 'start' if start else 'stop')
		print cmd.format(name)
		return commands.getoutput(cmd.format(name))
	@property
	def conf(self):
		return self._conf_
	@conf.setter
	def conf(self, v):
		import re

		self._conf_ = {"CHANNEL": 4}

		conf = {_.split('=')[0]: '='.join(_.split('=')[1:]) for _ in v}
		for _ in conf:
			if re.match(r'(["\']).*?\1', conf[_]):
				conf[_] = conf[_][1:-1]
		self._conf_.update(conf)

		## Automatically append the configure
		self._conf_["MAC"] = self.APWiFi['MAC']
		self._conf_["NIC"] = self.APWiFi['NAME']
		self._conf_["BROADCAST"] = "{0}.{1}.{2}.255".format(*self._conf_["IP"].split('.'))
		self._conf_["DHCP_RANGE"] = "{0}.{1}.{2}.100,{0}.{1}.{2}.200"
		self._conf_["DHCP_RANGE"] = self._conf_["DHCP_RANGE"].format(*self._conf_["IP"].split('.'))
	@property
	def WiFiList(self, path='/sys/class/net'):
		import os
		return [_ for _ in os.listdir(path) if _.startswith('wlan')]
	@property
	def APWiFi(self, path='/sys/class/net'):
		if hasattr(self, "_APWiFi_"):
			return self._APWiFi_

		for wifi in self.WiFiList:
			with open('{0}/{1}/address'.format(path, wifi)) as fd:
				mac = fd.read()
				if mac.startswith(self._conf_['MACPREFIX']):
					self._APWiFi_ = {"NAME": wifi, "MAC": mac.strip()}
					return self._APWiFi_
		else:
			return None
	@property
	def ScanWiFi(self, path='/sys/class/net'):
		if hasattr(self, "_ScanWiFi_"):
			return self._ScanWiFi_

		for wifi in self.WiFiList:
			with open('{0}/{1}/address'.format(path, wifi)) as fd:
				mac = fd.read()
				if not mac.startswith(self._conf_['MACPREFIX']):
					self._ScanWiFi_ = {"NAME": wifi, "MAC": mac.strip()}
					return self._ScanWiFi_
		else:
			return None
	@property
	def PopularESSID(self):
		ret = [_[-1] for _ in self._scan_()]
		ret = {_: ret.count(_) for _ in set(ret)}
		tmp = ""
		for _ in ret:
			if not tmp:
				tmp = _
			elif ret[tmp] <= ret[_]:
				tmp = _
		return tmp
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

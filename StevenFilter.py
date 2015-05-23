#!/usr/bin/env python2
#!coding:utf-8
from scapy.all import *
import ConfigParser


class Steven5538:
	def __init__(self):
		self.install   = None
		self.macprefix = None
		self.ip        = None
		self.channel   = None
		self.driver    = None
		self.keyword   = None
		self.readConfig()
		self.printConfig()

	def readConfig(self):
		'''
			讀Config檔
		'''
		parser = ConfigParser.ConfigParser()
		parser.read('fakeAP.conf.example')
		self.install = parser.get('SYSTEM','INSTALL')
		self.macprefix = parser.get('WIFI','MACPREFIX')
		self.ip = parser.get('WIFI','IP')
		self.channel = parser.get('WIFI','CHANNEL')
		self.driver = parser.get('WIFI','DRIVER')
		self.keyword = parser.get('SETTING','KEYWORD')
		if ',' in self.keyword:
			self.keyword = self.keyword.split(',')

	def printConfig(self):
		print 'Install ', self.install
		print 'Macprefix', self.macprefix
		print 'IP', self.ip
		print 'Channel', self.channel
		print 'Driver', self.driver
		print 'Keyword', self.keyword

if __name__ == '__main__':
	steven5538 = Steven5538()

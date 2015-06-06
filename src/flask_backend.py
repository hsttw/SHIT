#!/usr/bin/env python2
#! Copyright (C) 2015-2015 Hack Stuff. All right reserved.
#
# Author : 2015/05 cmj<cmj@cmj.tw>

from flask import Flask
from flask_restful import Resource, Api

__all__ = ['StevenServer', 'StevenCTL', 'Flask', 'Api']
class StevenServer(Resource):
	""" StevenServer - The Flask server used in SHIT, connected via Queue """

	def get(self):
		import json

		try:
			data, queue = [], self.queue
			while queue.qsize():
				data.append(queue.get())
			data = json.dumps(data)
		except Exception as e:
			data = "resource not found {0}".format(e)

		return "SHIT - Steven Hack Into This : {0}".format(data)
class StevenCTL(Resource):
	""" StevenCTL - The Flask server usedi in SHIT and control lower-end system """
	def get(self, src):
		import json

		data = []
		if 'client' == src:
			with open('/tmp/dnsmasq.lease') as fd:
				client = fd.read()
			client = [_ for _ in client.split('\n') if _]
			client = [_.split() for _ in client]
			client = {_[3]: {'MAC': _[1], 'IP': _[2]} for _ in client}
			data = json.dumps(client)

		return "SHIT - Steven Hack Into This : {0}".format(data)


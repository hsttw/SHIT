#! /usr/bin/env python
#! coding: utf-8
# Copyright (c) 2015-2015 cmj<cmj@cmj.tw>. All right reserved.

from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy import Column, Integer, String, DateTime, Index, BLOB
__all__ = ['Base', 'HTTP_RAW', 'MariaDB']
Base = declarative_base()
class HTTP_RAW(Base):
	__tablename__ = 'http'

	id        = Column(Integer, primary_key=True)
	src       = Column(String)
	dst       = Column(String)
	payload   = Column(BLOB)
	timestamp = Column(DateTime)

	def __init__(cls, src, dst, payload):
		from datetime import datetime

		cls.src = src
		cls.dst = dst
		cls.payload   = payload
		cls.timestamp = datetime.now()
	def __repr__(cls):
		return '<HTTP> {0.src:16} -> {0.dst:16} - {1}'.format(cls, cls.payload.split('\n')[0])
class MariaDB(object):
	def __init__(cls, db, encoding='utf-8'):
		import getpass, sqlalchemy
		from sqlalchemy import create_engine, MetaData
		from sqlalchemy.orm import sessionmaker

		engine   = 'sqlite:///{0}'.format(db)
		engine   = create_engine(engine, encoding=encoding, convert_unicode=False)

		engine.raw_connection().connection.text_factory = lambda x: str

		metadata = MetaData(engine)

		Base.metadata.create_all(engine)

		Session = sessionmaker(bind=engine)
		session = Session()

		cls.engine   = engine
		cls.session  = session

		# Inherit the session addribute to cls
		for attr in ('query', 'add', 'commit', 'rollback'):
			setattr(cls, attr, getattr(cls.session, attr))
	def __call__(cls, cmd):
		return cls.engine.execute(cmd)

	def schema(cls, table):
		if table not in cls.tables:
			raise KeyError(table)
		for _ in db('desc {0}'.format(table)):
			print _
	@property
	def tables(cls):
		return cls.engine.table_names()


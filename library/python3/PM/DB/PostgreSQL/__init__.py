#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import PM
import psycopg2
from psycopg2.extras import *
import psycopg2.extensions

# This connection should only be used by code by end code, not in libraries.
global_connection = None
def GetGlobalConnection():
	global global_connection
	if not global_connection:
		global_connection = psycopg2.connect(
			'host=%(host)s dbname=%(dbname)s user=%(user)s password=%(password)s' % PM.configurations['PostgreSQL'],
			cursor_factory = RealDictCursor
		)
	return global_connection

# This code should be used in libraries whenever possible.
autocommit_connection = None
def GetAutoCommitConnection():
	global autocommit_connection
	if not autocommit_connection:
		autocommit_connection = psycopg2.connect(
			'host=%(host)s dbname=%(dbname)s user=%(user)s password=%(password)s' % PM.configurations['PostgreSQL'],
			cursor_factory = RealDictCursor
		)
		autocommit_connection.set_isolation_level(psycopg2.extensions.ISOLATION_LEVEL_AUTOCOMMIT)
		#autocommit_connection.autocommit = True
	return autocommit_connection

# Creates a brand new connection. Only used if necessary, because will use up an additional new Postgre connection.
def CreateNewConnection():
	return psycopg2.connect(
		'host=%(host)s dbname=%(dbname)s user=%(user)s password=%(password)s' % PM.configurations['PostgreSQL'],
		cursor_factory = RealDictCursor
	)

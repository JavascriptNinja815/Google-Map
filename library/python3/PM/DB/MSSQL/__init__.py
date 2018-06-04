#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import PM
import pyodbc

# This connection should only be used by code by end code, not in libraries.
global_connection = None
def GetGlobalConnection():
	global global_connection
	if not global_connection:
		global_connection = mssql.connect(
			'DSN=%(dsn)s;UID=%(user)s;PWD=%(password)s' % PM.configurations['MSSQL'],
			cursor_factory = RealDictCursor
		)
	return global_connection

# This code should be used in libraries whenever possible.
autocommit_connection = None
def GetAutoCommitConnection():
	global autocommit_connection
	if not autocommit_connection:
		autocommit_connection = mssql.connect(
			'DSN=%(dsn)s;UID=%(user)s;PWD=%(password)s' % PM.configurations['MSSQL'],
			autocommit = True,
			cursor_factory = RealDictCursor
		)
	return autocommit_connection

# Creates a brand new connection. Only used if necessary, because will use up an additional new Postgre connection.
def CreateNewConnection():
	return mssql.connect(
		'DSN=%(dsn)s;UID=%(user)s;PWD=%(password)s' % PM.configurations['MSSQL'],
		autocommit = False,
		cursor_factory = RealDictCursor
	)

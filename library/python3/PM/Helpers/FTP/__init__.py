#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import ftplib

def downloadFile(host, user, password, download_filename, saveas_filename):
	ftp = ftplib.FTP(
		host = host,
		user = user,
		passwd = password
	)
	fp = open(saveas_filename, 'w')
	ftp.retrbinary('RETR ' + download_filename, fp.write)
	fp.close()

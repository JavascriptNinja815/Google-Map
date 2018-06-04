#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import pyodbc
import csv
import datetime
import decimal
import ftplib
import os.path

DSN = r'Sage Pro ERP'
USER = r'sa'
PASS = r'bear1ngs'

supplier_code = 'CGU01'

HOST = 'casterdepot.magemojo.com'
USERNAME = 'colsongroup'
PASSWORD = '0qpl2zc2ptt1o'
FILENAME = 'Caster Deport Ship Date Change.csv'

# Determine the file path for saving files locally.
file_path = os.path.dirname(os.path.abspath(__file__))

mssql = pyodbc.connect('DSN=' + DSN + ';UID=' + USER + ';PWD=' + PASS)
cursor = mssql.cursor()

print('Connecting to FTP server...')
ftp = ftplib.FTP(
	host = HOST,
	user = USERNAME,
	passwd = PASSWORD
)
print('\tDone.')

# Open a pointer to the file we're going to write.
print('Downloading file...')
filename_path = file_path + '\\' + FILENAME
fp = open(filename_path, 'w')
ftp.retrbinary('RETR ' + FILENAME, fp.write) # Retrieve and write to the file.
fp.close() # Close the file now that it has been written to.
fp = open(filename_path, 'r') # Open the file in read only mode.
print('\tDone.')

print('Importing the data...')
reader = csv.DictReader(fp, fieldnames = [
	'CD PO Number', 'CG Order #', 'Part Number', 'Part Description', 'Qty',
	'Shipped to Date Qty', 'Previous Ship Date', 'New Ship Date', 'Ship Date Revision'
], delimiter = ',')
ct = 0
for row in reader:
	ct += 1
	if ct == 1:
		continue # Skip heading row.
	print str(ct) + ' :: purno ' + row['CD PO Number'] + ' / vendor sono ' + row['CG Order #'] + ' / ' + row['Part Number']

	purno = row['CD PO Number'].strip()
	vendor_ordernum = row['CG Order #'].strip()

	part_number = row['Part Number'].strip()
	if len(part_number) > 60:
		part_number = part_number[:60]

	description = row['Part Description'].strip()
	if len(description) > 60:
		description = description[:60]

	quantity = 0
	try:
		quantity = format(decimal.Decimal(row['Qty'].strip().replace(',', '')), '.0f')
	except Exception as e:
		print '\tInvalid quantity, not int'

	shipped_to_date = 0
	try:
		shipped_to_date = format(decimal.Decimal(row['Shipped to Date Qty'].strip().replace(',', '')), '.0f')
	except Exception as e:
		print '\tInvalid shipped_to_date, not int'

	prev_ship_date = None
	if row['Previous Ship Date'].strip():
		try:
			prev_ship_date = datetime.datetime.strptime(row['Previous Ship Date'], '%m/%d/%y')
		except Exception as e:
			print('\tInvalid prev_ship_date')

	new_ship_date = None
	if row['New Ship Date'].strip():
		try:
			new_ship_date = datetime.datetime.strptime(row['New Ship Date'], '%m/%d/%y')
		except Exception as e:
			print('\tInvalid new_ship_date')

	ship_date_revision = row['Ship Date Revision'].strip()

	cursor.execute("""
		SELECT
			po_datechanges.po_datechange_id
		FROM
			PRO01.dbo.po_datechanges
		WHERE
			po_datechanges.supplier_code = ?
			AND
			po_datechanges.purno = ?
			AND
			po_datechanges.vendor_ordernum = ?
			AND
			po_datechanges.part_number = ?
			AND
			po_datechanges.description = ?
			AND
			po_datechanges.quantity = ?
			AND
			po_datechanges.shipped_to_date = ?
			AND
			po_datechanges.prev_ship_date """ + ("= '" + str(prev_ship_date) + "'" if prev_ship_date else 'IS NULL') + """
			AND
			po_datechanges.new_ship_date """ + ("= '" + str(new_ship_date) + "'" if new_ship_date else 'IS NULL') + """
	""", (
		supplier_code,
		purno,
		vendor_ordernum,
		part_number,
		description,
		quantity,
		shipped_to_date,
	))
	existing = cursor.fetchone()
	if existing:
		print '\tAlready exists, skipping'
		continue
	
	print '\tNew entry, inserting...'
	cursor.execute("""
		INSERT INTO
			PRO01.dbo.po_datechanges
		(
			supplier_code,
			purno,
			vendor_ordernum,
			part_number,
			description,
			quantity,
			shipped_to_date,
			prev_ship_date,
			new_ship_date,
			ship_date_rev
		) VALUES (
			?,
			?,
			?,
			?,
			?,
			?,
			?,
			?,
			?,
			?
		)
	""", (
		supplier_code,
		purno,
		vendor_ordernum,
		part_number,
		description,
		quantity,
		shipped_to_date,
		prev_ship_date,
		new_ship_date,
		ship_date_revision
	))
	mssql.commit()

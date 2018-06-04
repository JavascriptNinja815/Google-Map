#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import pyodbc
import csv
import decimal
import datetime
import ftplib
import os.path

DSN = r'Sage Pro ERP'
USER = r'sa'
PASS = r'bear1ngs'

supplier_code = 'CGU01'

HOST = 'casterdepot.magemojo.com'
USERNAME = 'colsongroup'
PASSWORD = '0qpl2zc2ptt1o'
FILENAME = 'Caster Depot Quote.csv'

brands_lookup = {
	'SHEPHERD BRAND': 'Shepherd',
	'COLSON BRAND': 'Colson',
	'MEDCASTER BRAND': 'MedCaster',
	'ALBION BRAND': 'Albion',
	'PEMCO BRAND': 'Pemco',
	'NO BRAND ASSIGNED': '(blank)',
	'JARVIS BRAND': 'Jarvis',
	'FAULTLESS BRAND': 'Faultless',
	'BASSICK BRAND': 'Bassick',
}

# Determine the file path for saving files locally.
file_path = os.path.dirname(os.path.abspath(__file__))

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
mssql = pyodbc.connect('DSN=' + DSN + ';UID=' + USER + ';PWD=' + PASS)
cursor = mssql.cursor()

fp = open('Caster Depot Quote.csv', 'r')
reader = csv.DictReader(fp, fieldnames = [
	'Part Number', 'Part Description', 'CG Quote #', 'Brand', 'Quote Date', # Date formatted: mm/dd/yy
	'Expires Date', 'Quote Qty', 'Price', 'FOB', 'Quote Notes'
], delimiter = ',')

ct = 0
for row in reader:
	ct += 1
	if ct == 1:
		continue # Skip heading row.
	print str(ct) + ' :: ' + row['Part Number'] + ' - ' + row['Part Description']

	mpn = row['Part Number'].strip()
	print '\tMPN: ' + mpn

	if row['Brand'].strip() in brands_lookup:
		brand = brands_lookup[row['Brand'].strip()]
	else:
		brand = row['Brand'].strip().lower().replace(' brand', '').title()
	print '\tBrand: ' + brand

	description = row['Part Description'].strip()
	if len(description) > 50:
		print '\tDescription too long, truncating to 50 characters...'
		description = description[:50]
	print '\tDescription: ' + description

	try:
		price = format(decimal.Decimal(row['Price'].replace(',', '').strip()), '.2f')
		print '\tPrice: ' + str(price)
	except Exception as e:
		print '\tInvalid Price, skipping... ' + row['Price']
		continue # Invalid price encountered.

	try:
		quantity = format(decimal.Decimal(row['Quote Qty'].replace(',', '').strip()), '.0f')
		print '\tQuantity: ' + str(quantity)
	except Exception as e:
		print '\tInvalid Quantity, skipping... ' + str(row['Quote Qty'])
		continue # Invalid quantity encountered.

	try:
		effective_date = datetime.datetime.strptime(row['Quote Date'], '%m/%d/%y')
		print '\tEffective Date: ' + str(effective_date)
	except Exception as e:
		print '\tInvalid Effective Date, skipping... ' + str(row['Quote Date'])
		continue # Invalid date, skip.

	try:
		expires_date = datetime.datetime.strptime(row['Expires Date'], '%m/%d/%y')
		print '\tExpires Date: ' + str(expires_date)
	except Exception as e:
		print '\tInvalid Expires Date, skipping... ' + str(expires_date)
		continue # Invalid date, skip.

	memo = 'NULL'
	if row['Quote Notes'].strip():
		memo = "'" + row['Quote Notes'].strip().replace("'", "''") + "'" # Escape apostrophes.
		print '\tMemo:' + memo

	# Check if price_item entry exists.
	cursor.execute("""
		SELECT
			price_items.price_item_id
		FROM
			PRO01.dbo.price_items
		WHERE
			price_items.supplier_code = ?
			AND
			price_items.brand = ?
			AND
			price_items.part_number = ?
			AND
			price_items.description = ?
	""", (
		supplier_code,
		brand,
		mpn,
		description,
	))
	price_item_id = cursor.fetchone()
	if price_item_id:
		price_item_id = price_item_id[0]
		print '\tPrice Items entry exists...'
	else:
		print '\tPrice Items entry doesnt exist, inserting it...'
		# Attempt to resolve item.
		cursor.execute("""
			SELECT
				icitem.item
			FROM
				PRO01.dbo.icitem
			WHERE
				icitem.item = ?
				OR
				icitem.itmdesc = ?
				OR
				icitem.item = ?
				OR
				icitem.itmdesc = ?
		""", (
			mpn,
			mpn,
			description,
			description,
		))
		existing_item = cursor.fetchone()
		print '\t\titem: ' + str(existing_item)
		item = 'NULL'
		if existing_item:
			item = "'" + existing_item[0].strip().replace("'", "''") + "'"

		# Create the price_item entry.
		cursor.execute("""
			INSERT INTO
				PRO01.dbo.price_items
			(
				supplier_code,
				part_number,
				description,
				brand,
				item
			)
			OUTPUT INSERTED.price_item_id
			VALUES (
				?,
				?,
				?,
				?,
				""" + item + """
			)
		""", (
			supplier_code,
			mpn,
			description,
			brand
		))
		price_item_id = cursor.fetchone()[0]
		mssql.commit()
	print '\t\tprice_item_id: ' + str(price_item_id)

	# Check if price_quantities entry exists.
	cursor.execute("""
		SELECT
			price_quantities.price_quantity_id
		FROM
			PRO01.dbo.price_quantities
		WHERE
			price_quantities.quote_pricing = 1
			AND
			price_quantities.price_item_id = ?
			AND
			price_quantities.quantity = ?
			AND
			price_quantities.price = ?
			AND
			price_quantities.effective_date = ?
			AND
			price_quantities.expires_date = ?
	""", (
		price_item_id,
		quantity,
		price,
		effective_date,
		expires_date,
	))
	price_quantity_id = cursor.fetchone()

	if price_quantity_id:
		price_quantity_id = price_quantity_id[0]
		print '\tPrice Quantities entry exists, skipping...'
		print '\t\tprice_quantity_id: ' + str(price_quantity_id) 
		continue
	print '\tPrice Quantities entry does not exist, inserting it...'

	cursor.execute("""
		INSERT INTO
			PRO01.dbo.price_quantities
		(
			price_item_id,
			quantity,
			price,
			quote_pricing,
			effective_date,
			expires_date,
			memo
		)
		OUTPUT INSERTED.price_quantity_id
		VALUES (
			?,
			?,
			?,
			1,
			?,
			?,
			?
		)
	""", (
		price_item_id,
		quantity,
		price,
		effective_date,
		expires_date,
		memo
	))
	price_quantity_id = cursor.fetchone()[0]
	mssql.commit()
	print '\t\tprice_quantity_id: ' + str(price_quantity_id)

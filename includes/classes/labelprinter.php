<?php

class LabelPrinter {
	private $printer;
	private $location;

	public function __construct($printer_id = False) {
		$this->setPrinter($printer_id);
	}

	public function setPrinter($printer_id) {
		$db = DB::get();

		// Query for printer data.
		$grab_printer = $db->query("
			SELECT
				printers.printer,
				printers.location_id,
				printers.real_printer_name
			FROM
				" . DB_SCHEMA_INTERNAL . ".printers
			WHERE
				printers.printer_id = " . $db->quote($printer_id) . "
		");
		$this->printer = $grab_printer->fetch();

		// Query for location data specific to the printer.
		$grab_location = $db->query("
			SELECT
				RTRIM(LTRIM(icloct.loctid)) AS loctid,
				RTRIM(LTRIM(icloct.locdesc)) AS locdesc
			FROM
				" . DB_SCHEMA_ERP . ".icloct
			WHERE
				icloct.loctid = " . $db->quote($this->printer['location_id']) . "
		");
		$this->location = $grab_location->fetch();
	}

	public function getPrinterData() {
		return $this->printer;
	}

	public function getLocationData() {
		return $this->location;
	}

	public function getBins($bin_like) {
		$db = DB::get();

		$grab_bins = $db->prepare("
			SELECT DISTINCT
				iciloc.recpbin
			FROM
				" . DB_SCHEMA_ERP . ".iciloc
			WHERE
				iciloc.loctid = " . $db->quote($this->location['loctid']) . "
				AND
				iciloc.recpbin != ''
				AND
				UPPER(iciloc.recpbin) LIKE UPPER(" . $db->quote($bin_like) . ")
		", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$grab_bins->execute();

		return $grab_bins;
	}

	public function getProducts($query_by, $product_like) {
		$db = DB::get();

		if($query_by == 'item') {
			$query_by_where = "UPPER(icitem.item) LIKE UPPER(" . $db->quote($product_like) . ")";
		} else if($query_by == 'bin') {
			$query_by_where = "UPPER(iciloc.recpbin) LIKE UPPER(" . $db->quote($product_like) . ")";
		}

		$query = "
			SELECT DISTINCT
				icitem.itmdesc,
				icitem.itmdes2,
				iciloc.recpbin,
				FLOOR(potran.qtyrec) AS qtyrec,
				potran.recdate,
				potran.purno,
				iciloc.loctid,
				icitem.item
			FROM
				" . DB_SCHEMA_ERP . ".icitem
			INNER JOIN
				" . DB_SCHEMA_ERP . ".iciloc
				ON
				iciloc.item = icitem.item
			LEFT JOIN
			-- INNER JOIN
				(
					SELECT
						potran.qtyrec,
						potran.recdate,
						potran.purno,
						potran.item,
						ROW_NUMBER() OVER (PARTITION BY potran.item ORDER BY potran.purno) AS rank
					FROM
						" . DB_SCHEMA_ERP . ".potran
				) potran
				ON
				potran.item = icitem.item
				AND
				potran.rank = 1
			LEFT JOIN
			-- INNER JOIN
				" . DB_SCHEMA_ERP . ".iciqty
				ON
				iciqty.item = icitem.item
			WHERE
				iciloc.loctid = " . $db->quote($this->location['loctid']) . "
				--AND
				--iciqty.qonhand > 0
				--AND
				--iciloc.recpbin != ''
				AND
				" . $query_by_where . "
			ORDER BY
				iciloc.recpbin,
				icitem.item
		";
		$grab_products = $db->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$grab_products->execute();

		return $grab_products;
	}

	public function printBins($bin_results) {
		$label = 'C:\\BarTender\\Labels\\Bin Label.btw';
		$filename = '\\\\glc-fs1\\BarTender\\Commander\\Dashboard Label Printing\\bin-' . time() . '.' . microtime() . '.xml';
		//$filename = '\\\\glc-fs1\\BarTender\\Commander\\Tests\bin-' . time() . '.xml';
		$bartender = new BarTender($label, $this->printer['real_printer_name']);
		$bartender->generateToFile($bin_results, $filename, function($row_data) {
			$new_row_data = array();
			foreach($row_data as $row_key => $row_value) {
				if(!is_numeric(trim($row_key))) {
					if($row_key == 'recdate') {
						$row_value = explode(' ', $row_value);
						$row_value = $row_value[0];
					}
					$new_row_data[trim($row_key)] = trim($row_value);
				}
			}
			return $new_row_data;
		});
	}

	public function printProducts($product_results) {
		$label = 'C:\\BarTender\\Labels\\Product Label.btw';
		$filename = '\\\\glc-fs1\\BarTender\\Commander\\Dashboard Label Printing\\product-' . time() . '.' . microtime() . '.xml';
		//$filename = '\\\\glc-fs1\\BarTender\\Commander\\Tests\product-' . time() . '.xml';
		$bartender = new BarTender($label, $this->printer['real_printer_name']);
		$bartender->generateToFile($product_results, $filename, function($row_data) {
			$new_row_data = array();
			foreach($row_data as $row_key => $row_value) {
				if(!is_numeric(trim($row_key))) {
					if($row_key == 'recdate') {
						$row_value = explode(' ', $row_value);
						$row_value = $row_value[0];
					}
					$new_row_data[trim($row_key)] = htmlentities(trim($row_value));
				}
			}
			return $new_row_data;
		});
	}

	public function printSalesOrders($so_results) {
		$label = 'C:\\BarTender\\Labels\\Product Label.btw';
		$filename = '\\\\glc-fs1\\BarTender\\Commander\\Dashboard Label Printing\\salesorder-' . time() . '.' . microtime() . '.xml';
		$bartender = new BarTender($label, $this->printer['real_printer_name']);
		$bartender->generateToFile($so_results, $filename, function($row_data) {
			$new_row_data = array();
			foreach($row_data as $row_key => $row_value) {
				if(!is_numeric(trim($row_key))) {
					if($row_key == 'recdate') {
						$row_value = explode(' ', $row_value);
						$row_value = $row_value[0];
					}
					$new_row_data[trim($row_key)] = htmlentities(trim($row_value));
				}
			}
			return $new_row_data;
		});
	}

	public function printShipToLabel($shipto_data) {
		$label = 'C:\\BarTender\\Labels\\Ship-To Label.btw';
		$filename = '\\\\glc-fs1\\BarTender\\Commander\\Dashboard Label Printing\\shipto-' . time() . '.' . microtime() . '.xml';
		$bartender = new BarTender($label, $this->printer['real_printer_name']);
		$bartender->generateToFile($shipto_data, $filename, function($row_data) {
			$new_row_data = array();
			foreach($row_data as $row_key => $row_value) {
				if(!is_numeric(trim($row_key))) {
					$new_row_data[trim($row_key)] = htmlentities(trim($row_value));
				}
			}
			return $new_row_data;
		});
	}
	
	// Prints Intelliship shipping labels.
	public function printIntellishipLabelsByBase64($printer, $base64_images) {
		$label = 'C:\\BarTender\\Labels\\Intelliship Label Base64.btw';
		$filename = '\\\\glc-fs1\\BarTender\\Commander\\Dashboard Label Printing\\intelliship-' . time() . '.' . microtime() . '.xml';
		$bartender = new BarTender($label, $printer);
		$label_data = [];
		foreach($base64_images as $base64_image) {
			$label_data[] = [
				'imagebase64' => $base64_image
			];
		}
		$bartender->generateToFile($label_data, $filename);
	}
	
	// Prints Intelliship shipping labels.
	public function printIntellishipLabelsByFilename($printer, $image_filenames) {
		$label = 'C:\\BarTender\\Labels\\Intelliship Label Filename.btw';
		$filename = '\\\\glc-fs1\\BarTender\\Commander\\Dashboard Label Printing\\intelliship-' . time() . '.' . microtime() . '.xml';
		$bartender = new BarTender($label, $printer);
		$label_data = [];
		foreach($image_filenames as $image_filename) {
			$label_data[] = [
				'imagefilename' => $image_filename
			];
		}
		$bartender->generateToFile($label_data, $filename);
	}

	public function printWorkOrders() {
		// TODO.
	}

	public function printAmazonProductLabels($asin, $amazon_part_number, $quantity, $brand){
		// Set the proper label and filename
		$label = 'c:\\BarTender\\Labels\\DoRodo\\Amazon_Product_2x1.btw';
		$filename = '\\\\glc-fs1\\BarTender\\Commander\\Dashboard Label Printing\\amazon-' . time() . '.' . microtime() . '.xml';

		// This can probably be done better in another class.
		$xml = '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
		$xml .= '<XMLScript Version="2.0">'.PHP_EOL;
		$xml .= '	<Command Name="Job1">'.PHP_EOL;
		$xml .= '		<Print>'.PHP_EOL;
		$xml .= '			<Format>'.$label.'</Format>'.PHP_EOL;
		$xml .=	'			<PrintSetup>'.PHP_EOL;
		$xml .=	'				<IdenticalCopiesOfLabel>'.$quantity.'</IdenticalCopiesOfLabel>'.PHP_EOL;
		$xml .=	'				<Printer>\\\\glc-fs1\DoRodo_LP_2824</Printer>'.PHP_EOL;
		$xml .= '			</PrintSetup>'.PHP_EOL;
		$xml .= '			<NamedSubString Name="ASIN">'.PHP_EOL;
		$xml .= '				<Value>'.$asin.'</Value>'.PHP_EOL;
		$xml .= '			</NamedSubString>'.PHP_EOL;
		$xml .= '			<NamedSubString Name="amazon_part_number">'.PHP_EOL;
		$xml .= '				<Value>'.$amazon_part_number.'</Value>'.PHP_EOL;
		$xml .= '			</NamedSubString>'.PHP_EOL;
		$xml .= '			<NamedSubString Name="brand">'.PHP_EOL;
		$xml .= '				<Value>'.$brand.'</Value>'.PHP_EOL;
		$xml .= '			</NamedSubString>'.PHP_EOL;
		$xml .= '		</Print>'.PHP_EOL;
		$xml .=	'	</Command>'.PHP_EOL;
		$xml .=	'</XMLScript>';

		// Write XML to file.
		// TODO: Remove this.
		// TMP: Write locally.
		//$fn = 'tmp.xml';
		file_put_contents($filename, $xml);

	}
}

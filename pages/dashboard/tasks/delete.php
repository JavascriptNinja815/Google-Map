<?php

$session->ensureLogin();

$grab_brand = $db->query("
	SELECT
		brands.brand_id
	FROM
		public.brands
	WHERE
		brands.brand_id = " . $db->quote($_POST['brand_id']) . "
		AND
		brands.account_id = " . $db->quote($session->login['account_id']) . "
");
if(!$grab_brand->rowCount()) {
	print json_encode([
		'success' => False,
		'message' => 'Invalid brand specified'
	]);
	exit;
}
$brand = $grab_brand->fetch();

$grab_products = $db->query("
	SELECT
		COUNT(*) AS count
	FROM
		public.products
	WHERE
		products.brand_id = " . $db->quote($brand['brand_id']) . "
");
$product_counts_result = $grab_products->fetch();
$product_count = $product_counts_result['count'];

if($product_count > 0) {
	print json_encode([
		'success' => False,
		'message' => 'Cannot delete brand, currently has ' . $product_count . ' product(s) associated with it'
	]);
	exit;
}

$delete_brand = $db->query("
	DELETE FROM
		public.brands
	WHERE
		brands.brand_id = " . $db->quote($brand['brand_id']) . "
		AND
		brands.account_id = " . $db->quote($session->login['account_id']) . "
");
if(!$delete_brand->rowCount()) {
	print json_encode([
		'success' => False,
		'message' => 'Deleting brand failed'
	]);
}

print json_encode([
	'success' => True
]);

<?php

$session->ensureLogin();
$session->ensureRole('Terminology');

$args = array(
	'title' => 'Terminology',
	'breadcrumbs' => array(
		'Terminology' => BASE_URI . '/dashboard/account/terminology'
	)
);

Template::Render('header', $args, 'account');

$grab_terminology = "
	SELECT
		terminology.terminology_id,
		terminology.name,
		terminology.aliases,
		terminology.description
	FROM
		" . DB_SCHEMA_ERP . ".terminology
	ORDER BY
		terminology.name
";
$grab_terminology = $db->prepare($grab_terminology, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)); // Cursor args passed required for retrieving rowCount.
$grab_terminology->execute();

?>

<fieldset>
	<legend>
		<div class="padded-x">Terminology</div>
	</legend>
	<table id="terminology-container" class="table table-small table-striped table-hover columns-sortable columns-filterable headers-sticky columns-draggable">
		<thead>
			<tr>
				<th class="filterable sortable">Term</th>
				<th class="filterable sortable">Description</th>
				<th class="filterable sortable">Alias(es)</th>
			</tr>
		</thead>
		<tbody>
			<?php
			while($term = $grab_terminology->fetch()) {
				?>
				<tr class="stripe">
					<td class="content"><?php print htmlentities($term['name']);?></td>
					<td class="content"><?php print $term['description'];?></td>
					<td class="content"><?php print implode('<br />', explode(',', $term['aliases']));?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</fieldset>

<?php Template::Render('footer', 'account');

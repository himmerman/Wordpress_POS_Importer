<?php
// PROD
$config = array('import_dir' => '/home/heartfelt_trans/heartfeltpages.com/upload/csv/',
		'import_db' => array(
			'user' => 'himmer',
			'name' => 'heartfel_wrdp2',
			'pass' => 'underhill14',
			'host' => 'mysql.itsrainingsunshine.com'
			),
		'import_image_dir' => '/home/heartfelt_trans/heartfeltpages.com/upload/images/',
		'url' => "http://www.heartfeltpages.com/"
		 );

// DEV
// $config = array('import_dir' => 'csv/',
// 		'import_db' => array(
// 			'user' => 'himmerma_admin',
// 			'name' => 'himmerma_wp3',
// 			'pass' => 'underhill14',
// 			'host' => 'localhost'
// 			),
// 		'import_image_dir' => '/home/himmerman/public_html/himmerweb.com/heartfelt/upload/images/',
// 		'url' => "http://himmerweb.com/heartfelt/"
// 		 );


// open new file in the import_dir

// connect to db
$db_conf = $config['import_db'];
$db = new mysqli($db_conf['host'], $db_conf['user'], $db_conf['pass'], $db_conf['name']);
// var_dump($db);
// read each row
$file = $config['import_dir'].'zencart.csv';

$prod_fh = fopen($file, 'r');

$head = fgetcsv($prod_fh);

while ($row = fgetcsv($prod_fh)) {
	// 
	print("<pre>");
	

	$prod_name = $row[2];
	$prod_name = trim(preg_replace('/[^a-z0-9]+/i', ' ', $prod_name));
	$post_name = preg_replace('/[^a-zA-Z0-9\']/', '-', strtolower($prod_name));
	// $post_name = str_replace(' ', '-', $post_name);
	$post_name = str_replace("'", '', $post_name);
	$post_name = trim($post_name);

	print_r($post_name);
	// die();
	$result = $db->query("SELECT * FROM wp_posts WHERE post_title = '{$prod_name}'");

	if ($prod_name == "") {
		continue;
	}

	if ($result->num_rows > 0) {
		// UPDATE PRODUCT
		$product = $result->fetch_array();
		echo "UPDATING " . $prod_name;
		// var_dump($product);
		// echo $prod_name;

		$sql = "SELECT `ID` as id FROM `wp_posts` WHERE `post_name` = '{$post_name}'";

		print("\n");
		print_r($sql);
		// die();
		$result = $db->query($sql);
		$product_id = $result->fetch_array();
		$product_id = $product_id[0];
		print("\n");
		print($product_id);
		// die();
		$sql = "UPDATE `wp_posts` SET `post_title` = '{$prod_name}', `post_name` = '{$post_name}', `post_modified` = NOW(), `post_modified_gmt`= NOW(), `pos_product_id` = '{$row[0]}' WHERE ID = {$product_id}";
		print($sql);
		// die();
		$db->query($sql);
		// print($product_id);
		// die();
		$img = $row[1];
		// move image
		$file_loc = "/home/heartfelt_trans/heartfeltpages.com/wp-content/uploads/2015/09/".$img;
		$pic_loc = "2015/09/".$img;
		print("\n");

		try {
			copy($config['import_image_dir'].$img, "/home/heartfelt_trans/heartfeltpages.com/wp-content/uploads/2015/09/".$img);
			// UPDATE image in DB
			$sql = "UPDATE `wp_posts` SET `post_title` = '{$img}', `post_name` = '{$img}', `post_modified` = NOW(),`post_modified_gmt` = NOW(), `guid` = 'http://heartfeltpages.com{$file_loc}' WHERE `post_parent` = {$product_id}";
			print("\n");


			$db->query($sql);
			
			// print($sql);

			$result = $db->query("SELECT `ID` FROM `wp_posts` WHERE `post_parent` = {$product_id}");
			$pic_id = $result->fetch_array();
			$pic_id = $pic_id[0];

			$db->query("UPDATE `wp_postmeta` SET `meta_value`='{$pic_loc}') WHERE `post_id` = {$pic_id}");

		} catch (Exception $e) {
			print("ERROR CAUGHT: ");
			print_r($e);
		}
		// print_r($img);
		
		$cat_name = ucwords(strtolower($row[8]));
		print("\n");
		var_dump($cat_name);

		$result = $db->query("SELECT * FROM wp_terms WHERE name = '{$cat_name}'");
		// var_dump($result);
		if ($result->num_rows == 0) {
			$low_cat_name = strtolower($cat_name);
			$cat_slug = preg_replace('/[^a-zA-Z0-9\']/', '-', $low_cat_name);
			$cat_slug = str_replace("'", '', $cat_slug);
			$db->query("INSERT INTO `wp_terms`(`term_id`, `name`, `slug`, `term_group`) VALUES (NULL,'{$cat_name}','{$cat_slug}',0)");
			$result = $db->query("SELECT last_insert_id()");
			$cat_id = $result->fetch_array();
			$cat_id = $cat_id[0];
			$db->query("INSERT INTO `wp_term_taxonomy`(`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES (NULL,{$cat_id},'product_cat','',0,(SELECT count(*) FROM wp_term_relationships WHERE term_taxonomy_id = {$cat_id}))");
		} else {
			$cat_id = $result->fetch_array();
			$cat_id = $cat_id[0];
			print("\n");

			// var_dump($cat_id);
		}
		

		$quantity = $row[6];
		$weight = $row[5];
		$price = $row[4];
		$sku = $row[0];

		$db->query("UPDATE `wp_postmeta` SET `meta_value` = {$quantity} WHERE `post_id` = '{$product_id}' AND `meta_key` = '_stock'");

		$db->query("UPDATE `wp_postmeta` SET `meta_value` = '{$price}' WHERE `post_id` = '{$product_id}' AND `meta_key` = '_price'");
		
		$db->query("UPDATE `wp_postmeta` SET `meta_value` = '{$sku}'WHERE `post_id` = '{$product_id}' AND `meta_key` = '_sku'");

		$db->query("UPDATE `wp_postmeta` SET `meta_value` = '{$weight}'WHERE `post_id` = '{$product_id}' AND `meta_key` = '_weight'");

		$db->query("UPDATE `wp_postmeta` SET `meta_value` = {$pic_id} WHERE `post_id` = '{$product_id}' AND `meta_key` = '_thumbnail_id'");



		// insert category id and product id into wp_term_relationships
		$sql = "UPDATE `wp_term_relationships` SET `term_taxonomy_id`= {$cat_id} WHERE `object_id` = {$product_id}";
		print("\n");
		// print_r($sql);
		// object_id is post id. term_taxonomy_id is category id. term order is 0
		$db->query($sql);


	} else {
		


		
		// NEW PRODUCT:
		print_r($row);


		// insert product into db into wp_posts
		
		
		$sql = "INSERT INTO `wp_posts`(`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (NULL,1,NOW(),NOW(),'','{$prod_name}','','publish','closed','closed','','{$post_name}','','',NOW(),NOW(),'',0,'',0,'product','',0)";
		print("\n");
		print_r($sql);
		// die();
		$db->query($sql);

		$result = $db->query("SELECT last_insert_id()");
		$product_id = $result->fetch_array();
		$product_id = $product_id[0];

		$guid = $config['url'] . '?post_type=product&p='.$product_id;
		$db->query("UPDATE `wp_posts` SET guid = '{$guid}' WHERE ID = {$product_id}");



		$img = $row[1];
		// move image
		$file_loc = "/home/heartfelt_trans/heartfeltpages.com/wp-content/uploads/2015/09/".$img;
		$pic_loc = "2015/09/".$img;
		print("\n");

		print_r($img);
		// INSERT image into DB
		try {
			copy($config['import_image_dir'].$img, "/home/heartfelt_trans/heartfeltpages.com/wp-content/uploads/2015/09/".$img);
			$sql = "INSERT INTO `wp_posts`(`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (NULL,1,NOW(),NOW(),'','{$img}','','inherit','closed','open','','{$img}','','',NOW(),NOW(),'','{$product_id}','http://heartfeltpages.com{$file_loc}',0,'attachment','image/jpeg',0)";
			print("\n");
			
			print_r($sql);
			// die();
			$db->query($sql);
			$result = $db->query("SELECT last_insert_id()");
			$pic_id = $result->fetch_array();
			$pic_id = $pic_id[0];
			
			// $pic_meta = read_exif_data($config['import_image_dir'].$img);
			// print("\n");

			// print_r($pic_meta);
			// print("\n");

			$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$pic_id}','_wp_attached_file', '{$pic_loc}')");
		} catch (Exception $e) {
			print("ERROR CAUGHT: ");
			print_r($e);
		}
		
		
		
		// $db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_wp_attachment_metadata','taxable' )");

		// insert Category if it doesn't exist in wp_terms and wp_terms_taxonomy
		$cat_name = ucwords(strtolower($row[8]));
		print("\n");

		var_dump($cat_name);

		$result = $db->query("SELECT * FROM wp_terms WHERE name = '{$cat_name}'");
		var_dump($result);
		if ($result->num_rows == 0) {
			$low_cat_name = strtolower($cat_name);
			$cat_slug = preg_replace('/[^a-zA-Z0-9\']/', '-', $low_cat_name);
			$cat_slug = str_replace("'", '', $cat_slug);
			$db->query("INSERT INTO `wp_terms`(`term_id`, `name`, `slug`, `term_group`) VALUES (NULL,'{$cat_name}','{$cat_slug}',0)");
			$result = $db->query("SELECT last_insert_id()");
			$cat_id = $result->fetch_array();
			$cat_id = $cat_id[0];
			$db->query("INSERT INTO `wp_term_taxonomy`(`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES (NULL,{$cat_id},'product_cat','',0,(SELECT count(*) FROM wp_term_relationships WHERE term_taxonomy_id = {$cat_id}))");
		} else {
			$cat_id = $result->fetch_array();
			$cat_id = $cat_id[0];
		print("\n");

			var_dump($cat_id);
		}


		
		


	

		// insert product id and meta data into wp_postmeta
		$quantity = $row[6];
		$weight = $row[5];
		$price = $row[4];
		$sku = $row[0];

		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_tax_class', '')");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_tax_status','taxable' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_product_image_gallery', '')");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_crosssell_ids','a:0:{}' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_upsell_ids','a:0:{}' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_stock', {$quantity})");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_backorders','no' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_manage_stock','yes' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_sold_individually', '')");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_price', '{$price}' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_sale_price_dates_to', '')");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_sale_price_dates_from', '')");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_product_attributes','a:0:{}' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_sku','{$sku}' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_height', '')");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_width', '')");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_length', '')");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_weight', {$weight})");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_featured','no' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_purchase_note', '')");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_sale_price', '')");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_regular_price', )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_virtual','no' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_downloadable','no' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','total_sales','0' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_stock_status','instock' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_visibility','visible' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_edit_lock','1434599176:1' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_edit_last','1' )");
		$db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_thumbnail_id',{$pic_id} )");
		// $db->query("INSERT INTO `wp_postmeta`(`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,'{$product_id}','_wp_attached_file','{$pic_loc}' )");


		// insert category id and product id into wp_term_relationships
		$sql = "INSERT INTO `wp_term_relationships`(`object_id`, `term_taxonomy_id`, `term_order`) VALUES ({$product_id}, {$cat_id},0)";
		print("\n");
		print_r($sql);
		// object_id is post id. term_taxonomy_id is category id. term order is 0
		$db->query($sql);
		// die();
	}
}
fclose($prod_fh);
print("</pre>");
// log file

// end



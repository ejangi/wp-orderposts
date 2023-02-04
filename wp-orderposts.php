<?php
/*
Plugin Name: Order posts
Plugin URI: http://orderposts.ucantblamem.com
Description: This plugin allows you to order posts the way you want (i.e. if you don't want to order posts by date).
Author: James Angus
Version: 0.3
Author URI: http://www.ucantblamem.com

*/



/**
 * Build the management page:
 */
function po_postorder()
{
	global $wpdb;

	$relationships = array();

	// Do we have any ordering to do?
	if (isset($_POST['action']) && is_array($_POST['action'])) {
		foreach ($_POST['action'] as $catid => $act) {
			// $action_posts = $wpdb->get_results("SELECT p.ID, p.menu_order FROM $wpdb->posts AS p LEFT JOIN $wpdb->post2cat AS p2c ON (p.ID = p2c.post_id) WHERE p2c.category_id = $catid AND (p.post_status = 'publish' OR p.post_status = 'private' OR p.post_status = 'draft') AND p.post_type = 'post' AND p.post_parent = '0' ORDER BY p.menu_order, p.ID ASC");
			
			$action_posts = get_posts("cat=".$catid. "&orderby=menu_order&order=ASC&numberposts=500");

			foreach ($act as $id => $dir) {
				foreach ($action_posts as $offset => $post) {
					$relationships[$post->ID] = $offset;
				}

				if ($dir == '+') {
					$end_onwards = $relationships[$id]+2;
					$start_array = array_slice($action_posts, 0, $relationships[$id]);
					$tmp_array = array_slice($action_posts, $relationships[$id], 2);
					$end_array = array_slice($action_posts, $end_onwards);
				} else {
					$end_onwards = $relationships[$id]+1;
					$start_array = array_slice($action_posts, 0, $relationships[$id]-1);
					$tmp_array = array_slice($action_posts, $relationships[$id]-1, 2);
					$end_array = array_slice($action_posts, $end_onwards);
				}

				if (($dir == '+' && count($tmp_array) > 1) || ($dir == '-' && count($tmp_array) > 1)) {
					$final_array = array();
					$i = 1;

					$tmp_array = array_reverse($tmp_array);
					$new_array = array_merge($start_array, $tmp_array, $end_array);

					foreach ($new_array as $item) {
						$item->menu_order = $i;
						$final_array[] = $item;
// echo "UPDATE $wpdb->posts SET menu_order = $item->menu_order WHERE ID = $item->ID";exit;
						$wpdb->query("UPDATE $wpdb->posts SET menu_order = $item->menu_order WHERE ID = $item->ID");
						$i++;
					}
				}

			} // foreach ($act as $id => $dir)
		} //foreach ($_POST['action'] as $catid => $act)
	} // if (isset($_POST['action']) && is_array($_POST['action']))

	?>
		<div class="wrap">
		<h2>Order Posts</h2>
		<p>Below is a list of categories and the posts for each. Use the arrows to order your posts as desired. You will need to make changes to your templates if you wish to honour this ordering. Please see the bottom of this page for more instructions on setting up your templates.</p>
		<!-- <img src="" alt=" " /> -->
		<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<ul style="list-style:none;margin-left: 0; margin-bottom: 40px; padding-left: 0">
		<?php
			// $categories = $wpdb->get_results("SELECT * FROM $wpdb->categories WHERE category_count > 0 ORDER BY cat_ID");
			$categories = get_categories('orderby=ID&order=ASC');

			if (is_array($categories) && count($categories) > 0) {
				foreach ($categories as $category) {
			?>
				<li><h3><?php echo $category->cat_name; ?> <input type="image" name="refresh[<?php echo $category->cat_ID; ?>]" value="refresh" src="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;image=refresh" class="refresh" style="background: none; border: 0;" /></h3>
					<ul style="list-style:none">
						<?php
							$i = 0;
							// $posts = $wpdb->get_results("SELECT p.*, p2c.category_id FROM $wpdb->posts AS p LEFT JOIN $wpdb->post2cat AS p2c ON (p.ID = p2c.post_id) WHERE p2c.category_id = $category->cat_ID AND (p.post_status = 'publish' OR p.post_status = 'private' OR p.post_status = 'draft') AND p.post_type = 'post' AND p.post_parent = '0' ORDER BY p.menu_order, p.ID ASC");
							
							$posts = get_posts("cat=".$category->cat_ID. "&orderby=menu_order&order=ASC&numberposts=500");
							
							if (is_array($posts) && count($posts) > 0) {
								$total = count($posts);
								foreach ($posts as $post) { ?>
									<li<?php if (($i % 2) == 0) { ?> class="alternate"<?php } ?> style="padding: .2em .5em"><?php echo $post->menu_order; ?>.
										<?php if ($i > 0) { ?><input type="image" name="action[<?php echo $category->cat_ID; ?>][<?php echo $post->ID; ?>]" value="-" src="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;image=up" class="up" style="background: none; border: 0" /><?php } else { ?><img src="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;image=spacer" alt= "" width="16" height="16" style="margin: 5px;" class="spacer" /><?php } ?>
										<?php if ($i < ($total - 1)) { ?><input type="image" name="action[<?php echo $category->cat_ID; ?>][<?php echo $post->ID; ?>]" value="+" src="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;image=down" class="down" style="background: none; border: 0" /><?php } else { ?><img src="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;image=spacer" alt= "" width="16" height="16" style="margin: 5px;" class="spacer" /><?php } ?>
										<strong><a href="<?php echo get_settings('siteurl').'/wp-admin/post.php?action=edit&post='.$post->ID; ?>" title="Edit this page"><?php echo $post->post_title;?></a></strong>
									</li>
									<?php
									$i++;
								}
							}
						?>
					</ul>
				</li>
			<?php
				}
			}
		?>
		</ul>

		<h2>Setting up your templates</h2>
		<p>To see this method of ordering in affect on your site, you will need to add the following line to your templates:</p>
		<pre><code>query_posts("cat=" . $cat . "&orderby=menu_order&order=ASC");</code></pre>
		<p>For instance, paste that line into the archive.php and/or index.php files just before:</p>
		<pre><code>while (have_posts()) : the_post();</code></pre>
		<p>This will ensure that your posts use your new ordering.</p>
		</form>
		</div>
		<?php
}



function direction_image($image = 'up')
{
	$img = array();

	header('Content-Type: image/gif');
	$img['up'] = 'R0lGODlhEAAQAOZOAAhotBFJdQxipgplrQZruxBcmA5fnxNYjhJakgldnwpiqA54y0eZ2htglxFKdrjV7Ct9vy+DxleYzEWEtafJ5D6U2CFcix52uwxhpRVck8ff8UiAq4u22Adsu+r0+0l+pyaByxZaj3+55XCv4CB6whxdkRFttwlYl9Hl9Q5fnhJakS+K0pjH7ARuwhNYjQpptR5ci+/2/AtuvAtyxMTb7TKJzRFhoB9UfDaO02mo2xlhmglXlRNemQlbm9Lk8wplrEeBsAlamejy+hBxvt/t+RB1xR9aiN3r9xBcl12f0/n8/gVvwhprqoWw0f///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAE4ALAAAAAAQABAAAAd5gE6Cg4SFhk5KLB6HhTEiDBVEjIJCIzgLCysojEc5NTMtS0UgGoY+SREyBKsdQyQPhDQSEC8AtrcmFxSCHAo9CQoDwgM/CSc7QU2FTALNAhg6kxM2BtUpDZNAPAXcSCWTGxkI4yowkx8hB+ouRpMWDgHxATeT9faHgQA7';
	$img['down'] = 'R0lGODlhEAAQAOZOAAhBbQk9ZgVIfQFVlwNPjAdEdQJSklWPvARMhAs1VhROfAo5Xh5EYgNPi0BvlQdOhZyvvTx5qwhAax1CXhNQgApGd1B/pFV/oPT19j11oAhFdgsuSQZRjejr7pmvwFaQvQg9ZxE7Xj5ZbVJ8n6u7yTFkjBRShG+CkQk8ZNzh5ihIYhBXkARXmAFVlsvT2gtAagoxUNrf5Dx3pwlLfwRMg+Xp7DhpkBFVi0l2mhU/YSBsp5awxQk9ZQo4XLbDzQJSkRJTh8vU21KNuylJYoKfuL/L1Ag5XwVUkzt8rw8/ZQk1V2SYwgo6YAo5Xf///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAE4ALAAAAAAQABAAAAd5gE6Cg4SFhoeISx8HjEI6iE5ILAOULSuQEUcGmz83kDIcBKINQJAZDwipNBSQFjMCsAImhTs4FyM2FQW7GgolDgpEgh4vEgDHyAAgTBCEJEkoAdIBPEYhPoZFOT0LC01KEy6IQQwJCTBDKZBOMSobIh3rgjUnGPL3gQA7';
	$img['refresh'] = 'R0lGODlhEAAQAPeiAP////7//wCH9uDy/vv+//T7/wCP/wmS/gB34gF/6+34/2m39f3+/wOB6gOA6Pn8/8Xo/8bp/9nv/q/X+Ah+39ju/gGL+ABx3giR+wuS++j3/1u1+ej2/wCR/wB/7+n2/2zD/2/D/wBeuQKR/wBtzABiwQl94QBpy/z+/9bv/9Tt/wmT/fT6/yKi/0St+7vg+6bb/wCE9YnE8vX6/wBYtGW9/Fys71ex+Mbn/xmB3DWW5QCI+SGM45jS/UOx/0u0/7Ld/bbb9+b1//f7/wCB7ZTQ/QBu1geC6TOr/wB55wBivo/R/+33/3nA+ABz3J3P9f7+/wV22dzx/wB+9gCM/QGH+Wm39ACK/fj8//b7/wBy2D+0/xuJ6CWm/9zw/gN/6wBu0dfw/63c/g6A3hSd/wBiwwSU/weU/gZ11AaW/4TD9DKi9AWC7Kvb/lm5/7zh/gaN+gBt2QB77d/x/QKD9ACH+XW37Rea/ABy3BZ/2F64+pPO/AB/7RyQ81yu8QCL/QBavgBz28/r/gCJ/ACG9zmj+gBz4D2t/wBqzQyT+ROb/wBnxvP6/1uz9gp313697i2I1AmP/Rad/+74/wBx1X7I/wCD8wiH72Wu5wB23gKR/QBmzJ7V/gOJ9wB97ABnyB6c+wB14f///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAKIALAAAAAAQABAAAAjhAEUJHCgQipcFOp4EIMgQSwgMoNZ8erSQoagHPhJ1UrSFAg0ZABgOYbRhEx4BSKIoQVNAVBYpbW700SPBxpEeLowgGlOAAAhNf1aQ6ZJigokaDRDEsbLww50YVMy00IApiQcinr4MCAkjQwJLgw5N4mHBwA46nEJyqJLJCZ8plQawOTPiChwhApuEWqQFSBEIKuqk6WAgkpiQXEiIUAMgTKMKbn4swcEERcgcJ/II2uOgDKQ5DACEHBikBCVHFwKBAWRnNEEALy4JICTHkJ8ZrhlGkHSg0JuKFgeyUEAgN8OAADs=';
	$img['spacer'] = 'R0lGODlhEAAQAIAAAP///wAAACH5BAEAAAAALAAAAAAQABAAAAIOhI+py+0Po5y02ouzPgUAOw==';

	if (array_key_exists($image, $img)) {
		echo base64_decode($img[$image]);
	}
	exit;
} // direction_image()



/**
 * Add management page to the Wordpress system:
 */
function po_add_post()
{
	// add_management_page('Order Posts', 'Order Posts', 10, __FILE__, 'po_postorder');
	add_options_page('Order Posts', 'Order Posts', 'manage_options', 'order-posts', 'po_postorder');
	// add_submenu_page('edit.php', 'Order Posts', 'Order Posts', 'Author', 'order-posts', 'po_postorder');
} // po_add_post()



if (isset($_GET['image']) && ($_GET['image'] == 'up' || $_GET['image'] == 'down' || $_GET['image'] == 'refresh' || $_GET['image'] == 'spacer')) {
	direction_image($_GET['image']);
} else {
	add_action('admin_menu', 'po_add_post');
}
?>

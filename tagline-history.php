<?php
/*  Copyright 2008 Matthew Weston  (email : admin@mattyboy.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
 * Plugin Name: Tagline History
 * Plugin URI: http://www.mattyboy.net/wp-tagline-history/
 * Version: 1.1.2
 * Description: This plugin stores a history of taglines and displays them to your website users. <a href="plugins.php?page=tagline-config">Config Page</a> 
 * Author: Matthew Weston.
 * Author URI: http://www.mattyboy.net/
 */
 
// Includes
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// Option Keys
define("key_wp_tagline", "blogdescription", true);
define("key_tagline_ver", "tagline_version", true);
define("key_tagline_db_ver", "tagline_db_version", true);
define("key_tagline_grace", "tagline_grace", true);
define("key_tagline_page", "tagline_page", true);
define("key_tagline_date_format", "tagline_date_format", true);
define("key_tagline_html_tmpl", "tagline_html_file", true);
define("key_tagline_css_tmpl", "tagline_css_file", true);

// Template Placeholders
define("ph_tagline_text", "TAGLINE_TEXT", true);
define("ph_tagline_id", "TAGLINE_ID", true);
define("ph_tagline_date", "TAGLINE_DATE", true);
define("ph_tagline_remove", "TAGLINE_REMOVE", true);

// Defaults
define("default_db_tablename", "tagline_history", true);
define("default_disabled", "disabled", true);
define("default_enabled", "enabled", true);
define("default_grace", 10, true);
define("default_date_format", "jS M Y h:i:s a", true);
define("current_version", "1.1.2", true);
define("current_dbversion", "1.0.0", true);

// Defailt Template Locations
define("default_html_location", $_SERVER['DOCUMENT_ROOT'] . "/wp-content/plugins/tagline-history/templates/tagline.html",true);
define("default_css_location", get_option('home') . "/wp-content/plugins/tagline-history/templates/tagline.css",true);

/////////////////////
// Hooks, actions and filters functions
/////////////////////

register_activation_hook(__FILE__,'tagline_install');
add_action('admin_menu', 'add_tagline_pages');
add_action('update_option_blogdescription', 'tagline_updated',10,2);
add_action('wp_head', 'tagline_stylesheet');
add_filter('the_content', 'print_tagline_history');

// Hook in the options page function
function add_tagline_pages() {
	global $wpdb;
        add_submenu_page('plugins.php', __('Tagline Configuration'), __('Tagline Configuration'), 'manage_options', 'tagline-config', 'tagline_options_page');
}

// Adds the style sheet reference to the tagline history page
function tagline_stylesheet()
{
	$tagline_page = get_option(key_tagline_page);
	if( !empty($tagline_page) &&  is_page($tagline_page) )
	{
		$tagline_css_tmpl = get_option(key_tagline_css_tmpl);
		// Add CSS to Content
		echo "\n" . '<link rel="stylesheet" href="'. $tagline_css_tmpl .'" type="text/css" />' . "\n";
	}		
}

// Use this method to uninstall tagline history entirely
function tagline_uninstall()
{	
	delete_option(key_tagline_grace);
	delete_option(key_tagline_page);
	delete_option(key_tagline_date_format);
	delete_option(key_tagline_date_format);
	delete_option(key_tagline_date_format);
	delete_option(key_tagline_db_ver);
	delete_option(key_tagline_ver);
	delete_option(key_tagline_html_tmpl);
	delete_option(key_tagline_css_tmpl);

	//TODO: Delete Table and Contents
	//global $wpdb;
	//$table_name = $wpdb->prefix . default_db_tablename;
}

// Tagline installation function, fires when tagline plugin is activated.
function tagline_install () 
{ 
	global $wpdb;

	$table_name = $wpdb->prefix . default_db_tablename;
	$installed_ver = get_option( key_tagline_ver );

	// Tagline Options
	add_option(key_wp_tagline, "Tagline History", "Tagline for blog");
	add_option(key_tagline_grace, default_grace, "Tagline History update grace period");
	add_option(key_tagline_page, "", "Tagline History page");
	add_option(key_tagline_date_format, default_date_format, "Tagline History date format");
	add_option(key_tagline_db_ver, current_dbversion, "Tagline Database Version");
	add_option(key_tagline_html_tmpl, default_html_location, "Tagline HTML Template File");
	add_option(key_tagline_css_tmpl, default_css_location, "Tagline CSS Template File");
		
	if($installed_ver == "1.0")
	{
		// Remove old place holder options, no longer used in version 1.1.0
		delete_option("tagline_prepend");
		delete_option("tagline_append");
		delete_option("tagline_item");
		delete_option("tagline_status");

		// Update Version Number
		update_option(key_tagline_ver, current_version);
	}
	
	if($installed_ver == "1.1.0" || $installed_ver == "1.1.1") // Fixed to comply with XHTML and ALLOW_URL_FOPEN
		update_option(key_tagline_ver, current_version);
	
	// Check if tagline table exists, if not it should be created.
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) 
	{

		// Create tagline history table
		$sql = "CREATE TABLE " . $table_name . " (
			 id mediumint(9) NOT NULL AUTO_INCREMENT,
  			 tagline VARCHAR(255) NOT NULL,
			 tagline_ts TIMESTAMP,
			 UNIQUE KEY id (id)
			);";

		dbDelta($sql);

		// Add current tagline into databse.
		$init_tagline = get_option(key_wp_tagline);
		$init_tagline_ts = gmdate('YmdHis', current_time('timestamp'));
		
		$insert = "INSERT INTO $table_name  (tagline, tagline_ts) VALUES ('" 
			. $wpdb->escape($init_tagline) . "','" . $init_tagline_ts . "')";
	
  		$results = $wpdb->query( $insert );

		// Add DB version to plugin options (Used if DB changes in later version)
		add_option(key_tagline_db_ver, current_dbversion);
		add_option(key_tagline_ver, current_version);
	}
}

// Action hook called when tagline option is updated
function tagline_updated ($old_value, $new_value)
{
	if( !empty($new_value) )
	{
		update_tagline($new_value);
	}
}

/////////////////////
// Tagline History Database Functions.
/////////////////////

// Update Tagline History Function
function update_tagline ($tagline)
{
	global $wpdb;

	// Remove any taglines within grace period from tagline history table
	$table_name = $wpdb->prefix . default_db_tablename;
	$grace_period = get_option(key_tagline_grace);
	$delete = "DELETE FROM $table_name WHERE tagline_ts > CURRENT_TIMESTAMP - " . $wpdb->escape($grace_period);
	$results = $wpdb->query( $delete ); 

	echo "<!-- " . $delete . " modified " . $results . " records-->\n";
	
	// Insert new tagline into tagline history table
	$current_ts = gmdate('YmdHis', current_time('timestamp'));
	$table_name = $wpdb->prefix . default_db_tablename;
	$insert = "INSERT INTO $table_name  (tagline, tagline_ts) VALUES ('" 
		. $wpdb->escape($tagline) . "','" . $current_ts . "')";
		
	$results = $wpdb->query( $insert );
	
	echo "<!-- " . $insert . " modified " . $results . " records-->\n";
	
	return $results;
}

// Retrieves current tagline from database
function get_latest_tagline()
{
	global $wpdb;
	$table_name = $wpdb->prefix . default_db_tablename;
	$select = "SELECT id, tagline, unix_timestamp(tagline_ts) tagline_ts FROM $table_name ORDER BY tagline_ts DESC LIMIT 1";
	$tagline = $wpdb->get_row($select);
	return $tagline;
}

// Retrieves all taglines from database
function get_tagline_history()
{
	global $wpdb;
	$table_name = $wpdb->prefix . default_db_tablename;
	$select = "SELECT id, tagline, unix_timestamp(tagline_ts) tagline_ts FROM $table_name ORDER BY tagline_ts DESC";
	$taglines = $wpdb->get_results($select);
	return $taglines;
}

// Removes tagline with given id
function remove_tagline ($tagline_id)
{
    global $wpdb;
	$table_name = $wpdb->prefix . default_db_tablename;
	$delete = "DELETE FROM $table_name WHERE id = " . $wpdb->escape($tagline_id);
	$results = $wpdb->query( $delete ); 
	return $results;
}

// Get count of taglines in databse
function get_tagline_count()
{
    global $wpdb;
	$table_name = $wpdb->prefix . default_db_tablename;
	$select = "SELECT count(*) FROM $table_name";
	$count = $wpdb->get_var($select);
	return $count;
}

/////////////////////
// Tagline History Option Page Functions.
/////////////////////


// Tagline Options Hook Function
function tagline_options_page ()
{
	global $wpdb;
	$table_name = $wpdb->prefix . default_db_tablename;
	
	// Status and update messages

	// Does Tagline History table exist?
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
	{
		?>
		<div id="message" class="error"><?php _e("Tagline History <strong>TABLE IS MISSING</strong>: $table_name"); ?></div>
		<?php
	}

	// Is postback, update user options
	if (isset($_POST['update-form'])) 
	{
		// Update grace
		$tagline_grace = (int) $_POST[key_tagline_grace];
		if( !is_int($tagline_grace) )
			$tagline_grace = default_grace;
		else if( $tagline_grace < 5 )
			$tagline_grace = 5;
		else if( $tagline_grace > 60 )
			$tagline_grace = 60;
		update_option(key_tagline_grace, $tagline_grace);
		
		// Update Tagline Page
		$tagline_page = $_POST[key_tagline_page];
		update_option(key_tagline_page, $tagline_page);
		
		// Update Tagline date format
		$tagline_date_format = $_POST[key_tagline_date_format];
		update_option(key_tagline_date_format, $tagline_date_format);

		// Update and store tagline
		$tagline = $_POST[key_wp_tagline];
		update_option(key_wp_tagline, $tagline);
	
		// Update HTML Template location
		$tagline_html_tmpl = $_POST[key_tagline_html_tmpl];
		update_option(key_tagline_html_tmpl, $tagline_html_tmpl);
		
		// Update CSS Template location
		$tagline_css_tmpl = $_POST[key_tagline_css_tmpl];
		update_option(key_tagline_css_tmpl, $tagline_css_tmpl);

		//TODO: Actually put some error messages up, rather then just successfully updated :{
		?>
		<div id="message" class="updated fade"><?php _e("Tagline History was successfully updated"); ?></div>
		<?php
	}

	// Get tagline information from options and database
	$tagline_page = get_option(key_tagline_page);
	$tagline_page_uri = get_page_uri($tagline_page);
	$tagline_version = get_option(key_tagline_ver);
	$tagline_dbversion = get_option(key_tagline_db_ver);
	$tagline_count = get_tagline_count();
	$tagline_html_tmpl = get_option(key_tagline_html_tmpl);
	$tagline_css_tmpl = get_option(key_tagline_css_tmpl);
	
	// Is a Tagline History page selected?
	if( empty($tagline_page) )
	{
		?>
			<div id="message" class="error"><?php _e("Tagline History page has not been selected. Please choose a page for Tagline History to use."); ?></div>
                <?php
	}

	// Has Tagline version been updated?
	if( $tagline_version != current_version )
	{
		?>
			<div id="message" class="error"><?php _e("To complete the upgrade to version " . current_version . " please deactivate and reactivate plugin"); ?></div>
                <?php
	}

	// Does HTML template exits?
	if( !file_exists($tagline_html_tmpl) )
	{
		?>
			<div id="message" class="error"><?php _e("HTML template path does not exist:  " . $tagline_html_tmpl ); ?></div>
		<?php
	}

	// Output the options page
	?>
	<div id="tagline-div" class="wrap">
		<h2><?php _e("Tagline History Options ($tagline_version)"); ?></h2>
		<form method="post" action="plugins.php?page=tagline-config">
		<?php wp_nonce_field('update-options') ?>
		<p class="submit"><input type="submit" name="update-form" value="<?php _e('Update Options »') ?>" /></p>
		
		<fieldset id="tagline_status" class="options">
			<legend><?php _e("Basic Options"); ?></legend>
			<table class="editform optiontable">
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo key_wp_tagline; ?>"><?php _e("Current tagline is"); ?></label>
					</th>
					<td>
						<?php render_html_input( key_wp_tagline, "text", 45 ); ?>
					</tr>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo key_tagline_page; ?>"><?php _e("Tagline History page name is"); ?></label>
					</th>
					<td>
						 <?php wp_dropdown_pages("name=".key_tagline_page."&selected=$tagline_page&show_option_none=None"); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo key_tagline_grace; ?>"><?php _e("Update grace period is"); ?></label>
					</th>
					<td>
						<?php render_html_input( key_tagline_grace, "text", 10 ); ?> <?php _e(" seconds"); ?>
					</td>
				</tr>
			</table>
		</fieldset>
		
		<fieldset id="tagline_advanced" class="options">
			<legend><?php _e("Advanced Options"); ?></legend>
			<table class="editform optiontable">
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo key_tagline_date_format; ?>"><?php _e("Tagline date format is"); ?></label>
					</td>
					<td>
						<?php render_html_input( key_tagline_date_format, "text", 45 ); ?><br/>
						<?php _e('Output:') ?> <strong><?php echo gmdate(get_option(key_tagline_date_format), current_time('timestamp')); ?></strong></td>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo key_tagline_html_tmpl; ?>"><?php _e("HTML Template Path"); ?></label>
					</td>
					<td>
						<?php render_html_input( key_tagline_html_tmpl, "text", 45 ); ?><br/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo key_tagline_css_tmpl; ?>"><?php _e("CSS Template URL"); ?></label>
					</td>
					<td>
						<?php render_html_input( key_tagline_css_tmpl, "text", 45 ); ?><br/>
					</td>
				</tr>
			</table>
		</fieldset>

		<fieldset id="tagline_advanced" class="options">
			<legend><?php _e("Other Information"); ?></legend>
			<table class="editform optiontable">
				<tr valign="top">
					<td>
					<?php
						if( !empty($tagline_page_uri) )
						{
							_e("You have <a href='/$tagline_page_uri/'>$tagline_count Tagline Histories</a> stored in the database."); 
						}
						else
						{
							_e("You have $tagline_count Tagline Histories stored in the database."); 
						}
					?>
					</td>
				</tr>
				<tr valign="top">
					<td><?php _e("Tagline database version: $tagline_dbversion"); ?></td>
				</tr>
			</table>
		</fieldset>

		<p class="submit"><input type="submit" name="update-form" value="<?php _e('Update Options »') ?>" /></p>
		</form>
	</div>
	<?php
	
}

/////////////////////
// Tagline History Page Functions: Used to render taglines to selected page.
/////////////////////

// Appends tagline history page entry with all stored taglines
function print_tagline_history($content)
{
	$tagline_page = get_option(key_tagline_page);
	if( !empty($tagline_page) &&  is_page($tagline_page) )
	{
		$content .= "<!-- Tagline History: Start -->\n";

		$tagline_html_tmpl = get_option(key_tagline_html_tmpl);
		if(file_exists($tagline_html_tmpl) )
		{
			// Check if admin and wether they clicked remove link
			admin_remove_tagline($content);

			// Get taglines from database
			$taglines = get_tagline_history();	

			// Loop taglines
			foreach ($taglines as $tagline) 
			{
				// Open tagline file and print to screen
				$lines = file( $tagline_html_tmpl );
				foreach ($lines as $line_num => $line) 
				{
					$content .= process_template_line($line, $tagline);
				}
			}
		}
		else
		{
			$content .= "<!-- Tagline HTML Template file doesn't exist: " . $tagline_html_tmpl . " -->\n";
		}
		
		$content .= "<!-- Tagline History: End -->\n";
	}

	return $content;
}

// Used to delete a tagline, when admin user clicks on remove link.
function admin_remove_tagline($content)
{
	global $userdata;
	get_currentuserinfo();

	// Check user is admin
	if($userdata->user_level > 5)
	{
		// Get tagline id from URL
		$tagline_id = $_GET[key_tagline_id];
		
		// If tagline id call remove tagline
		if( !empty($tagline_id) )
		{
			$content .= "<!-- Attempting to remove tagline: " . $tagline_id . "-->\n"; 
			$deleted = remove_tagline($tagline_id);
			$content .= "<!-- Removed " . $deleted . " tagline -->\n"; 
		}
	}
	
}

// Replaces placeholders with actual tagline values.
function process_template_line($template_line, $tagline)
{
	$tagline_date_format = get_option(key_tagline_date_format);
	
	$template_line = str_replace(ph_tagline_text, $tagline->tagline, $template_line);
	$template_line = str_replace(ph_tagline_id, $tagline->id, $template_line);
	$template_line = str_replace(ph_tagline_date, date($tagline_date_format, $tagline->tagline_ts), $template_line);
	$template_line = replace_remove_placeholder($template_line, $tagline->id);

	return $template_line;
}

// Replaces tagline remove placeholder with remove link on tagline history page for allowed users
function replace_remove_placeholder($template_line, $tagline_id)
{
	global $userdata;
	get_currentuserinfo();

	// Replace placeholder with remove link if admin user
	if( $userdata->user_level > 5 )
	{
		// Prevent remove from working anywhere except tagline history page.
		if( is_page($tagline_page) )
		{
			$tagline_page = get_option(key_tagline_page);
			$page_uri = get_page_uri($tagline_page);
			$template_line = str_replace(ph_tagline_remove, "<a href='/$page_uri/?" . key_tagline_id . "=$tagline_id'>remove</a>", $template_line);
		}
	}
	else // Just remove the placeholder if not admin
	{
		$template_line = str_replace(ph_tagline_remove, "", $template_line);
	}
	
	return $template_line;
}

/////////////////////
// HTML Helpers functions
/////////////////////

// Used to render HTML option tag
function render_html_option ($option_key, $option_value_key, $option_name)
{
	// Check if this option is the current option
	$isSelected = ( get_option($option_key) == $option_value_key )? " selected='selected' ": "";

	// Output option html
	echo "<option value='$option_value_key' $isSelected>$option_name</option>\n";
}

// Used to render HTML input tag
function render_html_input ($option_key, $option_type, $size)
{
	$value = get_option($option_key);
	echo "<input name='$option_key' type='$option_type' id='$option_key' value='$value' size='$size' />\n";
}

?>

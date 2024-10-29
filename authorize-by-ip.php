<?php
/*
Plugin Name: Authorize by IP
Plugin URI: http://www.hebeisenconsulting.com
Description:  Authorize by IP is a tool that allows access to the wordpress website based on specified IP addresses. This is most ideal when the wordpress website is still under construction, and a splash page is to be seen instead. Access can be given to developers or clients to be able to demo the wordpress website by including their IP in the allow list.
Version: 1.1
Author: Hebeisen Consulting - R Bueno
Author URI: http://www.hebeisenconsulting.com
License: A "Slug" license name e.g. GPL2
*/
/*  Copyright 2011 Hebeisen Consulting

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
add_action('admin_menu', 'ip_authorize_menu');
add_action('wp_head', 'ip_authorize_head');
add_action( 'setup_theme', 'header_override', -1 /* early */ );

//plugin installation
//create ew table upon activating plugin
function ip_authorize_install()
{
    global $wpdb;
    $table = $wpdb->prefix . "ip_authorize";
	if($wpdb->get_var("show tables like '$table'") != $table) {
	    $sql = "CREATE TABLE " . $table . " (
					  id int(11) NOT NULL AUTO_INCREMENT,
					  ip varchar(25) NOT NULL,
					  authorize int(1) NOT NULL,
					  PRIMARY KEY (id)
					)";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	    dbDelta($sql);
	}
	
    $table = $wpdb->prefix . "ip_authorize_cookie";
	if($wpdb->get_var("show tables like '$table'") != $table) {
	    $sql = "CREATE TABLE " . $table . " (
					  id int(11) NOT NULL AUTO_INCREMENT,
					  ip_id varchar(25) NOT NULL,
					  auth_hash varchar(250) NOT NULL,
					  authorize int(1) NOT NULL,
					  PRIMARY KEY (id)
					)";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	    dbDelta($sql);
	}
	
     $table = $wpdb->prefix . "ip_authorize_location";
	if($wpdb->get_var("show tables like '$table'") != $table) {
	    $sql = "CREATE TABLE " . $table . " (
					  id int(1) NOT NULL AUTO_INCREMENT,
					  url varchar(100) NOT NULL,
					  authorize int(1) NOT NULL,
					  PRIMARY KEY (id)
					)";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	    dbDelta($sql);
	}
}

register_activation_hook(__FILE__,'ip_authorize_install');

function ip_authorize_deactivate()
{
	global $wpdb;
	$table = $wpdb->prefix . "ip_authorize";
	if($wpdb->get_var("show tables like '$table'") == $table) 
		{
			//$sql = "DROP TABLE IF EXISTS". $table;
			$wpdb->query("DROP TABLE IF EXISTS $table");
		}
	
	$table = $wpdb->prefix . "ip_authorize_location";
	if($wpdb->get_var("show tables like '$table'") == $table) 
		{
			//$sql = "DROP TABLE IF EXISTS". $table;
			$wpdb->query("DROP TABLE IF EXISTS $table");
		}
	
	$table = $wpdb->prefix . "ip_authorize_cookie";
	if($wpdb->get_var("show tables like '$table'") == $table) 
		{
			//$sql = "DROP TABLE IF EXISTS". $table;
			$wpdb->query("DROP TABLE IF EXISTS $table");
		}	
}

register_deactivation_hook(__FILE__, 'ip_authorize_deactivate' );

function ip_authorize_menu()
{
	$page = add_options_page('Authorize by IP', 'Authorize by IP', 'manage_options', 'IP-Authorize-slug', 'IP_Authorize_option');
	add_action("admin_print_scripts-" . $page, "authorize_head");
}

function authorize_head()
{
?>
	<script language="JavaScript">
	function addMyIP()
{
	var MyIP = document.getElementById('IP');
	MyIP.value = "<?php echo $_SERVER['REMOTE_ADDR']; ?>";
}
	</script>
<?php
}

function IP_Authorize_option()
{
	global $wpdb;
	switch($_GET['a'])
	{
		case'add-new-ip':
			if(!filter_var($_POST['IP'], FILTER_VALIDATE_IP))
			  {
				echo '<div id="message" class="updated fade"><p>Invalid IP. Only IPv4 is allowed!.</p></div>';
			  }
			else
			  {
			  	//first, check if IP is already in database
			  	//before adding new IP
			  	$rec_ip = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM wp_ip_authorize WHERE ip = '" . $_POST['IP'] . "';"));
			  		if ( $rec_ip == "0" )
			  			{
						  	//add to database
						  	//default value will be 1
						  	$wpdb->query("INSERT INTO wp_ip_authorize(ip, authorize) VALUES('" . $_POST['IP'] . "', '1')");
						  	echo '<div id="message" class="updated fade"><p>New IP added.</p></div>';
						  }
					else
						{
							echo '<div id="message" class="updated fade"><p>Failed to add new IP. Given IPv4 is already in database.</p></div>';
						}
			  }
		break;
		case'add-new-url-unauthorized':
			if(!filter_var($_POST['url'], FILTER_VALIDATE_URL))
			  {
			  	echo '<div id="message" class="updated fade"><p>Invalid URL.</p></div>';
			  }
			else
			  {
				  $wpdb->query("INSERT INTO wp_ip_authorize_location(url, authorize) VALUES('" . $_POST['url'] . "', '0')");
				  echo '<div id="message" class="updated fade"><p>New URL added.</p></div>';
			  }
		break;
		case'block-ip':
			$wpdb->query("UPDATE wp_ip_authorize SET authorize = '0' WHERE id = '" . $_GET['id'] . "'");
			$url = $wpdb->get_results("SELECT * FROM wp_ip_authorize WHERE id = '" . $_GET['id'] . "'");
				foreach ( $url as $url )
					{
						$ip = $url->ip;
					}	
			$wpdb->query("UPDATE wp_ip_authorize_cookie SET authorize = '0' WHERE ip = '" . $ip . "'");
			
			echo '<div id="message" class="updated fade"><p>Success!.</p></div>';
		break;
		case'allow-ip':
			$wpdb->query("UPDATE wp_ip_authorize SET authorize = '1' WHERE id = '" . $_GET['id'] . "'");
			echo '<div id="message" class="updated fade"><p>Success!.</p></div>';
			$url = $wpdb->get_results("SELECT * FROM wp_ip_authorize WHERE id = '" . $_GET['id'] . "'");
				foreach ( $url as $url )
					{
						$ip = $url->ip;
					}	
			$wpdb->query("UPDATE wp_ip_authorize_cookie SET authorize = '1' WHERE ip = '" . $ip . "'");
		break;
		case'delete-ip':
			$wpdb->query("DELETE FROM wp_ip_authorize WHERE id = '" . $_GET['id'] . "'");
			echo '<div id="message" class="updated fade"><p>You have deleted IP!.</p></div>';
		break;
		case'void-all-cookies':
			$wpdb->query("DELETE FROM wp_ip_authorize_cookie");
			echo '<div id="message" class="updated fade"><p>All cookies have been cleaned!.</p></div>';
		break;
		case'update-url':
			if( $_POST['auth_ip'] == "")
			{
				$wpdb->query("DELETE FROM wp_ip_authorize_location");
			}
			else
			{
				if(!filter_var($_POST['auth_ip'], FILTER_VALIDATE_URL))
				  {
				  	echo '<div id="message" class="updated fade"><p>Invalid URL.</p></div>';
				  }
				else
				  {
					$wpdb->query("UPDATE wp_ip_authorize_location SET url = '" . $_POST['auth_ip'] . "' WHERE authorize = '0'");
					echo '<div id="message" class="updated fade"><p>Url updated!.</p></div>' . $_POST['auth_ip'];
				  }
			}			
		break;
	}
?>
	<div class="wrap">
	 <h2>Welcome to Authorize by IP for Wordpress</h2>			
<?php
	
	$record = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM wp_ip_authorize;"));
		if ( $record != "0" )
			{
?>
		<div class="postbox" style = "padding: 10px;">
				<table width = "100%">
				 <tr>
				  <td><strong>IP</strong></td><td><strong>Authorized</strong></td><td><strong>Action</strong></td>
				 </tr>
<?php
			$ip = $wpdb->get_results("SELECT * FROM wp_ip_authorize ORDER BY id ASC");
				foreach ( $ip as $ip )
					{
						$id = $ip->id;
						$record_ip = $ip->ip;
						$authorize = $ip->authorize; 										
?>					
				 <tr><td><?php echo $record_ip; ?></td><td><?php if( $authorize == "1" ){ echo "Yes"; }else{ echo "No"; } ?></td><td><?php if( $authorize == "1" ){ echo '<input type="button" value="Block" onClick = "location.href=\'options-general.php?page=IP-Authorize-slug&a=block-ip&id=' . $id . '\';">'; }else{ echo '<input type="button" value="Allow"  onClick = "location.href=\'options-general.php?page=IP-Authorize-slug&a=allow-ip&id=' . $id . '\';">'; } ?> <input type="button" value="Delete"  onClick = "location.href='options-general.php?page=IP-Authorize-slug&a=delete-ip&id=<?php echo $id; ?>';"></td></tr>
<?php
					}
?>
				</table>
		</div>
<?php
			}
	


	//check if location has content for unauthorized
		
	$record = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM wp_ip_authorize_location WHERE authorize = '0';"));
		if ( $record != "0" )
			{
?>
		<div class="postbox" style = "padding: 10px;">
		
				<table width = "600px">
				 <tr>
				  <td>URL For Unauthorized IP: </td>
				 
<?php
			$url = $wpdb->get_results("SELECT * FROM wp_ip_authorize_location WHERE authorize = '0'");
				foreach ( $url as $url )
					{
						$url_auth = $url->url;
					}									
?>					
				 <td><form method="post" action="options-general.php?page=IP-Authorize-slug&a=update-url"><input type="text" name="auth_ip" value="<?php echo $url_auth; ?>" size="50"> <input type="submit" value="Update"></form></td>
				</tr>
				</table>
		</div>
<?php
			}
			else
			{
?>
		<div class="postbox" style = "padding: 10px;">
			<h2>Add New URL for Unauthorized IP</h2>
		<form method="post" enctype="multipart/form-data" action = "options-general.php?page=IP-Authorize-slug&a=add-new-url-unauthorized">
			<table width="500px" border="0" cellpadding="5" cellspacing="1" class="box" align = "center">
			<tr> 
				<td>
					URL: <input name="url" type="text" id="url"> 
				</td>
			</tr>
			<tr>
				<td width="80"><input name="submit" type="submit" class="box" id="upload_image_button" value=" Submit "></td>
			</tr>
		</table>
		</form>
		</div>
<?php			
			} 
			
		//check if cookie has value
		$record = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM wp_ip_authorize_cookie;"));
		if ( $record != "0" )
			{
?>
		<div class="postbox" style = "padding: 10px;">
		
				<table width = "500px">
				 <tr>
				  <td>Void all cookie authorizations: <input type="button" value="Yes" onClick="location.href='options-general.php?page=IP-Authorize-slug&a=void-all-cookies';"></td>
				 </tr>
				</table>
		</div>
<?php
			}
?>
		<div class="postbox" style = "padding: 10px;">
			<h2>Add New IP</h2>
			<div style = "padding: 5px;">Only IPv4 is allowed.</div>
		<form method="post" enctype="multipart/form-data" action = "options-general.php?page=IP-Authorize-slug&a=add-new-ip">
			<table width="500px" border="0" cellpadding="5" cellspacing="1" class="box" align = "center">
			<tr> 
				<td>
					IP: <input name="IP" type="text" id="IP"> <input type="button" value="Use my IP" onClick="addMyIP()">
				</td>
			</tr>
			<tr>
				<td width="80"><input name="submit" type="submit" class="box" id="upload_image_button" value=" Submit "></td>
			</tr>
		</table>
		</form>
		</div>	
		
	</div>
<?php
}

function ip_authorize_head()
{
	global $wpdb;
	
	//get browser's IP
	$cip = $_SERVER['REMOTE_ADDR'];
	
	//check if Unauthorized ip is supplied
	$url = $wpdb->get_results("SELECT * FROM wp_ip_authorize_location WHERE authorize = '0'");
		foreach ( $url as $url )
			{
				$unmatched_ip_location = $url->url;
			}
	
	if ( $unmatched_ip_location == "" )
	//no value, means null
	//takes no action
	{
		echo "\n <!-- Authorize by IP -->";
		echo "\n <!-- No value supplied -->";
		echo "\n<!-- End Authorize by IP -->";
	}
	else
	{
		
		//check if cookie is present
		if( isset( $_COOKIE['auth_ip'] ) )
		{
			//check if cookie is matched in database
			$record = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM wp_ip_authorize_cookie WHERE auth_hash = '". $_COOKIE['auth_ip'] ."';"));				
			
			if( $record == "0")
			{
				//wrong cookie, check ip in database
				//check if match in database
				$check = $wpdb->get_results("SELECT * FROM wp_ip_authorize WHERE ip = '$cip'");
					foreach( $check as $check )
					{
						$check->ip;
						$check->authorize;
					}
				//unmatched, means unauthorized
				if( !$check->ip )
				{
					echo "\n <!-- Authorize by IP -->";
					echo "\n <meta http-equiv='refresh' content='0;url=$unmatched_ip_location' />";
					echo "\n<!-- End Authorize by IP -->";
					exit();
				}
				//matched, check if authorized or not
				else
				{	
					if( $check->authorize == "1" )
					{
					//authorized, load wordpress url
					//and do nothing
					}
					else
					{		 
					//unauthorized, redirect to alternative site
					echo "\n <!-- Authorize by IP -->";
					echo "\n <meta http-equiv='refresh' content='0;url=$unmatched_ip_location' />";
					echo "\n <!-- End Authorize by IP -->";
					exit();
					}
				}
			}
			else
			{
				//check if cookie is allowed in database
				$cookie = $wpdb->get_results("SELECT * FROM wp_ip_authorize_cookie WHERE ip = '$cip'");
					foreach( $cookie as $cookie )
					{
						$cookie->ip;
						$cookie->authorize;
					}
				if( $cookie->authorize == "0" )
				{
					//unauthorized, redirect
					echo "\n <!-- Authorize by IP -->";
					echo "\n <meta http-equiv='refresh' content='0;url=$unmatched_ip_location' />";
					echo "\n <!-- End Authorize by IP -->";				
					exit();
				}
			}
		}
		else
		{				
			//check if match in database
			$check = $wpdb->get_results("SELECT * FROM wp_ip_authorize WHERE ip = '$cip'");
				foreach( $check as $check )
				{
					$check->ip;
					$check->authorize;
				}
			//unmatched, means unauthorized
			if( !$check->ip )
			{
				echo "\n <!-- Authorize by IP -->";
				echo "\n <meta http-equiv='refresh' content='0;url=$unmatched_ip_location' />";
				echo "\n<!-- End Authorize by IP -->";
				exit();
			}
			//matched, check if authorized or not
			else
			{	
				if( $check->authorize == "1" )
				{
				//authorized, load wordpress url
				//and do nothing
				}
				else
				{		
				//unauthorized, redirect to alternative site
				echo "\n <!-- Authorize by IP -->";
				echo "\n <meta http-equiv='refresh' content='0;url=$unmatched_ip_location' />";
				echo "\n <!-- End Authorize by IP -->";
				exit();
				}
			}
		}	
	}
}

function header_override()
{
	add_filter( 'wp_loaded', 'cookie_header' );
}

function cookie_header()
{
	global $wpdb;
	
	session_start();
	$auth_hash = md5( time() );
	
	$cip = $_SERVER['REMOTE_ADDR'];
	
	//check if match in database
	$check = $wpdb->get_results("SELECT * FROM wp_ip_authorize WHERE ip = '$cip'");
		foreach( $check as $check )
		{
			$check->ip;
			$check->authorize;
		}
	
	//unmatched, means unauthorized
	if( !$check->ip )
	{
	//do nothing
	}
	//matched, check if authorized or not
	else
	{	
		if( $check->authorize == "1" )
		{
		//authorized, cookie manipulation here
			if ( !isset( $_COOKIE["auth_ip"] ) )
			{
		 		setcookie("auth_ip", "$auth_hash", time()+60*60*24*30); 	 
		 	}
		 		//echo $auth_hash;
		 		$wpdb->query("INSERT INTO wp_ip_authorize_cookie(ip, auth_hash, authorize) VALUES('" . $_SERVER['REMOTE_ADDR'] . "', '" . $_COOKIE["auth_ip"] . "', '1')");
		}
	}
}

?>
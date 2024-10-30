<?php
/**
 * @package Highlight_Bookmark_Manager
 * @version 1.11.2
 */
/*
Plugin Name: Highlight Bookmark Manager
Plugin URI: http://www.hilight.cc/
Description: Allow readers to highlight and bookmark POSTs with simply highlight buttons. Easy to add personal notes on POSTs and share.
Author: HiLight
Version: 1.11.2
Author URI: http://www.hilight.cc/
*/

$sharedConfigVariableName = 'highlight_bookmark_manager_settings';
$generalConfigVariableName = 'highlight_bookmark_manager_general';

$highlight_bookmark_manager_settings = get_option($sharedConfigVariableName);
$highlight_bookmark_manager_general = get_option($generalConfigVariableName);
$highlight_bookmark_manager_settings_variables = array(
    'web_id',
    'web_secret'
);
if($highlight_bookmark_manager_settings===false)
{
	$new_options = array();
	foreach($highlight_bookmark_manager_settings_variables as $v)
	{
		$new_options[$v] = '';
	}
	add_option($sharedConfigVariableName, $new_options);
	$highlight_bookmark_manager_settings = $new_options;
}

$highlight_bookmark_manager_status = null;

/*
FrontEnd
*/
// tool insert on page & single
if( !isset($highlight_bookmark_manager_general['web_id']) || (isset($highlight_bookmark_manager_general['web_id'])&&strlen($highlight_bookmark_manager_general['web_id'])==32) )
{
	// 
	add_action('wp_footer', 'highlight_bookmark_js');
}
function highlight_bookmark_js() {
	global $wp_query, $highlight_bookmark_manager_general;

  	if( $wp_query->is_single() || $wp_query->is_page() )
  	{
		echo "<script>
			if(typeof HiLightToolMode === \"undefined\"){
			var head = document.getElementsByTagName('head')[0];
			var hlt = document.createElement('script'); hlt.type = 'text/javascript'; hlt.id = 'HiLightJSMain';
			hlt.src = ('https:' == document.location.protocol ? 'https:' : 'http:') + '//hilight.club/plugin/hl/".(isset($highlight_bookmark_manager_general['web_id'])? $highlight_bookmark_manager_general['web_id']:'8wHu9dshVPRvm2nG').".js?_v='+Math.floor((new Date().getTime())/60000);
			head.appendChild(hlt);
			}
			</script>";
  	}
}
// end of tool insert on page & single


/*
BackEnd
*/
// notify
function hbm_notify_setting()
{
	global $highlight_bookmark_manager_general, $highlight_bookmark_manager_status;

	$tmp = explode('://', get_bloginfo('siteurl'));
	$hostname = $tmp[1];
	if(!isset($highlight_bookmark_manager_status['bookmarks']))
	{
		if($feedback_from_server = file_get_contents('http://manager.hilight.cc/api/wp_plugin/status?website_domain='.$hostname))
		{
			$feedback = json_decode($feedback_from_server, true);
			if($feedback['success'])
			{
				$highlight_bookmark_manager_status = $feedback['data'];
			}
			
		}
	}
	if(!$highlight_bookmark_manager_general || !isset($highlight_bookmark_manager_general['web_id']))
	{
		$sum = 0;
		if(isset($highlight_bookmark_manager_status['quotes']))
		{
			$sum += $highlight_bookmark_manager_status['quotes'];
		}
		if(isset($highlight_bookmark_manager_status['bookmarks']))
		{
			$sum += $highlight_bookmark_manager_status['bookmarks'];
		}
		echo '<p id="hbm_notify" class="update-nag">';
		echo 'Your website had ';
		echo ($highlight_bookmark_manager_status['quotes']? $highlight_bookmark_manager_status['quotes']:0).' quotes and ';
		echo ($highlight_bookmark_manager_status['bookmarks']? $highlight_bookmark_manager_status['bookmarks']:0).' bookmarks. ';
		echo '<a href="' .admin_url('admin.php') . '?page=highlight_bookmark_manager">Click Here</a> to validate your website and get more detail report!';
		echo '</p>';
	}
}
add_action( 'admin_notices', 'hbm_notify_setting' );
// End of notify

// admin menu & styling
add_action( 'admin_menu', 'hbm_plugin_menu' );
function hbm_plugin_menu()
{
	add_options_page( 'Highlight Bookmark Manager Options', 'Highlight Bookmark Manager', 'manage_options', 'highlight_bookmark_manager', 'hbm_general_options' );
}
function hbm_general_options()
{
	global $wp_query, $wp_post, $highlight_bookmark_manager_general, $highlight_bookmark_manager_status, $cmsConnector;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	// 
	$tmp = explode('://', get_bloginfo('siteurl'));
	$hostname = $tmp[1];

	if(!isset($highlight_bookmark_manager_status['bookmarks']))
	{
		if($feedback_from_server = file_get_contents('http://manager.hilight.cc/api/wp_plugin/status?website_domain='.$hostname))
		{
			$feedback = json_decode($feedback_from_server, true);
			if($feedback['success'])
			{
				$highlight_bookmark_manager_status = $feedback['data'];
			}
			
		}
	}

	echo '<div class="wrap">';
	echo '<div class="Header"><h1><img src="http://www.hilight.cc/assets/images/wp_icon_128x128.png" height="32" style="vertical-align:middle;">Highlight Bookmark Manager Settings</h1></div>';
	echo '<div class="Card-Small"><h2>Quotes</h2><div class="number">'.(isset($highlight_bookmark_manager_status['quotes'])&&$highlight_bookmark_manager_status['quotes']? $highlight_bookmark_manager_status['quotes']:0).'</div><div style="clear:both;"></div><div class="more"><a href="http://www.hilight.cc/#add-ons" target="_blank">more</a></div><div style="clear:both;"></div></div>';
	echo '<div class="Card-Small"><h2>Notes</h2><div class="number">'.(isset($highlight_bookmark_manager_status['notes'])&&$highlight_bookmark_manager_status['notes']? $highlight_bookmark_manager_status['notes']:0).'</div><div style="clear:both;"></div><div class="more"><a href="http://www.hilight.cc/#add-ons" target="_blank">more</a></div><div style="clear:both;"></div></div>';
	echo '<div class="Card-Small"><h2>Bookmarks</h2><div class="number">'.(isset($highlight_bookmark_manager_status['bookmarks'])&&$highlight_bookmark_manager_status['bookmarks']? $highlight_bookmark_manager_status['bookmarks']:0).'</div><div style="clear:both;"></div><div class="more"><a href="http://www.hilight.cc/#add-ons" target="_blank">more</a></div><div style="clear:both;"></div></div>';
	echo '<div style="clear:both;"></div>';
	echo '<form id="highlight-bookmark-manager-settings" method="post" action="options.php">';
	    settings_fields('highlight_bookmark_manager');
		echo '<div class="Card">
		            <div class="Card-hd">';
						if(!isset($highlight_bookmark_manager_general['web_id']))
						{
							echo '<h3 class="Card-hd-title">Fill below information to get a detailed report for free.</h3>';
						}
						else
						{
							echo '<h3 class="Card-hd-title">Information</h3>';
						}
		        echo  '</div>
		            <div class="Card-bd">';
				echo    '<table class="form-table">
							<tbody>
								<tr>
									<td colspan="2">';
									if(!isset($highlight_bookmark_manager_general['web_id'])){
										echo '<span style="color:#a94442;">Congratulations!The installation succeeded.Now Fill Web Id & Web Secret to get a free daily report.</span>';
									}
									else
									{
										echo '<span style="color:#a94442;">Congratulations!The installation succeeded.</span>';
									}
				echo				'</td>
								</tr>
								<tr>
									<th scope="row"><label for="web_id">Web Hostname</label></th>
									<td>
										'.get_bloginfo('siteurl').'
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="web_id">Web Id</label></th>
									<td>
										<input name="highlight_bookmark_manager_settings[web_id]" class="option-input" type="text" id="web_id" value="'.($highlight_bookmark_manager_general? $highlight_bookmark_manager_general['web_id']:'').'">
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="web_id">Web Secret</label></th>
									<td>
										<input name="highlight_bookmark_manager_settings[web_secret]" class="option-input" type="text" id="web_secret" value="'.($highlight_bookmark_manager_general? $highlight_bookmark_manager_general['web_secret']:'').'">
									</td>
								</tr>
								<tr>
									<td colspan="2" align="right">
										<input type="submit" name="submit" value="Submit" class="button button-primary">
									</td>
								</tr>
							</tbody>
						</table>';

						if(!isset($highlight_bookmark_manager_general['web_id']))
						{
							echo '<hr>';
							echo '<h2>How to get my web id and web secret?</h2>';
							echo '<p>';
							echo 'It\'s easy, just three steps!';
							//echo '<ol>';
							echo '<div>Step1. Login HighLight Bookmark Manager Console. <a href="http://manager.hilight.cc/validate/account/wordpress?domain='.urlencode($hostname).'&name='.urlencode(get_bloginfo('name')).'" target="_blank">Start Here</a> for free!</div>';
							echo '<div>Step2. Apply new account for your website with name and domain.</div>';
							echo '<div>Step3. Copy the website\'s Web id and Web secret.</div>';
							//echo '</ol>';
							echo '</p>';
						}
		        echo    '</div>
		        </div>';
		echo '</div>';
	echo '</form>';
}

add_action('admin_enqueue_scripts', 'load_hbm_wp_admin_style');
function load_hbm_wp_admin_style() {
	wp_register_style( 'hbm_wp_admin_css', plugin_dir_url( __FILE__ ) . 'css/options-page.css', false, '1.0.0' );
	wp_enqueue_style( 'hbm_wp_admin_css' );
}
// end of admin menu & styling

// admin option update
add_action('init', 'highlight_bookmark_manager_init');
function highlight_bookmark_manager_init()
{
    global $cmsConnector;

    //if(($cmsConnector->getCmsMinorVersion() >= 2.7 || $cmsConnector->assumeLatest())&& is_admin()) 
    //{
        add_action('admin_init', 'update_highlight_bookmark_manager_settings');
    //}
}
function update_highlight_bookmark_manager_settings() {
	global $sharedConfigVariableName;
    register_setting('highlight_bookmark_manager', $sharedConfigVariableName, 'highlight_bookmark_manager_save_settings');
}

function highlight_bookmark_manager_save_settings($input)
{
	global $sharedConfigVariableName, $highlight_bookmark_manager_settings, $highlight_bookmark_manager_settings_variables, $generalConfigVariableName;
	$newConfigs = array();
	foreach ($highlight_bookmark_manager_settings_variables as $variable)
	{
        if(isset($input[$variable]))
        {
        	// variable check
        	switch ($variable)
        	{
        		case 'web_id':
        			if(strlen($input[$variable])==32)
        			{
            			$newConfigs[$variable] = $input[$variable];
        			}
			        else
			        {
			        	return false;
			        }
        		break;
        		case 'web_secret':
        			if(strlen($input[$variable])==32)
        			{
            			$newConfigs[$variable] = $input[$variable];
        			}
			        else
			        {
			        	return false;
			        }
        		break;
        		case 'default':
        		break;
        	}
        }
        else
        {
        	//$newConfigs[$variable] = isset($highlight_bookmark_manager_settings[$variable]) ? $highlight_bookmark_manager_settings[$variable]:'';
        	return false;
        }
    }
	if($highlight_bookmark_manager_general===false)
	{
		add_option($generalConfigVariableName, $newConfigs);
	}
	else
	{
		update_option($generalConfigVariableName, $newConfigs);
	}
}
// end of admin option update
?>

<?php
namespace SIM\MANDATORY;
use SIM;

use function SIM\getModuleOption;
use function SIM\getValidPageLink;

const MODULE_VERSION		= '8.0.8';

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_mandatory_options', __NAMESPACE__.'\moduleOptions', 10, 2);
function moduleOptions($optionsHtml, $settings){
	ob_start();
    ?>
	<label for="reminder_freq">How often should people be reminded of remaining content to read</label>
	<br>
	<select name="reminder_freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['reminder_freq']);
		?>
	</select>
	<?php

	return $optionsHtml.ob_get_clean();
}

add_filter('sim_email_mandatory_settings', __NAMESPACE__.'\emailSettings', 10, 2);
function emailSettings($html, $settings){
	ob_start();
    ?>
	<h4>E-mail with read reminders</h4>
	<label>Define the e-mail people get when they shour read some mandatory content.</label>
	<?php
	$readReminder    = new ReadReminder(wp_get_current_user());
	$readReminder->printPlaceholders();
	$readReminder->printInputs($settings);

	return $html.ob_get_clean();
}

add_filter('sim_module_mandatory_data', __NAMESPACE__.'\moduleData');
function moduleData($dataHtml){
	//Get all the pages with an audience meta key
	$pages = get_posts(
		array(
			'orderby' 		=> 'post_name',
			'order' 		=> 'asc',
			'post_type' 	=> 'any',
			'post_status' 	=> 'publish',
			'meta_query'	=> [
				[
					'key' 		=> "audience",
					'compare'	=> 'EXISTS'
				],
				[
					'key' 		=> "audience",
					'value'		=> 'a:0:{}',
					'compare'	=> '!='
				],
				[
					'key' 		=> "audience",
					'value'		=> '',
					'compare'	=> '!='
				]
			],
			'numberposts'	=> -1				// all posts
		)
	);

	$keys	= getAudienceOptions(['empty'], 1);
	unset($keys['everyone']);

	$html	= '<script>';
		$html	.= "function showUserList(pageId, button){";
			$html	.= "document.querySelector(`#wrapper-\${pageId}`).classList.toggle('hidden');"; 
			$html	.= "if(button.textContent.includes('Show')){";
				$html	.= "button.textContent	= button.textContent.replace('Show', 'Hide')"; 
			$html	.= "}else{";
				$html	.= "button.textContent	= button.textContent.replace('Hide', 'Show')"; 
			$html	.= "}";
		$html	.= "}";
	$html	.= '</script>';

	$html	.= "<table class='mandatory-pages-overview'>";
		$html	.= "<thead>";
			$html	.= "<tr>";
				$html	.= "<th>Page</th>";
				$html	.= "<th>Users</th>";
			$html	.= "</tr>";
		$html	.= "</thead>";
		$html	.= "<tbody>";
			foreach($pages as $page){
				$audience   = get_post_meta($page->ID, 'audience', true);
				if(!is_array($audience) && !empty($audience)){
					$audience  = json_decode($audience, true);
				}

				$url	= get_permalink($page->ID);

				$users	= [];

				$html	.= "<tr>";
					$html	.= "<td><a href='$url'>{$page->post_title}</a></td>";

					// Evryone should read this
					if(isset($audience['everyone']) || ( isset($audience['beforearrival']) && isset($audience['afterarrival']))){
						$metaQuery	= array(
							array(
								'key' 		=> 'read_pages',
								'value' 	=> $page->ID,
								'compare' 	=> 'NOT LIKE'
							)
						);
					}elseif(isset($audience['beforearrival'])){
						$metaQuery	=  array(
							'relation' => 'AND',
							array(
								'key' 		=> 'read_pages',
								'value' 	=> $page->ID,
								'compare' 	=> 'NOT LIKE'
							),
							array(
								'key' 		=> 'arrival_date',
								'value' 	=> Date('Y-m-d'),
								'compare' 	=> '>'
							),
						);
					}elseif(isset($audience['afterarrival'])){
						$metaQuery	=  array(
							'relation' => 'AND',
							array(
								'key' 		=> 'read_pages',
								'value' 	=> $page->ID,
								'compare' 	=> 'NOT LIKE'
							),
							array(
								'key' 		=> 'arrival_date',
								'value' 	=> Date('Y-m-d'),
								'compare' 	=> '<'
							),
						);
					}

					// get all users who have not read this page/post
					$users	= get_users(
						array(
							'orderby'		=> 'display_name',
							'count_total'	=> false,
							'fields'		=> ['display_name', 'ID'],
							'meta_query' 	=> $metaQuery
						)
					);
					
					if(!empty($users)){
						$count			= count($users);
						$cell			= "$count users still have to read this.";
						$userEditPage	= getValidPageLink(getModuleOption('usermanagement', 'user_edit_page'));
						$cell	.= "<div id='wrapper-$page->ID' class='hidden'>";
							foreach($users as $user){
								$cell	.= "<a href='$userEditPage?userid=$user->ID'>$user->display_name<br>";
							}
						$cell	.= "</div>";
					}else{
						$cell	= "Read by everyone";
					}
					$html	.= "<td>$cell</td>";

					$html	.= "<td><button class='small show-user-list' onclick='showUserList($page->ID, this)'>Show who</button></td>";
					
				$html	.= "</tr>";
			}			
		$html	.= "</tbody>";
	$html	.= "</table>";


	return $dataHtml.$html;
}


add_filter('sim_module_mandatory_after_save', __NAMESPACE__.'\moduleUpdated');
function moduleUpdated($options){
	scheduleTasks();

	$roleSet = get_role( 'contributor' )->capabilities;

	// Only add the new role if it does not exist
	if(!wp_roles()->is_role( 'no_man_docs' )){
		add_role(
			'no_man_docs',
			'No mandatory documents',
			$roleSet
		);
	}

	return $options;
}
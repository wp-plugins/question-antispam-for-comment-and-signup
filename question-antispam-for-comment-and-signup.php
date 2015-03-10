<?php
/*
 Plugin Name: Question Antispam for Comment and Signup
 Plugin URI: http://qdb.wp.kukmara-rayon.ru/wp-ms-question-antispam/
 Description: Question and answer as antispam in signup and comment forms of Wordpress, set by admin, supports Multisite mode. The antispam question does not appear in single site mode registration.
 Author: Dinar Qurbanov
 Author URI: http://qdb.wp.kukmara-rayon.ru/
 Version: 0.0.5

 I have used WordPress Hashcash code, also I have looked at buhsl-Captcha, Cookies for Comments, Peter's Custom Anti-Spam codes, to learn and use their codes, and also copied something from them

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

2011 04 07
bug fixed, v 0.0.2
2011 09 03
add this antispam question to comment form
-- -- 04
move changelog into this file
move this file out from folder, delete folder
rename from "Signup Question Captcha" to "Wordpress Multisite Question Antispam"
rename this file from "signup-question-captcha.php" to "wp-ms-question-antispam.php"
replace all 'sqc' to 'wpmsqas' in this file.
change description from "Questions as CAPTCHA" to "Question and answer as antispam in signup and comment forms of Wordpress, set by admin, supports Multisite mode."
create plugin page http://qdb.wp.kukmara-rayon.ru/wp-ms-question-antispam/
change "Plugin URI" from http://qdb.wp.kukmara-rayon.ru/ to the plugin page.
change version from 0.0.2 to 0.0.3
direct "die" in "preprocess_comment" instead of setting "wp_delete_comment" and "die" in "comment_post", as in Peter's Custom Anti-Spam
add "I have used WordPress Hashcash code, also I have looked at buhsl-Captcha, Cookies for Comments, Peter's Custom Anti-Spam codes, to learn and use their codes" between version line and licence explanation
discover that antispam question and answer are same in all blogs, that is bad because blogs are in different languages
write a message in 3 languages
fix texts in admin page
have corrected it, same answers in all blogs, looking at Cookies for Comments code
change version to 0.0.4
seems when using "direct die" spam comments are saved, but not published, i change it back to old method
changing back to old method seems has not helped
have discovered that answer form is here even for logged in user
fix that with help of buhsl-captcha code
2011-09-14 15:56 utc+4:
code
`// admins can do what they like
if( is_admin() ){`
was not correct, is_admin do not mean user is admin, but that page is admin page. now i use is_user_logged_in() instead of it. i had copied the code, that now have appeared as incorrect, from wp-hashcash.
2011-10-19 8:54 utc+4 :
i want to prepare to set in wordpress plugins site. should move into folder. and make readme file.
i have renamed: from wp-ms-question-antispam to wp-simple-qa-antispam because signup is not only in multisite. qa is question-answer. i want to name this wp-signup-comment-simple-question-answer-antispam. i have changed my mind, i want to publish this in my site as single .php file before i make it prepared for wordpress plugins site. ah and "ms" is needed, because some plugins do not support ms mode, they are buggy in ms.
renamed to wp-ms-signup-comment-simple-question-answer-antispam.php
to do list: should make buddypress compatible. should make option for ms admin to change questions and answers in all blogs.
2011-11-7: once i have seen that old method to delete comments also leave some of them for moderation, for that, i am going to set it back to new "direct die" method. ... i have set it. now i going to set comparing answer with modifying to lowercase. ... i have set it now.
2013-11-03: i had not installed this in wp plugins site, i tried "wordpress-multisite-question-antispam" but "wordpress" was not alowed. now i try again , without that word. i rename: from Wordpress Multisite Question Antispam to Question Antispam. also wp-ms-question-antispam to question-antispam in plugin url in my blog ... that is private page yet
description:
antispam question for signup and comment forms of wordpress
2014-07-07:
going to make fixes for wordpress org plugins site
rename to Question Antispam for Comment and Signup, file and directory to question-antispam-for-comment-and-signup
version 0 0 5
*/

function wpmsqas_option($save = false){
	if($save) {
		/*if( function_exists( 'update_site_option' ) ) {
			update_site_option('plugin_signup_question_captcha', $save);
		} else {
			update_option('plugin_signup_question_captcha', $save);
		}*/
		update_option('question_antispam_plugin', $save);

		return $save;
	} else {
		/*if( function_exists( 'get_site_option' ) ) {
			$options = get_site_option('plugin_signup_question_captcha');
		} else {
			$options = get_option('plugin_signup_question_captcha');
		}*/
		$options = get_option('question_antispam_plugin');

		if(!is_array($options))
			$options = array();

		return $options;
	}
}

/**
 * Install WP Hashcash
 */

function wpmsqas_install () {
	// set our default options
	$options = wpmsqas_option();
	$options['comments-spam'] = $options['comments-spam'] || 0;
	$options['comments-ham'] = $options['comments-ham'] || 0;
	$options['signups-spam'] = $options['signups-spam'] || 0;
	$options['signups-ham'] = $options['signups-ham'] || 0;
	
	/*
	// akismet compat check
	if(function_exists('akismet_init')){
		$options['moderation'] = 'spam';
	} else {
		$options['moderation'] = 'delete';
	}
	*/
	$options['moderation'] = 'spam';
	
	// logging
	$options['logging'] = true;

	//question and answer
	$options['question'] = '10+10=?';
	$options['answer'] = '20';

	$options[ 'installed' ]=true;

	// update the key
	wpmsqas_option($options);
}

add_action('activate_signup_question_captcha', 'wpmsqas_install');

/**
 * Our plugin can also have a widget
 */

function wpmsqas_get_spam_ratio( $ham, $spam ) {
	if($spam + $ham == 0)
		$ratio = 0;
	else
		$ratio = round(100 * ($spam/($ham+$spam)),2);

	return $ratio;
}

function wpmsqas_widget_ratio($options){
	$signups_ham = (int)$options['signups-ham'];
	$signups_spam = (int)$options['signups-spam'];
	$ham = (int)$options['comments-ham'];
	$spam = (int)$options['comments-spam'];
	$ratio = wpmsqas_get_spam_ratio( $ham, $spam );
	$signups_ratio = wpmsqas_get_spam_ratio( $signups_ham, $signups_spam );

	$msg = "<li><span>$spam spam comments are blocked out, $ham comments are allowed.  " . $ratio ."% of your comments are spam!</span></li>";

	if( $signups_ham && $signups_spam )
		$msg = "<li><span>$signups_spam spam signups are blocked out, $signups_ham signups are allowed.  " . $signups_ratio ."% of your signups are spam!</span></li>";

	return $msg;
}


/**
 * Admin Options
 */

add_action('admin_menu', 'wpmsqas_add_options_to_admin');

function wpmsqas_add_options_to_admin() {
/*	if( function_exists( 'is_site_admin' ) && !is_site_admin() )
		return;

	if (function_exists('add_options_page')) {
		if( function_exists( 'is_site_admin' ) ) {
			add_submenu_page('wpmu-admin.php', __('Signup Question Captcha'), __('Signup Question Captcha'), 'manage_options', 'wpmsqas_admin', 'wpmsqas_admin_options');
		} else {
			add_options_page('Signup Question Captchah', 'Signup Question Captcha', 8, basename(__FILE__), 'wpmsqas_admin_options');
		}
	}*/
	add_options_page('Question Antispam', 'Question Antispam', 'manage_options', 'wpmsqas_config', 'wpmsqas_admin_options');
}

function wpmsqas_admin_options() {
	/*if( function_exists( 'is_site_admin' ) && !is_site_admin() )
		return;*/
	/*if ( isset($_POST['submit']) ) {
		if ( function_exists('current_user_can') && !current_user_can('manage_options') )
			die(__('Cheatin&#8217; uh?'));

		check_admin_referer( 'cfc' );
		if( $_POST[ 'cfc_spam' ] == 'spam' || $_POST[ 'cfc_spam' ] == 'delete' ) {
			update_option( 'cfc_spam', $_POST[ 'cfc_spam' ] );
		}
		update_option( 'cfc_speed', (int)$_POST[ 'cfc_speed' ] );
		if ( $_POST[ 'cfc_delivery' ] == 'css' || $_POST[ 'cfc_delivery' ] == 'img' )
			update_option( 'cfc_delivery', $_POST[ 'cfc_delivery' ] );
	}*/

	$options = wpmsqas_option();

	if( !isset( $options[ 'installed' ] ) ) {
		wpmsqas_install(); // MU has no activation hook
		$options = wpmsqas_option();
	}

	// POST HANDLER
	if($_POST['wpmsqas-submit']){
		check_admin_referer( 'wpmsqas-options' );
		if ( function_exists('current_user_can') && !current_user_can('manage_options') )
			die('Current user not authorized to managed options');

		$options['moderation'] = strip_tags(stripslashes($_POST['wpmsqas-moderation']));
		$options['logging'] = strip_tags(stripslashes($_POST['wpmsqas-logging']));
		$options['question'] = strip_tags(stripslashes($_POST['wpmsqas-question']));
		$options['answer'] = strip_tags(stripslashes($_POST['wpmsqas-answer']));
		wpmsqas_option($options);
	}
	
	// MAIN FORM
	echo '<style type="text/css">
		.wrap h3 { color: black; background-color: #e5f3ff; padding: 4px 8px; }

		.sidebar {
			border-right: 2px solid #e5f3ff;
			width: 200px;
			float: left;
			padding: 0px 20px 0px 10px;
			margin: 0px 20px 0px 0px;
		}

		.sidebar input {
			background-color: #FFF;
			border: none;
		}

		.main {
			float: left;
			width: 600px;
		}

		.clear { clear: both; }

		.input {width:100%;}
	</style>';

	echo '<div class="wrap">';

	echo '<div class="sidebar">';
	echo '<h3>Plugin</h3>';
	echo '<ul>
	<li><a href="http://qdb.wp.kukmara-rayon.ru/wp-ms-question-antispam/">Plugin\'s Homepage</a></li>';
	/*if( function_exists( 'is_site_admin' ) && is_site_admin() ) {
		echo '<li><a href="http://mu.wordpress.org/forums/">WordPress MU Forums</a></li>';
	}*/
	//echo '<li><a href="http://wordpress.org/tags/wp-hashcash">Plugin Support Forum</a></li>';
	echo '</ul>';		
	echo '<h3>Statistics</h3>';
	echo '<p>'.wpmsqas_widget_ratio($options).'</p>';
	echo '</div>';

	echo '<div class="main">';
	echo '<h2>Question Antispam</h2>';

	//echo '<h3>Standard Options</h3>';
	echo '<form method="POST" action="?page=' . $_GET[ 'page' ] . '&updated=true">';
	wp_nonce_field('wpmsqas-options');
	if( function_exists( 'is_site_admin' ) ) { // MU only
		//echo "<p>'Here was MU only block'</p>";
	}
	// moderation options
	$moderate = htmlspecialchars($options['moderation'], ENT_QUOTES);
	echo '<p><label for="wpmsqas-moderation">' . __('Moderation:', 'wpmsqas') . '</label>';
	echo '<select id="wpmsqas-moderation" name="wpmsqas-moderation">';
	//echo '<option value="moderate"'.($moderate=='moderate'?' selected':'').'>Moderate</option>';
	echo '<option value="spam"'.($moderate=='spam'?' selected':'').'>Move to spam</option>';
	echo '<option value="delete"'.($moderate=='delete'?' selected':'').'>Delete</option>';
	echo '</select>';
	echo '</p>';
	//question and answer
	echo '<p><label for="wpmsqas-question">' . __('Question:', 'wpmsqas') . '</label>';
	echo '<input id="wpmsqas-question" name="wpmsqas-question" value="'.$options['question'].'" class="input" />';
	echo '<p><label for="wpmsqas-answer">' . __('Answer:', 'wpmsqas') . '</label>';
	echo '<input id="wpmsqas-answer" name="wpmsqas-answer" value="'.$options['answer'].'" class="input" />';

	/*
	// logging options
	echo '<h3>Logging:</h3>';

	$logging = htmlspecialchars($options['logging'], ENT_QUOTES);
	echo '<p><label for="wpmsqas-logging">Logging</label>
		<input name="wpmsqas-logging" type="checkbox" id="wpmsqas-logging"'.($logging?' checked':'').'/> 
		<br /><span style="color: grey; font-size: 90%;">Logs the reason why a given comment failed the spam
		check into the comment body.  Works only if moderation / akismet mode is enabled.</span></p>';
	*/
	echo '<input type="hidden" id="wpmsqas-submit" name="wpmsqas-submit" value="1" />';
	echo '<input type="submit" id="wpmsqas-submit-override" name="wpmsqas-submit-override" value="Save Signup Question Captcha Settings"/>';
	echo '</form>';
	echo '</div>';

	echo '<div class="clear">';
	echo '<p style="text-align: center; font-size: .85em;">Author: Dinar Qurbanov, using free plugins\' codes</p>';
	echo '</div>';

	echo '</div>';
}

/**
 * Hook into the signups form
 */

function wpmuSignupForm( $errors ) {

	echo('<label for="wpmsqas_answer">Question against spammers:</label>');
	$error = $errors->get_error_message('captcha_wrong');
	if( isset($error) && $error != '') {
		echo '<p class="error">' . $error . '</p>';
	}
	$options = wpmsqas_option();
	echo('<label for="wpmsqas_answer">'.$options['question'].'</label><input type="text" name="wpmsqas_answer" />');	
}
add_action('signup_extra_fields', 'wpmuSignupForm');

/**
 * Validate our tag
 */

function wpmsqas_check_signup_question( $result ) {
	// get our options
	$options = wpmsqas_option();
	$spam = false;
	if( !strpos( $_SERVER[ 'PHP_SELF' ], 'wp-signup.php' ) || $_POST['stage'] == 'validate-blog-signup' || $_POST['stage'] == 'gimmeanotherblog' )
		return $result;

	// Check the wphc values against the last five keys
	$spam = ($_POST['wpmsqas_answer']!=$options['answer']);
	
	if($spam){
		$options['signups-spam'] = ((int) $options['signups-spam']) + 1;
		wpmsqas_option($options);
		$result['errors']->add( 'captcha_wrong', __('Answer is not correct.') );
	//echo '<p class="error">OK</p>';
	} else {
		$options['signups-ham'] = ((int) $options['signups-ham']) + 1;
		wpmsqas_option($options);
	}
	
	return $result;
}

add_filter( 'wpmu_validate_blog_signup', 'wpmsqas_check_signup_question' );
add_filter( 'wpmu_validate_user_signup', 'wpmsqas_check_signup_question' );





function wpmsqas_add_commentform(){
	global $user_ID;
	if (isset($user_ID) && intval($user_ID) > 0 ) {
		// skip the CAPTCHA 
		return true;
	}
	$options = wpmsqas_option();
	echo('<label for="wpmsqas_answer">'.$options['question'].'</label><input type="text" name="wpmsqas_answer" />');	
}

add_action('comment_form', 'wpmsqas_add_commentform');

function wpmsqas_check_comment_antispam_answer( $comment ) {
	// admins can do what they like
	/*if( is_admin() ){
		return $comment;
	}else{
		echo 'OK';
		exit;
	}*/
	if(is_user_logged_in()){
		return $comment;
	}
	// get our options
	// get our options
	$type = $comment['comment_type'];
	$options = wpmsqas_option();
	$spam = false;
	if($type == "trackback" || $type == "pingback"){
	} else {
		// Check the wphc values against the last five keys
		$spam = (
mb_strtolower($_POST['wpmsqas_answer'])!=
mb_strtolower($options['answer'])
		);
		//if($options['logging'] && $spam)
		//	$comment['comment_content'] .= "???";
	}

	if($spam){
		$options['comments-spam'] = ((int) $options['comments-spam']) + 1;
		wpmsqas_option($options);
			
		switch($options['moderation']){
			case 'delete':
				//add_filter('comment_post', create_function('$id', 'wp_delete_comment($id); die(\'Антиспам сорауга җавап дөрес түгел. Кире кайтыгыз.<br>Ответ на антиспамный вопрос неправилен. Вернитесь на предыдущую страницу<br>Your answer to antispam question is not correct. Go back to the previous page\');'));
				header("Content-Type: text/html; charset=utf-8");
				die('Антиспам сорауга җавап дөрес түгел. Кире кайтыгыз.<br>Ответ на антиспамный вопрос неправилен. Вернитесь на предыдущую страницу<br>Your answer to antispam question is not correct. Go back to the previous page');
				break;
			case 'spam':
				add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
				break;
			/*case 'moderate':
			default:
				add_filter('pre_comment_approved', create_function('$a,$b', ' return 0;'));
				break;*/
		}
	} else {
		add_filter('pre_comment_approved', create_function('$a', 'return 1;'));
		$options['comments-ham'] = ((int) $options['comments-ham']) + 1;
		wpmsqas_option($options);
	}
	
	return $comment;


}

add_action('preprocess_comment', 'wpmsqas_check_comment_antispam_answer');

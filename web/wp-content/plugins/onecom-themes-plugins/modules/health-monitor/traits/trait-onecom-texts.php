<?php
declare( strict_types=1 );

trait OnecomHMTexts {
	public $action_title       = 'action_title';
	public $overview           = 'overview';
	public $fix_button_text    = 'fix_button_text';
	public $ignore_link_text   = 'ignore_link_text';
	public $unignore_link_text = 'unignore_link_text';
	public $how_to_fix         = 'how_to_fix';
	public $how_to_fix_lite    = 'how_to_fix_lite';
	public $fix_confirmation   = 'fix_confirmation';
	public $upsell_text        = 'upsell_text';
	public $text               = array();
	public $revert_text;
	public $ignore_text;
	public $unignore_text;
	public $text_domain = 'onecom-wp';
	public $fix_text;
	public $ignore_critical_text;
	public $status_text;
	public $status_desc     = 'status_desc';
	public $status_resolved = 0;
	public $status_open     = 1;
	public $hm_description;
	public $hm_description_premium;
	public $ignored_lite_text;
	public $get_started;
	public $upgrade_modal_text = array();
	public $open_modal_link    = '';
	public $change_key;
	public $save_key;
	public $quick_fix_messages = array();
	public $table_prefix;

	public function init_trait() {
		$this->change_key             = __( 'Change', 'onecom-wp' );
		$this->save_key               = __( 'Save', 'onecom-wp' );
		$this->revert_text            = __( 'Revert', 'onecom-wp' );
		$this->ignore_text            = __( 'Ignore in future scans', 'onecom-wp' );
		$this->unignore_text          = __( 'Unignore', 'onecom-wp' );
		$this->fix_text               = __( 'How to fix', 'onecom-wp' );
		$this->ignore_critical_text   = __( 'Ignore for 24 hours', 'onecom-wp' );
		$this->status_text            = __( 'Status', 'onecom-wp' );
		$this->hm_description         = __( 'Health Monitor lets you monitor the essential security and performance checkpoints and fix them if needed.', 'onecom-wp' );
		$this->hm_description_premium = __( 'Monitor essential security and performance checkpoints, and fix them if needed. With the Pro version, you get the quick fix, ignore, and more functionalities.', 'onecom-wp' );
		$this->ignored_lite_text      = __( 'Get access to ignore functionality and more for free.', 'onecom-wp' );
		$this->get_started            = __( 'Get started', 'onecom-wp' );
		$this->open_modal_link        = '<a class="onecom__open-modal"> ' . __( 'Free upgrade', 'onecom-wp' ) . '</a>';
		$this->init_texts();
		$this->init_fix_messages();
		global $wpdb;
		$this->table_prefix = $wpdb->prefix ?? '$prefix_';
	}

	public function init_texts() {
		$this->text['uploads_index']       = array(
			$this->action_title     => __( 'Reduce the amount of files in the uploads folder', 'onecom-wp' ),
			$this->overview         => sprintf( __( 'The total file count of uploads directory exceeds the acceptable limits. Your website will open very slow and eventually it may get slower by the time. In extreme cases, %sone.com%s may suspend your domain temporarily.', 'onecom-wp' ), '<a target="_blank" href="https://www.one.com">', '</a>' ),
			$this->fix_button_text  => '',
			$this->how_to_fix       => sprintf( __( 'Reduce the size of the uploads folder by cleaning up the Media Library in your WordPress dashboard. Check out %sour guide%s to learn more about the WordPress Media Library and how to clean it up.', 'onecom-wp' ), '<a target="_blank" href="https://help.one.com/hc/en-us/articles/4402376353425-Clean-up-the-WordPress-media-library">', '</a>' ),
			$this->how_to_fix_lite  => '',
			$this->fix_confirmation => '',
			$this->status_desc      => array(
				$this->status_resolved => __( 'Uploads directory size is optimized', 'onecom-wp' ),
				$this->status_open     => __( 'The index of uploads directory is huge. Clean up these directories:', 'onecom-wp' ),
			),
		);
		$this->text['options_table_count'] = array(
			$this->action_title     => __( 'Optimize options table', 'onecom-wp' ),
			$this->overview         => __( 'The total row count of options table exceeds the acceptable limits. Your website will open very slow and eventually it may get slower by the time. In extreme cases, one.com may suspend your domain temporarily.<br/>Automatic backups will also fail with time.', 'onecom-wp' ),
			$this->fix_button_text  => '',
			$this->how_to_fix       => sprintf( __( 'Reduce the size of the "%soptions" table by removing obsolete data in phpMyAdmin. You can %sfollow our guide%s for the needed steps. Please contact our support, if you need assistance. We\'re happy to help.', 'onecom-wp' ), $this->table_prefix, '<a href="https://help.one.com/hc/en-us/articles/360012045457-How-to-optimise-the-WordPress-database" target="_blank">', '</a>' ),
			$this->how_to_fix_lite  => '',
			$this->fix_confirmation => '',

			$this->status_desc      => array(
				$this->status_resolved => __( 'Options table is optimized', 'onecom-wp' ),
				$this->status_open     => __( 'The size of options table is huge.', 'onecom-wp' ),
			),
		);
		$this->text['staging_time']        = array(
			$this->action_title     => __( 'Delete old  staging website.', 'onecom-wp' ),
			$this->overview         => __( 'A staging site which is not touched for long become vulnerable to hacking attacks. We therefore recommend deleting staging sites that you do not need.', 'onecom-wp' ),
			$this->how_to_fix       => __( 'Staging sites are managed from the one.com staging plugin. This is also where you can delete it.', 'onecom-wp' ),
			$this->how_to_fix_lite  => '',
			$this->fix_confirmation => '',
			$this->fix_button_text  => __( 'Go to Staging section', 'onecom-wp' ),

			$this->status_desc      => array(
				$this->status_resolved => __( 'All your old staging websites are deleted', 'onecom-wp' ),
				$this->status_open     => __( 'You have a staging site which is not touched for more than 6 months', 'onecom-wp' ),
			),
		);
		$this->text['backup_zip']          = array(
			$this->action_title     => __( 'Clean backups', 'onecom-wp' ),
			$this->overview         => __( 'Customers often create and forget about the backup zip files. These backup zip files can be downloaded by hackers and this downloaded backup can be analysed for vulnerabilities.', 'onecom-wp' ),
			$this->how_to_fix       => __( 'Delete the backup zips one-by-one in the shown list.', 'onecom-wp' ),
			$this->how_to_fix_lite  => '',
			$this->fix_confirmation => __( 'File %s deleted', 'onecom-wp' ),
			$this->fix_button_text  => __( 'Fix by deleting', 'onecom-wp' ),

			$this->status_desc      => array(
				$this->status_resolved => __( 'No old or obsolete backups', 'onecom-wp' ),
				$this->status_open     => __( 'Some old or obsolete backup files are present on your webspace', 'onecom-wp' ),
			),
		);
		$this->text['performance_cache']   = array(
			$this->action_title     => __( 'Enable Performance Cache.', 'onecom-wp' ),
			$this->overview         => __( 'With <a target="_blank" href="https://www.one.com">one.com</a> Performance Cache enabled your website loads a lot faster. We save a cached copy of your website on a Varnish server, that will then be served to your next visitors. This is especially useful if you have a lot of visitors. It also helps to improve your SEO ranking.', 'onecom-wp' ),
			$this->fix_button_text  => __( 'Enable Performance Cache', 'onecom-wp' ),
			$this->how_to_fix       => __( 'Click the button below.', 'onecom-wp' ),
			$this->how_to_fix_lite  => '', //__( 'Go to the <a target="_blank" href="' . admin_url( 'admin.php?page=onecom-wp-plugins' ) . '">Plugins section</a> of the one.com plugin and make sure one.com Performance Cache plugin is installed and Cache activated.', 'onecom-wp' ),
			$this->fix_confirmation => __( 'Performance cache enabled.', 'onecom-wp' ),
			$this->status_desc      => array(
				$this->status_resolved => __( 'Performance cache is enabled', 'onecom-wp' ),
				$this->status_open     => __( 'Performance cache is not enabled', 'onecom-wp' ),
			),
		);
		$this->text['updated_long_ago']    = array(
			$this->action_title    => __( 'Use compatible plugins', 'onecom-wp' ),
			$this->overview        => __( 'Plugins (that are not maintained anymore) pose security as well as stability threats.<br/>If a plugin is not tested with last 2 major version of WordPress, it is advisable to use alternatives.', 'onecom-wp' ),
			$this->fix_button_text => '',
			$this->how_to_fix      => __( 'Search for alternatives to reported plugins and replace these.', 'onecom-wp' ),
			$this->upsell_text     => __( 'Need help? Upgrade to one.com Managed WordPress for free and get specialised WordPress Support.', 'onecom-wp' ) . '<a class="onecom__open-modal"> ' . __( 'Free upgrade', 'onecom-wp' ) . '</a>',
			$this->how_to_fix_lite => __( 'Search for alternatives to reported plugins and replace these.', 'onecom-wp' ),
			$this->status_desc     => array(
				$this->status_resolved => __( 'All installed plugins are compatible with the last two major releases of WordPress', 'onecom-wp' ),
				$this->status_open     => __( 'One or more installed plugins are not compatible with the last two major WordPress versions.', 'onecom-wp' ),
			),
		);
		$this->text['pingbacks']           = array(
			$this->action_title     => __( 'Disable trackbacks &  pingbacks', 'onecom-wp' ),
			$this->overview         => __( 'Pingbacks notify a website when it has been mentioned by another website, like a form of courtesy communication. However, these notifications can be sent to any website willing to receive them, opening you up to DDoS attacks, which can take your website down in seconds and fill your posts with spam comments', 'onecom-wp' ),
			$this->fix_button_text  => __( 'Disable pingback', 'onecom-wp' ),
			$this->how_to_fix       => __( 'Click below.', 'onecom-wp' ),
			$this->how_to_fix_lite  => sprintf(
				__(
					'Go to %sWordPress admin > Settings > Discussion%s and uncheck the boxes that say

%s %sAttempt to notify any blogs linked to from the post%s
%sAllow link notifications from other blogs (pingbacks and trackbacks) on new posts%s %s',
					'onecom-wp'
				),
				'<strong>',
				'</strong>',
				'<ol>',
				'<li>',
				'</li>',
				'<li>',
				'</li>',
				'</ol>'
			),
			$this->fix_confirmation => __( 'You have successfuly disabled pingbacks and trackbacks.', 'onecom-wp' ),
			$this->upsell_text      => __( 'one.com Managed WordPress comes with a quick fix so you can spend more time on your website, less on security', 'onecom-wp' ) . $this->open_modal_link,
			$this->fix_confirmation => __( 'Pingbacks are disabled.', 'onecom-wp' ),
			$this->status_desc      => array(
				$this->status_resolved => __( 'Pingbacks are disabled.', 'onecom-wp' ),
				$this->status_open     => __( 'You have pingbacks enabled on your site.', 'onecom-wp' ),
			),
		);
		$this->text['logout_duration']     = array(
			$this->action_title     => __( 'Logout duration', 'onecom-wp' ),
			$this->overview         => __( 'By default, WordPress allows users to be logged in for 14 days. This can create security issues if a User logs in on a public computer and forgets to logout. To prevent this, you can reduce the duration for which a user session is remembered.', 'onecom-wp' ),
			$this->fix_button_text  => sprintf( __( 'Change logout time to %s hours', 'onecom-wp' ), '4' ),
			$this->fix_confirmation => sprintf( __( 'Logout time changed to %s hours', 'onecom-wp' ), '4' ),
			$this->how_to_fix       => __( 'Click on fix now below', 'onecom-wp' ),
			$this->status_desc      => array(
				$this->status_resolved => __( 'You are using optimal logout duration.', 'onecom-wp' ),
				$this->status_open     => __( 'You are using the default login expiration.', 'onecom-wp' ),
			),
		);
		$this->text['xmlrpc']              = array(
			$this->action_title     => __( 'Disable XML-RPC', 'onecom-wp' ),

			$this->overview         => __( 'XML-RPC is a legacy technology that is being used by Jetpack-plugin and the WordPress mobile application. In case you are not using neither of these, it is safe and recommended to disable it to further protect your website.', 'onecom-wp' ),
			$this->fix_button_text  => __( 'Disable XML RPC', 'onecom-wp' ),
			$this->how_to_fix       => __( 'Click the button below.', 'onecom-wp' ),
			$this->how_to_fix_lite  => __( 'You need to paste following code snippet in your .htaccess file', 'onecom-wp' ) . '<code>
<p>#one.com block xmlrpc</p>
<p>&lt;Files xmlrpc.php&gt;</p>
<p>order deny,allow</p>
<p>deny from all</p>
<p>&lt;/Files&gt;</p>
<p>#one.com block xmlrpc END</p></code>',
			$this->fix_confirmation => __( 'XML RPC disabled.', 'onecom-wp' ),
			$this->status_desc      => array(
				$this->status_resolved => __( 'You have disabled XML RPC in your site.', 'onecom-wp' ),
				$this->status_open     => __( 'XML-RPC is currently enabled.', 'onecom-wp' ),
			),
			$this->upsell_text      => __( 'one.com Managed WordPress comes with a quick fix so you can spend more time on your website, less on security ', 'onecom-wp' ) . $this->open_modal_link,
		);
		$this->text['spam_protection']     = array(
			$this->action_title     => __( 'Install a spam protection plugin', 'onecom-wp' ),
			$this->overview         => __( 'Unprotected forms on your site are biggest source of spam registrations and spam comments.<br/>We recommend enabling a spam protection plugin.', 'onecom-wp' ),
			$this->fix_button_text  => __( 'Install one.com plugin', 'onecom-wp' ),
			$this->how_to_fix       => __( 'Install and activate one.com spam protection plugin.', 'onecom-wp' ),
			$this->how_to_fix_lite  => __( 'Install and activate a spam protection plugin - go to <a target="_blank" href="' . admin_url( 'admin.php?page=onecom-wp-recommended-plugins' ) . '">recommended plugins</a> section and find your preferred option', 'onecom-wp' ),
			$this->fix_confirmation => __( 'one.com spam plugin is now installed and activated.', 'onecom-wp' ),
			$this->upsell_text      => __( 'one.com Managed WordPress comes with spam protection plugin and more included.', 'onecom-wp' ) . $this->open_modal_link,
			$this->status_desc      => array(
				$this->status_resolved => __( 'You have spam protection enabled.', 'onecom-wp' ),
				$this->status_open     => __( "You don't have any spam protection enabled.", 'onecom-wp' ),
			),
		);
		$this->text['login_attempts']      = array(
			$this->action_title     => __( 'Limit failed logins', 'onecom-wp' ),
			$this->overview         => __( 'By default, WordPress allows users to enter passwords as many times as they want. Hackers may try to exploit this by using scripts that enter different combinations until your website cracks.<br/>To prevent this, you can limit the number of failed login attempts per user.', 'onecom-wp' ),
			$this->fix_button_text  => __( 'Limit failed logins', 'onecom-wp' ),
			$this->upsell_text      => __( 'one.com Managed WordPress comes with this feature included and more.', 'onecom-wp' ) . $this->open_modal_link,
			$this->how_to_fix       => __( 'Failed login attempts can be easily limited by activating Spam protection plugin just a click.', 'onecom-wp' ),
			$this->how_to_fix_lite  => sprintf( __( 'Please install your preferred plugin such as <a target="_blank" href="https://wordpress.org/plugins/login-lockdown/">Login Lockdown</a> and limit the failed login attempts.', 'onecom-wp' ), '<a target="_blank" href="https://wordpress.org/plugins/login-lockdown/">', '</a>' ),
			$this->fix_confirmation => __( 'Failed login attempts limited', 'onecom-wp' ),

			$this->status_desc      => array(
				$this->status_resolved => __( 'Failed login attempts are limited.', 'onecom-wp' ),
				$this->status_open     => __( 'No limit for failed logins.', 'onecom-wp' ),
			),
		);
		$this->text['login_recaptcha']     = array(
			$this->action_title     => __( 'Protect your login-form', 'onecom-wp' ),
			$this->overview         => __( 'By default, WordPress does not have any feature to protect the login form against brute force attacks.<br/>To address this, you can use Google reCaptcha in login form.', 'onecom-wp' ),
			$this->fix_button_text  => __( 'Enable reCaptcha', 'onecom-wp' ),
			$this->how_to_fix       => __( "The login form can be protected by entering Site key and Site secret obtained from <a target='_blank' href='https://www.google.com/recaptcha/admin/create'>Google's dashboard</a>.<br/>Go to Google ReCaptcha Dasboard and follow these steps:", 'onecom-wp' ) . '<ol><li>' . __( "Get the Site key and Site secret from <a target='_blank' href='https://www.google.com/recaptcha/admin/create'>Google's ReCaptcha Dashboard</a>.", 'onecom-wp' ) . '</li><li>' . __( 'Click Enable reCaptcha below.', 'onecom-wp' ) . '</li><li>' . __( 'Enter the Site key and Site secret values and click enter', 'onecom-wp' ) . '</li></ol>',
			$this->how_to_fix_lite  => __( 'You can install a suitable plugin from WordPress plugin repo to fix this', 'onecom-wp' ),
			$this->fix_confirmation => __( 'Login form protected with reCaptcha', 'onecom-wp' ),
			$this->upsell_text      => __( 'one.com Managed WordPress comes with login protection included and more.', 'onecom-wp' ) . $this->open_modal_link,
			$this->status_desc      => array(
				$this->status_resolved => __( 'Your login form is protected.', 'onecom-wp' ),
				$this->status_open     => __( 'Your login form is unprotected', 'onecom-wp' ),
			),
		);
		$this->text['asset_minification']  = array(
			$this->action_title    => __( 'Asset minification Title', 'onecom-wp' ),
			$this->overview        => '',
			$this->fix_button_text => '',

			$this->status_desc     => array(
				$this->status_resolved => '',
				$this->status_open     => '',
			),
		);
		$this->text['php_updates']         = array(
			$this->action_title => __( 'Update to latest PHP version', 'onecom-wp' ),
			$this->overview     => __( 'PHP is the software that powers WordPress. It interprets the WordPress code and generates web pages people view. Naturally, PHP comes in different versions and is regularly updated. As newer versions are released, WordPress drops support for older PHP versions in favour of newer, faster versions with fewer bugs.', 'onecom-wp' ),
			$this->how_to_fix   => sprintf( __( 'You can update PHP from the one.com control panel, under PHP & Database - MariaDB. Check our guide for more information: <a target="_blank" href="https://help.one.com/hc/en/articles/360000449117-How-do-I-update-PHP-for-my-WordPress-site-">How do I update PHP for my WordPress site?</a>', 'onecom-wp' ), '<a href="' . OC_CP_LOGIN_URL . '" target="_blank">', '</a>', '<a target="_blank" href="https://help.one.com/hc/en/articles/360000449117-How-do-I-update-PHP-for-my-WordPress-site-">', '</a>' ),

			$this->status_desc  => array(
				$this->status_resolved => __( 'You are using the recommended PHP version. Boom!', 'onecom-wp' ),
				$this->status_open     => __( 'You are not using the latest stable PHP version.', 'onecom-wp' ),
			),
		);
		$this->text['plugin_updates']      = array(
			$this->action_title    => __( 'Update plugin(s)', 'onecom-wp' ),
			$this->overview        => __( 'Plugins that are not updated to latest version make your site vulnerable to security attacks. You should also delete plugins that are not in use.', 'onecom-wp' ),
			$this->fix_button_text => '',
			$this->how_to_fix      => sprintf( __( 'Plugins are managed from the Plugins section in WP Admin. %sGo to Plugins%s and update plugins', 'onecom-wp' ), '<a target="_blank" href="' . admin_url( 'plugins.php' ) . '">', '</a>' ),
			$this->status_desc     => array(
				$this->status_resolved => __( 'Great, all your plugins are updated.', 'onecom-wp' ),
				$this->status_open     => __( 'These plugins are not updated', 'onecom-wp' ),
			),
		);
		$this->text['theme_updates']       = array(
			$this->action_title    => __( 'Update theme(s)', 'onecom-wp' ),
			$this->overview        => __( 'Using outdated themes can break your site and generate potential security risks. You should also delete themes you do not use.', 'onecom-wp' ),
			$this->fix_button_text => '',
			$this->how_to_fix      => sprintf( __( 'Update your themes to the latest version. We recommend that you remove any themes that you don’t plan on using. %sGo to Themes%s and update them.', 'onecom-wp' ), '<a target="_blank" href="' . admin_url( 'themes.php' ) . '">', '</a>' ),

			$this->status_desc     => array(
				$this->status_resolved => __( 'All your themes are up to date. Good stuff! ', 'onecom-wp' ),
				$this->status_open     => __( 'These themes are not up to date', 'onecom-wp' ),
			),
		);
		$this->text['inactive_plugins']    = array(
			$this->action_title    => __( 'Remove inactive plugins', 'onecom-wp' ),
			$this->overview        => __( 'Inactive plugins are tempting targets for hackers . We recommend removing any plugins that you no longer need .', 'onecom-wp' ),
			$this->fix_button_text => __( 'Manage inactive plugins', 'onecom-wp' ),
			$this->how_to_fix      => __( 'Select " Manage inactive plugins " and delete all plugins that you don\'t need .', 'onecom-wp' ),
			$this->how_to_fix_lite => __( 'Go to "Plugins" in the left-side menu and delete plugins that you don\'t need. You can see all unused plugins in the "Inactive" tab.', 'onecom-wp' ),

			$this->status_desc     => array(
				$this->status_resolved => __( 'Your site has no inactive plugins. ', 'onecom-wp' ),
				$this->status_open     => __( 'Your site has %d inactive plugins', 'onecom-wp' ),
			),
		);

		$this->text['inactive_themes'] = array(
			$this->action_title    => __( 'Remove inactive themes', 'onecom-wp' ),
			$this->overview        => __( 'To enhance your site\'s security , we recommend removing any inactive themes except the default WordPress themes ( called " Twenty Twenty - Three " and similar ) and the theme you\'re currently using .', 'onecom-wp' ),
			$this->fix_button_text => __( 'Manage your themes', 'onecom-wp' ),
			$instruction_1 = __( 'Click the button " Manage your themes " to get to your themes overview . ', 'onecom-wp' ),
			$instruction_2 = __( 'Hover over the theme you want to delete and select “Theme Details”.', 'onecom-wp' ),
			$instruction_3 = __( 'Click “Delete” in the bottom-right corner.', 'onecom-wp' ),
			$instruction_4 = __( 'Go to “Appearance” > “Themes” in the left-side menu.', 'onecom-wp' ),
			$this->how_to_fix      => sprintf(
				'<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
				$instruction_1,
				$instruction_2,
				$instruction_3
			),
			$this->how_to_fix_lite => sprintf(
				'<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
				$instruction_4,
				$instruction_2,
				$instruction_3
			),

			$this->status_desc     => array(
				$this->status_resolved => __( 'Your site has no inactive themes .', 'onecom-wp' ),
				$this->status_open     => __( 'Your site has %d inactive themes', 'onecom-wp' ),
			),
		);
		$this->text['wp_updates']    = array(
			$this->action_title    => __( 'Update WordPress to latest version', 'onecom-wp' ),
			$this->overview        => str_replace( '\n', '', __( 'WordPress is an extremely popular platform, and with that popularity comes hackers that increasingly want to exploit WordPress based websites. Leaving your WordPress installation out of date is an almost guaranteed way to get hacked as you’re missing out on the latest security patches.', 'onecom-wp' ) ),
			$this->fix_button_text => '',
			$this->how_to_fix      => str_replace( '\n', '', sprintf( __( 'Update WordPress to the latest version, especially minor updates are important because they usually include security fixes. Check this guide for more instructions:  %sHow do I update a CMS like WordPress?%s', 'onecom-wp' ), '<a target="_blank" href="https://help.one.com/hc/en/articles/360001621938-How-do-I-update-a-CMS-like-WordPress-and-Joomla-">', '</a>' ) ),
			$this->how_to_fix_lite => str_replace( '\n', '', sprintf( __( 'Update WordPress to the latest version, especially minor updates are important because they usually include security fixes. Check this guide for more instructions:  %sHow do I update a CMS like WordPress?%s', 'onecom-wp' ), '<a target="_blank" href="https://help.one.com/hc/en/articles/360001621938-How-do-I-update-a-CMS-like-WordPress-and-Joomla-">', '</a>' ) ),
			$this->status_desc     => array(
				$this->status_resolved => __( 'You are using the latest WordPress version', 'onecom-wp' ),
				$this->status_open     => __( "You aren't using the newest version of WordPress", 'onecom-wp' ),
			),
		);
		$this->text['wp_connection'] = array(
			$this->action_title    => __( 'Cannot connect to wordpress.org', 'onecom-wp' ),
			$this->overview        => __( 'WordPress websites fetch critical information related to updates etc. from wordpress.org if a site is unable to connect to wordpress.org, it poses security risk since the latest update information is not available.', 'onecom-wp' ),
			$this->fix_button_text => '',
			$this->how_to_fix      => sprintf( __( "Try to disable all plugins and themes and do a new scan to check if the connection to wordpress.org is restored. If this worked, enable your plugins one-by-one, to find the culprit. If this didn't work, %splease contact our chat support%s.", 'onecom-wp' ), "<a target='_blank'  href='https://help.one.com/hc/en-us'>", '</a>' ),
			$this->status_desc     => array(
				$this->status_resolved => __( 'The connection to wordpress.org succeeded', 'onecom-wp' ),
				$this->status_open     => __( 'The connection to wordpress.org failed', 'onecom-wp' ),
			),
		);
		$this->text['core_updates']  = array(
			$this->action_title    => __( 'Enable automatic minor core updates', 'onecom-wp' ),
			$this->overview        => __( 'Enable automatic minor core updates again. Leaving your WordPress installation out of date is an almost guaranteed way to get hacked as you’re missing out on the latest security patches.', 'onecom-wp' ),
			$this->fix_button_text => '',
			$this->how_to_fix      => sprintf(
				__( 'Enable automatic minor WordPress core updates again, either by changing a setting in the plugin you use to manage updates or by changing a setting in wp-config. If you would like to know why updates are so important, check this guide: %sWhy you should always update WordPress%s.', 'onecom-wp' ),
				"<a target='_blank' href=" . $this->get_supported_locales() . '>',
				'</a>'
			),

			$this->how_to_fix_lite => sprintf(
				__( 'Enable automatic minor WordPress core updates again, either by changing a setting in the plugin you use to manage updates or by changing a setting in wp-config. If you would like to know why updates are so important, check this guide: %sWhy you should always update WordPress%s.', 'onecom-wp' ),
				"<a target='_blank' href=" . $this->get_supported_locales() . '>',
				'</a>'
			),
			$this->status_desc     => array(
				$this->status_resolved => __( 'The automatic minor core updates are enabled in your site.', 'onecom-wp' ),
				$this->status_open     => __( 'Automatic minor core updates are disabled', 'onecom-wp' ),
			),
		);

		$this->text['ssl']                  = array(
			$this->action_title    => __( 'Use a valid SSL certificate', 'onecom-wp' ),
			$this->overview        => sprintf( __( "SSL certification enabled HTTPS prevent intruders from tampering with the communications between your websites and your users' browsers. All domains hosted with %sone.com%s automatically get an SSL certificate assigned, so this state means that something is wrong with the configuration.", 'onecom-wp' ), "<a target='_blank' href='https://www.one.com'>", '</a>' ),
			$this->fix_button_text => '',
			$this->how_to_fix      => __( 'Let customer support check and fix this.', 'onecom-wp' ),
			$this->how_to_fix_lite => sprintf( __( 'Please contact our chat support, so we can check what is wrong and fix it.', 'onecom-wp' ), '<a href="https://help.one.com/hc/en-us" target="_blank">', '</a>' ),
			$this->status_desc     => array(
				$this->status_resolved => __( 'Your site has a valid SSL certificate', 'onecom-wp' ),
				$this->status_open     => __( "Your site doesn't have a working SSL certificate", 'onecom-wp' ),
			),
		);
		$this->text['file_execution']       = array(
			$this->action_title     => __( 'Prevent file execution in uploads folder', 'onecom-wp' ),
			$this->overview         => __( "By default, a plugin/theme vulnerability could allow a PHP file or other files to get uploaded into your site's directories and in turn execute harmful scripts that can wreak havoc on your website. Prevent this altogether by disabling direct execution in your uploads folder.", 'onecom-wp' ),
			$this->fix_button_text  => __( 'Protect uploads folder', 'onecom-wp' ),
			$this->fix_confirmation => __( 'Uploads folder is protected', 'onecom-wp' ),
			$this->how_to_fix       => __( 'Click the button to prevent file execution.', 'onecom-wp' ),
			$this->how_to_fix_lite  => __( 'Follow the steps here: <a target="_blank" href="https://help.one.com/hc/en/articles/360002102258-Disable-file-execution-in-the-WordPress-uploads-folder">Disable file execution in the WordPress uploads folder</a>', 'onecom-wp' ),
			$this->upsell_text      => __( 'one.com Managed WordPress comes with an easy fix and more.', 'onecom-wp' ) . '<a  class="onecom__open-modal"> ' . __( 'Free upgrade', 'onecom-wp' ) . '</a>',
			$this->status_desc      => array(
				$this->status_resolved => __( 'Your uploads folder is protected against malicious file execution', 'onecom-wp' ),
				$this->upsell_text     => __( 'one.com Managed WordPress comes with an easy fix and more.<br/><a>Free Upgrade</a>', 'onecom-wp' ),
				$this->status_open     => __( 'File execution in your uploads folder is enabled', 'onecom-wp' ),
			),
		);
		$this->text['file_permissions']     = array(
			$this->action_title    => __( 'Reduce File Permissions as recommended by wordpress.org', 'onecom-wp' ),
			$this->overview        => __( 'It is crucial to set correct file permissions to each file and directory in your WordPress setup. Incorrect file permission can introduce security vulnerabilities and make your site and easy target for hackers.', 'onecom-wp' ),
			$this->fix_button_text => '',
			$this->how_to_fix_lite => sprintf( __( 'To fix this, you need to use an FTP client to change the permissions of your files to 644, and of your folders to 755. Check our guide for step-by-step instructions: Change the file permissions via an FTP client', 'onecom-wp' ), '<a href="https://help.one.com/hc/en-us/articles/360002087097-Change-the-file-permissions-via-an-FTP-client" target="_blank">', '</a>' ),
			$this->how_to_fix      => sprintf( __( 'To fix this, you need to use an FTP client to change the permissions of your files to 644, and of your folders to 755. Check our guide for step-by-step instructions: Change the file permissions via an FTP client', 'onecom-wp' ), '<a href="https://help.one.com/hc/en-us/articles/360002087097-Change-the-file-permissions-via-an-FTP-client" target="_blank">', '</a>' ),

			$this->status_desc     => array(
				$this->status_resolved => __( 'The file permissions are set correctly', 'onecom-wp' ),
				$this->status_open     => __( 'Your site has incorrect file and folder permissions', 'onecom-wp' ),
			),
		);
		$this->text['DB']                   = array(
			$this->action_title    => __( 'Some title', 'onecom-wp' ),
			$this->overview        => __( 'Some overview', 'onecom-wp' ),
			$this->fix_button_text => __( 'Fix', 'onecom-wp' ),

			$this->status_desc     => array(
				$this->status_resolved => __( 'Resolved', 'onecom-wp' ),
				$this->status_open     => __( 'Open', 'onecom-wp' ),
			),
		);
		$this->text['file_edit']            = array(
			$this->action_title    => __( 'Disable file editing', 'onecom-wp' ),
			$this->overview        => __( "When file editing is enabled, Administrator users can edit the code of themes and plugins directly from the WordPress dashboard. This is a potential security risk because not everyone has the skills to write code, and if a hacker breaks in, they would have access to all your data. That's why we recommend disabling it.", 'onecom-wp' ),
			$this->fix_button_text => '',
			$this->how_to_fix_lite => sprintf( __( 'To fix this you need to add a line to your wp-config.php file which disables file editing options from your dashboard. We have created a guide with step-by-step instructions: %sDisable file editing in WordPress admin.%s', 'onecom-wp' ), '<a target="_blank" href="https://help.one.com/hc/articles/360002104398">', '</a>' ),
			$this->how_to_fix      => sprintf( __( 'To fix this you need to add a line to your wp-config.php file which disables file editing options from your dashboard. We have created a guide with step-by-step instructions: %sDisable file editing in WordPress admin.%s', 'onecom-wp' ), '<a target="_blank" href="https://help.one.com/hc/articles/360002104398">', '</a>' ),
			$this->status_desc     => array(
				$this->status_resolved => __( 'File editing from WordPress admin is disabled', 'onecom-wp' ),
				$this->status_open     => __( 'File editing from WordPress admin is allowed', 'onecom-wp' ),
			),
		);
		$this->text['usernames']            = array(
			$this->action_title     => __( 'Use custom usernames', 'onecom-wp' ),
			$this->overview         => __( 'Hackers often try to gain access to your WordPress administration with a Brute Force Attack, where robots try millions of different password and username combinations to try to log in. To make it more difficult to guess your login details, we recommend creating a unique username', 'onecom-wp' ),
			$this->how_to_fix       => __( 'Change the common username to a personal one, based on your name or nickname.', 'onecom-wp' ),
			$this->fix_button_text  => 'Change user name',
			$this->fix_confirmation => __( 'User name is changed', 'onecom-wp' ),
			$this->status_desc      => array(
				$this->status_resolved => __( 'You are using custom usernames for your login', 'onecom-wp' ),
				$this->status_open     => __( 'You are using a generic username that is easy to guess', 'onecom-wp' ),
			),
		);
		$this->text['dis_plugin']           = array(
			$this->action_title     => __( "You're using a plugin which we advice against", 'onecom-wp' ),
			$this->overview         => sprintf( __( 'Some plugins does the opposite of what they promise. Others make your site slow or are easy to hack. We therefore keep a list of discouraged plugins. See it here:  %sDiscouraged WordPress plugins%s.', 'onecom-wp' ), '<a target="_blank" href="https://help.one.com/hc/en/articles/115005586029-Discouraged-WordPress-plugins">', '</a>' ),
			$this->fix_button_text  => __( 'Deactivate plugin(s)', 'onecom-wp' ),
			$this->fix_confirmation => __( 'These plugins are now deactivated:', 'onecom-wp' ),
			$this->how_to_fix       => __( 'Deactivate the discouraged plugins. ', 'onecom-wp' ),
			$this->status_desc      => array(
				$this->status_resolved => __( 'You are doing great! None of your installed plugins, are on our list of discouraged plugins.', 'onecom-wp' ),
				$this->status_open     => __( 'You are using one or more of the plugins we advice against:', 'onecom-wp' ),
			),
		);
		$this->text['woocommerce_sessions'] = array(
			$this->action_title     => __( 'Expired woocommerce session data', 'onecom-wp' ),
			$this->overview         => __( 'You have some expired session data present in your database. <br/>Old sessions and customer carts will be stored in your database until they expire, so if you have modified the WooCommerce session expiration time in Clear Cart for WooCommerce, we recommend that you clear all existing WooCommerce sessions.', 'onecom-wp' ),
			$this->fix_button_text  => __( 'Fix now', 'onecom-wp' ),
			$this->how_to_fix       => __( 'Click Fix now to  automatically clean up the session garbage.', 'onecom-wp' ),
			$this->how_to_fix_lite  => __( 'Clear expired woocommerce session data', 'onecom-wp' ),
			$this->upsell_text      => __( 'one.com Managed WordPress comes with an easy fix and more.', 'onecom-wp' ) . '<a  class="onecom__open-modal"> ' . __( 'Free upgrade', 'onecom-wp' ) . '</a>',
			$this->fix_confirmation => __( 'The expired woocommerce session data is deleted.', 'onecom-wp' ),
			$this->status_desc      => array(
				$this->status_resolved => __( 'The expired woocommerce session data is deleted.', 'onecom-wp' ),
				$this->status_open     => __( 'You have some expired session data present in your database.', 'onecom-wp' ),
			),
		);
		$this->text['error_reporting']      = array(
			$this->action_title     => __( 'Hide error reporting', 'onecom-wp' ),
			$this->overview         => __( "Developers often use the built-in PHP and scripts error debugging feature, which displays code errors on the frontend of your website. It's useful for active development, but on live sites provides hackers yet another way to find loopholes in your site's security.", 'onecom-wp' ),
			$this->fix_button_text  => __( 'Fix now', 'onecom-wp' ),
			$this->how_to_fix       => sprintf( __( 'You can disable PHP error reporting in the one.com control panel and WordPress debugging in the wp.config.php file.Check these two guides for more details on how to manage these settings: <a target="_blank" href="https://help.one.com/hc/en-us/articles/115005593705-How-do-I-enable-error-messages-for-PHP-">How do I enable error messages for PHP?</a> and <a target="_blank" href="https://help.one.com/hc/en-us/articles/115005594045-How-do-I-enable-debugging-in-WordPress-">How do I enable debugging in WordPress?</a>', 'onecom-wp' ), '<a target="_blank" href="https://help.one.com/hc/en-us/articles/115005593705-How-do-I-enable-error-messages-for-PHP-">', '</a>', '<a target="_blank" href="https://help.one.com/hc/en-us/articles/115005594045-How-do-I-enable-debugging-in-WordPress-">', '</a>' ),
			$this->fix_confirmation => '',
			$this->status_desc      => array(
				$this->status_resolved => __( 'Error reporting and debugging mode are disabled', 'onecom-wp' ),
				$this->status_open     => __( 'Your site is configured to display errors to visitors', 'onecom-wp' ),
			),
		);

		$this->text['debug_enabled'] = array(
			$this->action_title     => __( 'Your site is set to log errors to a potentially public file', 'onecom-wp' ),
			$this->overview         => __( 'Debug mode is generally used to gather more details about an error or site failure, but the file where the information is collected might be publicly available. This means that any information about your errors could be visible to all users. To enhance the security of your site, we recommend disabling debug mode as soon as you don’t need it anymore.', 'onecom-wp' ),
			$instruction_1 = __( 'Go to your File Manager and open the file called " wp - config.php "', 'onecom-wp' ),
			$instruction_2 = __( "Scroll down to the line that says: <strong>define( \'WP_DEBUG_LOG\', true );</strong> in that file.", 'onecom-wp' ),
			$instruction_3 = __( "Change it to: <strong>define( \'WP_DEBUG_LOG\', false );</strong> and then click “Save” at the top of the page.", 'onecom-wp' ),
			$this->how_to_fix       => sprintf(
				'<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
				$instruction_1,
				$instruction_2,
				$instruction_3
			),
			$this->fix_confirmation => '',
			$this->status_desc      => array(
				$this->status_resolved => __( 'Debug mode is disabled.', 'onecom-wp' ),
				$this->status_open     => __( 'Debug mode is enabled for this site , potentially making error details publicly available .', 'onecom-wp' ),
			),
		);
		$this->text['debug_log_size']           = array(
			$this->action_title     => __( 'Your debug.log file is taking up too much space', 'onecom-wp' ),
			$this->overview         => __( 'The debug.log file can take up a lot of your disk space . We recommend deleting it if you no longer need it .', 'onecom-wp' ),
			$this->fix_button_text  => __( 'Delete file', 'onecom-wp' ),
			$this->upsell_text      => __( 'one.com Managed WordPress comes with a quick fix so you can spend more time on your website, less on security', 'onecom-wp' ) . '<a class="onecom__open-modal"> ' . __( 'Free upgrade', 'onecom-wp' ) . '</a>',
			$this->how_to_fix       => __( 'Click " Delete file " below to remove the debug.log file automatically . To remove it manually , locate and delete the file using your File Manager or an ( S ) FTP client . In most cases , the debug.log file is found in the folder called " wp - content " .', 'onecom-wp' ),
			$this->how_to_fix_lite  => __( 'Click " Delete file " below to remove the debug.log file automatically . To remove it manually , locate and delete the file using your File Manager or an ( S ) FTP client . In most cases , the debug.log file is found in the folder called " wp - content " .', 'onecom-wp' ),
			$this->fix_confirmation => 'Debug.log file deleted',
			$this->status_desc      => array(
				$this->status_resolved => __( 'Your debug.log file was deleted and is no longer taking up any disk space.', 'onecom-wp' ),
				$this->status_open     => __( 'Your debug.log file takes up over 100 MB in disk space .', 'onecom-wp' ),
			),
		);
		$this->text['user_enumeration']         = array(
			$this->action_title     => __( 'Disable user enumeration', 'onecom-wp' ),
			$this->overview         => __( "One of the more common methods for bots and hackers to gain access to your website is to find out login usernames and brute force the login area with tons of dummy passwords. The hope is that one the username and password combos will match, and voilà - they have access (you'd be surprised how common weak passwords are!).", 'onecom-wp' ) .
				__( 'There are two sides to this hacking method - the username and the password. The passwords are random guesses, but (unfortunately) the username is easy to get. Simply typing the query string ?author=1, ?author=2 and so on, will redirect the page to /author/username/ - bam, the bot now has your usernames to begin brute force attacks with.', 'onecom-wp' ) .
		__( 'This security recommendation locks down your website by preventing the redirect, making it much harder for bots to get your usernames. We highly advise actioning this recommendation.', 'onecom-wp' ),
			$this->fix_button_text  => __( 'Disable user enumeration', 'onecom-wp' ),
			$this->upsell_text      => __( 'one.com Managed WordPress comes with this feature included and more.', 'onecom-wp' ) . '<a class="onecom__open-modal"> ' . __( 'Free upgrade', 'onecom-wp' ) . '</a>',
			$this->how_to_fix       => __( 'Click the button below.', 'onecom-wp' ),
			$this->how_to_fix_lite  => sprintf( __( 'Install a plugin, for example, <a target="_blank" href="https://wordpress.org/plugins/stop-user-enumeration/">Stop User Enumeration</a>, and use that to disable User Enumeration.', 'onecom-wp' ), '<a target="_blank" href="https://wordpress.org/plugins/stop-user-enumeration/">', '</a>' ),
			$this->fix_confirmation => __( 'User enumeration is disabled.', 'onecom-wp' ),
			$this->status_desc      => array(
				$this->status_resolved => __( 'User enumeration is disabled.', 'onecom-wp' ),
				$this->status_open     => __( 'User enumeration is enabled on your site.', 'onecom-wp' ),
			),
		);
		$this->text['optimize_uploaded_images'] = array(
			$this->action_title     => __( 'Optimize uploaded images', 'onecom-wp' ),
			$this->overview         => __( 'By default, WordPress does not optimize images very well. We recommend using the Imagify plugin to increase performance and visitor experience on your website with faster image loading speed.', 'onecom-wp' ),
			$this->fix_button_text  => __( 'Go to Imagify', 'onecom-wp' ),
			$this->upsell_text      => '',
			$this->how_to_fix       => ( ! is_plugin_active( 'imagify/imagify.php' ) ) ? __( 'Install & activate the Imagify plugin, go to Imagify settings, and set up the plugin following the instructions on the page.', 'onecom-wp' ) : sprintf( __( 'Go to %sImagify settings%s and set up the plugin following the instructions on the page.', 'onecom-wp' ), '<a target="_blank" href="' . admin_url( 'options-general.php?page=imagify' ) . '">', '</a>' ),
			$this->how_to_fix_lite  => ( ! is_plugin_active( 'imagify/imagify.php' ) ) ? __( 'Install & activate the Imagify plugin, go to Imagify settings, and set up the plugin following the instructions on the page.', 'onecom-wp' ) : sprintf( __( 'Go to %sImagify settings%s and set up the plugin following the instructions on the page.', 'onecom-wp' ), '<a target="_blank" href="' . admin_url( 'options-general.php?page=imagify' ) . '">', '</a>' ),
			$this->fix_confirmation => '',
			$this->status_desc      => array(
				$this->status_resolved => ( is_plugin_active( 'imagify/imagify.php' ) ) ? __( 'Imagify is now set up. The images you upload will be optimized.', 'onecom-wp' ) : __( 'The images you upload will be optimized.', 'onecom-wp' ),
				$this->status_open     => __( 'Imagify is not set up', 'onecom-wp' ),
			),
		);
		$this->text['enable_cdn']               = array(
			$this->action_title     => __( 'Enable Performance CDN.', 'onecom-wp' ),
			$this->overview         => __( 'A content delivery network (CDN) is a system of distributed servers that deliver pages and other web content to a user, based on the geographic locations of the user, the origin of the webpage and the content delivery server. This is especially useful if you have a lot of visitors spread across the globe.', 'onecom-wp' ),
			$this->fix_button_text  => __( 'Enable CDN', 'onecom-wp' ),
			$this->upsell_text      => '',
			$this->how_to_fix       => __( 'Click the button below.', 'onecom-wp' ),
			$this->how_to_fix_lite  => '', //str_replace('<a target="_blank" href="' . admin_url( 'admin.php?page=onecom-wp-plugins' ) . '">one.com</a>', 'one.com', __( 'Go to the <a target="_blank" href="' . admin_url( 'admin.php?page=onecom-wp-plugins' ) . '">Plugins section</a> of the <a target="_blank" href="' . admin_url( 'admin.php?page=onecom-wp-plugins' ) . '">one.com</a> plugin and make sure one.com Performance Cache plugin is installed and CDN activated.', 'onecom-wp' )),
			$this->fix_confirmation => __( 'CDN is enabled.', 'onecom-wp' ),
			$this->status_desc      => array(
				$this->status_resolved => __( 'CDN is enabled', 'onecom-wp' ),
				$this->status_open     => __( 'CDN is not enabled', 'onecom-wp' ),
			),
		);
		$this->text['login_protection']         = array(
			$this->action_title     => __( 'Enable one.com Advanced Login Protection', 'onecom-wp' ),
			$this->overview         => __( 'We recommend that you enable the Advanced login Protection in the one.com control panel. This means you won’t need to remember passwords for your WordPress sites and your login will be more protected.', 'onecom-wp' ),
			$this->fix_button_text  => __( 'Go to one.com control panel', 'onecom-wp' ),
			$this->upsell_text      => '',
			$this->how_to_fix       => __( 'Click the button below.', 'onecom-wp' ),
			$this->how_to_fix_lite  => __( 'Click the button below.', 'onecom-wp' ),
			$this->fix_confirmation => __( 'Advanced login protection is enabled.', 'onecom-wp' ),
			$this->status_desc      => array(
				$this->status_resolved => __( 'Advanced login protection is enabled.', 'onecom-wp' ),
				$this->status_open     => __( 'Advanced login protection is disabled.', 'onecom-wp' ),
			),
		);
	}

	public function setStatusDesc( $newStatusDesc ) {
		$this->text['inactive_plugins'][ $this->status_desc ][ $this->status_open ] = $newStatusDesc;
	}

	public function get_text( $check ): array {
		$refined_check = str_replace( 'check_', '', $check );

		return $this->text[ $refined_check ];
	}

	public function init_fix_messages() {
		$this->quick_fix_messages = array(
			'error'   => array(
				'username_invalid'     => __( 'Please enter a valid username', 'onecom-wp' ),
				'username_not_changed' => __( 'User name could not be changed', 'onecom-wp' ),
			),
			'success' => array(
				'username_changed' => __( 'User name is changed', 'onecom-wp' ),
			),
		);
	}

	public function get_supported_locales(): string {
		$locale = get_locale();
		//      $language_part = substr($locale, 0, 2);

		$supported_locales = array(
			'en_US' => 'https://help.one.com/hc/en-us/articles/360000110977-Why-you-should-always-update-WordPress',
			'da_DK' => 'https://help.one.com/hc/da/articles/360000110977-Derfor-skal-du-altid-opdatere-WordPress',
			'de_DE' => 'https://help.one.com/hc/de/articles/360000110977-Warum-Sie-WordPress-immer-aktuell-halten-sollten',
			'es_ES' => 'https://help.one.com/hc/es/articles/360000110977--Por-qu%C3%A9-deber%C3%ADa-mantener-WordPress-siempre-actualizado',
			'fr_FR' => 'https://help.one.com/hc/fr/articles/360000110977-Pourquoi-vous-devez-toujours-mettre-%C3%A0-jour-WordPress',
			'it_IT' => 'https://help.one.com/hc/it/articles/360000110977-Perch%C3%A9-dovresti-sempre-aggiornare-WordPress',
			'pt_PT' => 'https://help.one.com/hc/pt/articles/360000110977-Raz%C3%B5es-para-manter-o-seu-WordPress-atualizado',
			'nl_NL' => 'https://help.one.com/hc/nl/articles/360000110977-Waarom-je-WordPress-altijd-moet-updaten',
			'sv_SE' => 'https://help.one.com/hc/sv/articles/360000110977-Varf%C3%B6r-du-alltid-b%C3%B6r-h%C3%A5lla-WordPress-uppdaterat',
			'fi'    => 'https://help.one.com/hc/fi/articles/360000110977-Miksi-WordPress-kannattaa-aina-p%C3%A4ivitt%C3%A4%C3%A4-uusimpaan-versioon',
			'nb_NO' => 'https://help.one.com/hc/no/articles/360000110977-Hvorfor-du-alltid-b%C3%B8r-oppdatere-WordPress',
		);
		if ( ! array_key_exists( $locale, $supported_locales ) ) {
			// Language not supported, return default locale
			return $supported_locales['en_US'];
		} else {
			// Language is supported
			return $supported_locales[ $locale ];
		}
	}
}

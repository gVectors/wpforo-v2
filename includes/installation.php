<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

function wpforo_activation() {
	#################################################################
	// Create wpForo Tables /////////////////////////////////////////
	wpforo_create_tables();

	#################################################################
	// Alter wpForo Tables //////////////////////////////////////////
	wpforo_alter_tables();

	#################################################################
	// Permalink Settings ///////////////////////////////////////////
	wpforo_fix_wp_permalink_structure();

	#################################################################
	// Creating wpforo folders //////////////////////////////////////
	wpforo_create_important_folders();

	#################################################################
	// Access Sets //////////////////////////////////////////////////
	wpforo_import_default_accesses();

	#################################################################
	// fix already installed options ////////////////////////////////
	wpforo_fix_installed_options();

	#################################################################
	// synchronize users ////////////////////////////////////////////
	WPF()->member->synchronize_users( 100 );
	WPF()->member->init_current_user();
	WPF()->member->clear_db_cache();

	#################################################################
	// Importing Language Packs and Phrases /////////////////////////
	WPF()->phrase->xml_import( 'english.xml' );
	WPF()->phrase->clear_cache();

	#################################################################
	// Creating Forum Page //////////////////////////////////////////
	wpforo_create_forum_page();

	#################################################################
	// Forum Navigation and Menu ////////////////////////////////////
	wpforo_import_default_menus();

	#################################################################
	// Boards ////////////////////////////////////////////////////
	wpforo_import_default_boards();

	#################################################################
	// Usergroup ////////////////////////////////////////////////////
	wpforo_import_default_usergroups();

	#################################################################
	// Forums ////////////////////////////////////////////////////
	wpforo_import_default_forums();

	#################################################################
	// UPDATE THEME OPTIONS  ////////////////////////////////////////
	wpforo_update_theme_options();

	#################################################################
	// wpForo Upgrade ///////////////////////////////
	wpforo_upgrade();

	#################################################################
	// UPDATE VERSION - END /////////////////////////////////////////
	wpforo_update_option( 'version', WPFORO_VERSION );
	WPF()->notice->clear();
	wpforo_clean_cache();
}

function wpforo_upgrade(){
	if( version_compare( get_option( 'wpforo_version', '' ), '2.0.0', '<' ) ){
		// ####  deactivate old addons
		if( ! function_exists( 'get_plugin_data' ) ) require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugins = [];
		$addons = wpforo_get_addons_info();
		foreach( $addons as $addon ){
			if( file_exists( $addon['ABSPATH'] ) ){
				$plugin_data = get_plugin_data( $addon['ABSPATH'] );
				if( $plugin_data['Version'] ){
					$plugin_basename = plugin_basename( $addon['ABSPATH'] );
					if( version_compare( $plugin_data['Version'], '3.0.0', '<' ) && is_plugin_active( $plugin_basename ) ) $plugins[] = $plugin_basename;
				}
			}
		}
		if( $plugins ) deactivate_plugins($plugins);

		// ####  migrate old options to new settings
		require_once WPFORO_DIR . "/includes/options-migration.php";
		_wpforo_migrate_old_options_to_new();
	}
}

function wpforo_create_tables() {
	require_once( WPFORO_DIR . '/includes/install-sql.php' );
	foreach( wpforo_get_install_sqls() as $sql ) {
		if( false === @WPF()->db->query( $sql ) ) {
			@WPF()->db->query( preg_replace( '#\)\s*ENGINE.*$#isu', ')', $sql ) );
		}
	}
}

function wpforo_alter_tables() {
	#################################################################
	// ADD `status` field in `languages` TABLE //////////////////////////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->languages, 'col' => 'status', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->languages . "` ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 1" );
	}

	#################################################################
	// ADD `package` field in `phrases` TABLE //////////////////////////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->phrases, 'col' => 'package', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->phrases . "` ADD `package` VARCHAR(255) NOT NULL DEFAULT 'wpforo'" );
	}
	//	WPF()->phrase->clear_cache();

	#################################################################
	// ADD `private` field in TOPIC TABLE  ///////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->topics, 'col' => 'private', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->topics . "` ADD `private` TINYINT(1) NOT NULL DEFAULT '0', ADD INDEX `is_private` (`private`);" );
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->topics . "` ADD INDEX `own_private` ( `userid`, `private`);" );
	}
	// ADD `solved` field in TOPIC TABLE  ///////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->topics, 'col' => 'solved', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->topics . "` ADD `solved` TINYINT(1) NOT NULL DEFAULT 0 AFTER `type`, ADD INDEX `solved` (`solved`)" );
		@WPF()->db->query(
			"UPDATE `" . WPF()->tables->topics . "` t
								INNER JOIN `" . WPF()->tables->posts . "` p ON p.`topicid` = t.`topicid` AND p.`is_answer` = 1
								SET t.`solved` = 1
								WHERE t.`solved` = 0"
		);
		wpforo_clean_cache();
	}
	// ADD `status` field in TOPICS & POSTS TABLE  ///////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->topics, 'col' => 'status', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->topics . "` ADD `status` TINYINT(1) NOT NULL DEFAULT '0', ADD INDEX `status` (`status`);" );
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->posts . "` ADD `status` TINYINT(1) NOT NULL DEFAULT '0', ADD INDEX `status` (`status`);" );
	}
	// ADD `name` and `email` field in TOPIC TABLE  ///////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->topics, 'col' => 'name', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->topics . "` ADD `name` VARCHAR(50) NOT NULL,  ADD `email` VARCHAR(50) NOT NULL" );
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->posts . "` ADD `name` VARCHAR(50) NOT NULL,  ADD `email` VARCHAR(50) NOT NULL" );
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->topics . "` ADD KEY `email` (`email`)" );
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->posts . "` ADD KEY `email` (`email`)" );
	}
	// ADD `utitle`, `role` and `access` to USERGROUP TABLE  /////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->usergroups, 'col' => 'utitle', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->usergroups . "` ADD `utitle` VARCHAR(100), ADD `role` VARCHAR(50), ADD `access` VARCHAR(50)" );
		@WPF()->db->query( "UPDATE `" . WPF()->tables->usergroups . "` SET `utitle` = 'Admin', `role` = 'administrator', `access` = 'full' WHERE `groupid` = 1" );
		@WPF()->db->query( "UPDATE `" . WPF()->tables->usergroups . "` SET `utitle` = 'Moderator', `role` = 'editor', `access` = 'moderator' WHERE `groupid` = 2" );
		@WPF()->db->query( "UPDATE `" . WPF()->tables->usergroups . "` SET `utitle` = 'Registered', `role` = 'subscriber', `access` = 'standard' WHERE `groupid` = 3" );
		@WPF()->db->query( "UPDATE `" . WPF()->tables->usergroups . "` SET `utitle` = 'Guest', `role` = '', `access` = 'read_only' WHERE `groupid` = 4" );
		@WPF()->db->query( "UPDATE `" . WPF()->tables->usergroups . "` SET `utitle` = 'Customer', `role` = 'customer', `access` = 'standard' WHERE `groupid` = 5" );
		@WPF()->db->query( "UPDATE `" . WPF()->tables->usergroups . "` SET `utitle` = 'name', `role` = 'subscriber', `access` = 'standard' WHERE `utitle` IS NULL OR `utitle` = ''" );
	}
	#################################################################
	// ADD `color` field in usergroups TABLE  ///////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->usergroups, 'col' => 'color', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->usergroups . "` ADD `color` varchar(7) NOT NULL DEFAULT ''" );
		@WPF()->db->query( "UPDATE `" . WPF()->tables->usergroups . "` SET `color` = '#FF3333' WHERE `groupid` = 1" );
		@WPF()->db->query( "UPDATE `" . WPF()->tables->usergroups . "` SET `color` = '#0066FF' WHERE `groupid` = 2" );
		@WPF()->db->query( "UPDATE `" . WPF()->tables->usergroups . "` SET `color` = '#222222' WHERE `groupid` = 4" );
		@WPF()->db->query( "UPDATE `" . WPF()->tables->usergroups . "` SET `color` = '#993366' WHERE `groupid` = 5" );
	}
	#################################################################
	// ADD `visible` field in usergroups TABLE  ///////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->usergroups, 'col' => 'visible', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->usergroups . "` ADD `visible` TINYINT(1) NOT NULL DEFAULT 1;" );
	}
	#################################################################
	// ADD `status` field in `languages` TABLE //////////////////////////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->usergroups, 'col' => 'is_default', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->usergroups . "` ADD COLUMN `is_default` TINYINT(1) NOT NULL DEFAULT 0" );
	}
	#################################################################
	// ADD `online_time` field in profiles TABLE  ///////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->profiles, 'col' => 'online_time', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->profiles . "` ADD `online_time` INT UNSIGNED NOT NULL DEFAULT 0, ADD KEY (`online_time`)" );
	}
	// ADD `is_email_confirmed` field in profiles TABLE  ///////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->profiles, 'col' => 'is_email_confirmed', 'check' => 'col_exists' ] ) ) {
		WPF()->db->query( "ALTER TABLE `" . WPF()->tables->profiles . "` ADD `is_email_confirmed` TINYINT(1) NOT NULL DEFAULT 0, ADD KEY (`is_email_confirmed`)" );
		WPF()->db->query(
			"UPDATE `" . WPF()->tables->profiles . "`
                                 JOIN `" . WPF()->tables->subscribes . "`
                                       ON `" . WPF()->tables->subscribes . "`.`userid` = `" . WPF()->tables->profiles . "`.`userid`
                                            SET `" . WPF()->tables->profiles . "`.`is_email_confirmed` = 1 
                                                WHERE `" . WPF()->tables->subscribes . "`.`active` = 1"
		);
		WPF()->db->query( "UPDATE `" . WPF()->tables->profiles . "` SET `is_email_confirmed` = 1 WHERE `groupid` = 1" );
	}
	#################################################################
	// DROP uname unique key from profiles TABLE  ///////////////////////////
	if( wpforo_db_check( [ 'table' => WPF()->tables->profiles, 'col' => 'UNIQUE USERNAME', 'check' => 'key_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->profiles . "` DROP KEY `UNIQUE USERNAME`" );
	}
	if( wpforo_db_check( [ 'table' => WPF()->tables->profiles, 'col' => 'UNIQUE ID', 'check' => 'key_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->profiles . "` DROP KEY `UNIQUE ID`" );
	}
	#################################################################
	// ADD `private` field in post TABLE  ///////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->posts, 'col' => 'private', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->posts . "` ADD `private` TINYINT(1) NOT NULL DEFAULT 0, ADD INDEX `is_private` (`private`)" );
	}
	#################################################################
	//Add user_name col in subsciption table///////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->subscribes, 'col' => 'user_name', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->subscribes . "` ADD `user_name` VARCHAR(60) NOT NULL DEFAULT ''" );
	}
	//Add user_email col in subsciption table
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->subscribes, 'col' => 'user_email', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->subscribes . "` ADD `user_email` VARCHAR(60) NOT NULL DEFAULT ''" );
	}
	//Add indexes for subscribe new fields
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->subscribes, 'col' => 'fld_group_unq', 'check' => 'key_exists' ] ) ) {
		$args = [ 'table' => WPF()->tables->subscribes, 'col' => 'itemid', 'check' => 'key_exists' ];
		if( wpforo_db_check( $args ) ) @WPF()->db->query( "ALTER TABLE `" . WPF()->tables->subscribes . "` DROP KEY `itemid`" );
		wpforo_add_unique_key( WPF()->tables->subscribes, 'subid', 'fld_group_unq', '`itemid`, `type`, `userid`, `user_email`(60)' );
	}
	if( strtolower( wpforo_db_check( [ 'table' => WPF()->tables->subscribes, 'col' => 'type', 'check' => 'col_type' ] ) ) !== 'varchar(50)' ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->subscribes . "` MODIFY `type` VARCHAR(50) NOT NULL" );
	}
	####################################################################
	//Add index for double condition queries to avoid SQl caching///////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->posts, 'col' => 'forumid_status', 'check' => 'key_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->posts . "` ADD KEY `forumid_status` (`forumid`,`status`)" );
	}
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->posts, 'col' => 'topicid_status', 'check' => 'key_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->posts . "` ADD KEY `topicid_status` (`topicid`,`status`)" );
	}
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->posts, 'col' => 'topicid_solved', 'check' => 'key_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->posts . "` ADD KEY `topicid_solved` (`topicid`,`is_answer`)" );
	}
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->topics, 'col' => 'forumid_status', 'check' => 'key_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->topics . "` ADD KEY `forumid_status` (`forumid`,`status`)" );
	}
	$topic_slug_key = @WPF()->db->get_row( "SHOW KEYS FROM `" . WPF()->tables->topics . "` WHERE `Key_name` LIKE 'slug'", ARRAY_A );
	if( $topic_slug_key && intval( wpfval( $topic_slug_key, 'Non_unique' ) ) === 0 ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->topics . "` DROP INDEX slug, ADD KEY slug (`slug`(191))" );
	}
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->posts, 'col' => 'topicid_parentid', 'check' => 'key_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->posts . "` ADD KEY `topicid_parentid` (`topicid`,`parentid`)" );
	}
	#################################################################
	// ADD `secondary_groupids` field in profiles TABLE  //////////////
	/*if( ! wpforo_db_check( [ 'table' => WPF()->tables->profiles, 'col' => 'secondary_groups', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->profiles . "` ADD `secondary_groups` VARCHAR(255)" );
	}*/
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->usergroups, 'col' => 'secondary', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->usergroups . "` ADD `secondary` TINYINT(1) NOT NULL DEFAULT 0;" );
		@WPF()->db->query( "UPDATE `" . WPF()->tables->usergroups . "` SET `secondary` = 1 WHERE `groupid` IN(3,5)" );
	}
	#################################################################
	// ADD `fields` field in profiles TABLE  ////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->profiles, 'col' => 'fields', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->profiles . "` ADD `fields` LONGTEXT" );
	}
	#################################################################
	// Change `phrase_key` type in phrases TABLE  ///////////////////
	if( strtolower( wpforo_db_check( [ 'table' => WPF()->tables->phrases, 'col' => 'phrase_key', 'check' => 'col_type' ] ) ) !== 'text' ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->phrases . "` MODIFY `phrase_key` TEXT" );
	}
	#################################################################
	// ADD `prefix` and `tags` fields in TOPIC TABLE  ///////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->topics, 'col' => 'tags', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->topics . "` 
			ADD `prefix` VARCHAR(100) NOT NULL DEFAULT '', 
			ADD `tags` TEXT, 
			ADD KEY (`prefix`), 
			ADD KEY (`tags`(190))"
		);
	}
	#################################################################
	//Add last_post indexes for forums and topics tables ////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->forums, 'col' => 'last_postid', 'check' => 'key_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->forums . "` ADD KEY(`last_postid`)" );
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->topics . "` ADD KEY `forumid_status_private` ( `forumid`,`status`,`private` ), ADD KEY(`last_post`)" );
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->posts . "`  ADD KEY `forumid_status_private` (`forumid`, `status`, `private`)" );
	}
	#################################################################
	//Add cover and cover_height fields to forums table ////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->forums, 'col' => 'cover', 'check' => 'col_exists' ] ) ){
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->forums . "` 
			ADD COLUMN `cover`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
		    ADD COLUMN `cover_height` INT(4) UNSIGNED NOT NULL DEFAULT 150"
		);
	}
	#################################################################
	//Add unique keys in VISITS TABLE ///////////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->visits, 'col' => 'unique_tracking', 'check' => 'key_exists' ] ) ) {
		wpforo_add_unique_key( WPF()->tables->visits, 'id', 'unique_tracking', '`userid`,`ip`,`forumid`,`topicid`' );
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->visits . "` ADD KEY `time_forumid` (`time`, `forumid`)" );
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->visits . "` ADD KEY `time_topicid` (`time`, `topicid`)" );
	}
	#################################################################
	// ADD `color` field in forums TABLE  ///////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->forums, 'col' => 'color', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->forums . "` ADD `color` varchar(7) NOT NULL DEFAULT ''" );
		@WPF()->db->query( "UPDATE `" . WPF()->tables->forums . "` SET `color` = concat('#',SUBSTRING((lpad(hex(round(rand() * 10000000)),6,0)),-6))" );
	}
	#################################################################
	// ADD `root` fields in POSTS TABLE  ///////////////
	//$post_table_count = @WPF()->db->get_var("SELECT COUNT(*) FROM `".WPF()->tables->posts."`");
	//if( $post_table_count < 100000 ){
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->posts, 'col' => 'root', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->posts . "` ADD `root` BIGINT, ADD KEY(`root`)" );
	}
	//}
	#################################################################
	// ADD `new` in ACTIVITY TABLE  /////////////////////////////////
	if( ! wpforo_db_check( [ 'table' => WPF()->tables->activity, 'col' => 'new', 'check' => 'col_exists' ] ) ) {
		@WPF()->db->query( "ALTER TABLE `" . WPF()->tables->activity . "` ADD `new` TINYINT NOT NULL DEFAULT '0' AFTER `permalink`, ADD KEY `itemtype_userid_new` (`itemtype`, `userid`, `new`)" );
	}

	################################################################
	// likes and votes Table to new reactions table ////////////////
	if( wpforo_db_check( [ 'table' => wpforo_fix_table_name( 'likes' ), 'check' => 'table_exists' ] ) ){
		$sql = "INSERT IGNORE INTO `". WPF()->tables->reactions ."` (
			SELECT NULL, `userid`, `postid`, `post_userid`, `reaction`, IF( `reaction` = 1, 'up', 'down' ), NULL, NULL 
				FROM `". wpforo_fix_table_name( 'votes' ) ."`
			UNION
			SELECT NULL, `userid`, `postid`, `post_userid`, 1, 'up', NULL, NULL 
				FROM `". wpforo_fix_table_name( 'likes' ) ."`
		)";
		WPF()->db->query($sql);
		$sql = "RENAME TABLE `". wpforo_fix_table_name( 'votes' ) ."` TO `". wpforo_fix_table_name( 'votes_' ) ."`";
		WPF()->db->query($sql);
		$sql = "RENAME TABLE `". wpforo_fix_table_name( 'likes' ) ."` TO `". wpforo_fix_table_name( 'likes_' ) ."`";
		WPF()->db->query($sql);
	}

	################################################################
	// profiles table fields fixing ////////////////////////////////
	if( wpforo_db_check( [ 'table' => WPF()->tables->profiles, 'col' => 'secondary_groups', 'check' => 'col_exists' ] ) ){
		$sql = "SELECT `userid`, `facebook`, `twitter`, `skype`, `fields`
			FROM `". WPF()->tables->profiles ."`
			WHERE (`facebook` IS NOT NULL AND `facebook` <> '') 
				OR (`twitter` IS NOT NULL AND `twitter`  <> '') 
				OR (`skype`   IS NOT NULL AND `skype`    <> '')";
		if( $users = (array) WPF()->db->get_results( $sql, ARRAY_A ) ){
			foreach( $users as $user ){
				$user['userid'] = wpforo_bigintval( $user['userid'] );
				$facebook       = trim( $user['facebook'] );
				$twitter        = trim( $user['twitter'] );
				$skype          = trim( $user['skype'] );
				$user['fields'] = array_merge( (array) json_decode($user['fields'], true), compact( 'facebook', 'twitter', 'skype' ) );
				$user['fields'] = json_encode( $user['fields'] );
				WPF()->db->update(
					WPF()->tables->profiles,
					[ 'fields' => $user['fields'] ],
					[ 'userid' => $user['userid'] ],
					'%s',
					'%d'
				);
			}
		}

		@WPF()->db->query(
		"ALTER TABLE `". WPF()->tables->profiles ."`
			DROP COLUMN `username`,
			DROP COLUMN `last_login`,
			DROP COLUMN `icq`,
			DROP COLUMN `aim`,
			DROP COLUMN `yahoo`,
			DROP COLUMN `msn`,
			DROP COLUMN `gtalk`,
			DROP COLUMN `like`,
			DROP COLUMN `site`,
			DROP COLUMN `facebook`,
			DROP COLUMN `twitter`,
			DROP COLUMN `skype`,
			ADD COLUMN `topics` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `posts`,
			ADD COLUMN `reactions_in` TEXT AFTER `comments`,
			ADD COLUMN `reactions_out` TEXT AFTER `reactions_in`,
			ADD COLUMN `points` INT NOT NULL DEFAULT 0 AFTER `reactions_out`,
			CHANGE COLUMN `secondary_groups` `secondary_groupids` VARCHAR(255) AFTER `groupid`,
			CHANGE COLUMN `rank` `custom_points` INT NOT NULL DEFAULT 0 AFTER `points`,
			MODIFY COLUMN `avatar` VARCHAR(255) AFTER `secondary_groupids`,
			MODIFY COLUMN `online_time` INT UNSIGNED AFTER `custom_points`,
			MODIFY COLUMN `timezone` VARCHAR(255) NOT NULL DEFAULT 'UTC+0' AFTER `online_time`,
			MODIFY COLUMN `location` VARCHAR(255) AFTER `timezone`,
			ADD COLUMN `cover` VARCHAR(255) NOT NULL DEFAULT '' AFTER `avatar`,
			ADD COLUMN `is_mention_muted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_email_confirmed`"
		);
	}

	WPF()->db->query( "TRUNCATE TABLE `" . WPF()->tables->visits . "`" );
}

function wpforo_uninstall() {
	if( ! wpforo_is_admin() ) return;
	if( ! current_user_can( 'activate_plugins' ) ) return;

	foreach( WPF()->_tables as $table ) WPF()->db->query( "DROP TABLE IF EXISTS `" . wpforo_fix_table_name( $table ) . "`" );

	WPF()->db->query(
		"DELETE FROM `" . WPF()->db->options . "`  
	        WHERE `option_name` LIKE 'widget_wpforo_widget_%' 
	        OR `option_name` REGEXP '^" . wpforo_prefix() . "'"
	);
	WPF()->db->query( "DELETE FROM `" . WPF()->db->usermeta . "` WHERE `meta_key` REGEXP '^" . wpforo_prefix() . "'" );

	wpforo_remove_directory( WPF()->folders['upload']['dir'] );
}

function wpforo_profile_notice() {
	if( is_multisite() ) {
		$users = WPF()->db->get_var( "SELECT COUNT(*) FROM `" . WPF()->db->usermeta . "` WHERE `meta_key` LIKE '" . WPF()->blog_prefix . "capabilities'" );
	} else {
		$users = WPF()->db->get_var( "SELECT COUNT(*) FROM `" . WPF()->db->users . "`" );
	}
	$profiles = WPF()->db->get_var( "SELECT COUNT(*) FROM `" . WPF()->tables->profiles . "`" );
	$delta    = $users - $profiles;
	$status   = ( $delta > 2 ) ? round( ( ( $profiles * 100 ) / $users ), 1 ) . '% (' . $profiles . ' / ' . $users . ') ' : '100%';
	$btext    = ( $profiles == 0 ) ? __( 'Start Profile Synchronization', 'wpforo' ) : __( 'Continue Synchronization', 'wpforo' );
	$url      = wp_nonce_url( admin_url( 'admin.php?page=wpforo-overview&wpfaction=synch_user_profiles' ), 'wpforo_synch_user_profiles' );
	$class    = 'wpforo-mnote notice notice-warning is-dismissible';
	$note     = __( 'This process may take a few seconds or dozens of minutes, please be patient and don\'t close this page.', 'wpforo' );
	$info     = __( 'You can permanently disable this message in Dashboard > Forums > Features admin page.', 'wpforo' );
	$button   = '<a href="' . $url . '" class="button button-primary button-large" style="font-size:14px;">' . $btext . ' &gt;&gt;</a>';
	$header   = __( 'wpForo Forum Installation | ', 'wpforo' );
	$message  = __( 'Forum users\' profile data are not synchronized yet, this step is required! Please click the button below to complete installation.', 'wpforo' );
	echo '<div class="' . $class . '" style="padding:15px 20px;"><h2 style="margin:0;">' . esc_html( $header ) . $status . ' </h2><p style="font-size:15px;margin:5px 0;">' . $message . '</p><p style="margin:0 0 10px 0;">' . $button . '</p><hr /><p style="margin:0;color:#dd0000;">' . $note . '</p><p style="margin:0;color:#999; font-size:12px;">' . $info . '</p></div>';
}

function wpforo_database_notice() {
	$url   = admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'tools' ) . '&tab=debug&view=tables' );
	$class = 'wpforo-dbnote notice notice-warning is-dismissible';
	//    $note = __( 'This process may take a few seconds or dozens of minutes, please be patient and don\'t close this page. Database backup is not required. If you got 500 Server Error please don\'t worry, the data updating process is still working in MySQL server.', 'wpforo');
	$info    = __( 'You can permanently disable this message in Dashboard > Forums > Features admin page.', 'wpforo' );
	$button  = '<a href="' . $url . '" class="button button-primary button-large" style="font-size:13px;">Go to Database Troubleshooter &gt;&gt;</a>';
	$header  = __( 'wpForo Database Update Problem - Action Required!', 'wpforo' );
	$message = __( 'Forum database is not updated properly. Please click the button below for further instruction.', 'wpforo' );
	echo '<div class="' . $class . '" style="padding:15px 20px;"><h2 style="margin:0;">' . esc_html( $header ) . ' </h2><p style="font-size:15px;margin:5px 0;">' . $message . '</p><p style="margin:0 0 10px 0;">' . $button . '</p><hr /><p style="margin:0;color:#999; font-size:12px;">' . $info . '</p></div>';
}

function wpforo_create_important_folders() {
	foreach( WPF()->folders as $folder ) if( ! is_dir( $folder['dir'] ) ) wp_mkdir_p( $folder['dir'] );
}

function wpforo_get_shortcode_pageid( $exclude = [] ) {
	$exclude = array_filter( array_map( 'wpforo_bigintval', (array) $exclude ) );
	$sql     = "SELECT `ID` FROM `" . WPF()->db->posts . "` 
        WHERE `post_content` LIKE '%[wpforo]%' 
        AND `post_status` LIKE 'publish' 
        AND `post_type` IN('" . implode( "','", wpforo_get_blog_content_types() ) . "')";
	if( $exclude ) $sql .= " AND `ID` NOT IN(" . implode( ',', $exclude ) . ")";

	return WPF()->db->get_var( $sql );
}

function wpforo_create_forum_page() {
	$pageid = WPF()->board->get_current( 'pageid' );
	if( ! $pageid || ! WPF()->db->get_var( "SELECT `ID` FROM `" . WPF()->db->posts . "` WHERE `ID` = '" . $pageid . "' AND ( `post_content` LIKE '%[wpforo]%' OR `post_content` LIKE '%[wpforo-index]%' ) AND `post_status` LIKE 'publish' AND `post_type` IN('" . implode( "','", wpforo_get_blog_content_types() ) . "')" ) ) {
		if( ! $page_id = wpforo_get_shortcode_pageid( get_option( 'page_on_front' ) ) ) {
			$wpforo_page = [
				'post_date'         => current_time( 'mysql', 1 ),
				'post_date_gmt'     => current_time( 'mysql', 1 ),
				'post_content'      => '[wpforo]',
				'post_title'        => 'Forum',
				'post_status'       => 'publish',
				'comment_status'    => 'close',
				'ping_status'       => 'close',
				'post_name'         => 'community',
				'post_modified'     => current_time( 'mysql', 1 ),
				'post_modified_gmt' => current_time( 'mysql', 1 ),
				'post_parent'       => 0,
				'menu_order'        => 0,
				'post_type'         => 'page',
			];
			$page_id     = wp_insert_post( $wpforo_page );
		}
		if( $page_id && ! is_wp_error( $page_id ) ) {
			wpforo_update_option( 'wpforo_pageid', $page_id );
		}
	}

	flush_rewrite_rules( false );
	nocache_headers();
}

function wpforo_repair_main_shortcode_page() {
	$pageid = WPF()->board->get_current( 'pageid' );
	$sql    = "SELECT `ID` FROM `" . WPF()->db->posts . "` 
				WHERE `ID` = " . $pageid . " 
				AND `post_content` LIKE '%[wpforo%' 
				AND `post_status` LIKE 'publish' 
				AND `post_type` IN('" . implode( "','", wpforo_get_blog_content_types() ) . "')";
	if( ! $pageid || ! WPF()->db->get_var( $sql ) ) {
		wpforo_create_forum_page();
	} else {
		flush_rewrite_rules( false );
		nocache_headers();
	}
}

function wpforo_import_default_boards() {
	if( ! WPF()->board->get_current( 'boardid' ) && ! WPF()->board->_get_board( 0 ) ) {
		$board                  = WPF()->board->get_current();
		$board['is_standalone'] = wpforo_get_option( 'wpforo_use_home_url', false );
		$board['excld_urls']    = array_filter( array_map( 'trim', explode( PHP_EOL, wpforo_get_option( 'wpforo_excld_urls', '' ) ) ) );
		WPF()->board->edit( [ 'boardid' => 0 ], WPF()->board->add( $board ) );
	}
}

function wpforo_import_default_menus() {
	$menu_name     = wpforo_phrase( 'wpForo Navigation', false, 'orig' );
	$menu_location = 'wpforo-menu';
	$menu_exists   = wp_get_nav_menu_object( $menu_name );
	if( ! $menu_exists ) {
		$id                = [];
		$menu_id           = wp_create_nav_menu( $menu_name );
		$id['wpforo-home'] = wp_update_nav_menu_item( $menu_id, 0, [
			'menu-item-title'     => wpforo_phrase( 'Forums', false ),
			'menu-item-classes'   => 'wpforo-home',
			'menu-item-url'       => '/%wpforo-home%/',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => 0,
			'menu-item-position'  => 0,
		] );

		$id['wpforo-members'] = wp_update_nav_menu_item( $menu_id, 0, [
			'menu-item-title'     => wpforo_phrase( 'Members', false ),
			'menu-item-classes'   => 'wpforo-members',
			'menu-item-url'       => '/%wpforo-members%/',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => 0,
			'menu-item-position'  => 0,
		] );

		$id['wpforo-recent'] = wp_update_nav_menu_item( $menu_id, 0, [
			'menu-item-title'     => wpforo_phrase( 'Recent Posts', false ),
			'menu-item-classes'   => 'wpforo-recent',
			'menu-item-url'       => '/%wpforo-recent%/',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => 0,
			'menu-item-position'  => 0,
		] );

		$id['wpforo-profile'] = wp_update_nav_menu_item( $menu_id, 0, [
			'menu-item-title'     => wpforo_phrase( 'My Profile', false ),
			'menu-item-classes'   => 'wpforo-profile',
			'menu-item-url'       => '/%wpforo-profile-home%/',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => 0,
			'menu-item-position'  => 0,
		] );

		if( isset( $id['wpforo-profile'] ) && $id['wpforo-profile'] ) {
			$id['wpforo-profile-account']       = wp_update_nav_menu_item( $menu_id, 0, [
				                                                                       'menu-item-title'     => wpforo_phrase( 'Account', false ),
				                                                                       'menu-item-classes'   => 'wpforo-profile-account',
				                                                                       'menu-item-url'       => '/%wpforo-profile-account%/',
				                                                                       'menu-item-status'    => 'publish',
				                                                                       'menu-item-parent-id' => $id['wpforo-profile'],
				                                                                       'menu-item-position'  => 1,
			                                                                       ] );
			$id['wpforo-profile-activity']      = wp_update_nav_menu_item( $menu_id, 0, [
				                                                                       'menu-item-title'     => wpforo_phrase( 'Activity', false ),
				                                                                       'menu-item-classes'   => 'wpforo-profile-activity',
				                                                                       'menu-item-url'       => '/%wpforo-profile-activity%/',
				                                                                       'menu-item-status'    => 'publish',
				                                                                       'menu-item-parent-id' => $id['wpforo-profile'],
				                                                                       'menu-item-position'  => 1,
			                                                                       ] );
			$id['wpforo-profile-subscriptions'] = wp_update_nav_menu_item( $menu_id, 0, [
				                                                                       'menu-item-title'     => wpforo_phrase( 'Subscriptions', false ),
				                                                                       'menu-item-classes'   => 'wpforo-profile-subscriptions',
				                                                                       'menu-item-url'       => '/%wpforo-profile-subscriptions%/',
				                                                                       'menu-item-status'    => 'publish',
				                                                                       'menu-item-parent-id' => $id['wpforo-profile'],
				                                                                       'menu-item-position'  => 2,
			                                                                       ] );
		}

		$id['wpforo-register'] = wp_update_nav_menu_item( $menu_id, 0, [
			'menu-item-title'     => wpforo_phrase( 'Register', false ),
			'menu-item-classes'   => 'wpforo-register',
			'menu-item-url'       => '/%wpforo-register%/',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => 0,
			'menu-item-position'  => 0,
		] );

		$id['wpforo-login'] = wp_update_nav_menu_item( $menu_id, 0, [
			'menu-item-title'     => wpforo_phrase( 'Login', false ),
			'menu-item-classes'   => 'wpforo-login',
			'menu-item-url'       => '/%wpforo-login%/',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => 0,
			'menu-item-position'  => 0,
		] );

		$id['wpforo-logout'] = wp_update_nav_menu_item( $menu_id, 0, [
			'menu-item-title'     => wpforo_phrase( 'Logout', false ),
			'menu-item-classes'   => 'wpforo-logout',
			'menu-item-url'       => '/%wpforo-logout%/',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => 0,
			'menu-item-position'  => 0,
		] );

		if( ! has_nav_menu( $menu_location ) ) {
			$locations = get_theme_mod( 'nav_menu_locations' );
			if( empty( $locations ) ) $locations = [];
			$locations[ $menu_location ] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}
}

function wpforo_import_default_accesses() {
	$cans_n = [
		'vf'   => 0,
		'enf'  => 0,
		'ct'   => 0,
		'vt'   => 0,
		'ent'  => 0,
		'et'   => 0,
		'dt'   => 0,
		'cr'   => 0,
		'ocr'  => 0,
		'vr'   => 0,
		'er'   => 0,
		'dr'   => 0,
		'tag'  => 0,
		'eot'  => 0,
		'eor'  => 0,
		'dot'  => 0,
		'dor'  => 0,
		'sb'   => 0,
		'l'    => 0,
		'r'    => 0,
		's'    => 0,
		'au'   => 0,
		'p'    => 0,
		'op'   => 0,
		'vp'   => 0,
		'sv'   => 0,
		'osv'  => 0,
		'v'    => 0,
		'a'    => 0,
		'va'   => 0,
		'at'   => 0,
		'oat'  => 0,
		'aot'  => 0,
		'cot'  => 0,
		'mt'   => 0,
		'ccp'  => 0,
		'cvp'  => 0,
		'cvpr' => 0,
	];
	$cans_r = [
		'vf'   => 1,
		'enf'  => 1,
		'ct'   => 0,
		'vt'   => 1,
		'ent'  => 1,
		'et'   => 0,
		'dt'   => 0,
		'cr'   => 0,
		'ocr'  => 0,
		'vr'   => 1,
		'er'   => 0,
		'dr'   => 0,
		'tag'  => 0,
		'eot'  => 0,
		'eor'  => 0,
		'dot'  => 0,
		'dor'  => 0,
		'sb'   => 1,
		'l'    => 0,
		'r'    => 0,
		's'    => 0,
		'au'   => 0,
		'p'    => 0,
		'op'   => 0,
		'vp'   => 0,
		'sv'   => 0,
		'osv'  => 0,
		'v'    => 0,
		'a'    => 0,
		'va'   => 1,
		'at'   => 0,
		'oat'  => 0,
		'aot'  => 0,
		'cot'  => 0,
		'mt'   => 0,
		'ccp'  => 0,
		'cvp'  => 0,
		'cvpr' => 1,
	];
	$cans_s = [
		'vf'   => 1,
		'enf'  => 1,
		'ct'   => 1,
		'vt'   => 1,
		'ent'  => 1,
		'et'   => 0,
		'dt'   => 0,
		'cr'   => 1,
		'ocr'  => 1,
		'vr'   => 1,
		'er'   => 0,
		'dr'   => 0,
		'tag'  => 1,
		'eot'  => 1,
		'eor'  => 1,
		'dot'  => 1,
		'dor'  => 1,
		'sb'   => 1,
		'l'    => 1,
		'r'    => 1,
		's'    => 0,
		'au'   => 0,
		'p'    => 0,
		'op'   => 1,
		'vp'   => 0,
		'sv'   => 0,
		'osv'  => 1,
		'v'    => 1,
		'a'    => 1,
		'va'   => 1,
		'at'   => 0,
		'oat'  => 1,
		'aot'  => 1,
		'cot'  => 0,
		'mt'   => 0,
		'ccp'  => 1,
		'cvp'  => 1,
		'cvpr' => 1,
	];
	$cans_m = [
		'vf'   => 1,
		'enf'  => 1,
		'ct'   => 1,
		'vt'   => 1,
		'ent'  => 1,
		'et'   => 1,
		'dt'   => 1,
		'cr'   => 1,
		'ocr'  => 1,
		'vr'   => 1,
		'er'   => 1,
		'dr'   => 1,
		'tag'  => 1,
		'eot'  => 1,
		'eor'  => 1,
		'dot'  => 1,
		'dor'  => 1,
		'sb'   => 1,
		'l'    => 1,
		'r'    => 1,
		's'    => 1,
		'au'   => 1,
		'p'    => 1,
		'op'   => 1,
		'vp'   => 1,
		'sv'   => 1,
		'osv'  => 1,
		'v'    => 1,
		'a'    => 1,
		'va'   => 1,
		'at'   => 1,
		'oat'  => 1,
		'aot'  => 1,
		'cot'  => 1,
		'mt'   => 1,
		'ccp'  => 1,
		'cvp'  => 1,
		'cvpr' => 1,
	];
	$cans_a = [
		'vf'   => 1,
		'enf'  => 1,
		'ct'   => 1,
		'vt'   => 1,
		'ent'  => 1,
		'et'   => 1,
		'dt'   => 1,
		'cr'   => 1,
		'ocr'  => 1,
		'vr'   => 1,
		'er'   => 1,
		'dr'   => 1,
		'tag'  => 1,
		'eot'  => 1,
		'eor'  => 1,
		'dot'  => 1,
		'dor'  => 1,
		'sb'   => 1,
		'l'    => 1,
		'r'    => 1,
		's'    => 1,
		'au'   => 1,
		'p'    => 1,
		'op'   => 1,
		'vp'   => 1,
		'sv'   => 1,
		'osv'  => 1,
		'v'    => 1,
		'a'    => 1,
		'va'   => 1,
		'at'   => 1,
		'oat'  => 1,
		'aot'  => 1,
		'cot'  => 1,
		'mt'   => 1,
		'ccp'  => 1,
		'cvp'  => 1,
		'cvpr' => 1,
	];

	//Add new Accesses in this array to add those in custom Accesses created by forum admin
	$cans_default = [
		'sb'   => 1,
		'au'   => 1,
		'p'    => 0,
		'op'   => 1,
		'vp'   => 0,
		'ccp'  => 0,
		'cvp'  => 0,
		'cvpr' => 1,
		'aot'  => 1,
		'tag'  => 1,
		'ocr'  => 0,
		'enf'  => 1,
		'ent'  => 1,
	];

	$sql      = "SELECT * FROM `" . WPF()->tables->accesses . "`";
	$accesses = WPF()->db->get_results( $sql, ARRAY_A );
	if( empty( $accesses ) ) {
		$cans_n = serialize( $cans_n );
		$cans_r = serialize( $cans_r );
		$cans_s = serialize( $cans_s );
		$cans_m = serialize( $cans_m );
		$cans_a = serialize( $cans_a );

		$sql = "INSERT IGNORE INTO `" . WPF()->tables->accesses . "` 
			(`access`, `title`, cans) VALUES	
			('no_access', 'No access', '" . $cans_n . "'),
			('read_only', 'Read only access', '" . $cans_r . "'),
			('standard', 'Standard access', '" . $cans_s . "'),
			('moderator', 'Moderator access', '" . $cans_m . "'),
			('full', 'Full access', '" . $cans_a . "')";

		WPF()->db->query( $sql );
	} else {
		foreach( $accesses as $access ) {
			$current = unserialize( $access['cans'] );
			if( strtolower( $access['access'] ) === 'no_access' ) {
				$default = $cans_n;
			} elseif( strtolower( $access['access'] ) === 'read_only' ) {
				$default = $cans_r;
			} elseif( strtolower( $access['access'] ) === 'standard' ) {
				$default = $cans_s;
			} elseif( strtolower( $access['access'] ) === 'moderator' ) {
				$default = $cans_m;
			} elseif( strtolower( $access['access'] ) === 'full' ) {
				$default = $cans_a;
			} else {
				$default = $cans_default;
			}
			if( ! empty( $default ) ) {
				$data_update = array_merge( $default, $current );
				if( ! empty( $data_update ) ) {
					$data_update = serialize( $data_update );
					WPF()->db->query( "UPDATE `" . WPF()->tables->accesses . "` SET `cans` = '" . WPF()->db->_real_escape( $data_update ) . "' WHERE `accessid` = " . intval( $access['accessid'] ) );
				}
			}
		}
	}
}

function wpforo_import_default_usergroups() {
	$cans_admin    = [
		'mf'           => '1',
		'ms'           => '1',
		'mt'           => '1',
		'mp'           => '1',
		'mth'          => '1',
		'vm'           => '1',
		'aum'          => '1',
		'em'           => '1',
		'vmg'          => '1',
		'aup'          => '1',
		'vmem'         => '1',
		'view_stat'    => '1',
		'vprf'         => '1',
		'vpra'         => '1',
		'vprs'         => '1',
		'bm'           => '1',
		'dm'           => '1',
		'upa'          => '1',
		'ups'          => '1',
		'va'           => '1',
		'vmu'          => '1',
		'vmm'          => '1',
		'vmt'          => '1',
		'vmct'         => '1',
		'vmr'          => '1',
		'vmw'          => '1',
		'vmsn'         => '1',
		'vmrd'         => '1',
		'vml'          => '1',
		'vmo'          => '1',
		'vms'          => '1',
		'vmam'         => '1',
		'vwpm'         => '1',
		'caa'          => 1,
		'vt_add_topic' => 1,
	];
	$cans_moder    = [
		'mf'           => '0',
		'ms'           => '0',
		'mt'           => '0',
		'mp'           => '0',
		'mth'          => '0',
		'vm'           => '0',
		'aum'          => '1',
		'em'           => '0',
		'vmg'          => '0',
		'aup'          => '1',
		'vmem'         => '1',
		'view_stat'    => '1',
		'vprf'         => '1',
		'vpra'         => '1',
		'vprs'         => '1',
		'bm'           => '1',
		'dm'           => '1',
		'upa'          => '1',
		'ups'          => '1',
		'va'           => '1',
		'vmu'          => '0',
		'vmm'          => '1',
		'vmt'          => '1',
		'vmct'         => '1',
		'vmr'          => '1',
		'vmw'          => '1',
		'vmsn'         => '1',
		'vmrd'         => '1',
		'vml'          => '1',
		'vmo'          => '1',
		'vms'          => '1',
		'vmam'         => '1',
		'vwpm'         => '1',
		'caa'          => 1,
		'vt_add_topic' => 1,
	];
	$cans_reg      = [
		'mf'           => '0',
		'ms'           => '0',
		'mt'           => '0',
		'mp'           => '0',
		'mth'          => '0',
		'vm'           => '0',
		'aum'          => '0',
		'em'           => '0',
		'vmg'          => '0',
		'aup'          => '1',
		'vmem'         => '1',
		'view_stat'    => '1',
		'vprf'         => '1',
		'vpra'         => '1',
		'vprs'         => '0',
		'bm'           => '0',
		'dm'           => '0',
		'upa'          => '1',
		'ups'          => '1',
		'va'           => '1',
		'vmu'          => '0',
		'vmm'          => '0',
		'vmt'          => '1',
		'vmct'         => '1',
		'vmr'          => '1',
		'vmw'          => '1',
		'vmsn'         => '1',
		'vmrd'         => '1',
		'vml'          => '1',
		'vmo'          => '1',
		'vms'          => '1',
		'vmam'         => '1',
		'vwpm'         => '1',
		'caa'          => 1,
		'vt_add_topic' => 1,
	];
	$cans_guest    = [
		'mf'           => '0',
		'ms'           => '0',
		'mt'           => '0',
		'mp'           => '0',
		'mth'          => '0',
		'vm'           => '0',
		'aum'          => '0',
		'em'           => '0',
		'vmg'          => '0',
		'aup'          => '0',
		'vmem'         => '1',
		'view_stat'    => '1',
		'vprf'         => '1',
		'vpra'         => '1',
		'vprs'         => '0',
		'bm'           => '0',
		'dm'           => '0',
		'upa'          => '0',
		'ups'          => '0',
		'va'           => '1',
		'vmu'          => '0',
		'vmm'          => '0',
		'vmt'          => '1',
		'vmct'         => '1',
		'vmr'          => '1',
		'vmw'          => '0',
		'vmsn'         => '1',
		'vmrd'         => '1',
		'vml'          => '1',
		'vmo'          => '1',
		'vms'          => '1',
		'vmam'         => '1',
		'vwpm'         => '0',
		'caa'          => 1,
		'vt_add_topic' => 0,
	];
	$cans_customer = [
		'mf'           => '0',
		'ms'           => '0',
		'mt'           => '0',
		'mp'           => '0',
		'mth'          => '0',
		'vm'           => '0',
		'aum'          => '0',
		'em'           => '0',
		'vmg'          => '0',
		'aup'          => '0',
		'vmem'         => '1',
		'view_stat'    => '1',
		'vprf'         => '1',
		'vpra'         => '1',
		'vprs'         => '0',
		'bm'           => '0',
		'dm'           => '0',
		'upa'          => '1',
		'ups'          => '1',
		'va'           => '1',
		'vmu'          => '0',
		'vmm'          => '0',
		'vmt'          => '1',
		'vmct'         => '1',
		'vmr'          => '1',
		'vmw'          => '1',
		'vmsn'         => '1',
		'vmrd'         => '1',
		'vml'          => '1',
		'vmo'          => '1',
		'vms'          => '1',
		'vmam'         => '1',
		'vwpm'         => '1',
		'caa'          => 1,
		'vt_add_topic' => 1,
	];

	//Add new Cans in this array to add those in custom Usergroup created by forum admin
	$cans_defaults = [ 'mf' => '0', 'ms' => '0', 'mt' => '0', 'mp' => '0', 'mth' => '0', 'vmem' => 1, 'view_stat' => '1', 'vprf' => 1, 'caa' => 1, 'vt_add_topic' => 1 ];

	$sql = "SELECT * FROM `" . WPF()->tables->usergroups . "`";
	if( ! $usergroups = WPF()->db->get_results( $sql, ARRAY_A ) ) {
		WPF()->usergroup->add( 'Admin', $cans_admin, '', 'administrator', 'full', '#FF3333' );
		WPF()->usergroup->add( 'Moderator', $cans_moder, '', 'editor', 'moderator', '#0066FF' );
		WPF()->usergroup->add( 'Registered', $cans_reg, '', 'subscriber', 'standard', '', 1, 1 );
		WPF()->usergroup->add( 'Guest', $cans_guest, '', '', 'read_only', '#222222', 0 );
		WPF()->usergroup->add( 'Customer', $cans_customer, '', 'customer', 'standard', '#993366', 1, 1 );
	} else {
		foreach( $usergroups as $usergroup ) {
			$current = unserialize( $usergroup['cans'] );
			if( strtolower( $usergroup['name'] ) === 'admin' ) {
				$default = $cans_admin;
			} elseif( strtolower( $usergroup['name'] ) === 'moderator' ) $default = $cans_moder;
			elseif( strtolower( $usergroup['name'] ) === 'registered' ) $default = $cans_reg;
			elseif( strtolower( $usergroup['name'] ) === 'guest' ) $default = $cans_guest;
			elseif( strtolower( $usergroup['name'] ) === 'customer' ) $default = $cans_customer;
			else {
				$default = $cans_defaults;
			}
			if( ! empty( $default ) ) {
				$data_update = array_merge( $default, $current );
				if( ! empty( $data_update ) ) {
					$data_update = serialize( $data_update );
					WPF()->db->query( "UPDATE `" . WPF()->tables->usergroups . "` SET `cans` = '" . WPF()->db->_real_escape( $data_update ) . "' WHERE `groupid` = " . intval( $usergroup['groupid'] ) );
				}
			}
		}
	}
}

function wpforo_import_default_forums() {
	$sql   = "SELECT COUNT(*) FROM `" . WPF()->tables->forums . "`";
	$count = WPF()->db->get_var( $sql );
	if( ! $count ) {
		if( $parentid = WPF()->forum->add( [ 'title' => __( 'Main Category', 'wpforo' ), 'description' => __( 'This is a simple category / section', 'wpforo' ), 'cat_layout' => 4, 'icon' => 'fas fa-comments' ], false ) ) {
			WPF()->forum->add( [ 'title' => __( 'Main Forum', 'wpforo' ), 'description' => __( 'This is a simple parent forum', 'wpforo' ), 'parentid' => $parentid, 'cat_layout' => 4, 'icon' => 'fas fa-comments' ], false );
		}
	}
}

function wpforo_fix_wp_permalink_structure() {
	$permalink_structure = get_option( 'permalink_structure' );
	if( ! $permalink_structure ) {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
	}
}

function wpforo_fix_installed_options() {
	/**
	 * in the update process, using old features settings set the right profile setting for the new option
	 */
	if( $features = wpforo_get_option( 'features', [], false ) ) {
		if( ! (int) wpfval( $features, 'profile' ) ) {
			if( (int) wpfval( $features, 'bp_profile' ) && class_exists( 'BP_Component' ) ) {
				$features['profile'] = 3;
			} elseif( (int) wpfval( $features, 'um_profile' ) && function_exists( 'UM' ) ) {
				$features['profile'] = 4;
			} elseif( (int) wpfval( $features, 'comment-author-link' ) ) {
				$features['profile'] = 2;
			} else {
				$features['profile'] = 1;
			}
			wpforo_update_option( 'features', array_map( 'intval', $features ) );
		}
	}

	#################################################################
	// CHECK Addon Notice /////////////////////////////////////////
	$lastHash = wpforo_get_option( 'addon_note_dismissed', false, false );
	$first    = wpforo_get_option( 'addon_note_first', false, false );
	if( $lastHash && $first === 'true' ) {
		wpforo_update_option( 'addon_note_first', 'false' );
	}

	#################################################################
	// AVOID PLUGIN CONFLICTS ///////////////////////////////////////
	/* Autoptimize *************************************************/
	$autopt = get_option( 'autoptimize_js_exclude' );
	if( $autopt && strpos( $autopt, 'wp-includes/js/tinymce' ) === false ) {
		$autopt = $autopt . ', wp-includes/js/tinymce';
		update_option( 'autoptimize_js_exclude', $autopt );
		if( class_exists( 'autoptimizeCache' ) && is_callable( [ 'autoptimizeCache', 'clearall' ] ) ) {
			autoptimizeCache::clearall();
		}
	}

	#################################################################
	// Adding #wpforo to custom css /////////////////////////////////
	if( $style_options = wpforo_get_option( 'style_options', [], false ) ) {
		$custom_css = wpfval( $style_options, 'custom_css' );
		if( $custom_css && strpos( $custom_css, '#wpforo #wpforo-wrap' ) === false ) {
			$style_options['custom_css'] = str_replace( '#wpforo-wrap', '#wpforo #wpforo-wrap', $style_options['custom_css'] );
			wpforo_update_option( 'style_options', $style_options );
		}
	}
}

function wpforo_update_theme_options() {
	if( $current_theme = wpforo_get_option( 'theme_options_' . WPF()->tpl->theme, [], false ) ) {
		$theme = WPF()->tpl->find_theme( '2022' );
		if( wpfval( $theme, 'layouts' ) ) {
			$current_theme['layouts'] = $theme['layouts'];
			$theme                    = wpforo_deep_merge( $theme, $current_theme );
			wpforo_update_option( 'theme_options_' . WPF()->tpl->theme, $theme );
		}
	}
}

function wpforo_clean_up() {
	WPF()->db->delete( WPF()->tables->topics, [ 'first_postid' => 0 ], [ '%d' ] );
}

// wpforo  database checker fixer tools
function wpforo_update_db() {
	$problems = wpforo_database_check();
	if( ! empty( $problems ) ) {
		$SQL = wpforo_database_fixer( $problems );
		if( wpfval( $SQL, 'fields' ) ) {
			foreach( $SQL['fields'] as $query ) WPF()->db->query( $query );
		}
		if( wpfval( $SQL, 'keys' ) ) {
			foreach( $SQL['keys'] as $query ) WPF()->db->query( $query );
		}
		if( wpfval( $SQL, 'tables' ) ) {
			foreach( $SQL['tables'] as $query ) {
				if( false === WPF()->db->query( $query ) ) {
					WPF()->db->query( preg_replace( '#\)[\r\n\t\s]*ENGINE.*$#isu', ')', $query ) );
				}
			}
		}
	}
	wpforo_update_option( 'version_db', WPFORO_VERSION );
}

/**
 * @param array $args
 *
 * @return bool|string|null
 */
function wpforo_db_check( $args = [] ) {
	$key = [ 'wpforo_db_check', $args ];
	if( WPF()->ram_cache->exists( $key ) ) return WPF()->ram_cache->get( $key );

	global $wpdb;

	$col   = esc_sql( trim( wpfval( $args, 'col' ) ) );
	$table = esc_sql( trim( wpfval( $args, 'table' ) ) );

	$result = null;
	switch( trim( wpfval( $args, 'check' ) ) ) {
		case 'table_exists':
			$result = (bool) $wpdb->get_var( "SHOW TABLES LIKE '$table'" );
		break;
		case 'col_exists':
			$result = (bool) $wpdb->get_var( "SHOW COLUMNS FROM `$table` LIKE '$col'" );
		break;
		case 'key_exists':
			$result = (bool) $wpdb->get_var( "SHOW KEYS FROM `$table` WHERE `Key_name` = '$col'" );
		break;
		case 'default_value':
			$c      = (array) $wpdb->get_row( "SHOW COLUMNS FROM `$table` LIKE '$col'", ARRAY_A );
			$result = wpfval( $c, 'Default' );
		break;
		case 'col_type':
			$c      = (array) $wpdb->get_row( "SHOW COLUMNS FROM `$table` LIKE '$col'", ARRAY_A );
			$result = wpfval( $c, 'Type' );
		break;
	}

	WPF()->ram_cache->set( $key, $result );

	return $result;
}

function wpforo_database_check() {
	$_tables      = [];
	$_table_diffs = [];
	require_once( WPFORO_DIR . '/includes/install-sql.php' );
	$wpforo_sql = wpforo_get_install_sqls();
	if( ! empty( $wpforo_sql ) ) {
		foreach( $wpforo_sql as $sql ) {
			if( preg_match( '|EXISTS \`([^\(]+)\`\s*\((.+)(PRIMARY.+)\)\s*ENGINE|is', $sql, $table ) ) {
				if( wpfval( $table, 1 ) ) {
					if( wpfval( $table, 2 ) ) {
						if( preg_match_all( '|\`([^\`]+)\`|is', $table[2], $fields, PREG_SET_ORDER ) ) {
							foreach( $fields as $field ) {
								if( wpfval( $field, 1 ) ) $_tables[ $table[1] ]['fields'][] = $field[1];
							}
						}
					}
					if( wpfval( $table, 3 ) ) {
						if( preg_match( '|PRIMARY KEY \(\`([^\`]+)\`\)|is', $table[3], $primary_key ) ) {
							$_tables[ $table[1] ]['keys'][] = $primary_key[1];
						}
						if( preg_match_all( '|KEY \`([^\`]+)\`|is', $table[3], $keys, PREG_SET_ORDER ) ) {
							foreach( $keys as $key ) {
								if( wpfval( $key, 1 ) ) $_tables[ $table[1] ]['keys'][] = $key[1];
							}
						}
					}
				}
			}
		}
		if( ! empty( $_tables ) ) {
			foreach( $_tables as $_name => $_structure ) {
				$_table_fields = [];
				$_table_keys   = [];
				$_table_exists = WPF()->db->get_var( "SHOW TABLES LIKE '" . esc_sql( $_name ) . "'" );
				if( $_table_exists ) {
					//Problems - Missing Field
					if( wpfval( $_structure, 'fields' ) ) {
						$_fields = WPF()->db->get_results( "SHOW FULL COLUMNS FROM " . esc_sql( $_name ), ARRAY_A );
						foreach( $_fields as $_field ) $_table_fields[] = $_field['Field'];
						$_count_orig = count( $_structure['fields'] );
						$_count_curr = count( $_table_fields );
						if( $_count_curr < $_count_orig ) {
							$diff = array_diff( $_structure['fields'], $_table_fields );
							if( ! empty( $diff ) ) $_table_diffs[ $_name ]['fields'][ $_name ] = $diff;
						}
					}
					//Problems - Missing Key
					if( wpfval( $_structure, 'keys' ) ) {
						$_keys = WPF()->db->get_results( "SHOW KEYS FROM " . esc_sql( $_name ), ARRAY_A );
						foreach( $_keys as $_key ) {
							if( strpos( $_key['Key_name'], 'PRIMARY' ) !== false ) {
								$_table_keys[] = $_key['Column_name'];
							} else {
								$_table_keys[] = $_key['Key_name'];
							}
						}
						$_table_keys = array_unique( $_table_keys );
						$_table_keys = array_values( $_table_keys );
						$_count_orig = count( $_structure['keys'] );
						$_count_curr = count( $_table_keys );
						if( $_count_curr < $_count_orig ) {
							$diff_keys = array_diff( $_structure['keys'], $_table_keys );
							if( ! empty( $diff_keys ) ) $_table_diffs[ $_name ]['keys'][ $_name ] = $diff_keys;
						}
					}
				} else {
					//Problems - Missing Table
					$_table_diffs[ $_name ]['exists'] = 'no';
				}
			}
		}
	}

	return $_table_diffs;
}

function wpforo_database_parse() {
	$_tables      = [];
	$_table_diffs = [];
	require_once( WPFORO_DIR . '/includes/install-sql.php' );
	$wpforo_sql = wpforo_get_install_sqls();
	if( ! empty( $wpforo_sql ) ) {
		foreach( $wpforo_sql as $sql ) {
			if( preg_match( '|EXISTS \`([^\(]+)\`\s*\((.+)(PRIMARY.+)\)\s*ENGINE|is', $sql, $table ) ) {
				if( wpfval( $table, 1 ) ) {
					if( wpfval( $table, 2 ) ) {
						$_tables[ $table[1] ]['fields'] = array_map( 'trim', explode( ',', $table[2] ) );
					}
					if( wpfval( $table, 3 ) ) {
						$_tables[ $table[1] ]['keys'] = array_map( 'trim', explode( PHP_EOL, $table[3] ) );
					}
				}
			}
		}
	}

	return $_tables;
}

function wpforo_database_fixer( $problems ) {
	$SQL = [];
	if( ! empty( $problems ) ) {
		require_once( WPFORO_DIR . '/includes/install-sql.php' );
		$table_structure = wpforo_database_parse();
		if( ! empty( $table_structure ) ) {
			foreach( $problems as $table_name => $problem ) {
				if( wpfval( $problem, 'fields' ) ) {
					foreach( $problem['fields'] as $problem_fields ) {
						if( ! empty( $problem_fields ) ) {
							foreach( $problem_fields as $problem_field ) {
								if( wpfval( $table_structure, $table_name, 'fields' ) ) {
									foreach( $table_structure[ $table_name ]['fields'] as $field_sql ) {
										if( strpos( $field_sql, '`' . $problem_field . '`' ) !== false ) {
											$SQL['fields'][] = 'ALTER TABLE `' . $table_name . '` ADD ' . $field_sql . ';';
										}
									}
								}
							}
						}
					}
				}
				if( wpfval( $problem, 'keys' ) ) {
					foreach( $problem['keys'] as $problem_keys ) {
						if( ! empty( $problem_keys ) ) {
							foreach( $problem_keys as $problem_key ) {
								if( wpfval( $table_structure, $table_name, 'keys' ) ) {
									foreach( $table_structure[ $table_name ]['keys'] as $key_sql ) {
										if( preg_match( '|KEY \`' . $problem_key . '\`|is', $key_sql ) ) {
											$SQL['keys'][] = 'ALTER TABLE `' . $table_name . '` ADD ' . trim( $key_sql, ',' ) . ';';
										}
									}
								}
							}
						}
					}
				}
				if( wpfval( $problem, 'exists' ) ) {
					$wpforo_sql = wpforo_get_install_sqls();
					if( wpfval( $wpforo_sql, $table_name ) ) {
						$SQL['tables'][] = preg_replace( '|\t+|', ' ', $wpforo_sql[ $table_name ] );
					}
				}
			}
		}
	}

	return $SQL;
}

function wpforo_add_unique_key( $table, $primary_key, $unique_key_name = '', $unique_fields = '' ) {

	$table               = esc_sql( trim( $table ) );
	$primary_key         = esc_sql( trim( $primary_key ) );
	$unique_fields       = esc_sql( trim( $unique_fields, ',' ) );
	$unique_fields_clean = preg_replace( '|\([^\(\)]+\)|', '', $unique_fields );
	$remove_rows         = '';
	$sql                 = "SELECT GROUP_CONCAT(`$primary_key`) duplicated_row_ids, 
                COUNT(*) duplication_count FROM 
                    `$table` GROUP BY $unique_fields_clean HAVING  duplication_count > 1";

	$rows = WPF()->db->get_results( $sql, ARRAY_A );
	if( ! empty( $rows ) ) {
		foreach( $rows as $row ) {
			$ids         = explode( ',', $row['duplicated_row_ids'] );
			$ids         = array_reverse( $ids );
			$ids         = array_slice( $ids, 1 );
			$remove_rows .= trim( implode( ',', $ids ), ',' ) . ',';
		}
		$remove_rows = esc_sql( trim( $remove_rows, ',' ) );
		if( $remove_rows ) {
			WPF()->db->query( "DELETE FROM `$table` WHERE `$primary_key` IN($remove_rows)" );
		}
	}
	$sql = "ALTER TABLE `$table` ADD UNIQUE KEY `$unique_key_name`( $unique_fields )";
	WPF()->db->query( $sql );
}

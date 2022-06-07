<?php if( ! defined( 'ABSPATH' ) || ! WPF()->usergroup->can( 'mt' ) ) die ?>

<div class="wrap"><h2 style="padding:0 0 30px 0; line-height: 20px;"><?php _e( 'Forum Tools', 'wpforo' ) ?></h2></div>
<?php WPF()->notice->show() ?>
<?php do_action( 'wpforo_tools_page_top' ) ?>
<div id="wpf-admin-wrap" class="wrap">
    <div id="icon-users" class="icon32"><br></div>
	<?php
	$tabs = [
		'debug' => __( 'Debug', 'wpforo' ),
		'misc'  => __( 'Misc', 'wpforo' ),
		'legal' => __( 'Privacy &amp; Rules', 'wpforo' ),
	];
	wpforo_admin_tools_tabs( $tabs, ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'debug' ) );
	?>
    <div class="wpf-info-bar" style="padding:1%">
		<?php
		$includefile = WPFORO_DIR . '/admin/tools-tabs/debug.php';
		if( ! empty( $_GET['tab'] ) ) {
			switch( $_GET['tab'] ) {
				case 'misc':
					$includefile = WPFORO_DIR . '/admin/tools-tabs/misc.php';
				break;
				case 'legal':
					$includefile = WPFORO_DIR . '/admin/tools-tabs/legal.php';
				break;
			}
		}
		include_once( $includefile );
		?>
    </div>
</div>

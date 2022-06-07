<?php if( ! defined( "ABSPATH" ) ) exit() ?>

<input type="hidden" name="wpfaction" value="akismet_settings_save">

<?php WPF()->settings->header( 'akismet' ); ?>

<div class="wpf-opt-row">
	<?php if( ! class_exists( 'Akismet' ) ): ?>
        <div style="width:94%; clear:both; margin:0 0 15px 0; text-align:center; line-height:22px; font-size:14px; color:#D35206; border:1px dotted #ccc; padding:10px 20px 10px 20px;; background:#F7F5F5;">
            <a href="https://wordpress.org/plugins/akismet/" target="_blank">Akismet</a> <?php _e( 'is not installed! For an advanced Spam Control please install Akismet antispam plugin, it works well with wpForo Spam Control system. Akismet is already integrated with wpForo. It\'ll help to filter posts and protect forum against spam attacks.', 'wpforo' ); ?>
        </div>
	<?php else: ?>
        <div style="color:#fff; background:#7C9B2E; font-size:20px; padding:10px 10px; text-align:center;">
            <strong>A&middot;kis&middot;met</strong> <?php _e( 'is enabled', 'wpforo' ); ?>
        </div>
	<?php endif; ?>
</div>

<?php WPF()->settings->form_field( 'akismet', 'akismet' ); ?>

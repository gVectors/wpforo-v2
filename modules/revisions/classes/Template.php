<?php

namespace wpforo\modules\revisions\classes;

class Template {
	public function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		if( wpforo_setting( 'posting', 'is_preview_on' )
		    || wpforo_setting( 'posting', 'is_draft_on' )
		) {
			add_action( 'wpforo_editor_topic_submit_after',         [ $this, 'show_html_into_form' ] );
			add_action( 'wpforo_editor_post_submit_after',          [ $this, 'show_html_into_form' ] );
			add_action( 'wpforo_portable_editor_post_submit_after', [ $this, 'show_html_into_form' ] );
		}
	}

	private function build_wrap_inner_html( $revisions_count = null ) {
		$buttons = '';
		if( wpforo_setting( 'posting', 'is_preview_on' ) ) {
			$buttons .= sprintf( '<span class="wpforo-revision-action-button wpforo_post_preview wpf-disabled"> <i class="fas fa-eye wpf-rev-preview-ico"></i> %1$s </span>', wpforo_phrase( 'Preview', false ) );
		}
		if( wpforo_setting( 'posting', 'is_draft_on' ) ) {
			$revisions_count = intval( $revisions_count );
			$buttons         .= sprintf( '<span class="wpforo-revision-action-button wpforo_revisions_history"><i class="fas fa-history wpf-rev-ico"></i> %1$s </span>', sprintf( wpforo_phrase( '%1$s Revisions', false ), '<span class="wpf-rev-history-count">' . $revisions_count . '</span>' ) ) . sprintf(
					'<span class="wpforo-revision-action-button wpforo_save_revision" style="display: none;"><i class="fas fa-save wpf-rev-save-ico"></i> %1$s </span>',
					wpforo_phrase( 'Save Draft', false )
				) . sprintf( '<span class="wpforo-revision-action-button wpforo_revision_saved wpf-disabled"><i class="fas fa-check wpf-rev-saved-ico"></i> %1$s </span>', wpforo_phrase( 'Saved', false ) );
		}

		return sprintf( '<div class="wpforo-revisions-action-buttons">%1$s</div><div class="wpforo-revisions-preview-wrap"></div>', $buttons );
	}


	private function show_wrap_inner_html( $revisions_count = null ) {
		echo $this->build_wrap_inner_html( $revisions_count );
	}

	public function show_html_into_form() {
		if( wpforo_setting( 'posting', 'is_draft_on' ) ) {
			$args            = [
				//			'textareaids_include' => (string) wpfval( $_POST, 'textareaid' ),
				'postids_include' => wpforo_bigintval( wpfval( $_POST, 'postid' ) ),
				'userids_include' => WPF()->current_userid,
				'emails_include'  => WPF()->current_user_email,
				'urls_include'    => WPF()->revision->get_current_url_query_vars_str(),
			];
			$revisions_count = wpforo_ram_get( [ WPF()->revision, 'get_count' ], $args );
		} else {
			$revisions_count = null;
		}

		?>
		<div class="wpf-clear"></div>
		<div class="wpforo-revisions-wrap"><?php $this->show_wrap_inner_html( $revisions_count ); ?></div>
		<?php
	}

	public function build_preview( $revision ) {
		return sprintf(
			'<div class="wpforo-revision" data-revisionid="%1$d" data-created="%2$d"> 
                <div class="wpforo-revision-top">
                    <div class="wpforo-revision-created"><i class="fas fa-eye wpf-rev-ico"></i> %3$s</div>
                </div>
                <div class="wpforo-revision-body">%4$s</div> 
            </div>',
			$revision['revisionid'],
			$revision['created'],
			wpforo_phrase( 'Preview', false ),
			wpforo_content( $revision, false )
		);
	}

	public function show_preview( $revision ) {
		echo $this->build_preview( $revision );
	}

	public function build_revision( $revision ) {
		return sprintf(
			'
            <div class="wpforo-revision" data-revisionid="%1$d" data-created="%2$s">
                <div class="wpforo-revision-top">
                    <div class="wpforo-revision-created"><i class="fas fa-clock wpf-rev-ico"></i> %3$s %4$s</div>
                    <div class="wpforo-revision-actions">
                        <span class="wpforo-revision-action-restore" style="cursor: pointer;"><i class="fas fa-history wpf-rev-ico"></i> %5$s</span>
                        &nbsp;|&nbsp;
                        <span class="wpforo-revision-action-delete" style="cursor: pointer;"><i class="fas fa-trash wpf-rev-ico"></i> %6$s</span>
                    </div>
                </div>
                <div class="wpforo-revision-body">%7$s</div>
            </div>',
			$revision['revisionid'],
			$revision['created'],
			wpforo_phrase( 'Revision', false ),
			wpforo_date( $revision['created'], 'ago', false ),
			wpforo_phrase( 'Restore', false ),
			wpforo_phrase( 'Delete', false ),
			wpforo_content( $revision, false )
		);
	}

	public function show_revision( $revision ) {
		echo $this->build_revision( $revision );
	}
}

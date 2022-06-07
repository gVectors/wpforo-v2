<?php

namespace wpforo\admin\listtables;

use WP_List_Table;

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	require_once( ABSPATH . 'wp-admin/includes/template.php' );
}

class Boards extends WP_List_Table {
	/** ************************************************************************
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 ***************************************************************************/
	function __construct() {
		//Set parent defaults
		parent::__construct( [
			                     'singular' => 'board',      //singular name of the listed records
			                     'plural'   => 'boards',     //plural name of the listed records
			                     'ajax'     => false,        //does this table support ajax?
			                     'screen'   => 'wpForoBoards',
		                     ] );

	}

	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	function column_pageid( $item ) {
		return ( ( $edit_post_link = get_edit_post_link( $item['pageid'] ) ) ? '<a href="' . $edit_post_link . '">' . $item['pageid'] . '</a>' : '<span style="color: red">' . $item['pageid'] . ' ' . __( 'not found', 'wpforo' ) . '</span>' );
	}

	function column_status( $item ) {
		return ( $item['status'] ? '<span style="color: green">' . __( 'YES', 'wpforo' ) . '</span>' : '<span style="color: red">' . __( 'NO', 'wpforo' ) . '</span>' );
	}

	function column_is_standalone( $item ) {
		return ( $item['is_standalone'] ? '<span style="color: green">' . __( 'YES', 'wpforo' ) . '</span>' : '<span style="color: red">' . __( 'NO', 'wpforo' ) . '</span>' );
	}

	/** ************************************************************************
	 * Recommended. This is a custom column method and is responsible for what
	 * is rendered in any column with a name/slug of 'title'. Every time the class
	 * needs to render a column, it first looks for a method named
	 * column_{$column_title} - if it exists, that method is run. If it doesn't
	 * exist, column_default() is called instead.
	 *
	 * This example also illustrates how to implement rollover actions. Actions
	 * should be an associative array formatted as 'slug'=>'link html' - and you
	 * will need to generate the URLs yourself. You could even ensure the links
	 *
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td> (movie title only)
	 **************************************************************************@see WP_List_Table::::single_row_columns()
	 */
	function column_boardid( $item ) {
		$ehref = admin_url(
			sprintf(
				'admin.php?page=%1$s&wpfaction=%2$s&boardid=%3$s',
				'wpforo-boards',
				'wpforo_board_save_form',
				$item['boardid']
			)
		);
		$vhref = wpforo_url( '', $item['slug'] );
		$shref = rtrim( wpforo_url( 'sitemap.xml', $item['slug'] ), '/' );
		$dhref = wp_nonce_url(
			admin_url(
				sprintf(
					'admin.php?page=%1$s&wpfaction=%2$s&boardid=%3$s',
					'wpforo-boards',
					'board_delete',
					$item['boardid']
				)
			),
			'wpforo-board-delete-' . $item['boardid']
		);
		//Build row actions
		$actions = [
			'edit'    => '<a href="' . $ehref . '">' . __( 'Edit', 'wpforo' ) . '</a>',
			'view'    => '<a href="' . $vhref . '" target="_blank">' . __( 'View', 'wpforo' ) . '</a>',
			'sitemap' => '<a href="' . $shref . '" target="_blank">' . __( 'Sitemap', 'wpforo' ) . '</a>',
			'delete'  => '<a onclick="return confirm(\'' . __( "Are you sure you want to DELETE this board and his data permanently?", 'wpforo' ) . '\');" href="' . $dhref . '">' . __( 'Delete', 'wpforo' ) . '</a>',
		];
		if( ! $item['boardid'] ) unset( $actions['delete'] );
		if( ! $item['status'] ) unset( $actions['view'] );

		//Return the title contents
		return sprintf(
			         '%1$s %2$s',
			/*$1%s*/ $item['boardid'],
			/*$2%s*/ $this->row_actions( $actions )
		);
	}


	/** ************************************************************************
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value
	 * is the column's title text. If you need a checkbox for bulk actions, refer
	 * to the $columns array below.
	 *
	 * The 'cb' column is treated differently than the rest. If including a checkbox
	 * column in your table you must create a column_cb() method. If you don't need
	 * to bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
	 *
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 **************************************************************************@see WP_List_Table::::single_row_columns()
	 */
	function get_columns() {
		return [
			'boardid'       => __( 'boardid', 'wpforo' ),
			'title'         => __( 'title', 'wpforo' ),
			'slug'          => __( 'slug', 'wpforo' ),
			'pageid'        => __( 'pageid', 'wpforo' ),
			'locale'        => __( 'locale', 'wpforo' ),
			'is_standalone' => __( 'is standalone', 'wpforo' ),
			'status'        => __( 'enabled', 'wpforo' ),
		];
	}

	/** ************************************************************************
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args(), although the following properties and methods
	 * are frequently interacted with here...
	 *
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 **************************************************************************/
	function prepare_items() {
		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();


		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		/**
		 * REQUIRED. Now we can add our *sorted* data to the items' property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = WPF()->board->get_boards();
	}
}

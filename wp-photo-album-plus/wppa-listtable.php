<?php
/* wppa-listtable.php
* Package: wp-photo-album-plus
*
* Copy of wp version
* Modified by OpaJaap
* Version 9.0.00.000
*
*/


class WPPA_List_Table {

	public $items;
	protected $_args;
	protected $_pagination_args = array();
	private $_actions;
	private $_pagination;
	protected $modes = array();
	protected $_column_headers;
	protected $compat_fields = array( '_args', '_pagination_args', 'screen', '_actions', '_pagination' );
	protected $compat_methods = array(
		'set_pagination_args',
		'get_views',
		'get_bulk_actions',
		'bulk_actions',
		'row_actions',
		'months_dropdown',
		'view_switcher',
		'comments_bubble',
		'get_items_per_page',
		'pagination',
		'get_sortable_columns',
		'get_column_info',
		'get_table_classes',
		'display_tablenav',
		'extra_tablenav',
		'single_row_columns',
	);

	public $screen;

	public function __construct( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'plural'   => '',
				'singular' => '',
				'ajax'     => false,
				'screen'   => null,
			)
		);

		$this->screen = convert_to_screen( $args['screen'] );

		add_filter( "manage_{$this->screen->id}_columns", array( $this, 'get_columns' ), 0 );

		if ( ! $args['plural'] ) {
			$args['plural'] = $this->screen->base;
		}

		$args['plural']   = sanitize_key( $args['plural'] );
		$args['singular'] = sanitize_key( $args['singular'] );

		$this->_args = $args;

		if ( $args['ajax'] ) {
			// wp_enqueue_script( 'list-table' );
			add_action( 'admin_footer', array( $this, '_js_vars' ) );
		}

		if ( empty( $this->modes ) ) {
			$this->modes = array(
				'list'    => ( 'Compact view' ),
				'excerpt' => ( 'Extended view' ),
			);
		}
	}

	// Make private properties readable for backward compatibility.
	public function __get( $name ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			return $this->$name;
		}
	}

	// Make private properties settable for backward compatibility.
	/*
	public function __set( $name, $value ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			$this->$name = $value;
			return $this->$name;
		}
	}
	*/

	// Make private properties checkable for backward compatibility.
	public function __isset( $name ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			return isset( $this->$name );
		}

		return false;
	}

	// Make private properties un-settable for backward compatibility.
	public function __unset( $name ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			unset( $this->$name );
		}
	}

	// Make private/protected methods readable for backward compatibility.
	public function __call( $name, $arguments ) {
		if ( in_array( $name, $this->compat_methods, true ) ) {
			return $this->$name( ...$arguments );
		}
		return false;
	}

	// Checks the current user's permissions
	public function ajax_user_can() {
		die( 'function WP_List_Table::ajax_user_can() must be overridden in a subclass.' );
	}

	// Prepares the list of items for displaying.
	public function prepare_items() {
		die( 'function WP_List_Table::prepare_items() must be overridden in a subclass.' );
	}

	// An internal method that sets all the necessary pagination arguments
	protected function set_pagination_args( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'total_items' => 0,
				'total_pages' => 0,
				'per_page'    => 0,
			)
		);

		if ( ! $args['total_pages'] && $args['per_page'] > 0 ) {
			$args['total_pages'] = ceil( $args['total_items'] / $args['per_page'] );
		}

		// Redirect if page number is invalid and headers are not already sent.
		if ( ! headers_sent() && ! wp_doing_ajax() && $args['total_pages'] > 0 && $this->get_pagenum() > $args['total_pages'] ) {
			wp_redirect( add_query_arg( 'paged', $args['total_pages'] ) );
			exit;
		}

		$this->_pagination_args = $args;
	}

	// Access the pagination args.
	public function get_pagination_arg( $key ) {
		if ( 'page' === $key ) {
			return $this->get_pagenum();
		}

		if ( isset( $this->_pagination_args[ $key ] ) ) {
			return $this->_pagination_args[ $key ];
		}

		return 0;
	}

	// Whether the table has items to display or not
	public function has_items() {
		return ! empty( $this->items );
	}

	// Message to be displayed when there are no items
	public function no_items() {
		esc_html_e( 'No items found.', 'wp-photo-album-plus'  );
	}

	// Displays the search box.
	public function search_box( $text, $input_id ) {

		/*
		$dummy = wp_verify_nonce( 'dummy-code', 'dummy-action' ); // Just to satisfy Plugin Check
		if ( ! wppa_get( 's', '', 'text' ) && ! $this->has_items() ) {
			return;
		}

		$input_id = $input_id . '-search-input';

		if ( wppa_get( 'order-by', '', 'text' ) ) {
			echo '<input type="hidden" name="order-by" value="' . esc_attr( wppa_get( 'order-by', '', 'text' ) ) . '" />';
		}
		if ( ! empty( $_REQUEST['dir'] ) ) {
			echo '<input type="hidden" name="dir" value="' . esc_attr( $_REQUEST['dir'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
			echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['detached'] ) ) {
			echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
		}
		?>
<p class="search-box">
	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
	<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
		<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
</p>
		<?php
		*/
	}

	// Generates views links.
	protected function get_views_links( $link_data = array() ) {
		if ( ! is_array( $link_data ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: The $link_data argument. */
					( 'The %s argument must be an array.' ),
					'<code>$link_data</code>'
				),
				'6.1.0'
			);

			return array( '' );
		}

		$views_links = array();

		foreach ( $link_data as $view => $link ) {
			if ( empty( $link['url'] ) || ! is_string( $link['url'] ) || '' === trim( $link['url'] ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %1$s: The argument name. %2$s: The view name. */
						( 'The %1$s argument must be a non-empty string for %2$s.' ),
						'<code>url</code>',
						'<code>' . esc_html( $view ) . '</code>'
					),
					'6.1.0'
				);

				continue;
			}

			if ( empty( $link['label'] ) || ! is_string( $link['label'] ) || '' === trim( $link['label'] ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %1$s: The argument name. %2$s: The view name. */
						( 'The %1$s argument must be a non-empty string for %2$s.' ),
						'<code>label</code>',
						'<code>' . esc_html( $view ) . '</code>'
					),
					'6.1.0'
				);

				continue;
			}

			$views_links[ $view ] = sprintf(
				'<a href="%s"%s>%s</a>',
				esc_url( $link['url'] ),
				isset( $link['current'] ) && true === $link['current'] ? ' class="current" aria-current="page"' : '',
				$link['label']
			);
		}

		return $views_links;
	}

	// Gets the list of views available on this table.
	protected function get_views() {
		return array();
	}

	// Displays the list of views available on this table.
	public function views() {
	}
	/*
		$views = $this->get_views();
		/**
		 * Filters the list of available list table views.
		 *
		 * The dynamic portion of the hook name, `$this->screen->id`, refers
		 * to the ID of the current screen.
		 *
		 * @since 3.1.0
		 *
		 * @param string[] $views An array of available list table views.
		 */
	/*
		$views = apply_filters( "views_{$this->screen->id}", $views );

		if ( empty( $views ) ) {
			return;
		}

		$this->screen->render_screen_reader_content( 'heading_views' );

		echo "<ul class='subsubsub'>\n";
		foreach ( $views as $class => $view ) {
			$views[ $class ] = "\t<li class='$class'>$view";
		}
		echo implode( " |</li>\n", $views ) . "</li>\n";
		echo '</ul>';
	}
	*/

	// Retrieves the list of bulk actions available for this table.
	protected function get_bulk_actions() {
		return array();
	}

	// Displays the bulk actions dropdown.
	protected function bulk_actions( $which = '' ) {

		if ( is_null( $this->_actions ) ) {
			$this->_actions = $this->get_bulk_actions();

			/**
			 * Filters the items in the bulk actions menu of the list table.
			 *
			 * The dynamic portion of the hook name, `$this->screen->id`, refers
			 * to the ID of the current screen.
			 *
			 * @since 3.1.0
			 * @since 5.6.0 A bulk action can now contain an array of options in order to create an optgroup.
			 *
			 * @param array $actions An array of the available bulk actions.
			 */

			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

			$two = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return;
		}

		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . ( 'Select bulk action' ) . '</label>';
		echo '<select name="action' . esc_attr( $two ) . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
		echo '<option value="-1">' . ( 'Bulk actions' ) . "</option>\n";

		foreach ( $this->_actions as $key => $value ) {
			if ( is_array( $value ) ) {
				echo "\t" . '<optgroup label="' . esc_attr( $key ) . '">' . "\n";

				foreach ( $value as $name => $title ) {
					$class = ( 'edit' === $name ) ? ' class="hide-if-no-js"' : '';
					if ( 'edit' === $name ) {
						echo "\t\t" . '<option value="' . esc_attr( $name ) . '" class="hide-if-no-js">' . esc_html( $title ) . "</option>\n";
					}
					else {
						echo "\t\t" . '<option value="' . esc_attr( $name ) . '">' . esc_html( $title ) . "</option>\n";
					}
				}
				echo "\t" . "</optgroup>\n";
			} else {
				if ( 'edit' === $key ) {
					echo "\t" . '<option value="' . esc_attr( $key ) . '" class="hide-if-no-js">' . esc_html( $value ) . "</option>\n";
				}
				else {
					echo "\t" . '<option value="' . esc_attr( $key ) . '">' . esc_attr( $value ) . "</option>\n";
				}
			}
		}

		echo "</select>\n";

		submit_button( ( 'Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
		echo "\n";
	}

	// Gets the current action selected from the bulk actions dropdown.
	public function current_action() {
		$dummy = wp_verify_nonce( 'dummy-code', 'dummy-action' ); // Just to satisfy Plugin Check
		if ( wppa_get( 'filter_action', '', 'text' ) ) {
			return false;
		}

		$a = wppa_get( 'action', '', 'text' );
		if ( $a && $a != -1 ) {
			return $a;
		}

		return false;
	}

	// Generates the required HTML for a list of row action links.
	protected function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );

		if ( ! $action_count ) {
			return '';
		}

		$mode = get_user_setting( 'posts_list_mode', 'list' );

		if ( 'excerpt' === $mode ) {
			$always_visible = true;
		}

		$output = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';

		$i = 0;

		foreach ( $actions as $action => $link ) {
			++$i;

			$separator = ( $i < $action_count ) ? ' | ' : '';

			$link = str_replace( 'nixyz', '', $link );
			$output .= "<span class='$action'>{$link}{$separator}</span>";
		}

		$output .= '</div>';

		$output .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . ( 'Show more details' ) . '</span></button>';

		return $output;
	}

	// Displays a dropdown for filtering items in the list table by month.
	protected function months_dropdown( $post_type ) {
		return;
	}

	/*
		global $wpdb, $wp_locale;

		/**
		 * Filters whether to remove the 'Months' drop-down from the post list table.
		 *
		 * @since 4.2.0
		 *
		 * @param bool   $disable   Whether to disable the drop-down. Default false.
		 * @param string $post_type The post type.
		 */
	/*
		if ( apply_filters( 'disable_months_dropdown', false, $post_type ) ) {
			return;
		}

		/**
		 * Filters whether to short-circuit performing the months dropdown query.
		 *
		 * @since 5.7.0
		 *
		 * @param object[]|false $months   'Months' drop-down results. Default false.
		 * @param string         $post_type The post type.
		 */

		/*
		$months = apply_filters( 'pre_months_dropdown_query', false, $post_type );

		if ( ! is_array( $months ) ) {
			$extra_checks = "AND post_status != 'auto-draft'";
			if ( ! isset( $_GET['post_status'] ) || 'trash' !== $_GET['post_status'] ) {
				$extra_checks .= " AND post_status != 'trash'";
			} elseif ( isset( $_GET['post_status'] ) ) {
				$extra_checks = $wpdb->prepare( ' AND post_status = %s', $_GET['post_status'] );
			}

			$months = wppa_get_results(
				$wpdb->prepare(
					"
				SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
				FROM $wpdb->posts
				WHERE post_type = %s
				$extra_checks
				ORDER BY post_date DESC
			",
					$post_type
				)
			);
		}
		*/

		/**
		 * Filters the 'Months' drop-down results.
		 *
		 * @since 3.7.0
		 *
		 * @param object[] $months    Array of the months drop-down query results.
		 * @param string   $post_type The post type.
		 */

		/*
		$months = apply_filters( 'months_dropdown_results', $months, $post_type );

		$month_count = count( $months );

		if ( ! $month_count || ( 1 == $month_count && 0 == $months[0]->month ) ) {
			return;
		}

		$m = isset( $_GET['m'] ) ? (int) $_GET['m'] : 0;
		?>
		<label for="filter-by-date" class="screen-reader-text"><?php wppa_echo( get_post_type_object( $post_type )->labels->filter_by_date ); ?></label>
		<select name="m" id="filter-by-date">
			<option<?php selected( $m, 0 ); ?> value="0"><?php esc_html_e( 'All dates' ); ?></option>
		<?php
		foreach ( $months as $arc_row ) {
			if ( 0 == $arc_row->year ) {
				continue;
			}

			$month = zeroise( $arc_row->month, 2 );
			$year  = $arc_row->year;

			printf(
				"<option %s value='%s'>%s</option>\n",
				selected( $m, $year . $month, false ),
				esc_attr( $arc_row->year . $month ),
				/* translators: 1: Month name, 2: 4-digit year. */
/*
				sprintf( ( '%1$s %2$d' ), esc_html( $wp_locale->get_month( $month ) ), esc_html( $year ) )
			);
		}
		?>
		</select>
		<?php
	}
	*/

	// Displays a view switcher.
	protected function view_switcher( $current_mode ) {
	}
	/*
		?>
		<input type="hidden" name="mode" value="<?php echo esc_attr( $current_mode ); ?>" />
		<div class="view-switch">
		<?php
		foreach ( $this->modes as $mode => $title ) {
			$classes      = array( 'view-' . $mode );
			$aria_current = '';

			if ( $current_mode === $mode ) {
				$classes[]    = 'current';
				$aria_current = ' aria-current="page"';
			}

			printf(
				"<a href='%s' class='%s' id='view-switch-$mode'$aria_current><span class='screen-reader-text'>%s</span></a>\n",
				esc_url( remove_query_arg( 'attachment-filter', add_query_arg( 'mode', $mode ) ) ),
				implode( ' ', $classes ),
				$title
			);
		}
		?>
		</div>
		<?php
	}
	*/

	// Displays a comment count bubble.
	protected function comments_bubble( $post_id, $pending_comments ) {
	}
	/*
		$approved_comments = get_comments_number();

		$approved_comments_number = number_format_i18n( $approved_comments );
		$pending_comments_number  = number_format_i18n( $pending_comments );

		$approved_only_phrase = sprintf(
			/* translators: %s: Number of comments. */
	/*
			_n( '%s comment', '%s comments', $approved_comments ),
			$approved_comments_number
		);

		$approved_phrase = sprintf(
			/* translators: %s: Number of comments. */
	/*
			_n( '%s approved comment', '%s approved comments', $approved_comments ),
			$approved_comments_number
		);

		$pending_phrase = sprintf(
			/* translators: %s: Number of comments. */
	/*
			_n( '%s pending comment', '%s pending comments', $pending_comments ),
			$pending_comments_number
		);

		if ( ! $approved_comments && ! $pending_comments ) {
			// No comments at all.
			printf(
				'<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">%s</span>',
				( 'No comments' )
			);
		} elseif ( $approved_comments && 'trash' === get_post_status( $post_id ) ) {
			// Don't link the comment bubble for a trashed post.
			printf(
				'<span class="post-com-count post-com-count-approved"><span class="comment-count-approved" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></span>',
				$approved_comments_number,
				$pending_comments ? $approved_phrase : $approved_only_phrase
			);
		} elseif ( $approved_comments ) {
			// Link the comment bubble to approved comments.
			printf(
				'<a href="%s" class="post-com-count post-com-count-approved"><span class="comment-count-approved" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
				esc_url(
					add_query_arg(
						array(
							'p'              => $post_id,
							'comment_status' => 'approved',
						),
						admin_url( 'edit-comments.php' )
					)
				),
				$approved_comments_number,
				$pending_comments ? $approved_phrase : $approved_only_phrase
			);
		} else {
			// Don't link the comment bubble when there are no approved comments.
			printf(
				'<span class="post-com-count post-com-count-no-comments"><span class="comment-count comment-count-no-comments" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></span>',
				$approved_comments_number,
				$pending_comments ? ( 'No approved comments' ) : ( 'No comments' )
			);
		}

		if ( $pending_comments ) {
			printf(
				'<a href="%s" class="post-com-count post-com-count-pending"><span class="comment-count-pending" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
				esc_url(
					add_query_arg(
						array(
							'p'              => $post_id,
							'comment_status' => 'moderated',
						),
						admin_url( 'edit-comments.php' )
					)
				),
				$pending_comments_number,
				$pending_phrase
			);
		} else {
			printf(
				'<span class="post-com-count post-com-count-pending post-com-count-no-pending"><span class="comment-count comment-count-no-pending" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></span>',
				$pending_comments_number,
				$approved_comments ? ( 'No pending comments' ) : ( 'No comments' )
			);
		}
	}
	*/

	// Gets the current page number.
	public function get_pagenum() {
		$dummy = wp_verify_nonce( 'dummy-code', 'dummy-action' ); // Just to satisfy Plugin Check
		$pagenum = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0;

		if ( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] ) {
			$pagenum = $this->_pagination_args['total_pages'];
		}

		return max( 1, $pagenum );
	}

	// Gets the number of items to display on a single page.
	protected function get_items_per_page( $option, $default_value = 20 ) {
		$per_page = (int) get_user_option( $option );
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = $default_value;
		}

		/**
		 * Filters the number of items to be displayed on each page of the list table.
		 *
		 * The dynamic hook name, `$option`, refers to the `per_page` option depending
		 * on the type of list table in use. Possible filter names include:
		 *
		 *  - `edit_comments_per_page`
		 *  - `sites_network_per_page`
		 *  - `site_themes_network_per_page`
		 *  - `themes_network_per_page'`
		 *  - `users_network_per_page`
		 *  - `edit_post_per_page`
		 *  - `edit_page_per_page'`
		 *  - `edit_{$post_type}_per_page`
		 *  - `edit_post_tag_per_page`
		 *  - `edit_category_per_page`
		 *  - `edit_{$taxonomy}_per_page`
		 *  - `site_users_network_per_page`
		 *  - `users_per_page`
		 *
		 * @since 2.9.0
		 *
		 * @param int $per_page Number of items to be displayed. Default 20.
		 */
		return (int) apply_filters( "{$option}", $per_page );
	}

	/**
	 * Displays the pagination.
	 *
	 * @since 3.1.0
	 *
	 * @param string $which
	 */
	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args ) ) {
			return;
		}

		$total_items     = $this->_pagination_args['total_items'];

		// By opaJaap, if less than one full page of the smallest kind, ignore baging
		if ( $total_items < '21' ) {
			return;
		} // End mod

		$total_pages     = $this->_pagination_args['total_pages'];
		$infinite_scroll = false;
		if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
			$infinite_scroll = $this->_pagination_args['infinite_scroll'];
		}

		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		$output = '<span class="displaying-num">' . sprintf(
			/* translators: %s: Number of items. */
			_n( '%s item', '%s items', $total_items, 'wp-photo-album-plus' ),
			number_format_i18n( $total_items )
		) . '</span>';

		$current              = $this->get_pagenum();
		$removable_query_args = wp_removable_query_args();

		$current_url = set_url_scheme( 'http://' . wppa_http_host() . wppa_request_uri() );

		$current_url = remove_query_arg( $removable_query_args, $current_url );

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		$disable_first = false;
		$disable_last  = false;
		$disable_prev  = false;
		$disable_next  = false;

		if ( 1 == $current ) {
			$disable_first = true;
			$disable_prev  = true;
		}
		if ( $total_pages == $current ) {
			$disable_last = true;
			$disable_next = true;
		}

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				( 'First page' ),
				'&laquo;'
			);
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
				( 'Previous page' ),
				'&lsaquo;'
			);
		}

		if ( 'bottom' === $which ) {
			$html_current_page  = $current;
			$total_pages_before = '<span class="screen-reader-text">' . ( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		} else {
			$html_current_page = sprintf(
				"%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
				'<label for="current-page-selector" class="screen-reader-text">' . ( 'Current Page' ) . '</label>',
				$current,
				strlen( $total_pages )
			);
		}
		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[]     = $total_pages_before . sprintf(

			/* translators: 1: Current page, 2: Total pages. */
			__( '%1$s of %2$s', 'wp-photo-album-plus' ),
			$html_current_page,
			$html_total_pages
		) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
				( 'Next page' ),
				'&rsaquo;'
			);
		}

		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				( 'Last page' ),
				'&raquo;'
			);
		}

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) ) {
			$pagination_links_class .= ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . implode( "\n", $page_links ) . '</span>';

		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}

		/**/
		// Added by OpaJaap: page size selector
		$ps = $this->_pagination_args['per_page']; // current pagesize
		$current_url = set_url_scheme( 'http://' . wppa_http_host() . wppa_request_uri() );
		$current_url = remove_query_arg( 'paged', $current_url );
		$output .= '
		<span>' . __( 'Page size', 'wp-photo-album-plus' ) . '
			<select
				style="margin-bottom:3px"
				onchange="jQuery( \'#wppa-admin-spinner\' ).show();document.location.href=\''.$current_url.'&paged=1&wppa-pagesize=\'+this.value;"
				>
				<option value="10" ' . ( $ps == 10 ? 'selected' : '' ) . '>10</option>
				<option value="20" ' . ( $ps == '20' ? 'selected' : '' ) . '>20</option>
				<option value="50" ' . ( $ps == '50' ? 'selected' : '' ) . '>50</option>
				<option value="100" ' . ( $ps == 100 ? 'selected' : '' ) . '>100</option>
				<option value="200" ' . ( $ps == '200' ? 'selected' : '' ) . '>200</option>
				<option value="500" ' . ( $ps == '500' ? 'selected' : '' ) . '>500</option>
				<option value="1000" ' . ( $ps == '1000' ? 'selected' : '' ) . '>1000</option>
			</select>
		</span>';
		/**/

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		wppa_echo( $this->_pagination );
	}

	// Gets a list of columns.
	public function get_columns() {
		die( 'function WP_List_Table::get_columns() must be overridden in a subclass.' );
	}

	// Gets a list of sortable columns.
	protected function get_sortable_columns() {
		return array();
	}

	// Gets the name of the default primary column.
	protected function get_default_primary_column_name() {
		$columns = $this->get_columns();
		$column  = '';

		if ( empty( $columns ) ) {
			return $column;
		}

		// We need a primary defined so responsive views show something,
		// so let's fall back to the first non-checkbox column.
		foreach ( $columns as $col => $column_name ) {
			if ( 'cb' === $col ) {
				continue;
			}

			$column = $col;
			break;
		}

		return $column;
	}

	/**
	 * Public wrapper for WP_List_Table::get_default_primary_column_name().
	 *
	 * @since 4.4.0
	 *
	 * @return string Name of the default primary column.
	 */
	public function get_primary_column() {
		return $this->get_primary_column_name();
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 4.3.0
	 *
	 * @return string The name of the primary column.
	 */
	protected function get_primary_column_name() {
		$columns = get_column_headers( $this->screen );
		$default = $this->get_default_primary_column_name();

		// If the primary column doesn't exist,
		// fall back to the first non-checkbox column.
		if ( ! isset( $columns[ $default ] ) ) {
			$default = self::get_default_primary_column_name();
		}

		/**
		 * Filters the name of the primary column for the current list table.
		 *
		 * @since 4.3.0
		 *
		 * @param string $default Column name default for the specific list table, e.g. 'name'.
		 * @param string $context Screen ID for specific list table, e.g. 'plugins'.
		 */
		$column = apply_filters( 'list_table_primary_column', $default, $this->screen->id );

		if ( empty( $column ) || ! isset( $columns[ $column ] ) ) {
			$column = $default;
		}

		return $column;
	}

	/**
	 * Gets a list of all, hidden, and sortable columns, with filter applied.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	protected function get_column_info() {
		// $_column_headers is already set / cached.
		if (
			isset( $this->_column_headers ) &&
			is_array( $this->_column_headers )
		) {
			/*
			 * Backward compatibility for `$_column_headers` format prior to WordPress 4.3.
			 *
			 * In WordPress 4.3 the primary column name was added as a fourth item in the
			 * column headers property. This ensures the primary column name is included
			 * in plugins setting the property directly in the three item format.
			 */
			if ( 4 === count( $this->_column_headers ) ) {
				return $this->_column_headers;
			}

			$column_headers = array( array(), array(), array(), $this->get_primary_column_name() );
			foreach ( $this->_column_headers as $key => $value ) {
				$column_headers[ $key ] = $value;
			}

			$this->_column_headers = $column_headers;

			return $this->_column_headers;
		}

		$columns = get_column_headers( $this->screen );
		$hidden  = get_hidden_columns( $this->screen );

		$sortable_columns = $this->get_sortable_columns();
		/**
		 * Filters the list table sortable columns for a specific screen.
		 *
		 * The dynamic portion of the hook name, `$this->screen->id`, refers
		 * to the ID of the current screen.
		 *
		 * @since 3.1.0
		 *
		 * @param array $sortable_columns An array of sortable columns.
		 */
		$_sortable = apply_filters( "manage_{$this->screen->id}_sortable_columns", $sortable_columns );

		$sortable = array();
		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) ) {
				continue;
			}

			$data = (array) $data;
			if ( ! isset( $data[1] ) ) {
				$data[1] = false;
			}

			$sortable[ $id ] = $data;
		}

		$primary               = $this->get_primary_column_name();
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );

		return $this->_column_headers;
	}

	/**
	 * Returns the number of visible columns.
	 *
	 * @since 3.1.0
	 *
	 * @return int
	 */
	public function get_column_count() {
		list ( $columns, $hidden ) = $this->get_column_info();
		$hidden                    = array_intersect( array_keys( $columns ), array_filter( $hidden ) );
		return count( $columns ) - count( $hidden );
	}

	/**
	 * Prints column headers, accounting for hidden and sortable columns.
	 *
	 * @since 3.1.0
	 *
	 * @param bool $with_id Whether to set the ID attribute or not
	 */
	public function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();
		$dummy = wp_verify_nonce( 'dummy-code', 'dummy-action' ); // Just to satisfy Plugin Check

		$current_url = set_url_scheme( 'http://' . wppa_http_host() . wppa_request_uri() );
		$current_url = remove_query_arg( 'paged', $current_url );

		$current_orderby = wppa_get( 'order-by', '', 'text' );

		if ( wppa_get( 'order-by', '', 'text' ) == 'desc' ) {
			$current_order = 'desc';
		} else {
			$current_order = 'asc';
		}

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb']     = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . ( 'Select All' ) . '</label>'
				. '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			if ( in_array( $column_key, $hidden, true ) ) {
				$class[] = 'hidden';
			}

			if ( 'cb' === $column_key ) {
				$class[] = 'check-column';
			} elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ), true ) ) {
				$class[] = 'num';
			}

			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}

			if ( isset( $sortable[ $column_key ] ) ) {
				list( $orderby, $desc_first ) = $sortable[ $column_key ];

				if ( $current_orderby === $orderby ) {
					$order = 'asc' === $current_order ? 'desc' : 'asc';

					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = strtolower( $desc_first );

					if ( ! in_array( $order, array( 'desc', 'asc' ), true ) ) {
						$order = $desc_first ? 'desc' : 'asc';
					}

					$class[] = 'sortable';
					$class[] = 'desc' === $order ? 'asc' : 'desc';
				}

				$column_display_name = sprintf(
					'<a href="%s"><span>%s</span><span class="sorting-indicator"></span></a>',
					esc_url( add_query_arg( ['order-by' => $orderby, 'dir' => $order], $current_url ) ),
					$column_display_name
				);
			}

			$tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
			$scope = ( 'th' === $tag ) ? 'scope="col"' : '';
			$id    = $with_id ? "id='$column_key'" : '';

			if ( ! empty( $class ) ) {
				$class = "class='" . implode( ' ', $class ) . "'";
			}

			wppa_echo( "<$tag $scope $id $class>$column_display_name</$tag>" );
		}
	}

	/**
	 * Displays the table.
	 *
	 * @since 3.1.0
	 */
	public function display() {
		$singular = $this->_args['singular'];

		$this->display_tablenav( 'top' );

		$this->screen->render_screen_reader_content( 'heading_list' );
		?>
<table class="wp-list-table <?php wppa_echo( implode( ' ', $this->get_table_classes() ) ); ?>">
	<thead>
	<tr>
		<?php $this->print_column_headers(); ?>
	</tr>
	</thead>

	<tbody id="the-list"
		<?php
		if ( $singular ) {
			wppa_echo( " data-wp-lists='list:$singular'" );
		}
		?>
		>
		<?php $this->display_rows_or_placeholder(); ?>
	</tbody>

	<tfoot>
	<tr>
		<?php $this->print_column_headers( false ); ?>
	</tr>
	</tfoot>

</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Gets a list of CSS classes for the WP_List_Table table tag.
	 *
	 * @since 3.1.0
	 *
	 * @return string[] Array of CSS classes for the table tag.
	 */
	protected function get_table_classes() {
		$mode = get_user_setting( 'posts_list_mode', 'list' );

		$mode_class = esc_attr( 'table-view-' . $mode );

		return array( 'widefat', 'fixed', 'striped', $mode_class, $this->_args['plural'] );
	}

	/**
	 * Generates the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}
		?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<?php if ( $this->has_items() ) : ?>
		<div class="alignleft actions bulkactions">
			<?php $this->bulk_actions( $which ); ?>
		</div>
			<?php
		endif;
		$this->extra_tablenav( $which );
		$this->pagination( $which );
		?>

		<br class="clear" />
	</div>
		<?php
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @since 3.1.0
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {}

	/**
	 * Generates the tbody element for the list table.
	 *
	 * @since 3.1.0
	 */
	public function display_rows_or_placeholder() {
		if ( $this->has_items() ) {
			$this->display_rows();
		} else {
			wppa_echo( '<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">' );
			$this->no_items();
			echo '</td></tr>';
		}
	}

	/**
	 * Generates the table rows.
	 *
	 * @since 3.1.0
	 */
	public function display_rows() {
		foreach ( $this->items as $item ) {
			$this->single_row( $item );
		}
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @since 3.1.0
	 *
	 * @param object|array $item The current item
	 */
	public function single_row( $item ) {
		echo '<tr>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * @param object|array $item
	 * @param string $column_name
	 */
	protected function column_default( $item, $column_name ) {}

	/**
	 * @param object|array $item
	 */
	protected function column_cb( $item ) {}

	/**
	 * Generates the columns for a single row of the table.
	 *
	 * @since 3.1.0
	 *
	 * @param object|array $item The current item.
	 */
	protected function single_row_columns( $item ) {
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$classes = "$column_name column-$column_name";
			if ( $primary === $column_name ) {
				$classes .= ' has-row-actions column-primary';
			}

			if ( in_array( $column_name, $hidden, true ) ) {
				$classes .= ' hidden';
			}

			// Comments column uses HTML in the display name with screen reader text.
			// Strip tags to get closer to a user-friendly string.
			$data = 'data-colname="' . esc_attr( wp_strip_all_tags( $column_display_name ) ) . '"';

			$attributes = "class='$classes' $data";

			if ( 'cb' === $column_name ) {
				wppa_echo( '<th scope="row" class="check-column">' );
				wppa_echo( $this->column_cb( $item ) );
				wppa_echo( '</th>' );
			} elseif ( method_exists( $this, '_column_' . $column_name ) ) {
				wppa_echo( call_user_func(
					array( $this, '_column_' . $column_name ),
					$item,
					$classes,
					$data,
					$primary
				) );
			} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
				wppa_echo( "<td $attributes>" );
				wppa_echo( call_user_func( array( $this, 'column_' . $column_name ), $item ) );
				wppa_echo( $this->handle_row_actions( $item, $column_name, $primary ) );
				wppa_echo( '</td>' );
			} else {
				wppa_echo( "<td $attributes>" );
				wppa_echo( $this->column_default( $item, $column_name ) );
				wppa_echo( $this->handle_row_actions( $item, $column_name, $primary ) );
				wppa_echo( '</td>' );
			}
		}
	}

	/**
	 * Generates and display row actions links for the list table.
	 *
	 * @since 4.3.0
	 *
	 * @param object|array $item        The item being acted upon.
	 * @param string       $column_name Current column name.
	 * @param string       $primary     Primary column name.
	 * @return string The row actions HTML, or an empty string
	 *                if the current column is not the primary column.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		return $column_name === $primary ? '<button type="button" class="toggle-row"><span class="screen-reader-text">' . ( 'Show more details' ) . '</span></button>' : '';
	}

	/**
	 * Handles an incoming ajax request (called from admin-ajax.php)
	 *
	 * @since 3.1.0
	 */
	public function ajax_response() {
		$this->prepare_items();

		ob_start();
		$dummy = wp_verify_nonce( 'dummy-code', 'dummy-action' ); // Just to satisfy Plugin Check
		if ( ! empty( $_REQUEST['no_placeholder'] ) ) {
			$this->display_rows();
		} else {
			$this->display_rows_or_placeholder();
		}

		$rows = ob_get_clean();

		$response = array( 'rows' => $rows );

		if ( isset( $this->_pagination_args['total_items'] ) ) {
			$response['total_items_i18n'] = sprintf(
				/* translators: Number of items. */
				_n( '%s item', '%s items', $this->_pagination_args['total_items'], 'wp-photo-album-plus' ),
				number_format_i18n( $this->_pagination_args['total_items'] )
			);
		}
		if ( isset( $this->_pagination_args['total_pages'] ) ) {
			$response['total_pages']      = $this->_pagination_args['total_pages'];
			$response['total_pages_i18n'] = number_format_i18n( $this->_pagination_args['total_pages'] );
		}

		die( wp_json_encode( $response ) );
	}

	/**
	 * Sends required variables to JavaScript land.
	 *
	 * @since 3.1.0
	 */
	public function _js_vars() {
		$args = array(
			'class'  => get_class( $this ),
			'screen' => array(
				'id'   => $this->screen->id,
				'base' => $this->screen->base,
			),
		);

		printf( "<script type='text/javascript'>list_args = %s;</script>\n", wp_json_encode( $args ) );
	}
}

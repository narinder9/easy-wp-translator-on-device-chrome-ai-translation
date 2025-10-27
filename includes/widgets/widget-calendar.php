<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Widgets;


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WP_Widget_Calendar;


if ( ! class_exists( 'WP_Widget_Calendar' ) ) {
	require_once ABSPATH . '/wp-includes/default-widgets.php';
}

/**
 * This classes rewrite the whole Calendar widget functionality as there is no filter on sql queries and only a filter on final output.
 * Code last checked: WP 5.5.
 *
 * A request to add filters on sql queries exists.
 * Method used in 0.4.x: use of the get_calendar filter and overwrite the output of get_calendar function -> not very efficient (add 4 to 5 sql queries).
 * Method used since 0.5: remove the WP widget and replace it by our own -> our language filter will not work if get_calendar is called directly by a theme.
 *
 *  
 */
class EWT_Widget_Calendar extends WP_Widget_Calendar {
	protected static $ewt_instance = 0; // Can't use $instance of WP_Widget_Calendar as it's private :/.

	/**
	 * Outputs the content for the current Calendar widget instance.
	 * Modified version of the parent function to call our own get_calendar() method.
	 *
	 *  
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is trusted as per widget API usage.
		echo $args['before_widget'];
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is trusted as per widget API usage.
		if ( $title ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is trusted as per widget API usage.
			echo $args['before_title'] . $title . $args['after_title'];
		}
		if ( 0 === self::$ewt_instance ) { #modified#
			echo '<div id="calendar_wrap" class="calendar_wrap">';
		} else {
			echo '<div class="calendar_wrap">';
		}
		empty( EWT()->curlang ) ? get_calendar() : self::get_calendar(); #modified#
		echo '</div>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is trusted as per widget API usage.
		echo $args['after_widget'];

		++self::$ewt_instance; #modified#
	}

	/**
	 * Modified version of the WP `get_calendar()` function to filter the queries.
	 *
	 *  
	 *
	 * @param array $args {
	 *     Optional. Arguments for the `get_calendar` function.
	 *
	 *     @type bool   $initial   Whether to use initial calendar names. Default true.
	 *     @type bool   $display   Whether to display the calendar output. Default true.
	 *     @type string $post_type Optional. Post type. Default 'post'.
	 * }
	 * @return void|string Void if `$display` argument is true, calendar HTML if `$display` is false.
	 */
	static function get_calendar( $args = array() ) {
		global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;

		// Allowed HTML tags for calendar output
		$calendar_allowed_html = array(
			'table'   => array(
				'id'    => true,
				'class' => true,
			),
			'caption' => array(),
			'thead'   => array(),
			'tbody'   => array(),
			'tr'      => array(),
			'th'      => array(
				'scope'      => true,
				'aria-label' => true,
			),
			'td'      => array(
				'id'       => true,
				'class'    => true,
				'colspan'  => true,
			),
			'nav'     => array(
				'aria-label' => true,
				'class'      => true,
			),
			'span'    => array(
				'class' => true,
			),
			'a'       => array(
				'href'       => true,
				'aria-label' => true,
			),
		);

		$defaults = array(
			'initial'   => true,
			'display'   => true,
			'post_type' => 'post',
		);

		$original_args = func_get_args();
		$args          = array();

		if ( ! empty( $original_args ) ) {
			if ( ! is_array( $original_args[0] ) ) {
				if ( isset( $original_args[0] ) && is_bool( $original_args[0] ) ) {
					$defaults['initial'] = $original_args[0];
				}
				if ( isset( $original_args[1] ) && is_bool( $original_args[1] ) ) {
					$defaults['display'] = $original_args[1];
				}
			} else {
				$args = $original_args[0];
			}
		}

		/** This filter is documented in wp-includes/general-template.php */
		$args = apply_filters( 'ewt_get_calendar_args', wp_parse_args( $args, $defaults ) );

		$args['lang'] = EWT()->curlang->slug; #added#

		if ( ! post_type_exists( $args['post_type'] ) ) {
			$args['post_type'] = 'post';
		}

		$w = 0;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['w'] ) ) {
			$w = (int) $_GET['w'];
		}

		/*
		 * Normalize the cache key.
		 *
		 * The following ensures the same cache key is used for the same parameter
		 * and parameter equivalents. This prevents `post_type > post, initial > true`
		 * from generating a different key from the same values in the reverse order.
		 *
		 * `display` is excluded from the cache key as the cache contains the same
		 * HTML regardless of this function's need to echo or return the output.
		 *
		 * The global values contain data generated by the URL query string variables.
		 */
		$cache_args = $args;
		unset( $cache_args['display'] );

		$cache_args['globals'] = array(
			'm'        => $m,
			'monthnum' => $monthnum,
			'year'     => $year,
			'week'     => $w,
		);

		wp_recursive_ksort( $cache_args );
		$key   = md5( serialize( $cache_args ) );
		$cache = wp_cache_get( 'get_calendar', 'calendar' );

		if ( $cache && is_array( $cache ) && isset( $cache[ $key ] ) ) {
			/** This filter is documented in wp-includes/general-template.php */
			$output = apply_filters( 'get_calendar', $cache[ $key ], $args );

			if ( $args['display'] ) {
				echo wp_kses( $output, $calendar_allowed_html );
				return;
			}

			return $output;
		}

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		$post_type = $args['post_type'];

		// Quick check. If we have no posts at all, abort!
		if ( ! $posts ) {
			$prepared_query = $wpdb->prepare(
				"SELECT 1 as test FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s LIMIT %d",
				$post_type,
				'publish',
				1
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery -- This is safe query and already prepared in $prepared_query.
			$gotsome = $wpdb->get_var($prepared_query);
			if ( ! $gotsome ) {
				$cache[ $key ] = '';
				wp_cache_set( 'get_calendar', $cache, 'calendar' );
				return;
			}
		}

		// week_begins = 0 stands for Sunday.
		$week_begins = (int) get_option( 'start_of_week' );

		// Let's figure out when we are.
		if ( ! empty( $monthnum ) && ! empty( $year ) ) {
			$thismonth = zeroise( (int) $monthnum, 2 );
			$thisyear  = (int) $year;
		} elseif ( ! empty( $w ) ) {
			// We need to get the month from MySQL.
			$thisyear = (int) substr( $m, 0, 4 );
			// It seems MySQL's weeks disagree with PHP's.
			$d         = ( ( $w - 1 ) * 7 ) + 6;
			$prepared_query = $wpdb->prepare(
				"SELECT DATE_FORMAT((DATE_ADD(%s, INTERVAL %d DAY)), '%%m')",
				"{$thisyear}0101",
				$d
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery -- This is safe query and already prepared in $prepared_query.
			$thismonth = $wpdb->get_var( $prepared_query );

		} elseif ( ! empty( $m ) ) {
			$thisyear = (int) substr( $m, 0, 4 );
			if ( strlen( $m ) < 6 ) {
				$thismonth = '01';
			} else {
				$thismonth = zeroise( (int) substr( $m, 4, 2 ), 2 );
			}
		} else {
			$thisyear  = current_time( 'Y' );
			$thismonth = current_time( 'm' );
		}

		$unixmonth = mktime( 0, 0, 0, $thismonth, 1, $thisyear );
		$last_day  = gmdate( 't', $unixmonth );

		$join_clause  = EWT()->model->post->join_clause(); #added#
		$where_clause = EWT()->model->post->where_clause( EWT()->curlang ); #added#

		// Get the next and previous month and year with at least one post.

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- This is safe $join_clause and already esc_sql used.
		$previous_prepared_query = $wpdb->prepare("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year FROM {$wpdb->posts} {$join_clause} WHERE post_date < %s AND post_type = %s AND post_status = 'publish' {$where_clause} ORDER BY post_date DESC LIMIT 1",
			"{$thisyear}-{$thismonth}-01",
			$post_type
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery -- This is safe query and already prepared in $previous_prepared_query.
		$previous                = $wpdb->get_row( $previous_prepared_query );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- This is safe $join_clause and already esc_sql used.
		$next_prepared_query = $wpdb->prepare("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year FROM {$wpdb->posts} {$join_clause} WHERE post_date > %s AND post_type = %s AND post_status = 'publish' {$where_clause} ORDER BY post_date ASC LIMIT 1",
			"{$thisyear}-{$thismonth}-{$last_day} 23:59:59",
			$post_type
		);  #modified#

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery -- This is safe query and already prepared in $next_prepared_query.
		$next                = $wpdb->get_row( $next_prepared_query );

		// translators: Calendar caption: 1: Month name, 2: 4-digit year.
		$calendar_caption = _x( '%1$s %2$s', 'calendar caption' ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- This is a default WordPress text domain.
		$calendar_output  = '<table id="wp-calendar" class="wp-calendar-table">
		<caption>' . sprintf(
			$calendar_caption,
			$wp_locale->get_month( $thismonth ),
			gmdate( 'Y', $unixmonth )
		) . '</caption>
		<thead>
		<tr>';

		$myweek = array();

		for ( $wdcount = 0; $wdcount <= 6; $wdcount++ ) {
			$myweek[] = $wp_locale->get_weekday( ( $wdcount + $week_begins ) % 7 );
		}

		foreach ( $myweek as $wd ) {
			$day_name         = $args['initial'] ? $wp_locale->get_weekday_initial( $wd ) : $wp_locale->get_weekday_abbrev( $wd );
			$wd               = esc_attr( $wd );
			$calendar_output .= "\n\t\t<th scope=\"col\" aria-label=\"$wd\">$day_name</th>";
		}

		$calendar_output .= '
		</tr>
		</thead>
		<tbody>
		<tr>';

		$daywithpost = array();

		// Get days with posts using placeholders and $wpdb->prepare().
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- This is safe $join_clause and already esc_sql used.
		$dayswithposts_prepared_query = $wpdb->prepare("SELECT DISTINCT DAYOFMONTH(post_date) FROM {$wpdb->posts} {$join_clause} WHERE post_date >= %s AND post_type = %s AND post_status = 'publish' AND post_date <= %s {$where_clause}",
			sprintf( '%04d-%02d-01 00:00:00', $thisyear, $thismonth ),
			$post_type,
			sprintf( '%04d-%02d-%02d 23:59:59', $thisyear, $thismonth, $last_day )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery -- This is safe query and already prepared in $dayswithposts_prepared_query.
		$dayswithposts = $wpdb->get_results( $dayswithposts_prepared_query, ARRAY_N );

		if ( $dayswithposts ) {
			foreach ( (array) $dayswithposts as $daywith ) {
				$daywithpost[] = (int) $daywith[0];
			}
		}

		// See how much we should pad in the beginning.
		$pad = calendar_week_mod( (int) gmdate( 'w', $unixmonth ) - $week_begins );
		if ( 0 != $pad ) {
			$calendar_output .= "\n\t\t" . '<td colspan="' . esc_attr( $pad ) . '" class="pad">&nbsp;</td>';
		}

		$newrow      = false;
		$daysinmonth = (int) gmdate( 't', $unixmonth );

		for ( $day = 1; $day <= $daysinmonth; ++$day ) {
			if ( $newrow ) {
				$calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
			}
			$newrow = false;

			if ( current_time( 'j' ) == $day &&
				current_time( 'm' ) == $thismonth &&
				current_time( 'Y' ) == $thisyear ) {
				$calendar_output .= '<td id="today">';
			} else {
				$calendar_output .= '<td>';
			}

			if ( in_array( $day, $daywithpost, true ) ) {
				// Any posts today?
				// phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- This is a default WordPress text domain.
				$date_format = gmdate( _x( 'F j, Y', 'daily archives date format' ), strtotime( "{$thisyear}-{$thismonth}-{$day}" ) );
				/* translators: Post calendar label. %s: Date. */
				$label            = sprintf( __( 'Posts published on %s','easy-wp-translator' ), $date_format ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- This is a default WordPress text domain.
				$calendar_output .= sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					get_day_link( $thisyear, $thismonth, $day ),
					esc_attr( $label ),
					$day
				);
			} else {
				$calendar_output .= $day;
			}

			$calendar_output .= '</td>';

			if ( 6 == calendar_week_mod( (int) gmdate( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins ) ) {
				$newrow = true;
			}
		}

		$pad = 7 - calendar_week_mod( (int) gmdate( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins );
		if ( 0 != $pad && 7 != $pad ) {
			$calendar_output .= "\n\t\t" . '<td class="pad" colspan="' . esc_attr( $pad ) . '">&nbsp;</td>';
		}

		$calendar_output .= "\n\t</tr>\n\t</tbody>";

		$calendar_output .= "\n\t</table>";

		// phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- This is a default WordPress text domain.
		$calendar_output .= '<nav aria-label="' . __( 'Previous and next months', 'easy-wp-translator' ) . '" class="wp-calendar-nav">';

		if ( $previous ) {
			$calendar_output .= "\n\t\t" . '<span class="wp-calendar-nav-prev"><a href="' . get_month_link( $previous->year, $previous->month ) . '">&laquo; ' .
				$wp_locale->get_month_abbrev( $wp_locale->get_month( $previous->month ) ) .
			'</a></span>';
		} else {
			$calendar_output .= "\n\t\t" . '<span class="wp-calendar-nav-prev">&nbsp;</span>';
		}

		$calendar_output .= "\n\t\t" . '<span class="pad">&nbsp;</span>';

		if ( $next ) {
			$calendar_output .= "\n\t\t" . '<span class="wp-calendar-nav-next"><a href="' . get_month_link( $next->year, $next->month ) . '">' .
				$wp_locale->get_month_abbrev( $wp_locale->get_month( $next->month ) ) .
			' &raquo;</a></span>';
		} else {
			$calendar_output .= "\n\t\t" . '<span class="wp-calendar-nav-next">&nbsp;</span>';
		}

		$calendar_output .= '
		</nav>';

		$cache[ $key ] = $calendar_output;
		wp_cache_set( 'get_calendar', $cache, 'calendar' );

		/** This filter is documented in wp-includes/general-template.php */
		$calendar_output = apply_filters( 'get_calendar', $calendar_output, $args );

		if ( $args['display'] ) {
			echo wp_kses( $calendar_output, $calendar_allowed_html );
			return;
		}

		return $calendar_output;
	}
}

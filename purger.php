<?php

namespace rtCamp\WP\Nginx {

	class Purger {

		function purgePostOnComment( $comment_id, $comment ) {
			$oldstatus = '';
			$approved = $comment->comment_approved;

			if ( $approved == null )
				$newstatus = false;
			elseif ( $approved == '1' )
				$newstatus = 'approved';
			elseif ( $approved == '0' )
				$newstatus = 'unapproved';
			elseif ( $approved == 'spam' )
				$newstatus = 'spam';
			elseif ( $approved == 'trash' )
				$newstatus = 'trash';
			else
				$newstatus = false;

			$this->purgePostOnCommentChange( $newstatus, $oldstatus, $comment );
		}

		function purgePostOnCommentChange( $newstatus, $oldstatus, $comment ) {

			global $rt_wp_nginx_helper, $blog_id;
			if ( ! $rt_wp_nginx_helper->options[ 'enable_purge' ] ) {
				return;
			}


			$_post_id = $comment->comment_post_ID;
			$_comment_id = $comment->comment_ID;

			$this->log( "* * * * *" );
			$this->log( "* Blog :: " . addslashes( get_bloginfo( 'name' ) ) . " ($blog_id)." );
			$this->log( "* Post :: " . get_the_title( $_post_id ) . " ($_post_id)." );
			$this->log( "* Comment :: $_comment_id." );
			$this->log( "* Status Changed from $oldstatus to $newstatus" );
			switch ( $newstatus ) {
				case 'approved':
					if ( $rt_wp_nginx_helper->options[ 'purge_page_on_new_comment' ] == 1 ) {
						$this->log( "* Comment ($_comment_id) approved. Post ($_post_id) purging..." );
						$this->log( "* * * * *" );
						$this->purgePost( $_post_id );
					}
					break;
				case 'spam':
				case 'unapproved':
				case 'trash':
					if ( $oldstatus == 'approve' ) {
						if ( $rt_wp_nginx_helper->options[ 'purge_page_on_deleted_comment' ] == 1 ) {
							$this->log( "* Comment ($_comment_id) removed as ($newstatus). Post ($_post_id) purging..." );
							$this->log( "* * * * *" );
							$this->purgePost( $_post_id );
						}
					}
					break;
			}
		}

		function purgePost( $_ID ) {

			global $rt_wp_nginx_helper, $blog_id;
			if ( ! $rt_wp_nginx_helper->options[ 'enable_purge' ] ) {
				return;
			}
			switch ( current_filter() ) {
				case 'publish_post':
					$this->log( "* * * * *" );
					$this->log( "* Blog :: " . addslashes( get_bloginfo( 'name' ) ) . " ($blog_id)." );
					$this->log( "* Post :: " . get_the_title( $_ID ) . " ($_ID)." );
					$this->log( "* Post ($_ID) published or edited and its status is published" );
					$this->log( "* * * * *" );
					break;

				case 'publish_page':
					$this->log( "* * * * *" );
					$this->log( "* Blog :: " . addslashes( get_bloginfo( 'name' ) ) . " ($blog_id)." );
					$this->log( "* Page :: " . get_the_title( $_ID ) . " ($_ID)." );
					$this->log( "* Page ($_ID) published or edited and its status is published" );
					$this->log( "* * * * *" );
					break;

				case 'comment_post':
				case 'wp_set_comment_status':
					break;

				default:
					$_post_type = get_post_type( $_ID );
					$this->log( "* * * * *" );
					$this->log( "* Blog :: " . addslashes( get_bloginfo( 'name' ) ) . " ($blog_id)." );
					$this->log( "* Custom post type '$_post_type' :: " . get_the_title( $_ID ) . " ($_ID)." );
					$this->log( "* CPT '$_post_type' ($_ID) published or edited and its status is published" );
					$this->log( "* * * * *" );
					break;
			}

			$this->log( "Function purgePost BEGIN ===" );

			if ( $rt_wp_nginx_helper->options[ 'purge_homepage_on_edit' ] == 1 ) {
				$homepage_url = trailingslashit( home_url() );

				$this->log( "Purging homepage '$homepage_url'" );
				$this->purgeUrl( $homepage_url );
			}


			if ( current_filter() == 'comment_post' || current_filter() == 'wp_set_comment_status' ) {
				$this->_purge_by_options( $_ID, $blog_id, $rt_wp_nginx_helper->options[ 'purge_page_on_new_comment' ], $rt_wp_nginx_helper->options[ 'purge_archive_on_new_comment' ], $rt_wp_nginx_helper->options[ 'purge_archive_on_new_comment' ] );
			} else {
				$this->_purge_by_options( $_ID, $blog_id, $rt_wp_nginx_helper->options[ 'purge_page_on_mod' ], $rt_wp_nginx_helper->options[ 'purge_archive_on_edit' ], $rt_wp_nginx_helper->options[ 'purge_archive_on_edit' ] );
			}
            
            $this->purge_urls();
            
			$this->log( "Function purgePost END ^^^" );
		}

		private function _purge_by_options( $_post_ID, $blog_id, $_purge_page, $_purge_archive, $_purge_custom_taxa ) {

			global $rt_wp_nginx_helper;

			$_post_type = get_post_type( $_post_ID );

			if ( $_purge_page ) {
				if ( $_post_type == 'post' || $_post_type == 'page' ) {
					$this->log( "Purging $_post_type (id $_post_ID, blog id $blog_id)" );
				} else {
					$this->log( "Purging custom post type '$_post_type' (id $_post_ID, blog id $blog_id)" );
				}

				$this->purgeUrl( get_permalink( $_post_ID ) );
			}

			if ( $_purge_archive ) {

				if ( function_exists( 'get_post_type_archive_link' ) && ( $_post_type_archive_link = get_post_type_archive_link( $_post_type ) ) ) {
					$this->log( "Purging post type archive (" . $_post_type . ")" );
					$this->purgeUrl( $_post_type_archive_link );
				}

				if ( $_post_type == 'post' ) {
					$this->log( "Purging date" );

					$day = get_the_time( 'd', $_post_ID );
					$month = get_the_time( 'm', $_post_ID );
					$year = get_the_time( 'Y', $_post_ID );

					if ( $year ) {
						$this->purgeUrl( get_year_link( $year ) );
						if ( $month ) {
							$this->purgeUrl( get_month_link( $year, $month ) );
							if ( $day )
								$this->purgeUrl( get_day_link( $year, $month, $day ) );
						}
					}
				}

				if ( $categories = wp_get_post_categories( $_post_ID ) ) {
					$this->log( "Purging category archives" );

					foreach ( $categories as $category_id ) {
						$this->log( "Purging category " . $category_id );
						$this->purgeUrl( get_category_link( $category_id ) );
					}
				}

				if ( $tags = get_the_tags( $_post_ID ) ) {
					$this->log( "Purging tag archives" );

					foreach ( $tags as $tag ) {
						$this->log( "Purging tag " . $tag->term_id );
						$this->purgeUrl( get_tag_link( $tag->term_id ) );
					}
				}

				if ( $author_id = get_post( $_post_ID )->post_author ) {
					$this->log( "Purging author archive" );
					$this->purgeUrl( get_author_posts_url( $author_id ) );
				}
			}

			if ( $_purge_custom_taxa ) {
				if ( $custom_taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ) ) ) {
					$this->log( "Purging custom taxonomies related" );
					foreach ( $custom_taxonomies as $taxon ) {

						if ( ! in_array( $taxon, array( 'category', 'post_tag', 'link_category' ) ) ) {

							if ( $terms = get_the_terms( $_post_ID, $taxon ) ) {
								foreach ( $terms as $term ) {
									$this->purgeUrl( get_term_link( $term, $taxon ) );
								}
							}
						} else {
							$this->log( "Your built-in taxonomy '" . $taxon . "' has param '_builtin' set to false.", "WARNING" );
						}
					}
				}
			}
		}

		function purgeUrl( $url, $feed = true ) {

			global $rt_wp_nginx_helper;

			$this->log( "- Purging URL | " . $url );

			$parse = parse_url( $url );

			switch ($rt_wp_nginx_helper->options['purge_method']) {
				case 'unlink_files':
					$_url_purge_base = $parse[ 'scheme' ] . '://' . $parse[ 'host' ] . $parse[ 'path' ];
					$_url_purge = $_url_purge_base;

					if ( isset( $parse[ 'query' ] ) && $parse[ 'query' ] != '' ) {
						$_url_purge .= '?' . $parse[ 'query' ];
					}

					$this->_delete_cache_file_for( $_url_purge );

					if ( $feed ) {
						$feed_url = rtrim( $_url_purge_base, '/' ) . '/feed/';
						$this->_delete_cache_file_for( $feed_url );
						$this->_delete_cache_file_for( $feed_url . 'atom/' );
						$this->_delete_cache_file_for( $feed_url . 'rdf/' );
					}
                    break;
				case 'get_request':
					// Go to default case
				default:
					$_url_purge_base = $parse[ 'scheme' ] . '://' . $parse[ 'host' ] . '/purge' . $parse[ 'path' ];
					$_url_purge = $_url_purge_base;

					if ( isset( $parse[ 'query' ] ) && $parse[ 'query' ] != '' ) {
						$_url_purge .= '?' . $parse[ 'query' ];
					}

					$this->_do_remote_get( $_url_purge );

					if ( $feed ) {
						$feed_url = rtrim( $_url_purge_base, '/' ) . '/feed/';
						$this->_do_remote_get( $feed_url );
						$this->_do_remote_get( $feed_url . 'atom/' );
						$this->_do_remote_get( $feed_url . 'rdf/' );
					}
                    break;
			}

		}

		/*********************
		* Deletes local cache files without a 3rd party nginx module.
		*
		* Does not require any other modules. Requires that the cache be stored on the same server as WordPress. You must also be using the default nginx cache options (levels=1:2) and (fastcgi_cache_key "$scheme$request_method$host$request_uri").
		*
		* Read more on how this works here: 
		* https://www.digitalocean.com/community/tutorials/how-to-setup-fastcgi-caching-with-nginx-on-your-vps#purging-the-cache
		**********************/
		private function _delete_cache_file_for( $url ) {

			// Verify cache path is set
			if (!defined('RT_WP_NGINX_HELPER_CACHE_PATH')) {
				$this->log('Error purging because RT_WP_NGINX_HELPER_CACHE_PATH was not defined. URL: '.$url, 'ERROR');
				return false;
			}

			// Verify URL is valid
			$url_data = parse_url($url);
			if(!$url_data) {
				$this->log('Error purging because specified URL did not appear to be valid. URL: '.$url, 'ERROR');
			    return false;
			}

			// Build a hash of the URL
			$hash = md5($url_data['scheme'].'GET'.$url_data['host'].$url_data['path']);

			// Ensure trailing slash
			$cache_path = RT_WP_NGINX_HELPER_CACHE_PATH;
			$cache_path = (substr($cache_path, -1) == '/') ? $cache_path : $cache_path.'/';
			
			// Set path to cached file
			$cached_file = $cache_path . substr($hash, -1) . '/' . substr($hash,-3,2) . '/' . $hash;

			// Verify cached file exists
			if (!file_exists($cached_file)) {
				$this->log( "- - " . $url . " is currently not cached (checked for file: $cached_file)" );
				return false;
			}

			// Delete the cached file
			if (unlink($cached_file)) {
				$this->log( "- - " . $url . " *** PURGED ***" );
			} else {
				$this->log("- - An error occurred deleting the cache file. Check the server logs for a PHP warning.", "ERROR");
			}
		}

		private function _do_remote_get( $url ) {

			$response = wp_remote_get( $url );

			if ( is_wp_error( $response ) ) {
				$_errors_str = implode( " - ", $response->get_error_messages() );
				$this->log( "Error while purging URL. " . $_errors_str, "ERROR" );
			} else {
				if ( $response[ 'response' ][ 'code' ] ) {
					switch ( $response[ 'response' ][ 'code' ] ) {
						case 200:
							$this->log( "- - " . $url . " *** PURGED ***" );
							break;
						case 404:
							$this->log( "- - " . $url . " is currently not cached" );
							break;
						default:
							$this->log( "- - " . $url . " not found (" . $response[ 'response' ][ 'code' ] . ")", "WARNING" );
					}
				}
			}
		}

		function checkHttpConnection() {

			$purgeURL = plugins_url( "nginx-manager/check-proxy.php" );
			$response = wp_remote_get( $purgeURL );

			if ( ! is_wp_error( $response ) && ($response[ 'body' ] == 'HTTP Connection OK') ) {
				return "OK";
			}

			return "KO";
		}

		function log( $msg, $level = 'INFO' ) {

			global $rt_wp_nginx_helper;
			if ( ! $rt_wp_nginx_helper->options[ 'enable_log' ] ) {
				return;
			}

			$log_levels = array( "INFO" => 0, "WARNING" => 1, "ERROR" => 2, "NONE" => 3 );

			if ( $log_levels[ $level ] >= $log_levels[ $rt_wp_nginx_helper->options[ 'log_level' ] ] ) {
				if ( $fp = fopen( $rt_wp_nginx_helper->functional_asset_path() . 'nginx.log', "a+" ) ) {
					fwrite( $fp, "\n" . gmdate( "Y-m-d H:i:s " ) . " | " . $level . " | " . $msg );
					fclose( $fp );
				}
			}

			return true;
		}

		function checkAndTruncateLogFile() {

			global $rt_wp_nginx_helper;

			$maxSizeAllowed = (is_numeric( $rt_wp_nginx_helper->options[ 'log_filesize' ] )) ? $rt_wp_nginx_helper->options[ 'log_filesize' ] * 1048576 : 5242880;

			$fileSize = filesize( $rt_wp_nginx_helper->functional_asset_path() . 'nginx.log' );

			if ( $fileSize > $maxSizeAllowed ) {

				$offset = $fileSize - $maxSizeAllowed;

				if ( $file_content = file_get_contents( $rt_wp_nginx_helper->functional_asset_path() . 'nginx.log', NULL, NULL, $offset ) ) {

					if ( $file_content = strstr( $file_content, "\n" ) ) {

						if ( $fp = fopen( $rt_wp_nginx_helper->functional_asset_path() . 'nginx.log', "w+" ) ) {
							fwrite( $fp, $file_content );
							fclose( $fp );
						}
					}
				}
			}
		}

		function purgeImageOnEdit( $attachment_id ) {

			$this->log( "Purging media on edit BEGIN ===" );

			if ( wp_attachment_is_image( $attachment_id ) ) {

				$this->purgeUrl( wp_get_attachment_url( $attachment_id ), false );
				$attachment = wp_get_attachment_metadata( $attachment_id );

				if ( ! empty( $attachment[ 'sizes' ] ) && is_array( $attachment[ 'sizes' ] ) ) {
					foreach ( $attachment[ 'sizes' ] as $size_name => $size ) {
						$resize_image = wp_get_attachment_image_src( $attachment_id, $size_name );
						if ( $resize_image )
							$this->purgeUrl( $resize_image[ 0 ], false );
					}
				}
				$this->purgeURL( get_attachment_link( $attachment_id ) );
			} else {
				$this->log( "Media (id $attachment_id) edited: no image", "WARNING" );
			}

			$this->log( "Purging media on edit END ^^^" );
		}

		function purge_on_post_moved_to_trash( $new_status, $old_status, $post ) {

			global $rt_wp_nginx_helper, $blog_id;
			if ( ! $rt_wp_nginx_helper->options[ 'enable_purge' ] ) {
				return;
			}
			if ( $new_status == 'trash' ) {

				$this->log( "# # # # #" );
				$this->log( "# Post '$post->post_title' (id $post->ID) moved to the trash." );
				$this->log( "# # # # #" );

				$this->log( "Function purge_on_post_moved_to_trash (post id $post->ID) BEGIN ===" );



				if ( $rt_wp_nginx_helper->options[ 'purge_homepage_on_del' ] == 1 ) {
					$this->_purge_homepage();
				}


				$this->_purge_by_options( $post->ID, $blog_id, false, $rt_wp_nginx_helper->options[ 'purge_archive_on_del' ], $rt_wp_nginx_helper->options[ 'purge_archive_on_del' ] );



				$this->log( "Function purge_on_post_moved_to_trash (post id $post->ID) END ===" );
			}

			return true;
		}

		private function _purge_homepage() {

			$homepage_url = trailingslashit( home_url() );

			$this->log( sprintf( __( "Purging homepage '%s'", "nginx-helper" ), $homepage_url ) );
			$this->purgeUrl( $homepage_url );

			return true;
		}

		private function _purge_personal_urls() {

			global $rt_wp_nginx_helper;

			$this->log( __( "Purging personal urls", "nginx-helper" ) );

			if ( isset( $rt_wp_nginx_helper->options[ 'purgeable_url' ][ 'urls' ] ) ) {

				foreach ( $rt_wp_nginx_helper->options[ 'purgeable_url' ][ 'urls' ] as $u ) {
					$this->purgeUrl( $u, false );
				}
			} else {
				$this->log( "- " . __( "No personal urls available", "nginx-helper" ) );
			}

			return true;
		}

		private function _purge_post_categories( $_post_id ) {

			$this->log( __( "Purging category archives", "nginx-helper" ) );

			if ( $categories = wp_get_post_categories( $_post_id ) ) {
				foreach ( $categories as $category_id ) {
					$this->log( sprintf( __( "Purging category '%d'", "nginx-helper" ), $category_id ) );
					$this->purgeUrl( get_category_link( $category_id ) );
				}
			}

			return true;
		}

		private function _purge_post_tags( $_post_id ) {

			$this->log( __( "Purging tags archives", "nginx-helper" ) );

			if ( $tags = get_the_tags( $_post_id ) ) {
				foreach ( $tags as $tag ) {
					$this->log( sprintf( __( "Purging tag '%s' (id %d)", "nginx-helper" ), $tag->name, $tag->term_id ) );
					$this->purgeUrl( get_tag_link( $tag->term_id ) );
				}
			}

			return true;
		}

		private function _purge_post_custom_taxa( $_post_id ) {

			$this->log( __( "Purging post custom taxonomies related", "nginx-helper" ) );

			if ( $custom_taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ) ) ) {
				foreach ( $custom_taxonomies as $taxon ) {
					$this->log( sprintf( "+ " . __( "Purging custom taxonomy '%s'", "nginx-helper" ), $taxon ) );

					if ( ! in_array( $taxon, array( 'category', 'post_tag', 'link_category' ) ) ) {

						if ( $terms = get_the_terms( $_post_id, $taxon ) ) {
							foreach ( $terms as $term ) {
								$this->purgeUrl( get_term_link( $term, $taxon ) );
							}
						}
					} else {
						$this->log( sprintf( "- " . __( "Your built-in taxonomy '%s' has param '_builtin' set to false.", "nginx-helper" ), $taxon ), "WARNING" );
					}
				}
			} else {
				$this->log( "- " . __( "No custom taxonomies", "nginx-helper" ) );
			}

			return true;
		}

		private function _purge_all_categories() {

			$this->log( __( "Purging all categories", "nginx-helper" ) );

			if ( $_categories = get_categories() ) {

				foreach ( $_categories as $c ) {
					$this->log( sprintf( __( "Purging category '%s' (id %d)", "nginx-helper" ), $c->name, $c->term_id ) );
					$this->purgeUrl( get_category_link( $c->term_id ) );
				}
			} else {
				$this->log( __( "No categories archives", "nginx-helper" ) );
			}

			return true;
		}

		private function _purge_all_posttags() {

			$this->log( __( "Purging all tags", "nginx-helper" ) );

			if ( $_posttags = get_tags() ) {

				foreach ( $_posttags as $t ) {
					$this->log( sprintf( __( "Purging tag '%s' (id %d)", "nginx-helper" ), $t->name, $t->term_id ) );
					$this->purgeUrl( get_tag_link( $t->term_id ) );
				}
			} else {
				$this->log( __( "No tags archives", "nginx-helper" ) );
			}

			return true;
		}

		private function _purge_all_customtaxa() {

			$this->log( __( "Purging all custom taxonomies", "nginx-helper" ) );

			if ( $custom_taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ) ) ) {

				foreach ( $custom_taxonomies as $taxon ) {
					$this->log( sprintf( "+ " . __( "Purging custom taxonomy '%s'", "nginx-helper" ), $taxon ) );

					if ( ! in_array( $taxon, array( 'category', 'post_tag', 'link_category' ) ) ) {

						if ( $terms = get_terms( $taxon ) ) {
							foreach ( $terms as $term ) {
								$this->purgeUrl( get_term_link( $term, $taxon ) );
							}
						}
					} else {
						$this->log( sprintf( "- " . __( "Your built-in taxonomy '%s' has param '_builtin' set to false.", "nginx-helper" ), $taxon ), "WARNING" );
					}
				}
			} else {
				$this->log( "- " . __( "No custom taxonomies", "nginx-helper" ) );
			}

			return true;
		}

		private function _purge_all_taxonomies() {

			$this->_purge_all_categories();
			$this->_purge_all_posttags();
			$this->_purge_all_customtaxa();

			return true;
		}

		private function _purge_all_posts() {

			$this->log( __( "Purging all posts, pages and custom post types.", "nginx-helper" ) );

			$args = array(
				'numberposts' => 0,
				'post_type' => 'any',
				'post_status' => 'publish' );

			if ( $_posts = get_posts( $args ) ) {

				foreach ( $_posts as $p ) {
					$this->log( sprintf( "+ " . __( "Purging post id '%d' (post type '%s')", "nginx-helper" ), $p->ID, $p->post_type ) );
					$this->purgeUrl( get_permalink( $p->ID ) );
				}
			} else {
				$this->log( "- " . __( "No posts", "nginx-helper" ) );
			}

			return true;
		}

		private function _purge_all_date_archives() {

			$this->log( __( "Purging all date-based archives.", "nginx-helper" ) );

			$this->_purge_all_daily_archives();

			$this->_purge_all_monthly_archives();

			$this->_purge_all_yearly_archives();

			return true;
		}

		private function _purge_all_daily_archives() {

			global $wpdb;

			$this->log( __( "Purging all daily archives.", "nginx-helper" ) );

			$_query_daily_archives = $wpdb->prepare(
					"SELECT YEAR(post_date) AS 'year', MONTH(post_date) AS 'month', DAYOFMONTH(post_date) AS 'dayofmonth', count(ID) as posts
                FROM $wpdb->posts
                WHERE post_type = 'post' AND post_status = 'publish'
                GROUP BY YEAR(post_date), MONTH(post_date), DAYOFMONTH(post_date)
                ORDER BY post_date DESC"
			);

			if ( $_daily_archives = $wpdb->get_results( $_query_daily_archives ) ) {

				foreach ( $_daily_archives as $_da ) {
					$this->log( sprintf( "+ " . __( "Purging daily archive '%s/%s/%s'", "nginx-helper" ), $_da->year, $_da->month, $_da->dayofmonth ) );
					$this->purgeUrl( get_day_link( $_da->year, $_da->month, $_da->dayofmonth ) );
				}
			} else {
				$this->log( "- " . __( "No daily archives", "nginx-helper" ) );
			}
		}

		private function _purge_all_monthly_archives() {

			global $wpdb;

			$this->log( __( "Purging all monthly archives.", "nginx-helper" ) );

			$_query_monthly_archives = $wpdb->prepare(
					"SELECT YEAR(post_date) AS 'year', MONTH(post_date) AS 'month', count(ID) as posts
                FROM $wpdb->posts
                WHERE post_type = 'post' AND post_status = 'publish'
                GROUP BY YEAR(post_date), MONTH(post_date)
                ORDER BY post_date DESC"
			);

			if ( $_monthly_archives = $wpdb->get_results( $_query_monthly_archives ) ) {

				foreach ( $_monthly_archives as $_ma ) {
					$this->log( sprintf( "+ " . __( "Purging monthly archive '%s/%s'", "nginx-helper" ), $_ma->year, $_ma->month ) );
					$this->purgeUrl( get_month_link( $_ma->year, $_ma->month ) );
				}
			} else {
				$this->log( "- " . __( "No monthly archives", "nginx-helper" ) );
			}
		}

		private function _purge_all_yearly_archives() {

			global $wpdb;

			$this->log( __( "Purging all yearly archives.", "nginx-helper" ) );

			$_query_yearly_archives = $wpdb->prepare(
					"SELECT YEAR(post_date) AS 'year', count(ID) as posts
                FROM $wpdb->posts
                WHERE post_type = 'post' AND post_status = 'publish'
                GROUP BY YEAR(post_date)
                ORDER BY post_date DESC"
			);

			if ( $_yearly_archives = $wpdb->get_results( $_query_yearly_archives ) ) {

				foreach ( $_yearly_archives as $_ya ) {
					$this->log( sprintf( "+ " . __( "Purging yearly archive '%s'", "nginx-helper" ), $_ya->year ) );
					$this->purgeUrl( get_year_link( $_ya->year ) );
				}
			} else {
				$this->log( "- " . __( "No yearly archives", "nginx-helper" ) );
			}
		}

		function purge_them_all() {

			$this->log( __( "Let's purge everything!", "nginx-helper" ) );

			$this->_purge_homepage();

			$this->_purge_personal_urls();

			$this->_purge_all_posts();

			$this->_purge_all_taxonomies();

			$this->_purge_all_date_archives();

			$this->log( __( "Everthing purged!", "nginx-helper" ) );

			return true;
		}

		function purge_on_term_taxonomy_edited( $term_id, $tt_id, $taxon ) {

			$this->log( __( "Term taxonomy edited or deleted", "nginx-helper" ) );

			if ( current_filter() == 'edit_term' && $term = get_term( $term_id, $taxon ) ) {
				$this->log( sprintf( __( "Term taxonomy '%s' edited, (tt_id '%d', term_id '%d', taxonomy '%s')", "nginx-helper" ), $term->name, $tt_id, $term_id, $taxon ) );
			} else if ( current_filter() == 'delete_term' ) {
				$this->log( sprintf( __( "A term taxonomy has been deleted from taxonomy '%s', (tt_id '%d', term_id '%d')", "nginx-helper" ), $taxon, $term_id, $tt_id ) );
			}

			$this->_purge_homepage();

			return true;
		}

		function purge_on_check_ajax_referer( $action, $result ) {

			switch ( $action ) {
				case 'save-sidebar-widgets' :

					$this->log( __( "Widget saved, moved or removed in a sidebar", "nginx-helper" ) );

					$this->_purge_homepage();

					break;

				default :
					break;
			}

			return true;
		}

		function true_purge_all(){
			$this->unlinkRecursive(RT_WP_NGINX_HELPER_CACHE_PATH, false);
			$this->log( "* * * * *" );
			$this->log( "* Purged Everything!" );
			$this->log( "* * * * *" );
		}

		/** Source - http://stackoverflow.com/a/1360437/156336 **/		
		
		function unlinkRecursive( $dir, $deleteRootToo ) {
			if ( ! $dh = opendir( $dir ) ) {
				return;
			}
			while ( false !== ($obj = readdir( $dh )) ) {
				if ( $obj == '.' || $obj == '..' ) {
					continue;
				}

				if ( ! @unlink( $dir . '/' . $obj ) ) {
					$this->unlinkRecursive( $dir . '/' . $obj, true );
				}
			}
			
			if ($deleteRootToo){
				rmdir($dir);
			}

			closedir( $dh );

			return;
		}
        
        function purge_urls() {

			global $rt_wp_nginx_helper;

			$parse = parse_url( site_url() );

			switch ($rt_wp_nginx_helper->options['purge_method']) {
				case 'unlink_files':
					$_url_purge_base = $parse[ 'scheme' ] . '://' . $parse[ 'host' ];
					
                    if( isset( $rt_wp_nginx_helper->options['purge_url'] ) && ! empty( $rt_wp_nginx_helper->options['purge_url'] ) ) {
                        $purge_urls = explode( "\r\n", $rt_wp_nginx_helper->options['purge_url'] );
                        
                        foreach ($purge_urls as $purge_url ) {
                            $purge_url = trim( $purge_url );
                            
                            if( strpos( $purge_url, '*' ) === false ) {
                                $purge_url = $_url_purge_base . $purge_url;
                                $this->log( "- Purging URL | " . $url );
                                $this->_delete_cache_file_for( $purge_url );
                            }
                        }
                    }
					break;
				case 'get_request':
					// Go to default case
				default:
					$_url_purge_base = $parse[ 'scheme' ] . '://' . $parse[ 'host' ] . '/purge';
					
                    if( isset( $rt_wp_nginx_helper->options['purge_url'] ) && ! empty( $rt_wp_nginx_helper->options['purge_url'] ) ) {
                        $purge_urls = explode( "\r\n", $rt_wp_nginx_helper->options['purge_url'] );
                        
                        foreach ($purge_urls as $purge_url ) {
                            $purge_url = trim( $purge_url );
                            
                            if( strpos( $purge_url, '*' ) === false ) {
                                $purge_url = $_url_purge_base . $purge_url;
                                $this->log( "- Purging URL | " . $url );
                                $this->_do_remote_get( $purge_url );
                            }
                        }
                    }
					break;
			}

		}
	}
}

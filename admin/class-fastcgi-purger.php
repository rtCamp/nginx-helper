<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://rtcamp.com/nginx-helper/
 * @since      2.0.0
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin
 */

/**
 * Description of FastCGI_Purger
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin
 * @author     rtCamp
 */
class FastCGI_Purger extends Purger {

    public function purgeUrl( $url, $feed = true ) {
        global $nginx_helper_admin;

        $this->log( "- Purging URL | " . $url );

        $parse = parse_url( $url );

        switch ( $nginx_helper_admin->options['purge_method'] ) {
            case 'unlink_files':
                $_url_purge_base = $parse['scheme'] . '://' . $parse['host'] . $parse['path'];
                $_url_purge = $_url_purge_base;

                if ( isset( $parse['query']) && $parse['query'] != '' ) {
                    $_url_purge .= '?' . $parse['query'];
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
                $_url_purge_base = $parse['scheme'] . '://' . $parse['host'] . '/purge' . $parse['path'];
                $_url_purge = $_url_purge_base;

                if ( isset( $parse['query']) &&  '' != $parse['query'] ) {
                    $_url_purge .= '?' . $parse['query'];
                }

                $this->_do_remote_get( $_url_purge );

                if ( $feed ) {
                    $feed_url = rtrim($_url_purge_base, '/' ) . '/feed/';
                    $this->_do_remote_get( $feed_url );
                    $this->_do_remote_get( $feed_url . 'atom/' );
                    $this->_do_remote_get( $feed_url . 'rdf/' );
                }
                break;
        }
    }
    
    public function customPurgeUrls() {
        global $nginx_helper_admin;

        $parse = parse_url( site_url() );
        
        $purge_urls = isset( $nginx_helper_admin->options['purge_url'] ) && ! empty( $nginx_helper_admin->options['purge_url'] ) ?
            explode( "\r\n", $nginx_helper_admin->options['purge_url'] ) : array();

        // Allow plugins/themes to modify/extend urls. Pass urls array in first parameter, second says if wildcards are allowed
        $purge_urls = apply_filters( 'rt_nginx_helper_purge_urls', $purge_urls, false );
            
        switch ( $nginx_helper_admin->options['purge_method'] ) {
            case 'unlink_files':
                $_url_purge_base = $parse['scheme'] . '://' . $parse['host'];

                if( is_array( $purge_urls ) && ! empty( $purge_urls ) ) {
                    foreach ( $purge_urls as $purge_url ) {
                        $purge_url = trim( $purge_url );

                        if ( strpos($purge_url, '*' ) === false ) {
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
                $_url_purge_base = $parse['scheme'] . '://' . $parse['host'] . '/purge';

                if( is_array( $purge_urls ) && ! empty( $purge_urls ) ) {
                    foreach ( $purge_urls as $purge_url ) {
                        $purge_url = trim( $purge_url );

                        if ( strpos( $purge_url, '*' ) === false ) {
                            $purge_url = $_url_purge_base . $purge_url;
                            $this->log( "- Purging URL | " . $url );
                            $this->_do_remote_get( $purge_url );
                        }
                    }
                }
                break;
        }
    }
    
    public function purgeAll() {
        $this->unlinkRecursive( RT_WP_NGINX_HELPER_CACHE_PATH, false );
        $this->log( "* * * * *" );
        $this->log( "* Purged Everything!" );
        $this->log( "* * * * *" );
    }
}

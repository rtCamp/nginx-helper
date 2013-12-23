<?php

namespace rtCamp\WP\Nginx {

    class Compatibility {

        protected $have_nginx;

        public static function instance() {
            static $self = false;
            if (!$self) {
                $self = new Compatibility();
            }
            return $self;
        }

        private function __construct() {
            $this->have_nginx = ('nginx' == substr($_SERVER['SERVER_SOFTWARE'], 0, 5));
            if ($this->have_nginx) {
                add_filter('got_rewrite', array($this, 'got_rewrite'), 999);

                // For compatibility with several plugins and nginx HTTPS proxying schemes
                if (empty($_SERVER['HTTPS']) || 'off' == $_SERVER['HTTPS']) {
                    unset($_SERVER['HTTPS']);
                }
            }
        }

        public function got_rewrite($got) {
            return true;
        }

        public function haveNginx() {
            return $this->have_nginx;
        }
    }
}
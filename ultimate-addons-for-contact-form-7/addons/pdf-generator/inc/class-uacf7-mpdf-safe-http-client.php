<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure mPDF third-party autoloader is loaded for interfaces
if ( ! interface_exists( 'Mpdf\Http\ClientInterface' ) && defined( 'UACF7_PATH' ) && file_exists( UACF7_PATH . 'third-party/vendor/autoload.php' ) ) {
	require_once UACF7_PATH . 'third-party/vendor/autoload.php';
}

use Mpdf\Http\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Mpdf\PsrHttpMessageShim\Response;

/**
 * Safe HTTP Client for mPDF to prevent Server-Side Request Forgery (SSRF).
 *
 * Intercepts all outgoing mPDF requests to ensure that:
 * 1. Local WordPress media/assets are read directly from the filesystem (avoiding network loopback/timeouts).
 * 2. Requests to private networks, loopback (localhost), and cloud metadata (169.254.169.254) are strictly blocked.
 * 3. Safe external resources are fetched using WordPress's secure HTTP API (wp_safe_remote_get).
 */
class UACF7_Mpdf_Safe_Http_Client implements ClientInterface {

	/**
	 * Intercept and handle mPDF remote content requests safely.
	 *
	 * @param RequestInterface $request
	 * @return Response
	 */
	public function sendRequest( RequestInterface $request ) {
		if ( null === $request->getUri() ) {
			return new Response( 400 );
		}

		$url = (string) $request->getUri();

		// 1. Resolve local WordPress files directly from disk
		$local_path = self::url_to_local_path( $url );
		if ( $local_path && @file_exists( $local_path ) && @is_readable( $local_path ) ) {
			$mime = self::get_mime_type( $local_path );
			$data = @file_get_contents( $local_path );
			if ( false !== $data ) {
				return new Response( 200, [ 'Content-Type' => [ $mime ] ], $data );
			}
		}

		// 2. Anti-SSRF check: strictly block private, loopback, and cloud metadata targets
		if ( ! self::is_safe_url( $url ) ) {
			return new Response( 400 );
		}

		// 3. Fetch safe remote content using WordPress HTTP API
		return self::safe_remote_fetch( $url );
	}

	/**
	 * Resolve a URL or path to an existing readable local filesystem path on the WordPress install.
	 *
	 * @param string $url
	 * @return string|false
	 */
	public static function url_to_local_path( $url ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return false;
		}

		// Strip query strings and hash anchors for filesystem check
		$clean_url = preg_replace( '/[?#].*$/', '', $url );

		// Check if it's already an existing readable filesystem path
		if ( @file_exists( $clean_url ) && @is_readable( $clean_url ) ) {
			return self::normalize_path( $clean_url );
		}

		// Handle file:// scheme
		if ( stripos( $clean_url, 'file://' ) === 0 ) {
			$local_file = substr( $clean_url, 7 );
			if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
				$local_file = ltrim( $local_file, '/' );
			}
			if ( @file_exists( $local_file ) && @is_readable( $local_file ) ) {
				return self::normalize_path( $local_file );
			}
		}

		// Clean protocol for URL matching
		$target_url = preg_replace( '#^https?:#i', '', $clean_url );

		// 1. Match WordPress uploads directory
		if ( function_exists( 'wp_upload_dir' ) ) {
			$upload_dir = wp_upload_dir();
			if ( ! empty( $upload_dir['baseurl'] ) && ! empty( $upload_dir['basedir'] ) ) {
				$upload_url = preg_replace( '#^https?:#i', '', $upload_dir['baseurl'] );
				if ( strpos( $target_url, $upload_url ) === 0 ) {
					$rel  = substr( $target_url, strlen( $upload_url ) );
					$path = $upload_dir['basedir'] . $rel;
					if ( @file_exists( $path ) && @is_readable( $path ) ) {
						return self::normalize_path( $path );
					}
				}
			}
		}

		// 2. Match content_url() / WP_CONTENT_DIR
		if ( function_exists( 'content_url' ) && defined( 'WP_CONTENT_DIR' ) ) {
			$c_url = preg_replace( '#^https?:#i', '', content_url() );
			if ( strpos( $target_url, $c_url ) === 0 ) {
				$rel  = substr( $target_url, strlen( $c_url ) );
				$path = WP_CONTENT_DIR . $rel;
				if ( @file_exists( $path ) && @is_readable( $path ) ) {
					return self::normalize_path( $path );
				}
			}
		}

		// 3. Match relative or root-relative wp-content paths: /wp-content/... or wp-content/...
		if ( defined( 'WP_CONTENT_DIR' ) && preg_match( '#^/?wp-content/(.+)$#i', $clean_url, $matches ) ) {
			$path = WP_CONTENT_DIR . '/' . $matches[1];
			if ( @file_exists( $path ) && @is_readable( $path ) ) {
				return self::normalize_path( $path );
			}
		}

		// 4. Match site_url() / ABSPATH
		if ( function_exists( 'site_url' ) && defined( 'ABSPATH' ) ) {
			$s_url = preg_replace( '#^https?:#i', '', site_url() );
			if ( strpos( $target_url, $s_url ) === 0 ) {
				$rel  = substr( $target_url, strlen( $s_url ) );
				$path = ABSPATH . ltrim( $rel, '/' );
				if ( @file_exists( $path ) && @is_readable( $path ) ) {
					return self::normalize_path( $path );
				}
			}
		}

		return false;
	}

	/**
	 * Verify that a remote URL is safe to fetch and not targeting private, loopback, or metadata addresses.
	 *
	 * @param string $url
	 * @return bool
	 */
	public static function is_safe_url( $url ) {
		if ( ! is_string( $url ) || empty( $url ) ) {
			return false;
		}

		// Reject carriage returns, line feeds, tabs, or null bytes
		if ( preg_match( '/[\r\n\t\0]/', $url ) ) {
			return false;
		}

		// WordPress built-in validation
		if ( function_exists( 'wp_http_validate_url' ) && ! wp_http_validate_url( $url ) ) {
			return false;
		}

		$parsed = parse_url( $url );
		if ( empty( $parsed['scheme'] ) || empty( $parsed['host'] ) ) {
			return false;
		}

		$scheme = strtolower( $parsed['scheme'] );
		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			return false;
		}

		// Disallow user:pass credentials in URL
		if ( isset( $parsed['user'] ) || isset( $parsed['pass'] ) ) {
			return false;
		}

		// Disallow non-standard safe ports (only 80, 443, 8080 allowed)
		if ( isset( $parsed['port'] ) && ! in_array( (int) $parsed['port'], [ 80, 443, 8080 ], true ) ) {
			return false;
		}

		$host = strtolower( trim( $parsed['host'] ) );

		// Disallow localhost / known special local hostnames
		if ( in_array( $host, [ 'localhost', '127.0.0.1', '::1', '0.0.0.0' ], true ) ) {
			return false;
		}

		// Block dangerous domain suffixes (PHP 7.4+ compatible)
		$blocked_suffixes = [ '.localhost', '.local', '.internal', '.corp', '.home', '.lan' ];
		foreach ( $blocked_suffixes as $suffix ) {
			$len = strlen( $suffix );
			if ( substr( $host, -$len ) === $suffix ) {
				return false;
			}
		}

		// Block cloud metadata hostnames
		if ( strpos( $host, '169.254' ) !== false || strpos( $host, 'metadata.google' ) !== false || strpos( $host, 'metadata.azure' ) !== false ) {
			return false;
		}

		// Resolve host to IP addresses
		$ip_list = [];
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			$ip_list[] = $host;
		} else {
			$records = @dns_get_record( $host, DNS_A + DNS_AAAA );
			if ( ! empty( $records ) && is_array( $records ) ) {
				foreach ( $records as $rec ) {
					if ( isset( $rec['ip'] ) ) {
						$ip_list[] = $rec['ip'];
					}
					if ( isset( $rec['ipv6'] ) ) {
						$ip_list[] = $rec['ipv6'];
					}
				}
			}
			if ( empty( $ip_list ) ) {
				$ip = @gethostbyname( $host );
				if ( $ip && $ip !== $host ) {
					$ip_list[] = $ip;
				}
			}
		}

		if ( empty( $ip_list ) ) {
			return false;
		}

		// Validate every resolved IP
		foreach ( $ip_list as $ip ) {
			// Reject private and reserved IP ranges
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return false;
			}

			// Explicit checks for IPv4 ranges:
			// 127.0.0.0/8 (loopback), 169.254.0.0/16 (link-local/metadata), 0.0.0.0/8 (current net)
			if ( strpos( $ip, '127.' ) === 0 || strpos( $ip, '169.254.' ) === 0 || strpos( $ip, '0.' ) === 0 ) {
				return false;
			}

			// IPv6 checks: loopback, link-local, unique local
			$ip_lower = strtolower( $ip );
			if ( $ip_lower === '::1' || strpos( $ip_lower, 'fe80:' ) === 0 || strpos( $ip_lower, 'fc00:' ) === 0 || strpos( $ip_lower, 'fd00:' ) === 0 ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Perform a safe remote HTTP request using WordPress HTTP API.
	 *
	 * @param string $url
	 * @return Response
	 */
	private static function safe_remote_fetch( $url ) {
		if ( function_exists( 'wp_safe_remote_get' ) ) {
			$response = wp_safe_remote_get( $url, [
				'timeout'     => 5,
				'redirection' => 2,
				'sslverify'   => true,
				'headers'     => [
					'Accept' => 'image/*, text/css, */*',
				],
			] );

			if ( is_wp_error( $response ) ) {
				return new Response( 400 );
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( $code < 200 || $code >= 300 ) {
				return new Response( $code ?: 400 );
			}

			$body        = wp_remote_retrieve_body( $response );
			$headers_raw = wp_remote_retrieve_headers( $response );
			$headers     = [];
			if ( ! empty( $headers_raw ) ) {
				foreach ( $headers_raw as $k => $v ) {
					$headers[ $k ] = is_array( $v ) ? $v : [ $v ];
				}
			}

			return new Response( 200, $headers, $body );
		}

		return new Response( 400 );
	}

	/**
	 * Detect MIME type for local files.
	 *
	 * @param string $path
	 * @return string
	 */
	private static function get_mime_type( $path ) {
		if ( function_exists( 'wp_check_filetype' ) ) {
			$ft = wp_check_filetype( $path );
			if ( ! empty( $ft['type'] ) ) {
				return $ft['type'];
			}
		}
		$ext   = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		$mimes = [
			'png'  => 'image/png',
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'gif'  => 'image/gif',
			'webp' => 'image/webp',
			'svg'  => 'image/svg+xml',
			'css'  => 'text/css',
		];
		return isset( $mimes[ $ext ] ) ? $mimes[ $ext ] : 'application/octet-stream';
	}

	/**
	 * Normalize filesystem path.
	 *
	 * @param string $path
	 * @return string
	 */
	private static function normalize_path( $path ) {
		if ( function_exists( 'wp_normalize_path' ) ) {
			return wp_normalize_path( $path );
		}
		$path = str_replace( '\\', '/', $path );
		return preg_replace( '|(?<=.)/+|', '/', $path );
	}
}


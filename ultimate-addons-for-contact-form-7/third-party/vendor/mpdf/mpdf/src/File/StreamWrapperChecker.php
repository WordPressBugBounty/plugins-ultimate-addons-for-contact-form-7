<?php

namespace Mpdf\File;

use Mpdf\Mpdf;

final class StreamWrapperChecker
{

	private $mpdf;

	public function __construct(Mpdf $mpdf)
	{
		$this->mpdf = $mpdf;
	}

	/**
	 * @param string $filename
	 * @return bool
	 * @since 7.1.8
	 */
	public function hasBlacklistedStreamWrapper($filename)
	{
		if (strpos($filename, '://') !== false) {
			$wrappers = stream_get_wrappers();
			$whitelistStreamWrappers = $this->getWhitelistedStreamWrappers();
			$normalized = ltrim($filename, ':/\\ ');
			foreach ($wrappers as $wrapper) {
				if (in_array($wrapper, $whitelistStreamWrappers)) {
					continue;
				}

				if (stripos($filename, $wrapper . '://') === 0 || stripos($normalized, $wrapper . '://') === 0) {
					return true;
				}
			}
		}

		return false;
	}

	public function getWhitelistedStreamWrappers()
	{
		return array_diff($this->mpdf->whitelistStreamWrappers, ['phar']); // remove 'phar' (security issue)
	}

}

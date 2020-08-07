<?php declare(strict_types = 1);
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * Helper class for sign / encrypt data.
 */
class CEncryptHelper {

	/**
	 * Signature algorithm.
	 */
	public const SIGN_ALGO = 'aes-256-ecb';

	/**
	 * Session secret key.
	 *
	 * @static
	 *
	 * @var string
	 */
	private static $key;

	/**
	 * Return session key.
	 *
	 * @static
	 *
	 * @return string|null
	 */
	private static function getKey(): ?string {
		if (!self::$key) {
			$config = select_config();
			// This if contain copy in CEncryptedCookieSession class.
			if ($config['session_key'] === '') {
				self::$key = self::generateKey();

				if (!self::updateKey(self::$key)) {
					return null;
				}

				return self::$key;
			}

			self::$key = $config['session_key'];
		}

		return self::$key;
	}

	/**
	 * Timing attack safe string comparison.
	 *
	 * @static
	 *
	 * @param string $known_string
	 * @param string $user_string
	 *
	 * @return boolean
	 */
	public static function checkSign(string $known_string, string $user_string): bool {
		return hash_equals($known_string, $user_string);
	}

	/**
	 * Encrypt string with session key.
	 *
	 * @static
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	public static function sign(string $data): string {
		$key = self::getKey();

		return openssl_encrypt($data, self::SIGN_ALGO, $key);
	}

	/**
	 * Generate random 16 bytes key.
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function generateKey(): string {
		return bin2hex(openssl_random_pseudo_bytes(16));
	}

	/**
	 * Update secret session key.
	 *
	 * @static
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public static function updateKey(string $key): bool {
		return DBexecute(
			'UPDATE config'.
			' SET session_key='.zbx_dbstr($key).
			' WHERE '.dbConditionInt('configid', [1])
		);
	}
}

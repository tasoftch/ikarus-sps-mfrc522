<?php
/*
 * Copyright (c) 2022 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Ikarus\MiFare\Authentication;


class BasicAuthentication implements AuthenticationInterface
{
	protected $authType;
	protected $authKey;

	/**
	 * BasicAuthentication constructor.
	 * @param $authType
	 * @param $authKey
	 */
	public function __construct($authType = self::AUTH_TYPE_A, $authKey = [0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF])
	{
		$this->authType = $authType;
		$this->authKey = $authKey;
	}

	/**
	 * @param array $key
	 * @return static
	 */
	public static function A(array $key) {
		return new static(static::AUTH_TYPE_A, $key);
	}

	/**
	 * @param array $key
	 * @return static
	 */
	public static function B(array $key) {
		return new static(static::AUTH_TYPE_B, $key);
	}

	/**
	 * @return static
	 */
	public static function defaultAuthentication() {
		return new static();
	}


	/**
	 * @inheritDoc
	 */
	public function getAuthenticationType(): int
	{
		return $this->authType;
	}

	/**
	 * @inheritDoc
	 */
	public function getKey(): array
	{
		return $this->authKey;
	}
}
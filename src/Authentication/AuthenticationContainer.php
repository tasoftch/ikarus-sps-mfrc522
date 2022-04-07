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


use Ikarus\MiFare\Exception\IncompleteAuthContainerException;

class AuthenticationContainer implements AuthenticationContainerInterface
{
	private $authentications = [];
	private $accessBits = [0x0, 0x0, 0x0, 0x4];

	public $userByte = 0x0;

	/**
	 * AuthenticationContainer constructor.
	 * @param array $authentications
	 * @param int[] $accessBits
	 */
	public function __construct(array $authentications, array $accessBits = NULL)
	{
		foreach($authentications as $authentication) {
			if($authentication instanceof AuthenticationInterface) {
				$this->authentications[ $authentication->getAuthenticationType() ] = $authentication;
			}
		}

		if(!$this->authentications[ AuthenticationInterface::AUTH_TYPE_A ] || !$this->authentications[ AuthenticationInterface::AUTH_TYPE_B ])
			throw (new IncompleteAuthContainerException("Container must have a keyA and keyB authentication"));

		if(is_array($accessBits) && count($accessBits) == 4)
			$this->accessBits = $accessBits;
	}


	/**
	 * @inheritDoc
	 */
	public function getAuthentication(int $type): AuthenticationInterface
	{
		return $this->authentications[ $type ];
	}

	/**
	 * @inheritDoc
	 */
	public function getAccessBits(): array
	{
		list($access0, $access1, $access2, $access3) = $this->accessBits;
		return [
			((~$access3 & 0x2) << 6) | ((~$access2 & 0x2) << 5) | ((~$access1 & 0x2) << 4) | ((~$access0 & 0x2) << 3) |
			((~$access3 & 0x1) << 3) | ((~$access2 & 0x1) << 2) | ((~$access1 & 0x1) << 1) | ((~$access0 & 0x1) << 0),
			(($access3 & 0x1) << 7) | (($access2 & 0x1) << 6) | (($access1 & 0x1) << 5) | (($access0 & 0x1) << 4) |
			((~$access3 & 0x4) << 1) | ((~$access2 & 0x4) << 0) | ((~$access1 & 0x4) >> 1) | ((~$access0 & 0x4) >>2),
			(($access3 & 0x4) << 5) | (($access2 & 0x4) << 4) | (($access1 & 0x4) << 3) | (($access0 & 0x4) << 2) |
			(($access3 & 0x2) << 2) | (($access2 & 0x2) << 1) | (($access1 & 0x2)) | (($access0 & 0x2) >> 1),
			$this->userByte & 0xFF
		];
	}

	/**
	 * Use the self::DB_ACCESS_* constants to grant access to a data block.
	 *
	 * @param int $access
	 * @param int $block
	 */
	public function setDataBlockAccess(int $access, int $block) {
		$this->accessBits[$block % 3] = $access;
	}

	/**
	 * Use the self::DT_ACCESS_* constants to grant access to the trailer block.
	 *
	 * @param int $access
	 */
	public function setTrailerBlockAccess(int $access) {
		$this->accessBits[3] = $access;
	}
}
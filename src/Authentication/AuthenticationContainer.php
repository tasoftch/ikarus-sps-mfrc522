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
	protected $authentications = [];
	protected $accessBits;

	/**
	 * AuthenticationContainer constructor.
	 * @param array $authentications
	 * @param AccessBits $accessBits
	 */
	public function __construct(array $authentications, AccessBits $accessBits = NULL)
	{
		foreach($authentications as $authentication) {
			if($authentication instanceof AuthenticationInterface) {
				$this->authentications[ $authentication->getAuthenticationType() ] = $authentication;
			}
		}

		if(!$this->authentications[ AuthenticationInterface::AUTH_TYPE_A ] || !$this->authentications[ AuthenticationInterface::AUTH_TYPE_B ])
			throw (new IncompleteAuthContainerException("Container must have a keyA and keyB authentication"));

		$this->accessBits = $accessBits ?: new AccessBits();
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
	public function getAccessBits(): AccessBits
	{
		return $this->accessBits;
	}
}
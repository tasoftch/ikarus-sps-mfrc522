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

namespace Ikarus\MiFare\UID;


use Ikarus\MiFare\Exception\InvalidUIDChecksumException;
use Ikarus\MiFare\Exception\UnknownUIDException;

abstract class AbstractCardID implements CardIDInterface
{
	protected $bytes;
	protected $checksum;

	/**
	 * @param array $bytes
	 * @return static
	 */
	public static function makeID(array $bytes) {
		if(count($bytes) == 5)
			return new CardID4($bytes);
		if(count($bytes) == 8)
			return new CardID7($bytes);

		throw (new UnknownUIDException("Unknown card UID"))->setUUID($bytes);
	}

	/**
	 * AbstractCardID constructor.
	 * @param array $bytes
	 */
	public function __construct(array $bytes)
	{
		$checkByte = array_pop($bytes);
		$check = 0;
		foreach($bytes as $byte)
			$check ^= $byte;

		if($check != $checkByte)
			throw (new InvalidUIDChecksumException("Identifier checksum is wrong"))->setUUID($bytes);
		$this->bytes = $bytes;
		$this->checksum = $checkByte;
	}

	/**
	 * @return array
	 */
	public function getIdentifierBytes(): array
	{
		return $this->bytes;
	}

	public function getChecksum(): int
	{
		return $this->checksum;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getIdentifier();
	}
}
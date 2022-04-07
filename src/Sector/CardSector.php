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

namespace Ikarus\MiFare\Sector;


use ArrayAccess;
use Ikarus\MiFare\Authentication\AuthenticationInterface;

class CardSector implements SectorInterface, ArrayAccess
{
	/** @var int */
	protected $id;
	/** @var array */
	protected $bytes = [
		0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0,  // Datablock 0
		0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0,  // Datablock 1
		0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0  //  Datablock 2
	];
	/** @var AuthenticationInterface */
	protected $authentication;

	/**
	 * CardSector constructor.
	 * @param int $id
	 * @param array|string $bytes
	 * @param AuthenticationInterface $authentication
	 */
	public function __construct(int $id, $bytes, AuthenticationInterface $authentication)
	{
		$this->id = $id % 32;
		if(is_string($bytes)) {
			for($idx=0;$idx < min(strlen($bytes), count($this->bytes));$idx++)
				$this->bytes[$idx] = ord($bytes[$idx]);
		} elseif(is_array($bytes)) {
			for($idx=0;$idx < min(count($bytes), count($this->bytes));$idx++)
				$this->bytes[$idx] = $bytes[$idx] % 0xFF;
		}
		$this->authentication = $authentication;
	}


	/**
	 * @inheritDoc
	 */
	public function getSectorID(): int
	{
		return $this->id;
	}

	/**
	 * @inheritDoc
	 */
	public function getBytes(): array
	{
		return $this->bytes;
	}

	/**
	 * @inheritDoc
	 */
	public function getString(): string
	{
		return join("", array_map(function($c){return chr($c);}, $this->getBytes()));
	}

	/**
	 * @inheritDoc
	 */
	public function getAuthentication(): AuthenticationInterface
	{
		return $this->authentication;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetExists($offset)
	{
		return isset($this->bytes[$offset]);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetGet($offset)
	{
		return $this->bytes[$offset] ?? NULL;
	}

	// Can not change the sector content.
	public function offsetSet($offset, $value){}
	public function offsetUnset($offset){}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getString();
	}

	public function __debugInfo()
	{
		return [
			"String" => $this->getString(),
			'BLOCK_0' => join(" ", array_map(function($x){return sprintf("0x%02X", $x);}, array_slice($this->getBytes(), 0, 16))),
			'BLOCK_1' => join(" ", array_map(function($x){return sprintf("0x%02X", $x);}, array_slice($this->getBytes(), 16, 16))),
			'BLOCK_2' => join(" ", array_map(function($x){return sprintf("0x%02X", $x);}, array_slice($this->getBytes(), 32, 16)))
		];
	}
}
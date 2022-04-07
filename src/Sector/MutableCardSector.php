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


use Ikarus\MiFare\Authentication\AuthenticationInterface;

class MutableCardSector extends CardSector implements MutableSectorInterface
{
	private $data_pos = 0;

	/**
	 * MutableCardSector constructor.
	 * Data is optional
	 *
	 * @param int $id
	 * @param AuthenticationInterface $authentication
	 * @param array|string $bytes
	 */
	public function __construct(int $id, AuthenticationInterface $authentication, $bytes = [])
	{
		parent::__construct($id, $bytes, $authentication);
	}

	/**
	 * @inheritDoc
	 */
	public function appendBytes(array $contents)
	{
		foreach($contents as $content) {
			if($this->data_pos >= count($this->bytes))
				return;
			$this->bytes[$this->data_pos++] = $content & 0xFF;
		}
	}

	/**
	 * Resets the sector data
	 */
	public function reset() {
		foreach ($this->bytes as &$byte)
			$byte = 0x20;
		$this->data_pos = 0;
	}

	/**
	 * Sets the sector data to the given bytes
	 *
	 * @param array $bytes
	 */
	public function setBytes(array $bytes) {
		$this->reset();
		$this->appendBytes($bytes);
	}

	/**
	 * Appends the given ASCII bytes of the string to the sector data
	 *
	 * @param string $string
	 */
	public function appendString(string $string) {
		$this->appendBytes(
			array_map(function($c){return ord($c);}, str_split($string))
		);
	}

	/**
	 * Sets the sector data to the ASCII bytes of the passed string
	 *
	 * @param string $string
	 */
	public function setString(string $string) {
		$this->reset();
		$this->appendString($string);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet($offset, $value)
	{
		$offset = ($offset*1) % count($this->bytes);
		$this->bytes[$offset] = $value & 0xFF;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset($offset)
	{
		trigger_error("Can not unset sector data", E_USER_WARNING);
		$this->offsetSet($offset, 0x20);
	}
}
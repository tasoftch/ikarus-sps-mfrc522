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


use InvalidArgumentException;

/**
 * Class AccessBits
 * @package Ikarus\MiFare\Authentication
 *
 * @property int C0
 * @property int C1
 * @property int C2
 * @property int C3
 *
 * @property int userByte
 *
 * @property bool C01
 * @property bool C02
 * @property bool C03
 *
 * @property bool C11
 * @property bool C12
 * @property bool C13
 *
 * @property bool C21
 * @property bool C22
 * @property bool C23
 *
 * @property bool C31
 * @property bool C32
 * @property bool C33
 */
class AccessBits
{
	private $C0 = self::C000;
	private $C1 = self::C000;
	private $C2 = self::C000;
	private $C3 = self::C001;

	const C000				 			= 0b000;
	const C010							= 0b010;
	const C100							= 0b001;
	const C110							= 0b011;
	const C001							= 0b100;
	const C011							= 0b110;
	const C101							= 0b101;
	const C111							= 0b111;

	private $userByte = 0;

	// Access table using keys A and B for data block 0-2
	//
	//			READ	WRITE	INCREMENT	DECREMENT
	// C000 	A|B		A|B		A|B			A|B
	// C010	 	A|B		---		---			---
	// C100		A|B		B		---			---
	// C110		A|B		B		B			A|B
	// C001		A|B		---		---			A|B
	// C011		B		B		---			---
	// C101		B		---		---			---
	// C111		---		---		---			---


	// Access table using keys A and B for trailer block
	//
	//			KEY A			ACCESS BITS		KEY B
	// 			READ	WRITE	READ	WRITE	READ	WRITE
	// C000 	---		A		A		---		A		A
	// C010 	---		---		A		---		A		---
	// C100		---		B		A|B		---		---		B
	// C110		---		---		A|B		---		---		---
	// C001		---		A		A		A		A		A
	// C011		---		B		A|B		B		---		B
	// C101		---		---		A|B		B		---		---
	// C111		---		---		A|B		---		---		---




	/**
	 * Allows access like $bits->C1 = AccessBits::C001
	 * or $bits->C03 = 1  => $bits->C0 == AccessBits::C100
	 *
	 * @param $name
	 * @param $value
	 */
	public function __set($name, $value)
	{
		if(strcasecmp($name, 'userbyte') == 0) {
			$this->userByte = $value & 0xFF;
			return;
		}

		if(preg_match("/^C([0-3])([1-3]?)$/i", $name, $ms)) {
			if($ms[2]) {
				$C = "C$ms[1]";
				if($value)
					$this->$C |= 1<<($ms[2]-1);
				else
					$this->$C &= ~(1<<($ms[2]-1));
			} else {
				$C = "C$ms[1]";
				$this->$C = $value &0x7;
			}
		} else {
			throw new InvalidArgumentException("Can not access $name");
		}
	}

	public function __get($name)
	{
		if(strcasecmp($name, 'userbyte') == 0) {
			return $this->userByte;
		}

		if(preg_match("/^C([0-3])([1-3]?)$/i", $name, $ms)) {
			if($ms[2]) {
				$C = "C$ms[1]";
				return ($this->$C & (1<<($ms[2]-1))) ? true : false;
			} else {
				$C = "C$ms[1]";
				return $this->$C;
			}
		} else {
			throw new InvalidArgumentException("Can not access $name");
		}
	}

	public function getAccessBits(): array {
		list($access0, $access1, $access2, $access3) = [$this->C0, $this->C1, $this->C2, $this->C3];
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
	 * AccessBits constructor.
	 * The constructor distinguishes between 4 bytes (as read access bits from card) or 5 bytes (human readable access bytes written in C0 - C3 and C1 - C3.
	 *
	 * @param array $accessBitsOrBytes
	 */
	public function __construct(array $accessBitsOrBytes = [])
	{
		if(count($accessBitsOrBytes) == 5) {
			// Human readable access bytes
			$this->userByte = array_pop($accessBitsOrBytes) &0xFF;
			$this->C0 = $accessBitsOrBytes[0] & 0x7;
			$this->C1 = $accessBitsOrBytes[1] & 0x7;
			$this->C2 = $accessBitsOrBytes[2] & 0x7;
			$this->C3 = $accessBitsOrBytes[3] & 0x7;
		} elseif(count($accessBitsOrBytes) == 4) {
			// Card access bits
			$this->userByte = array_pop($accessBitsOrBytes) &0xFF;
			list(, $byte1, $byte2) = $accessBitsOrBytes;
			$this->C3 =
				(($byte1>>7) & 0b1) | ((($byte2>>3) & 0b1) << 1) | ((($byte2>>7) & 0b1) << 2);
			$this->C2 =
				(($byte1>>6) & 0b1) | ((($byte2>>2) & 0b1) << 1) | ((($byte2>>6) & 0b1) << 2);
			$this->C1 =
				(($byte1>>5) & 0b1) | ((($byte2>>1) & 0b1) << 1) | ((($byte2>>5) & 0b1) << 2);
			$this->C0 =
				(($byte1>>4) & 0b1) | (($byte2 & 0b1) << 1) | ((($byte2>>4) & 0b1) << 2);
		}
	}



	public function __debugInfo()
	{
		return [
			"C0" => strrev( sprintf("%03b", $this->C0) ),
			"C1" => strrev( sprintf("%03b", $this->C1) ),
			"C2" => strrev( sprintf("%03b", $this->C2) ),
			"C3" => strrev( sprintf("%03b", $this->C3) ),
			"ACB" => join(" ", array_map(function($v){return sprintf("0x%02X", $v);}, $this->getAccessBits()))
		];
	}
}
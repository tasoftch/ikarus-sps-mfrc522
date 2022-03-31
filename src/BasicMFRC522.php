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

namespace Ikarus\SPS\SPI;


abstract class BasicMFRC522 extends AbstractMFRC522
{
	public function request(int $mode) {
		$this->_write(self::BitFramingReg, 0x07);
		list($status,,$bits) = $this->_sendToCard(self::PCD_TRANSCEIVE, [$mode]);


		if($status != self::MI_OK || $bits != 0x10)
			$status = self::MI_ERR;
		return [$status, $bits];
	}

	public function anticoll() {
		$this->_write(self::BitFramingReg, 0x0);

		list($status, $data, $bits) = $this->_sendToCard(self::PCD_TRANSCEIVE, [ self::PICC_ANTICOLL, 0x20 ]);

		if($status == self::MI_OK) {
			if(count($data) == 5) {
				$check = 0;
				for($e=0;$e<4;$e++)
					$check ^= $data[$e];

				if($check != $data[4])
					$status = self::MI_ERR;
			} else
				$status = self::MI_ERR;
		}

		return [$status, $data];
	}

	public function calculateCRC(array $data) {
		$this->_clearBitMask(self::DivIrqReg, 0x04);
		$this->_setBitMask(self::FIFOLevelReg, 0x80);

		foreach($data as $d)
			$this->_write(self::FIFODataReg, $d);

		$this->_write(self::CommandReg, self::PCD_CALCCRC);
		$i = 0xFF;
		while (1) {
			$n = $this->_read(self::DivIrqReg);
			$i--;
			if(!(($i >= 0) and ! ($n & 0x04) ) )
				break;
		}

		$data = [];
		$data[] = $this->_read(self::CRCResultRegL);
		$data[] = $this->_read(self::CRCResultRegM);
		return $data;
	}

	public function selectTag(array $serNum) {
		$buffer = [
			self::PICC_SElECTTAG,
			0x70
		];
		foreach($serNum as $s)
			$buffer[] = $s;

		list($l, $m) = $this->calculateCRC($buffer);
		$buffer[] = $l;
		$buffer[] = $m;

		list($status, $data, $bits) = $this->_sendToCard(self::PCD_TRANSCEIVE, $buffer);
		if($status == self::MI_OK && $bits == 0x18)
			return $data[0];
		return 0;
	}

	public function authenticate(array $serialNumber, int $authMode, int $blockAddr = 7, array $key = [0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xF]) {
		$buffer = [$authMode, $blockAddr];
		foreach($key as $k)
			$buffer[] = $k;
		for ($e=0;$e<4;$e++)
			$buffer[] = $serialNumber[$e];
		list($status) = $this->_sendToCard(self::PCD_AUTHENT, $buffer);
		$reg = $this->_read(self::Status2Reg);
		if(!(($reg & 0x08) != 0))
			return self::MI_ERR;
		return $status;
	}

	public function stopCrypro() {
		$this->_clearBitMask(self::Status2Reg, 0x08);
	}

	public function read(int $blockAddr) {
		$data = [self::PICC_READ, $blockAddr];
		list($l, $m) = $this->calculateCRC($data);
		$data[] = $l;
		$data[] = $m;
		list($status, $backData) = $this->_sendToCard(self::PCD_TRANSCEIVE, $data);
		if($status != self::MI_OK)
			return $status;

		return count($backData) != 16 ? -1 : $backData;
	}

	public function write(int $blockAddr, array $writeData)
	{
		$data = [self::PICC_WRITE, $blockAddr];
		list($l, $m) = $this->calculateCRC($data);
		$data[] = $l;
		$data[] = $m;
		list($status, $backData, $bits) = $this->_sendToCard(self::PCD_TRANSCEIVE, $data);
		if ($status != self::MI_OK && $bits != 4 && ($backData[0] & 0x0F) != 0x0A)
			return self::MI_ERR;

		$data = [];
		for ($e = 0; $e < 16; $e++)
			$data[] = $writeData[$e] ?? 0;
		list($l, $m) = $this->calculateCRC($data);
		$data[] = $l;
		$data[] = $m;

		list($status, $backData, $bits) = $this->_sendToCard(self::PCD_TRANSCEIVE, $data);
		if ($status != self::MI_OK && $bits != 4 && ($backData[0] & 0x0F) != 0x0A)
			return self::MI_ERR;
		return $status;
	}
}
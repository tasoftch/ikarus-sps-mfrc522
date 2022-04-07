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

namespace Ikarus\MiFare;

abstract class AbstractRC522CardReader extends AbstractBasicCardReader
{
	const MAX_LEN = 16;

	const PCD_IDLE = 0x00;
	const PCD_AUTHENT = 0x0E;
	const PCD_RECEIVE = 0x08;
	const PCD_TRANSMIT = 0x04;
	const PCD_TRANSCEIVE = 0x0C;
	const PCD_RESETPHASE = 0x0F;
	const PCD_CALCCRC = 0x03;

	const Reserved00 = 0x00;
	const CommandReg = 0x01;
	const CommIEnReg = 0x02;
	const DivlEnReg = 0x03;
	const CommIrqReg = 0x04;
	const DivIrqReg = 0x05;
	const ErrorReg = 0x06;
	const Status1Reg = 0x07;
	const Status2Reg = 0x08;
	const FIFODataReg = 0x09;
	const FIFOLevelReg = 0x0A;
	const WaterLevelReg = 0x0B;
	const ControlReg = 0x0C;
	const BitFramingReg = 0x0D;
	const CollReg = 0x0E;
	const Reserved01 = 0x0F;

	const Reserved20 = 0x20;
	const CRCResultRegM = 0x21;
	const CRCResultRegL = 0x22;
	const Reserved21 = 0x23;
	const ModWidthReg = 0x24;
	const Reserved22 = 0x25;
	const RFCfgReg = 0x26;
	const GsNReg = 0x27;
	const CWGsPReg = 0x28;
	const ModGsPReg = 0x29;
	const TModeReg = 0x2A;
	const TPrescalerReg = 0x2B;
	const TReloadRegH = 0x2C;
	const TReloadRegL = 0x2D;
	const TCounterValueRegH = 0x2E;
	const TCounterValueRegL = 0x2F;

	const Reserved10 = 0x10;
	const ModeReg = 0x11;
	const TxModeReg = 0x12;
	const RxModeReg = 0x13;
	const TxControlReg = 0x14;
	const TxAutoReg = 0x15;
	const TxSelReg = 0x16;
	const RxSelReg = 0x17;
	const RxThresholdReg = 0x18;
	const DemodReg = 0x19;
	const Reserved11 = 0x1A;
	const Reserved12 = 0x1B;
	const MifareReg = 0x1C;
	const Reserved13 = 0x1D;
	const Reserved14 = 0x1E;
	const SerialSpeedReg = 0x1F;

	/**
	 * @param int $addr
	 * @param int $value
	 */
	abstract protected function writeRegister(int $addr, int $value);

	/**
	 * @param int $addr
	 * @return int
	 */
	abstract protected function readRegister(int $addr): int;


	/**
	 * @param int $mode
	 * @return array
	 */
	protected function request(int $mode): array
	{
		$this->writeRegister(self::BitFramingReg, 0x07);
		list($status,,$bits) = $this->sendToCard(self::PCD_TRANSCEIVE, [$mode]);


		if($status != self::MI_OK || $bits != 0x10)
			$status = self::MI_ERR;
		return [$status, $bits];
	}

	/**
	 * @return array
	 */
	protected function anticoll(): array
	{
		$this->writeRegister(self::BitFramingReg, 0x0);

		list($status, $data) = $this->sendToCard(self::PCD_TRANSCEIVE, [ self::PICC_ANTICOLL, 0x20 ]);

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

	/**
	 * @param array $data
	 * @return array
	 */
	public function calculateCRC(array $data): array
	{
		$this->clearBitmask(self::DivIrqReg, 0x04);
		$this->setBitmask(self::FIFOLevelReg, 0x80);

		foreach($data as $d)
			$this->writeRegister(self::FIFODataReg, $d);

		$this->writeRegister(self::CommandReg, self::PCD_CALCCRC);
		$i = 0xFF;
		while (1) {
			$n = $this->readRegister(self::DivIrqReg);
			$i--;
			if(!(($i >= 0) and ! ($n & 0x04) ) )
				break;
		}

		$data = [];
		$data[] = $this->readRegister(self::CRCResultRegL);
		$data[] = $this->readRegister(self::CRCResultRegM);
		return $data;
	}

	/**
	 * @param array $cardID
	 * @return int|mixed
	 */
	protected function selectCardID(array $cardID)
	{
		$buffer = [
			self::PICC_SElECTTAG,
			0x70
		];
		foreach($cardID as $s)
			$buffer[] = $s;

		list($l, $m) = $this->calculateCRC($buffer);
		$buffer[] = $l;
		$buffer[] = $m;

		list($status, $data, $bits) = $this->sendToCard(self::PCD_TRANSCEIVE, $buffer);
		if($status == self::MI_OK && $bits == 0x18)
			return $data[0];
		return 0;
	}

	/**
	 * @param array $cardID
	 * @param int $authMode
	 * @param int $blockAddr
	 * @param array $key
	 * @return int
	 */
	protected function authenticate(array $cardID, int $authMode, int $blockAddr, array $key): int
	{
		$buffer = [$authMode, $blockAddr];
		foreach($key as $k)
			$buffer[] = $k;
		foreach($cardID as $s)
			$buffer[] = $s;
		list($status) = $this->sendToCard(self::PCD_AUTHENT, $buffer);
		$reg = $this->readRegister(self::Status2Reg);
		if(!(($reg & 0x08) != 0))
			return self::MI_ERR;
		return $status;
	}

	/**
	 *
	 */
	protected function stopCrypto()
	{
		$this->clearBitmask(self::Status2Reg, 0x08);
	}

	/**
	 * @param int $blockAddr
	 * @return array
	 */
	protected function read(int $blockAddr): array
	{
		$data = [self::PICC_READ, $blockAddr];
		list($l, $m) = $this->calculateCRC($data);
		$data[] = $l;
		$data[] = $m;
		list($status, $backData) = $this->sendToCard(self::PCD_TRANSCEIVE, $data);
		if($status != self::MI_OK)
			return [];

		return count($backData) != 16 ? [] : $backData;
	}

	/**
	 * @param int $blockAddr
	 * @param array $data
	 * @return int
	 */
	protected function write(int $blockAddr, array $writeData): int
	{
		$data = [self::PICC_WRITE, $blockAddr];
		list($l, $m) = $this->calculateCRC($data);
		$data[] = $l;
		$data[] = $m;
		list($status, $backData, $bits) = $this->sendToCard(self::PCD_TRANSCEIVE, $data);
		if ($status != self::MI_OK && $bits != 4 && ($backData[0] & 0x0F) != 0x0A)
			return self::MI_ERR;

		$data = [];
		for ($e = 0; $e < 16; $e++)
			$data[] = $writeData[$e] ?? 0;
		list($l, $m) = $this->calculateCRC($data);
		$data[] = $l;
		$data[] = $m;

		list($status, $backData, $bits) = $this->sendToCard(self::PCD_TRANSCEIVE, $data);
		if ($status != self::MI_OK && $bits != 4 && ($backData[0] & 0x0F) != 0x0A)
			return self::MI_ERR;

		return $status;
	}

	/**
	 *
	 */
	public function antennaOn() {
		$v = $this->readRegister(self::TxControlReg);
		if(~($v & 0x03))
			$this->setBitmask(self::TxControlReg, 0x03);
	}

	/**
	 *
	 */
	public function antennaOff() {
		$this->clearBitmask(self::TxControlReg, 0x03);
	}

	/**
	 * Sends data to the card and returns an array containing [status, backData, dataLen]
	 *
	 * @param int $command
	 * @param array $data
	 * @return array
	 */
	protected function sendToCard(int $command, array $data): array {
		$status = self::MI_ERR;
		$irqEn = 0x00;
		$waitIRq = 0x00;


		if($command == self::PCD_AUTHENT) {
			$irqEn = 0x12;
			$waitIRq = 0x10;
		} elseif($command == self::PCD_TRANSCEIVE) {
			$irqEn = 0x77;
			$waitIRq = 0x30;
		}

		$this->writeRegister(self::CommIEnReg, $irqEn|0x80);
		$this->clearBitmask(self::CommIrqReg, 0x80);
		$this->setBitmask(self::FIFOLevelReg, 0x80);

		$this->writeRegister(self::CommandReg, self::PCD_IDLE);

		foreach($data as $d) {
			$this->writeRegister(self::FIFODataReg, $d);
		}

		$this->writeRegister(self::CommandReg, $command);

		if($command == self::PCD_TRANSCEIVE)
			$this->setBitmask(self::BitFramingReg, 0x80);

		$i = 2000;
		while (1) {
			$n = $this->readRegister(self::CommIrqReg);

			$i--;
			if( $i<1 || ($n & $waitIRq) || ($n & 0x1) )
				break;
		}

		$this->clearBitmask(self::BitFramingReg, 0x80);

		$data = [];
		$dataLen = 0;

		if($i != 0) {
			$err = $this->readRegister(self::ErrorReg);

			if( ($err & 0x1B) == 0x0) {
				$status = self::MI_OK;

				if($n & $irqEn & 0x01)
					$status = self::MI_NOTAGERR;

				if($command == self::PCD_TRANSCEIVE) {
					$n = $this->readRegister(self::FIFOLevelReg);
					$lastBits = $this->readRegister(self::ControlReg) & 0x07;

					if($lastBits != 0) {
						$dataLen = ($n - 1) * 8 + $lastBits;
					} else {
						$dataLen = $n*8;
					}

					$n = max(1, min($n, self::MAX_LEN));
					for($e=0;$e<$n;$e++)
						$data[] = $this->readRegister(self::FIFODataReg);
				}
			} else {
				$status = self::MI_ERR;
			}
		}

		return [$status, $data, $dataLen];
	}

	/**
	 * @param int $register
	 * @param int $mask
	 */
	protected function setBitmask(int $register, int $mask)
	{
		$v = $this->readRegister($register);
		$this->writeRegister($register, $v | $mask);
	}

	/**
	 * @param int $register
	 * @param int $mask
	 */
	protected function clearBitmask(int $register, int $mask)
	{
		$v = $this->readRegister($register);
		$this->writeRegister($register, $v & ~$mask);
	}
}
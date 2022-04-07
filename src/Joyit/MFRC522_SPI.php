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

namespace Ikarus\MiFare\Joyit;


use Ikarus\MiFare\AbstractRC522CardReader;
use Ikarus\Raspberry\Pin\OutputPinInterface;
use TASoft\Bus\SPI;

class MFRC522_SPI extends AbstractRC522CardReader
{
	/** @var SPI */
	protected $device;
	/** @var OutputPinInterface */
	protected $resetPin;

	protected $debug = 0;


	/**
	 * MFRC522 constructor.
	 * @param SPI $device
	 * @param OutputPinInterface $resetPin
	 */
	public function __construct(SPI $device, OutputPinInterface $resetPin)
	{
		$resetPin->setValue(0);
		$this->device = $device;
		$this->resetPin = $resetPin;
		$resetPin->setValue(1);

		$this->writeRegister(self::CommandReg, self::PCD_RESETPHASE);
		$this->writeRegister(self::TModeReg, 0x8D);
		$this->writeRegister(self::TPrescalerReg, 0x3E);
		$this->writeRegister(self::TReloadRegL, 30);
		$this->writeRegister(self::TReloadRegH, 0);

		$this->writeRegister(self::TxAutoReg, 0x40);
		$this->writeRegister(self::ModeReg, 0x3D);

		$this->antennaOn();
	}

	public function __destruct()
	{
		$this->device->close();
	}

	/**
	 * @inheritDoc
	 */
	protected function writeRegister(int $addr, int $value)
	{
		if($this->debug)
			echo "W $addr, $value\n";
		$this->device->write([ ($addr << 1) & 0x7E, $value ]);
	}

	/**
	 * @inheritDoc
	 */
	protected function readRegister(int $addr): int
	{
		$value = $this->device->transfer([(($addr << 1) & 0x7E) | 0x80, 0]);
		if($this->debug)
			echo "R $addr, $value[1]\n";
		return $value[1] ?? -1;
	}
}
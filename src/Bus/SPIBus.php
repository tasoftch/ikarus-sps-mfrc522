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

namespace Ikarus\MiFare\Bus;


use Ikarus\Raspberry\Pin\OutputPinInterface;
use TASoft\Bus\SPI;

class SPIBus implements BusInterface
{
	/** @var SPI */
	protected $device;
	/** @var OutputPinInterface */
	protected $resetPin;

	public $debug = 0;

	/**
	 * MFRC522_SPI constructor.
	 * @param SPI $device
	 * @param OutputPinInterface $resetPin
	 */
	public function __construct(SPI $device, OutputPinInterface $resetPin)
	{
		$resetPin->setValue(0);
		$this->device = $device;
		$this->resetPin = $resetPin;
		$resetPin->setValue(1);
	}


	/**
	 * @inheritDoc
	 */
	public function read(int $addr): int
	{
		$value = $this->device->transfer([(($addr << 1) & 0x7E) | 0x80, 0]);
		if($this->debug)
			echo "R $addr, $value[1]\n";
		return $value[1] ?? -1;
	}

	/**
	 * @inheritDoc
	 */
	public function write(int $addr, int $value)
	{
		if($this->debug)
			echo "W $addr, $value\n";
		$this->device->write([ ($addr << 1) & 0x7E, $value ]);
	}

	/**
	 * @inheritDoc
	 */
	public function reset()
	{
	}

	/**
	 * @inheritDoc
	 */
	public function close()
	{
		$this->resetPin->setValue(0);
		$this->device->close();
	}
}
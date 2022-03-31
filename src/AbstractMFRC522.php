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

namespace Ikarus\SPS;


use Ikarus\Raspberry\Pin\OutputPinInterface;
use TASoft\Bus\SPI;

abstract class AbstractMFRC522
{
	const MAX_LEN = 16;

	const PCD_IDLE = 0x00;
	const PCD_AUTHENT = 0x0E;
	const PCD_RECEIVE = 0x08;
	const PCD_TRANSMIT = 0x04;
	const PCD_TRANSCEIVE = 0x0C;
	const PCD_RESETPHASE = 0x0F;
	const PCD_CALCCRC = 0x03;

	const PICC_REQIDL = 0x26;
	const PICC_REQALL = 0x52;
	const PICC_ANTICOLL = 0x93;
	const PICC_SElECTTAG = 0x93;
	const PICC_AUTHENT1A = 0x60;
	const PICC_AUTHENT1B = 0x61;
	const PICC_READ = 0x30;
	const PICC_WRITE = 0xA0;
	const PICC_DECREMENT = 0xC0;
	const PICC_INCREMENT = 0xC1;
	const PICC_RESTORE = 0xC2;
	const PICC_TRANSFER = 0xB0;
	const PICC_HALT = 0x50;

	const MI_OK = 0;
	const MI_NOTAGERR = 1;
	const MI_ERR = 2;

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

	const Reserved30 = 0x30;
	const TestSel1Reg = 0x31;
	const TestSel2Reg = 0x32;
	const TestPinEnReg = 0x33;
	const TestPinValueReg = 0x34;
	const TestBusReg = 0x35;
	const AutoTestReg = 0x36;
	const VersionReg = 0x37;
	const AnalogTestReg = 0x38;
	const TestDAC1Reg = 0x39;
	const TestDAC2Reg = 0x3A;
	const TestADCReg = 0x3B;
	const Reserved31 = 0x3C;
	const Reserved32 = 0x3D;
	const Reserved33 = 0x3E;
	const Reserved34 = 0x3F;

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

		$this->_reset();
		$this->_write(self::TModeReg, 0x8D);
		$this->_write(self::TPrescalerReg, 0x3E);
		$this->_write(self::TReloadRegL, 30);
		$this->_write(self::TReloadRegH, 0);

		$this->_write(self::TxAutoReg, 0x40);
		$this->_write(self::ModeReg, 0x3D);

		$this->antennaOn();
	}

	protected function _write($addr, $value) {
		if($this->debug)
			echo "W $addr, $value\n";
		$this->device->write([ ($addr << 1) & 0x7E, $value ]);
	}

	protected function _read($addr) {
		$value = $this->device->transfer([(($addr << 1) & 0x7E) | 0x80, 0]);
		if($this->debug)
			echo "R $addr, $value[1]\n";
		return $value[1] ?? -1;
	}

	protected function _close() {
		$this->device->close();
	}

	public function __destruct()
	{
		$this->_close();
	}

	protected function _reset() {
		$this->_write(self::CommandReg, self::PCD_RESETPHASE);
	}

	protected function _setBitMask($register, $mask) {
		$v = $this->_read($register);
		$this->_write($register, $v | $mask);
	}

	protected function _clearBitMask($register, $mask) {
		$v = $this->_read($register);
		$this->_write($register, $v & ~$mask);
	}

	public function antennaOn() {
		$v = $this->_read(self::TxControlReg);
		if(~($v & 0x03))
			$this->_setBitMask(self::TxControlReg, 0x03);
	}

	public function antennaOff() {
		$this->_clearBitMask(self::TxControlReg, 0x03);
	}

	protected function _sendToCard(int $command, array $data) {
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

        $this->_write(self::CommIEnReg, $irqEn|0x80);
        $this->_clearBitMask(self::CommIrqReg, 0x80);
        $this->_setBitMask(self::FIFOLevelReg, 0x80);

        $this->_write(self::CommandReg, self::PCD_IDLE);

        foreach($data as $d) {
        	$this->_write(self::FIFODataReg, $d);
		}

        $this->_write(self::CommandReg, $command);

        if($command == self::PCD_TRANSCEIVE)
        	$this->_setBitMask(self::BitFramingReg, 0x80);

        $i = 2000;
        while (1) {
        	$n = $this->_read(self::CommIrqReg);

        	$i--;
        	if( $i<1 || ($n & $waitIRq) || ($n & 0x1) )
        		break;
		}

		$this->_clearBitMask(self::BitFramingReg, 0x80);

        $data = [];
        $dataLen = 0;

        if($i != 0) {
        	$err = $this->_read(self::ErrorReg);

        	if( ($err & 0x1B) == 0x0) {
        		$status = self::MI_OK;

        		if($n & $irqEn & 0x01)
        			$status = self::MI_NOTAGERR;

        		if($command == self::PCD_TRANSCEIVE) {
        			$n = $this->_read(self::FIFOLevelReg);
        			$lastBits = $this->_read(self::ControlReg) & 0x07;

        			if($lastBits != 0) {
        				$dataLen = ($n - 1) * 8 + $lastBits;
					} else {
        				$dataLen = $n*8;
					}

        			$n = max(1, min($n, self::MAX_LEN));
        			for($e=0;$e<$n;$e++)
        				$data[] = $this->_read(self::FIFODataReg);
				}
			} else {
        		$status = self::MI_ERR;
			}
		}

        return [$status, $data, $dataLen];
	}
}
# Ikarus SPS MFRC522 package
This package ships with a wrapper to access the MFRC522 module of JOY-IT.

### Installation
You need to install the [php-spi-extension](https://github.com/tasoftch/php-spi-extension) and the [php-secure-int64-extension](https://github.com/tasoftch/php-secure-int64-extension).

### Setup

Wiring like [here](https://cdn-reichelt.de/documents/datenblatt/C300/SBC-RFID-RC522-ANLEITUNG-08-07-2019.pdf)

### Usage

```php
<?php
use Ikarus\Raspberry\Pinout\Revision_3\DynamicBCMPinout;
use Ikarus\SPS\SimpleMFRC522;
use TASoft\Bus\SPI;
use Ikarus\Raspberry\RaspberryPiDevice;

$dev = RaspberryPiDevice::getDevice();
$dev->requirePinout(
	(new DynamicBCMPinout())
		->addOutputPin(25)
);

$reset = $dev->getOutputPin(25);

$sensor = new SimpleMFRC522(new SPI(0, 0, 1000000), $reset);
echo "Please hold a badge or card near the sensor:", PHP_EOL;
$content = $sensor->readCardContents(5, $uid, true);

if($uid == -1)
    echo "No badge or card detected. Time is up.", PHP_EOL;
else
    echo "Badge ($uid) with content: \"$content\".", PHP_EOL; 
```

[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](#manual)
[![Version](https://img.shields.io/badge/Symcon-Version%20%3E=%205.2-green.svg)](#)
[![Code](https://img.shields.io/badge/Code-PHP-blue.svg)](#manual)

# Magic Home Wifi LED
 
Modules for IP-Symcon from ** Version 5.2 ** to control the Magic Home Wifi RGB / w controller or compatible.

As the new ** Dynamic Forms ** are used, a full ** configuration ** is only possible via the ** WebFront **.

# Tested with
Magic Mini RGB / wifi Controller for LED Strip / Strip. Works with Alexa, Google Home, IFTTT, and Siri IR remote control, 16 million colors, 20 dynamic modes

![Magic Mini RGB/w Wifi Controller](img/tested1.png?raw=true "Magic Mini RGB/w Wifi Controller")

# Manual
Currently the module is functional but unchecked or only with RGBW devices, see above, tested by me ;-)


**Contents**

1. [Control](#1-control)  
2. [Extras](#2-extras)
3. [Supported protocols](#3-protocols)
4. [Modules](#4-modules)
5. [Installation](#5-installation)
6. [Function reference](#6-funktion-reference) 
7. [Annotation](#7-annotation)

## 1. Control
- On off
- Colour
- Brightness
- White channel
- Extra white channel
- Color mode 20 Dynamic modes
- Color mode speed

## 2. Extras
- Editing device timers [1-6]
    - delete expired timers
    - Create or change devices timers
    - read and write the device timer list
    - The timer list is permanently stored on the device and works independently of IPS, provided the device time is up to date.
- read / set device time
    - Supports automatic updating of the device time after a power failure
- Works with return channel
    - Changes are detected as a result, even if the device is controlled by the Andoid / Apple APP
- Option to select whether data should be permanently stored on the device
    - Data such as color, Weißkannal are permanently stored on the device and are retained even after a power failure.
- Manual protocol type selection
- Manual RGB / w protocol option
    - Depending on protocol type (for LEDNET original off, all others on)
- Manual CheckSum option
    - Some devices require a check sum of transmitted data

## 3. Protocols
- MagicHome
- LEDNET
- LEDNET Original


## 4. Modules
- Discover module for finding the devices in the network
- Module for controlling the device
- Groups Module for controlling device groups
- Languages EN, DE


## 5. Installation

**a. Set up Controler on the network**

A new controller must first be integrated on the smartphone with the associated APP in your network. Only after the first setup is the controller recognized by the Doscover module.

```
TIP
Since the Wifi devices I know only support the 2G Wifi you have to set up the first time
Pay attention to the following with the Andoid / Apple APP:
If the 2G + 5G Wifi radio on the same SID should the 5G during the setup on the router
be disabled because otherwise there may be problems recognizing or setting up the Wifi device.
After setup, the 5G can easily be activated on the router again.
```

** b. Installing via Modules instance **

Open IP Symcon's web console with _http://{IP-Symcon IP}:3777/console/_

Then the object tree _Open_.

![Object Tree](img/objectbaum.png?raw=true "Object Tree")

The instance 'Modules' below core instances in the object tree of IP-Symcon (> = Ver 5.x) with a doubleclick _Open_

![Object tree](img/object_tree.png?raw=true "Object tree")

and press the plus sign.

![Plus](img/plus.png?raw=true "Plus")
	
![ModulURL](img/add_module.png?raw=true "Add Module")

Enter the following modules URL in the field and confirm with _OK_:

```
https://github.com/softbar/MagicHomeWifiLED 
```

Then an entry for the module appears in the list of the instance _Modules_

By default, the branch _master_ is loaded, which contains current changes and adjustments.
Only the _master_ branch is kept current.

![Master](img/master.png?raw=true "master") 

**c. Setting up the modules**

In IP Symcon, first right-click **Discovery Instances** to add a **new instance** with **Add Object** -> Instance_ (_CTRL + 1_ in the Legacy Console), and select **WifiBulb RGB/w Discover**.

![Add Discovery Instance](img/create_discover.png?raw=true "Add Discovery Instance")

After setting up the Discovery instance, a list of available devices appears.

The device is green, if it has not been created yet.

![List](img/discover_list.png?raw=true "Devices found")

Now highlight the desired device and click on ** Create ** or ** Create All **, the instance will be created.

```
The instances created by the Discovery Module can be found in the object tree under:
IP-Symcon -> Wlan RGB/w devices
```

## 6. Funktion reference

**RequestUpdate**
```php
WBC_RequestUpdate(int $InstanceID)
``` 
Parameter:
- _$InstanceID_ ObjectID of the WifiBulb device


**SetPower**
```php
WBC_SetPower(int $InstanceID, bool $PowerOn)
``` 
Parameter:
- _$InstanceID_ ObjectID of the WifiBulb device
- _$PowerOn_ True for ON or false for OFF


**SetColor**
```php
WBC_SetColor(int $InstanceID, int $Color)
``` 
Parameter:
- _$InstanceID_ ObjectID of the WifiBulb device
- _$Color_ New color value 


**SetRGBW**
```php
WBC_SetRGBW(int $InstanceID, int $Red, int $Green, int $Blue, int $White = -1)
``` 
Parameter:
- _$InstanceID_ ObjectID of the WifiBulb device
- _$Red_ New color value for red (0-255)
- _$Green_ New color value for green (0-255)
- _$Blue_ New color value for blue (0-255)
- _$White_ New value for White (0-255) or -1 for no change


**SetRed**
```php
WBC_SetRed(int $InstanceID, int $Level255)
``` 
Parameter:
- _$InstanceID_ ObjectID of the WifiBulb device
- _$Level255_ New color value for red (0-255)


**SetGreen**
```php
WBC_SetGreen(int $InstanceID, int $Level255)
``` 
Parameter:
- _$InstanceID_ ObjectID of the WifiBulb device
- _$Level255_ New color value for green (0-255)


**SetBlue**
```php
WBC_SetBlue(int $InstanceID, int $Level255)
``` 
Parameter:
- _$InstanceID_ ObjectID of the WifiBulb device
- _$Level255_ New color value for blue (0-255)


**SetBrightness**
```php
WBC_SetBrightness(int $InstanceID, int $Level255)
``` 
Parameter:
- _$InstanceID_ ObjectID of the WifiBulb device
- _$Level255_ New value for brightness (0-255)

**SetWhite**
```php
WBC_SetWhite(int $InstanceID, int $Level255)
``` 
Parameter:
- _$InstanceID_ ObjectID of the WifiBulb device
- _$Level255_ New value for white (0-255)

**SetColdWhite**
```php
WBC_SetColdWhite(int $InstanceID, int $Level255)
``` 
Parameter:
- _$InstanceID_ ObjectID of the WifiBulb device
- _$Level255_ New value for cod white (0-255)

**RunProgram**
```php
WBC_RunProgram(int $InstanceID, int $ProgramID, int $Speed100)
``` 
Parameter:
- _$InstanceID_ ObjectID of the WifiBulb device
- _$ProgramID_ ProgramID to execute (37-56) 0=aus
- _$Speed100_ New value for Animation Speed (0-100)


## 7. Annotation
`




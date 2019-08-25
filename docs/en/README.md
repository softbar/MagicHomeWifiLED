[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](#manual)
[![Version](https://img.shields.io/badge/Symcon-Version%20%3E=%205.2-green.svg)](#)
[![Code](https://img.shields.io/badge/Code-PHP-blue.svg)](#manual)

# Magic Home Wifi LED
 
Modules for IP-Symcon from ** Version 5.2 ** to control the Magic Home Wifi RGB / w controller or compatible.

As the new ** Dynamic Forms ** are used, a full ** configuration ** is only possible via the ** WebFront **.

# Tested with
Magic Mini RGB / wifi Controller for LED Strip / Strip. Works with Alexa, Google Home, IFTTT, and Siri IR remote control, 16 million colors, 20 dynamic modes

# Manual
Currently the module is functional but unchecked or only with RGBW devices, see above, tested by me ;-)


**Inhaltsverzeichnis**

1. [Control](#1-control)  
2. [Extras](#2-extras)
3. [Supported protocols](#3-protocols)
4. [Modules](#4-modules)
5. [Installation](#5-installation)
6. [Annotation](#6-annotation)

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
- Control module for controlling the device
- Languages EN, DE


## 5. Installation
A new controller must first be integrated on the smartphone with the associated APP in your network. Only after the first setup is the controller recognized by the Doscover module.
``` `
TIP
Since the Wifi devices I know only support the 2G Wifi you have to set up the first time
Pay attention to the following with the Andoid / Apple APP:
If the 2G + 5G Wifi radio on the same SID should the 5G during the setup on the router
be disabled because otherwise there may be problems recognizing or setting up the Wifi device.
After setup, the 5G can easily be activated on the router again.
`` `


## 6. Annotation
`




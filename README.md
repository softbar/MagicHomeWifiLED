[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](#anleitung)
[![Version](https://img.shields.io/badge/Symcon-Version%20%3E=%205.2-green.svg)](#)
[![Code](https://img.shields.io/badge/Code-PHP-blue.svg)](#anleitung)

# Magic Home Wifi LED
 
Module für IP-Symcon ab **Version 5.2**  zur Steuerung des Magic Home Wifi RGB/w Controllers oder kompatible
Da die neuen **dynamischen formulare** benutzt werden ist eine vollständige **Konfiguration** nur über das **WebFront** möglich.


# Getestet mit

Magic Mini RGB/w Wifi Controller für LED Strip/Streifen. Funktioniert mit Alexa, Google Home, IFTTT, und Siri IR Fernbedienung Steuerung, 16 Mio Farben, 20 Dynamische Modi 


# Anleitung
Derzeit ist das Modul funktionsfähig jedoch ungeprüft bzw. nur mit RGBW Geräten, siehe oben, von mir getestet;-)


**Inhaltsverzeichnis**

1. [Steuerung](#1-steuerung)  
2. [Extras](#2-extras)
3. [Unterstüzte Protokolle](#3-protokolle)
4. [Module](#4-module)
5. [Installation](#5-installation)
6. [Anmerkung](#6-anmerkung)

## 1. Steuerung
- An / Aus
- Farbe
- Helligkeit
- Weiß Kanal
- Extra Weiß Kanal
- Farbmodus 20 Dynamische Modi
- Farbmodus Geschwindigkeit

## 2. Extras
- Bearbeiten von Geräte Timern [1-6]
    - löschen von abgelaufenen Timern
    - erstellen oder ändern von Geräte timern
    - lesen und schreiben der Geräte Timerliste
- Geräte Uhrzeit lesen/setzen
    - Unterstützt automatisches aktuallieren der Gerätezeit nach stromausfall
- Arbeitet mit Rückkanal
    - dadurch werden Änderungen erkannt die über die Andoid/Apple APP vorgenommen werden

## 3. Protokolle
- LEDNET Original
- LEDNET
- MagicHome

## 4. Module
- Discover Modul zum finden der Geräte im Netzwerk
- Steuerung Modul zur Steuerung des Gerätes
- Sprachen EN, DE

## 5. Installation

Das Wifi Gerät **muss** zuerst **mit** der zugehörigen **APP** im WLAN **eingebunden werden**. Erst **dann** wird es vom Discover Modul **erkannt**!
```
TIPP
Da die mir bekannten Wifi Geräte nur das 2G Wifi unterstützen sollte man beim erstmaligen einrichten mit der Andoid/Apple APP auf folgendes achten:
Falls das 2G + 5G Wifi auf der gleichen SID funkt muss das 5G während der Einrichtung auf dem Smartphone , auf dem Router deaktiviert werden da es sonst Probleme beim erkennen bzw. einrichten des Wifi Gerätes geben kann.
Nach der Einrichtung kann das 5G wieder problemlos auf dem Router aktiviert werden.
```  


## 6. Anmerkung




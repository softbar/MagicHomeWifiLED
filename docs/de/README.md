[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](#anleitung)
[![Version](https://img.shields.io/badge/Symcon-Version%20%3E=%205.2-green.svg)](#)
[![Code](https://img.shields.io/badge/Code-PHP-blue.svg)](#anleitung)

# Magic Home Wifi LED
Module für IP-Symcon ab **Version 5.2**  zur Steuerung des Magic Home Wifi RGB/w Controllers oder kompatible.

Da die neuen **dynamischen Formulare** benutzt werden ist eine vollständige **Konfiguration** nur über das **WebFront** möglich.


# Getestet mit
Magic Mini RGB/wifi Controller für LED Strip/Streifen. Funktioniert mit Alexa, Google Home, IFTTT, und Siri IR Fernbedienung Steuerung, 16 Mio Farben, 20 Dynamische Modi

# Anleitung
Derzeit ist das Modul funktionsfähig jedoch ungeprüft bzw. nur mit RGBW Geräten, siehe oben, von mir getestet;-)


**Inhaltsverzeichnis**

1. [Steuerung](#1-steuerung)  
2. [Extras](#2-extras)
3. [Unterstützte Protokolle](#3-protokolle)
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
    - die Timerliste wird permanent auf dem Gerät gespeichert und arbeitet unabhängig von IPS , vorausgesetzt die Gerätezeit ist aktuell.
- Geräte Uhrzeit lesen/setzen
    - Unterstützt automatisches aktuallieren der Gerätezeit nach stromausfall
- Arbeitet mit Rückkanal
    - Änderungen werden dadurch Zeitnah erkannt auch wenn das Gerät über die Andoid/Apple APP gesteuert wird
- Option zur Auswahl ob Daten dauerhaft auf dem Gerät gespeichert werden sollen
    - Daten wie Farbe, Weißkannal werden auf dem Gerät permanent gespeichert und bleiben auch nach einem Stromausfall erhalten.
- Manuelle Protokoll-Type Auswahl
- Manuelle RGB/w Protokoll Option
    - Abhängig vom Protokoll-Type (für LEDNET Original Aus, alle anderen Ein)
- Manuelle CheckSum Option
    - Einige Geräte benötigen eine Check Summe der übertragenen Daten

## 3. Protokolle
- MagicHome
- LEDNET
- LEDNET Original


## 4. Module
- Discover Modul zum finden der Geräte im Netzwerk
- Steuerung Modul zur Steuerung des Gerätes
- Sprachen EN, DE


## 5. Installation

Ein neuer Controller muss zuerst auf dem Smartphone mit der zugehörigen APP in deinem Netzwerk eingebunden werden. Erst nach dem erstmaligen einrichten wird der Controller vom Doscovermodul erkannt.
```
TIPP
Da die mir bekannten Wifi Geräte nur das 2G Wifi unterstützen muss man beim erstmaligen einrichten
mit der Andoid/Apple APP auf folgendes achten:
Falls das 2G + 5G Wifi auf der gleichen SID funkt sollte das 5G während der Einrichtung auf dem Router
deaktiviert werden da es sonst Probleme beim erkennen bzw. einrichten des Wifi Gerätes geben kann.
Nach der Einrichtung kann das 5G wieder problemlos auf dem Router aktiviert werden.
```  


## 6. Anmerkung




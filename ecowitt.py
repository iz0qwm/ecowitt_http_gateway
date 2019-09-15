#!/usr/bin/python
#
# Copyright 2019 Raffaello Di Martino
# From a work of Matthew Wall on fileparser driver
#
# weewx driver that reads data from a file coming from ecowitt_gateway
# https://github.com/iz0qwm/ecowitt_http_gateway/
#
# This program is free software: you can redistribute it and/or modify it under
# the terms of the GNU General Public License as published by the Free Software
# Foundation, either version 3 of the License, or any later version.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE.
#
# See http://www.gnu.org/licenses/


# This driver will read data from a file generated by the https://github.com/iz0qwm/ecowitt_http_gateway/
# name=value pair, for example:
#
# outTemp=79.3
# barometer=29.719
# pressure=29.719
# outHumidity=70
# windSpeed=0.00
# windDir=277
# windGust=0.00
# rainRate=0.000
# rain_total=6.903
# inTemp=79.7
# inHumidity=76
# radiation=0.00
# UV=0
# windchill=
# dewpoint=
# extraTemp1=78.44
# extraHumid1=74
# soilTemp1=0
# txBatteryStatus=
# rainBatteryStatus=1.6
# outTempBatteryStatus=0
#
# The units must be in the weewx.US unit system:
#   degree_F, inHg, inch, inch_per_hour, mile_per_hour
#
# To use this driver, put this file in the weewx drivers directory (i.e. /usr/share/weewx/weewx/drivers ), then make
# the following changes to weewx.conf:
#
# [Station]
#     station_type = ecowitt
# [ecowitt]
#     poll_interval = 65                    # number of seconds, just a little more the GW1000 update time
#     path = /var/log/ecowitt/weewx.txt     # location of data file
#     driver = weewx.drivers.ecowitt
#
# The variables in the file have the same names from those in the database
# so you don't need a mapping section
#
# But if the variables in the file have names different from those in the database
# schema, then create a mapping section called label_map.  This will map the
# variables in the file to variables in the database columns.  For example:
#
# [ecowitt]
#     ...
#     [[label_map]]
#         temp = outTemp
#         humi = outHumidity
#         in_temp = inTemp
#         in_humid = inHumidity

from __future__ import with_statement
import syslog
import time

import weewx.drivers

DRIVER_NAME = 'ecowitt'
DRIVER_VERSION = "0.1"

def logmsg(dst, msg):
    syslog.syslog(dst, 'ecowitt: %s' % msg)

def logdbg(msg):
    logmsg(syslog.LOG_DEBUG, msg)

def loginf(msg):
    logmsg(syslog.LOG_INFO, msg)

def logerr(msg):
    logmsg(syslog.LOG_ERR, msg)

def _get_as_float(d, s):
    v = None
    if s in d:
        try:
            v = float(d[s])
        except ValueError as e:
            logerr("cannot read value for '%s': %s" % (s, e))
    return v

def loader(config_dict, engine):
    return ecowittDriver(**config_dict[DRIVER_NAME])

class ecowittDriver(weewx.drivers.AbstractDevice):
    """weewx driver for ecowitt GW1000"""

    def __init__(self, **stn_dict):
        # where to find the data file
        self.path = stn_dict.get('path', '/var/log/ecowitt/weewx.txt')
        # how often to poll the weather data file, seconds
        self.poll_interval = float(stn_dict.get('poll_interval', 2.5))
        # mapping from variable names to weewx names
        self.label_map = stn_dict.get('label_map', {})
        self.last_rain = None
 
        loginf("data file is %s" % self.path)
        loginf("polling interval is %s" % self.poll_interval)
        loginf('label map is %s' % self.label_map)

    def genLoopPackets(self):
        while True:
            # read whatever values we can get from the file
            data = {}
            try:
                with open(self.path) as f:
                    for line in f:
                        eq_index = line.find('=')
                        name = line[:eq_index].strip()
                        value = line[eq_index + 1:].strip()
                        data[name] = value
            except Exception as e:
                logerr("read failed: %s" % e)

            # map the data into a weewx loop packet
            _packet = {'dateTime': int(time.time() + 0.5),
                       'usUnits': weewx.US}
            for vname in data:
                _packet[self.label_map.get(vname, vname)] = _get_as_float(data, vname)

            self._augment_packet(_packet)
            yield _packet
            time.sleep(self.poll_interval)

    def _augment_packet(self, packet):
        packet['rain'] = weewx.wxformulas.calculate_rain(
            packet['rain_total'], self.last_rain)
        self.last_rain = packet['rain_total']

    @property
    def hardware_name(self):
        return "ecowitt"

# To test this driver, run it directly as follows:
#   PYTHONPATH=python /usr/share/weewx/weewx/drivers/ecowitt.py
if __name__ == "__main__":
    import weeutil.weeutil
    driver = ecowittDriver()
    for packet in driver.genLoopPackets():
        print weeutil.weeutil.timestamp_to_string(packet['dateTime']), packet
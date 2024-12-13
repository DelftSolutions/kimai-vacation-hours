# VacationHours

For Kimai2

## Installation

First clone it to your Kimai installation `plugins` directory:
```
cd /kimai/var/plugins/
git clone https://github.com/delftsolutions/kimai-vacation-hours.git VacationHoursBundle
```

And then rebuild the cache (As described on https://www.kimai.org/documentation/cache.html). `cd` into the top Kimai directory and execute the following commands:
```
bin/console kimai:reloadd --env=prod
chown -R :www-data .
chmod -R g+r .
chmod -R g+rw var/
```

You probably need to `chown` and `chmod` based on your local setup.

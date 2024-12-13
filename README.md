# VacationHours

For Kimai2

## Installation

First clone it to your Kimai installation `plugins` directory:
```
cd /kimai/var/plugins/
git clone https://github.com/delftsolutions/kimai-vacation-hours.git VacationHoursBundle
```

And then rebuild the cache: 
```
cd /kimai/
bin/console kimai:reload
```

You probably need to `chown` and `chmod` based on your local setup.

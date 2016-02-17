"""
SARK utilities
"""

__all__ = ['database', 'globals']

import logging
try:  # Python 2.7+
    from logging import NullHandler
except ImportError:
    class NullHandler(logging.Handler):
        def emit(self, record):
            pass

logging.getLogger(__name__).addHandler(NullHandler())

# Maintain a reference to the globals function
_globals = globals

try:
    from sark.db import database

    globals = database.get_values("SELECT * FROM globals where pkey = 'global'")

    """from cStringIO import StringIO
    from ConfigParser import SafeConfigParser

    config_file = StringIO()
    with open('/etc/asterisk/asterisk.conf') as f:
        for line in f:
            config_file.write(line.strip())

    asterisk_config = SafeConfigParser()
    asterisk_config.read(config_file)
    print asterisk_config.sections()"""

    from sark import utils

except Exception as e:
    logging.debug(e)
    raise RuntimeError('The SARK module is not properly configured')

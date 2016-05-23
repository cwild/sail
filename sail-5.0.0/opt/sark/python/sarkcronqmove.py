#!/usr/bin/env python

import errno
import logging
import os
import sark
import sys

logger = logging.getLogger('sark.queue.move')

MONITOR_STAGE = sark.globals.get('MONITORSTAGE', '/home/sark/monstage')
MONITOR_OUT = sark.globals.get('MONITOROUT', '/home/sark/monout')
RECQSEARCHLIM = sark.globals.get('RECQSEARCHLIM', 400)
RECQDITHER = sark.globals.get('RECQDITHER', 1)


def main():

    for directory in (MONITOR_STAGE, MONITOR_OUT):
        logger.debug('Check for directory, %s', directory)

        if not os.path.isdir(directory):
            logger.critical('Directory does not exist: %s', directory)
            return errno.ENOENT

    # Which files should we be working with?
    filenames = [f for f in os.listdir(MONITOR_STAGE) if os.path.isfile(f)]

    if not filenames:
        logger.debug('No files to process in %s', MONITOR_STAGE)
        return 0

    lines_processed = 0
    for line in sark.utils.reverse_readline('/var/log/asterisk/queue_log'):

        # convert the NONE text fields into null pointers
        fields = [None if f == 'NONE' else f for f in line.split('|')]

        # epoch, uniqueid of call, queue name, bridged channel, event, param1, param2, param3
        epoch, call_id, queue_name, bridged_chan, event = fields[:5]
        params = fields[5:]

        if event == 'CONNECT':
            logger.debug('Matching line: %s', line)
            lines_processed += 1

        if lines_processed >= RECQSEARCHLIM:
            logger.debug('Stopped processing after %d lines', lines_processed)
            break

    # Finished, exit cleanly
    return 0

if __name__ == "__main__":
    logging.basicConfig(level=logging.DEBUG)
    sys.exit(main())

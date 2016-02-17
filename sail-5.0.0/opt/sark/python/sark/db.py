import logging
import sqlite3

logger = logging.getLogger(__name__)

def dict_factory(cursor, row):
    d = {}
    for idx, col in enumerate(cursor.description):
        d[col[0]] = row[idx]
    return d


class Database(object):

    def __init__(self, path):
        self.conn = sqlite3.connect(path)
        self.conn.row_factory = dict_factory

    def __getattr__(self, name):
        return getattr(self.conn, name)

    def __str__(self):
        return str(self.conn)

    def close(self):
        try:
            self.conn.close()
        except Exception as e:
            logger.debug(e)

    def do_query(self, query, args=(), commit=False):
        stmt = self.conn.execute(query, tuple(args))
        return stmt.fetchone()

    def get_values(self, query, default=None):
        try:
            return self.do_query(query)
        except Exception as e:
            logger.exception(e)

        return default


database = Database('/opt/sark/db/sark.db')
